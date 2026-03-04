<?php

namespace App\Services\Admin;

use App\Models\AttendanceRecord;
use App\Models\AttendanceAuditLog;
use App\Models\AttendanceSession;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\ClassSectionMapping;
use App\Models\Subject;
use App\Models\User;
use App\Services\Attendance\AttendanceCalendarService;
use App\Services\Subscription\PlanFeatureService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

class AttendanceManagementService
{
    public function __construct(
        private readonly PlanFeatureService $planFeatureService,
        private readonly AttendanceCalendarService $attendanceCalendarService
    ) {
    }

    public function reportData(User $user, array $filters): array
    {
        $schoolId = (int) $user->school_id;
        $isAdvancedReportEnabled = $this->planFeatureService->isEnabledForSchoolId($schoolId, PlanFeatureService::FEATURE_ADVANCED_ATTENDANCE_REPORTS);
        $isEnterpriseExportEnabled = $this->planFeatureService->isEnabledForSchoolId($schoolId, PlanFeatureService::FEATURE_ENTERPRISE_ATTENDANCE_EXPORT);
        $isAuditLogsEnabled = $this->planFeatureService->isEnabledForSchoolId($schoolId, PlanFeatureService::FEATURE_ATTENDANCE_AUDIT_LOGS);
        $classes = SchoolClass::query()->where('school_id', $schoolId)->orderBy('display_order')->orderBy('name')->get();
        $sections = Section::query()->where('school_id', $schoolId)->orderBy('name')->get();
        $this->ensureDefaultClassSectionMappings($schoolId, $classes, $sections);
        $activeSectionsByClass = ClassSectionMapping::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->get(['school_class_id', 'section_id'])
            ->groupBy('school_class_id')
            ->map(fn ($items) => $items->pluck('section_id')->values()->all())
            ->toArray();
        $dailySubject = Subject::query()->firstOrCreate(
            ['school_id' => $schoolId, 'name' => 'Daily Attendance'],
            ['code' => 'ATTN']
        );

        $selectedDate = $filters['attendance_date'] ?? now()->toDateString();
        $selectedDateFrom = $isAdvancedReportEnabled ? ($filters['date_from'] ?? null) : null;
        $selectedDateTo = $isAdvancedReportEnabled ? ($filters['date_to'] ?? null) : null;
        $selectedStatus = $isAdvancedReportEnabled ? ($filters['status'] ?? null) : null;
        $selectedClassId = isset($filters['school_class_id']) ? (int) $filters['school_class_id'] : ($classes->first()?->id ?? null);
        $selectedSectionId = isset($filters['section_id']) ? (int) $filters['section_id'] : ($sections->first()?->id ?? null);

        $attendanceRows = collect();
        $session = null;
        $rangeMode = false;
        $auditLogs = collect();
        $selectedDateCalendarEvent = null;
        $summary = [
            AttendanceRecord::STATUS_PRESENT => 0,
            AttendanceRecord::STATUS_ABSENT => 0,
            AttendanceRecord::STATUS_LATE => 0,
            AttendanceRecord::STATUS_HALF_DAY => 0,
            AttendanceRecord::STATUS_LEAVE => 0,
        ];

        if ($selectedClassId && $selectedSectionId) {
            $selectedDateCalendarEvent = $this->attendanceCalendarService->eventForDate(
                $schoolId,
                $selectedDate,
                $selectedClassId,
                $selectedSectionId
            );

            if ($isAdvancedReportEnabled && ($selectedDateFrom || $selectedDateTo || $selectedStatus)) {
                $from = $selectedDateFrom ?: $selectedDate;
                $to = $selectedDateTo ?: $selectedDate;
                $rangeMode = true;

                $recordsQuery = AttendanceRecord::query()
                    ->whereHas('attendanceSession', function ($query) use ($schoolId, $selectedClassId, $selectedSectionId, $dailySubject, $from, $to): void {
                        $query->where('school_id', $schoolId)
                            ->where('school_class_id', $selectedClassId)
                            ->where('section_id', $selectedSectionId)
                            ->where('subject_id', $dailySubject->id)
                            ->where('period_no', 1)
                            ->whereBetween('attendance_date', [$from, $to]);
                    })
                    ->with(['student', 'attendanceSession.markedBy']);

                if ($selectedStatus) {
                    $recordsQuery->where('status', $selectedStatus);
                }

                $attendanceRows = $recordsQuery->get()->sortBy([
                    fn ($record) => $record->attendanceSession?->attendance_date?->format('Y-m-d'),
                    fn ($record) => $record->student?->name,
                ])->values();
                $summary = $this->buildSummary($attendanceRows);
            } else {
            $session = AttendanceSession::query()
                ->where('school_id', $schoolId)
                ->where('school_class_id', $selectedClassId)
                ->where('section_id', $selectedSectionId)
                ->where('subject_id', $dailySubject->id)
                ->where('period_no', 1)
                ->whereDate('attendance_date', $selectedDate)
                ->with(['records.student', 'markedBy'])
                ->first();

            if ($session) {
                $attendanceRows = $session->records->sortBy(fn ($record) => $record->student?->name)->values();
                $summary = $this->buildSummary($attendanceRows);
            }
            }
        }

        if ($isAuditLogsEnabled && Schema::hasTable('attendance_audit_logs') && $selectedClassId && $selectedSectionId) {
            $auditQuery = AttendanceAuditLog::query()
                ->where('school_id', $schoolId)
                ->whereHas('attendanceSession', function ($query) use ($selectedClassId, $selectedSectionId): void {
                    $query->where('school_class_id', $selectedClassId)
                        ->where('section_id', $selectedSectionId);
                })
                ->with(['student', 'teacher', 'changedBy', 'attendanceSession'])
                ->orderByDesc('changed_at')
                ->limit(20);

            if ($rangeMode && ($selectedDateFrom || $selectedDateTo)) {
                $from = $selectedDateFrom ?: $selectedDate;
                $to = $selectedDateTo ?: $selectedDate;
                $auditQuery->whereBetween('changed_at', [$from.' 00:00:00', $to.' 23:59:59']);
            } else {
                $auditQuery->whereDate('changed_at', $selectedDate);
            }

            $auditLogs = $auditQuery->get();
        }

        return [
            'school' => $user->school,
            'classes' => $classes,
            'sections' => $sections,
            'attendanceRows' => $attendanceRows,
            'session' => $session,
            'summary' => $summary,
            'selectedDate' => $selectedDate,
            'selectedDateFrom' => $selectedDateFrom,
            'selectedDateTo' => $selectedDateTo,
            'selectedStatus' => $selectedStatus,
            'selectedClassId' => $selectedClassId,
            'selectedSectionId' => $selectedSectionId,
            'today' => now()->toDateString(),
            'isAdvancedReportEnabled' => $isAdvancedReportEnabled,
            'isEnterpriseExportEnabled' => $isEnterpriseExportEnabled,
            'isAuditLogsEnabled' => $isAuditLogsEnabled,
            'rangeMode' => $rangeMode,
            'statusTypes' => AttendanceRecord::STATUSES,
            'auditLogs' => $auditLogs,
            'activeSectionsByClass' => $activeSectionsByClass,
            'selectedDateCalendarEvent' => $selectedDateCalendarEvent,
        ];
    }

    public function exportCsvRows(User $user, array $filters): array
    {
        $schoolId = (int) $user->school_id;
        if (! $this->planFeatureService->isEnabledForSchoolId($schoolId, PlanFeatureService::FEATURE_ENTERPRISE_ATTENDANCE_EXPORT)) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Attendance CSV export is available only in ENTERPRISE plan.',
            ]);
        }

        $data = $this->reportData($user, $filters);
        $rows = [[
            'Date',
            'Class',
            'Section',
            'Student ID',
            'Student Name',
            'Status',
            'Leave Type',
            'Remark',
            'Marked By',
        ]];

        foreach ($data['attendanceRows'] as $record) {
            $session = $record->attendanceSession;
            $className = $session?->schoolClass?->name ?? $data['classes']->firstWhere('id', $data['selectedClassId'])?->name;
            $sectionName = $session?->section?->name ?? $data['sections']->firstWhere('id', $data['selectedSectionId'])?->name;
            $rows[] = [
                $session?->attendance_date?->format('Y-m-d') ?? $data['selectedDate'],
                $className,
                $sectionName,
                $record->student?->student_id,
                $record->student?->name,
                $record->status,
                $record->leave_type,
                $record->remark,
                $data['rangeMode']
                    ? ($session?->markedBy?->name ?? '')
                    : ($data['session']?->markedBy?->name ?? ''),
            ];
        }

        return $rows;
    }

    private function ensureDefaultClassSectionMappings(int $schoolId, Collection $classes, Collection $sections): void
    {
        if ($classes->isEmpty() || $sections->isEmpty()) {
            return;
        }

        $hasAnyMapping = ClassSectionMapping::query()
            ->where('school_id', $schoolId)
            ->exists();

        if ($hasAnyMapping) {
            return;
        }

        foreach ($classes as $class) {
            foreach ($sections as $section) {
                ClassSectionMapping::query()->updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'school_class_id' => $class->id,
                        'section_id' => $section->id,
                    ],
                    [
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function buildSummary(Collection $rows): array
    {
        $summary = [
            AttendanceRecord::STATUS_PRESENT => 0,
            AttendanceRecord::STATUS_ABSENT => 0,
            AttendanceRecord::STATUS_LATE => 0,
            AttendanceRecord::STATUS_HALF_DAY => 0,
            AttendanceRecord::STATUS_LEAVE => 0,
        ];

        foreach ($rows as $row) {
            if (isset($summary[$row->status])) {
                $summary[$row->status]++;
            }
        }

        return $summary;
    }
}
