<?php

namespace App\Services\Portal;

use App\Models\AttendanceRecord;
use App\Models\SchoolCalendarEvent;
use App\Models\Student;
use App\Models\StudentAttendanceNotification;
use App\Models\StudentUserProfile;
use App\Models\User;
use App\Services\Attendance\AttendanceCalendarService;
use App\Services\Subscription\PlanFeatureService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class StudentPortalService
{
    public function __construct(
        private readonly PlanFeatureService $planFeatureService,
        private readonly AttendanceCalendarService $attendanceCalendarService
    ) {
    }

    public function portalData(User $user, array $filters = []): array
    {
        $student = $this->resolveStudent($user);
        $schoolId = (int) $user->school_id;
        $isAdvancedReportEnabled = $this->planFeatureService->isEnabledForSchoolId($schoolId, PlanFeatureService::FEATURE_ADVANCED_ATTENDANCE_REPORTS);
        $isEnterpriseEnabled = $this->planFeatureService->isEnabledForSchoolId($schoolId, PlanFeatureService::FEATURE_ENTERPRISE_ATTENDANCE_EXPORT);
        $isDeviceAttendanceEnabled = $this->planFeatureService->isEnabledForSchoolId($schoolId, PlanFeatureService::FEATURE_DEVICE_ATTENDANCE);

        $month = (string) ($filters['month'] ?? now()->format('Y-m'));
        $defaultStart = "{$month}-01";
        $defaultEnd = date('Y-m-t', strtotime($defaultStart));

        $dateFrom = $defaultStart;
        $dateTo = $defaultEnd;
        $selectedStatus = null;
        $requestedOverviewChart = (string) ($filters['overview_chart'] ?? 'doughnut');
        $selectedOverviewChart = in_array($requestedOverviewChart, ['doughnut', 'bar', 'line'], true)
            ? $requestedOverviewChart
            : 'doughnut';

        if ($isAdvancedReportEnabled) {
            $selectedStatus = $filters['status'] ?? null;
            if (! empty($filters['date_from'])) {
                $dateFrom = (string) $filters['date_from'];
            }
            if (! empty($filters['date_to'])) {
                $dateTo = (string) $filters['date_to'];
            }
            if ($dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
        }

        if (! $student) {
            return [
                'school' => $user->school,
                'student' => null,
                'records' => collect(),
                'notifications' => collect(),
                'percentage' => 0.0,
                'summary' => $this->summary(collect()),
                'month' => $month,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'selectedStatus' => $selectedStatus,
                'selectedOverviewChart' => $selectedOverviewChart,
                'featureFlags' => [
                    'advanced_reports' => $isAdvancedReportEnabled,
                    'enterprise_features' => $isEnterpriseEnabled,
                    'device_attendance' => $isDeviceAttendanceEnabled,
                ],
                'lowAttendanceAlert' => false,
                'deviceMarkedCount' => 0,
                'overviewChartData' => [
                    'statusLabels' => ['Present', 'Absent', 'Late', 'Half-day', 'Leave'],
                    'statusValues' => [0, 0, 0, 0, 0],
                    'trendLabels' => [],
                    'trendPercentages' => [],
                ],
                'holidayCalendarDates' => [],
                'holidayCalendarTitles' => [],
            ];
        }

        $recordsQuery = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereHas('attendanceSession', fn ($query) => $query->whereBetween('attendance_date', [$dateFrom, $dateTo]))
            ->with(['attendanceSession.schoolClass', 'attendanceSession.section', 'attendanceSession.markedBy']);

        if ($isAdvancedReportEnabled && ! empty($selectedStatus)) {
            $recordsQuery->where('status', $selectedStatus);
        }

        $records = $recordsQuery
            ->get()
            ->sortBy(fn ($record) => $record->attendanceSession?->attendance_date?->format('Y-m-d').'|'.$record->attendanceSession?->period_no);

        $notifications = Schema::hasTable('student_attendance_notifications')
            ? StudentAttendanceNotification::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->orderByDesc('notified_at')
                ->limit(10)
                ->get()
            : collect();

        $percentage = $this->percentage($records);
        $deviceMarkedCount = $records->filter(
            fn ($record) => Str::startsWith((string) ($record->remark ?? ''), 'Marked via device:')
        )->count();
        $summary = $this->summary($records);
        $holidayCalendar = $this->holidayCalendarForStudent($schoolId, $student);

        return [
            'school' => $user->school,
            'student' => $student,
            'records' => $records,
            'notifications' => $notifications,
            'percentage' => $percentage,
            'summary' => $summary,
            'month' => $month,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'selectedStatus' => $selectedStatus,
            'selectedOverviewChart' => $selectedOverviewChart,
            'featureFlags' => [
                'advanced_reports' => $isAdvancedReportEnabled,
                'enterprise_features' => $isEnterpriseEnabled,
                'device_attendance' => $isDeviceAttendanceEnabled,
            ],
            'lowAttendanceAlert' => $records->count() > 0 && $percentage < 75,
            'deviceMarkedCount' => $deviceMarkedCount,
            'overviewChartData' => $this->overviewChartData($records, $summary, $dateFrom, $dateTo),
            'holidayCalendarDates' => $holidayCalendar['dates'],
            'holidayCalendarTitles' => $holidayCalendar['titles'],
        ];
    }

    public function monthlyCsvRows(User $user, string $month): array
    {
        $data = $this->portalData($user, ['month' => $month]);
        $student = $data['student'];
        $records = $data['records'];

        if (! $student) {
            return [];
        }

        $rows = [[
            'Student ID',
            'Student Name',
            'Date',
            'Class',
            'Section',
            'Period',
            'Status',
            'Leave Type',
            'Remark',
        ]];

        foreach ($records as $record) {
            $session = $record->attendanceSession;
            $rows[] = [
                $student->student_id,
                $student->name,
                $session?->attendance_date?->format('Y-m-d'),
                $session?->schoolClass?->name,
                $session?->section?->name,
                $session?->period_no,
                $record->status,
                $record->leave_type,
                $record->remark,
            ];
        }

        $rows[] = ['', '', '', '', '', '', 'Attendance %', number_format($data['percentage'], 2).'%', ''];

        return $rows;
    }

    private function resolveStudent(User $user): ?Student
    {
        $profile = StudentUserProfile::query()->where('user_id', $user->id)->with('student')->first();
        if ($profile?->student) {
            return $profile->student;
        }

        if (! empty($user->email)) {
            return Student::query()->where('school_id', $user->school_id)->where('email', $user->email)->first();
        }

        return null;
    }

    private function percentage(Collection $records): float
    {
        if ($records->count() === 0) {
            return 0;
        }

        $effective = 0.0;
        $denominator = 0.0;

        foreach ($records as $record) {
            if ($record->status === AttendanceRecord::STATUS_LEAVE) {
                continue;
            }

            $denominator += 1;

            if (in_array($record->status, [AttendanceRecord::STATUS_PRESENT, AttendanceRecord::STATUS_LATE], true)) {
                $effective += 1;
            } elseif ($record->status === AttendanceRecord::STATUS_HALF_DAY) {
                $effective += 0.5;
            }
        }

        if ($denominator <= 0) {
            return 0;
        }

        return ($effective / $denominator) * 100;
    }

    private function summary(Collection $records): array
    {
        return [
            'present' => $records->where('status', AttendanceRecord::STATUS_PRESENT)->count(),
            'absent' => $records->where('status', AttendanceRecord::STATUS_ABSENT)->count(),
            'late' => $records->where('status', AttendanceRecord::STATUS_LATE)->count(),
            'half_day' => $records->where('status', AttendanceRecord::STATUS_HALF_DAY)->count(),
            'leave' => $records->where('status', AttendanceRecord::STATUS_LEAVE)->count(),
        ];
    }

    private function overviewChartData(Collection $records, array $summary, string $dateFrom, string $dateTo): array
    {
        $statusLabels = ['Present', 'Absent', 'Late', 'Half-day', 'Leave'];
        $statusValues = [
            (int) ($summary['present'] ?? 0),
            (int) ($summary['absent'] ?? 0),
            (int) ($summary['late'] ?? 0),
            (int) ($summary['half_day'] ?? 0),
            (int) ($summary['leave'] ?? 0),
        ];

        $trendLabels = [];
        $trendPercentages = [];
        $byDate = [];

        foreach ($records as $record) {
            $date = $record->attendanceSession?->attendance_date?->format('Y-m-d');
            if (! $date) {
                continue;
            }
            $byDate[$date][] = $record->status;
        }

        $start = \Illuminate\Support\Carbon::parse($dateFrom)->startOfDay();
        $end = \Illuminate\Support\Carbon::parse($dateTo)->startOfDay();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $statuses = $byDate[$date] ?? [];
            $effective = 0.0;
            $denominator = 0.0;
            foreach ($statuses as $status) {
                if ($status === AttendanceRecord::STATUS_LEAVE) {
                    continue;
                }
                $denominator += 1;
                if ($status === AttendanceRecord::STATUS_PRESENT || $status === AttendanceRecord::STATUS_LATE) {
                    $effective += 1;
                } elseif ($status === AttendanceRecord::STATUS_HALF_DAY) {
                    $effective += 0.5;
                }
            }
            $trendLabels[] = $cursor->format('M d');
            $trendPercentages[] = $denominator > 0 ? round(($effective / $denominator) * 100, 2) : 0.0;
            $cursor->addDay();
        }

        return [
            'statusLabels' => $statusLabels,
            'statusValues' => $statusValues,
            'trendLabels' => $trendLabels,
            'trendPercentages' => $trendPercentages,
        ];
    }

    /**
     * @return array{dates: array<int, string>, titles: array<string, string>}
     */
    private function holidayCalendarForStudent(int $schoolId, Student $student): array
    {
        $events = $this->attendanceCalendarService->listBySchool($schoolId)
            ->where('is_active', true)
            ->where('event_type', SchoolCalendarEvent::TYPE_HOLIDAY)
            ->filter(function (SchoolCalendarEvent $event) use ($student): bool {
                $classMatches = $event->school_class_id === null || (int) $event->school_class_id === (int) $student->school_class_id;
                $sectionMatches = $event->section_id === null || (int) $event->section_id === (int) $student->section_id;

                return $classMatches && $sectionMatches;
            });

        $dates = $events
            ->map(fn (SchoolCalendarEvent $event) => (string) $event->event_date?->format('Y-m-d'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $titles = $events
            ->filter(fn (SchoolCalendarEvent $event) => $event->event_date !== null)
            ->mapWithKeys(fn (SchoolCalendarEvent $event) => [
                $event->event_date->format('Y-m-d') => (string) $event->title,
            ])
            ->all();

        return [
            'dates' => $dates,
            'titles' => $titles,
        ];
    }
}
