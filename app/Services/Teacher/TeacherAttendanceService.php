<?php

namespace App\Services\Teacher;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassTeacherAssignment;
use App\Models\SchoolCalendarEvent;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Attendance\AttendanceAuditService;
use App\Services\Attendance\AttendanceCalendarService;
use App\Services\Attendance\AttendanceNotificationService;
use App\Services\Admin\AdminDashboardNotificationService;
use App\Services\Subscription\PlanFeatureService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class TeacherAttendanceService
{
    public function __construct(
        private readonly AttendanceNotificationService $notificationService,
        private readonly AdminDashboardNotificationService $adminNotificationService,
        private readonly PlanFeatureService $planFeatureService,
        private readonly AttendanceAuditService $attendanceAuditService,
        private readonly AttendanceCalendarService $attendanceCalendarService
    ) {
    }

    public function pageData(User $user, array $filters): array
    {
        $teacher = $this->resolveTeacher($user);
        $assignments = collect();
        $selectedAssignment = null;
        $students = collect();
        $recordsByStudent = [];
        $today = now()->toDateString();
        $requestedOverviewChart = (string) ($filters['overview_chart'] ?? 'doughnut');
        $selectedOverviewChart = in_array($requestedOverviewChart, ['doughnut', 'bar', 'line'], true)
            ? $requestedOverviewChart
            : 'doughnut';
        $canUseCorrectionWindow = $this->planFeatureService->isEnabledForSchoolId((int) $user->school_id, PlanFeatureService::FEATURE_ATTENDANCE_CORRECTION_WINDOW);
        $hasExtendedCorrectionWindow = $this->planFeatureService->isEnabledForSchoolId((int) $user->school_id, PlanFeatureService::FEATURE_EXTENDED_CORRECTION_WINDOW);
        $isDeviceAttendanceEnabled = $this->planFeatureService->isEnabledForSchoolId((int) $user->school_id, PlanFeatureService::FEATURE_DEVICE_ATTENDANCE);
        $selectedAttendanceDate = $today;
        $session = null;
        $selectedDateCalendarEvent = null;
        if ($canUseCorrectionWindow && ! empty($filters['attendance_date'])) {
            $selectedAttendanceDate = (string) $filters['attendance_date'];
        }

        if ($teacher && $teacher->has_attendance_access) {
            $assignments = ClassTeacherAssignment::query()
                ->where('school_id', $user->school_id)
                ->where('teacher_id', $teacher->id)
                ->with(['schoolClass', 'section'])
                ->orderBy('school_class_id')
                ->orderBy('section_id')
                ->get();

            $assignmentId = isset($filters['assignment_id']) ? (int) $filters['assignment_id'] : ($assignments->first()?->id ?? null);
            $selectedAssignment = $assignmentId ? $assignments->firstWhere('id', $assignmentId) : null;

            if ($selectedAssignment) {
                $students = Student::query()
                    ->where('school_id', $selectedAssignment->school_id)
                    ->where('school_class_id', $selectedAssignment->school_class_id)
                    ->where('section_id', $selectedAssignment->section_id)
                    ->orderBy('name')
                    ->get();

                $subject = $this->dailySubject((int) $selectedAssignment->school_id);
                $session = AttendanceSession::query()
                    ->where('school_id', $selectedAssignment->school_id)
                    ->where('school_class_id', $selectedAssignment->school_class_id)
                    ->where('section_id', $selectedAssignment->section_id)
                    ->where('subject_id', $subject->id)
                    ->where('period_no', 1)
                    ->whereDate('attendance_date', $selectedAttendanceDate)
                    ->with(['records', 'markedBy'])
                    ->first();

                if ($session) {
                    $recordsByStudent = $session->records->keyBy('student_id')->toArray();
                }

                $selectedDateCalendarEvent = $this->attendanceCalendarService->eventForDate(
                    (int) $selectedAssignment->school_id,
                    $selectedAttendanceDate,
                    (int) $selectedAssignment->school_class_id,
                    (int) $selectedAssignment->section_id
                );
            }
        }

        $summary = $this->summaryFromRecords($recordsByStudent);
        $deviceMarkedCount = collect($recordsByStudent)->filter(
            fn ($record) => Str::startsWith((string) ($record['remark'] ?? ''), 'Marked via device:')
        )->count();
        $overviewChartData = $this->overviewChartData($selectedAssignment, $summary);
        $holidayCalendar = $this->holidayCalendarForAssignment((int) $user->school_id, $selectedAssignment);

        return [
            'school' => $user->school,
            'teacher' => $teacher,
            'assignments' => $assignments,
            'selectedAssignment' => $selectedAssignment,
            'students' => $students,
            'recordsByStudent' => $recordsByStudent,
            'today' => $today,
            'selectedAttendanceDate' => $selectedAttendanceDate,
            'canUseCorrectionWindow' => $canUseCorrectionWindow,
            'minimumAttendanceDate' => ($canUseCorrectionWindow ? ($hasExtendedCorrectionWindow ? now()->subDays(30)->toDateString() : now()->subDay()->toDateString()) : $today),
            'maximumAttendanceDate' => $today,
            'statusTypes' => AttendanceRecord::STATUSES,
            'leaveTypes' => AttendanceRecord::LEAVE_TYPES,
            'summary' => $summary,
            'deviceMarkedCount' => $deviceMarkedCount,
            'featureFlags' => [
                'correction_window' => $canUseCorrectionWindow,
                'extended_correction_window' => $hasExtendedCorrectionWindow,
                'device_attendance' => $isDeviceAttendanceEnabled,
            ],
            'sessionMarkedBy' => $session?->markedBy?->name,
            'selectedOverviewChart' => $selectedOverviewChart,
            'overviewChartData' => $overviewChartData,
            'selectedDateCalendarEvent' => $selectedDateCalendarEvent,
            'holidayCalendarDates' => $holidayCalendar['dates'],
            'holidayCalendarTitles' => $holidayCalendar['titles'],
        ];
    }

    public function markToday(User $user, array $payload): void
    {
        $teacher = $this->resolveTeacher($user);
        if (! $teacher || ! $teacher->has_attendance_access) {
            throw new AuthorizationException('Teacher attendance access is disabled.');
        }

        $assignment = ClassTeacherAssignment::query()
            ->where('school_id', $user->school_id)
            ->where('teacher_id', $teacher->id)
            ->findOrFail((int) $payload['assignment_id']);

        $subject = $this->dailySubject((int) $assignment->school_id);
        $today = now()->toDateString();
        $attendanceDate = $today;
        $canUseCorrectionWindow = $this->planFeatureService->isEnabledForSchoolId((int) $assignment->school_id, PlanFeatureService::FEATURE_ATTENDANCE_CORRECTION_WINDOW);
        $hasExtendedCorrectionWindow = $this->planFeatureService->isEnabledForSchoolId((int) $assignment->school_id, PlanFeatureService::FEATURE_EXTENDED_CORRECTION_WINDOW);
        if ($canUseCorrectionWindow && ! empty($payload['attendance_date'])) {
            $candidateDate = Carbon::parse((string) $payload['attendance_date'])->toDateString();
            $minimumDate = $hasExtendedCorrectionWindow ? now()->subDays(30)->toDateString() : now()->subDay()->toDateString();
            if ($candidateDate < $minimumDate || $candidateDate > $today) {
                throw ValidationException::withMessages([
                    'attendance_date' => $hasExtendedCorrectionWindow
                        ? 'Enterprise correction window allows only the last 30 days.'
                        : 'Pro plan correction window allows only today or yesterday.',
                ]);
            }
            $attendanceDate = $candidateDate;
        }

        if ($this->attendanceCalendarService->isHoliday(
            (int) $assignment->school_id,
            $attendanceDate,
            (int) $assignment->school_class_id,
            (int) $assignment->section_id
        )) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Selected date is marked as holiday in school calendar. Attendance is locked.',
            ]);
        }

        $sessionAttributes = [
            'school_id' => $assignment->school_id,
            'school_class_id' => $assignment->school_class_id,
            'section_id' => $assignment->section_id,
            'subject_id' => $subject->id,
            'period_no' => 1,
            'attendance_date' => $attendanceDate,
        ];

        try {
            $session = AttendanceSession::query()->firstOrCreate(
                $sessionAttributes,
                ['marked_by' => $user->id]
            );
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            $session = AttendanceSession::query()->where($sessionAttributes)->firstOrFail();
            $session->update(['marked_by' => $user->id]);
        }

        $students = Student::query()
            ->where('school_id', $assignment->school_id)
            ->where('school_class_id', $assignment->school_class_id)
            ->where('section_id', $assignment->section_id)
            ->get()
            ->keyBy('id');

        foreach ($payload['records'] as $studentId => $entry) {
            $studentId = (int) $studentId;
            $student = $students->get($studentId);
            if (! $student) {
                continue;
            }

            $status = $entry['status'] ?? AttendanceRecord::STATUS_PRESENT;
            $leaveType = $status === AttendanceRecord::STATUS_LEAVE ? ($entry['leave_type'] ?? null) : null;
            $remark = $entry['remark'] ?? null;

            $record = AttendanceRecord::query()->firstOrNew([
                'attendance_session_id' => $session->id,
                'student_id' => $studentId,
            ]);

            $previous = $record->exists ? $record->status : null;
            $beforeRecord = $record->exists ? clone $record : null;

            $record->fill([
                'status' => $status,
                'leave_type' => $leaveType,
                'remark' => $remark,
            ]);
            $record->save();

            $this->attendanceAuditService->logRecordChange(
                $user,
                $teacher,
                $session,
                $studentId,
                $beforeRecord,
                $record
            );

            $this->notificationService->syncStudentPortalAbsentNotification(
                $student,
                $session,
                $status
            );

            if ($status === AttendanceRecord::STATUS_ABSENT && $previous !== AttendanceRecord::STATUS_ABSENT) {
                $this->notificationService->notifyAbsent($student, $session);
            }
        }

        $this->adminNotificationService->notifyAttendanceMarked(
            $session,
            $assignment,
            $teacher,
            $students->count()
        );
    }

    private function resolveTeacher(User $user): ?Teacher
    {
        return Teacher::query()
            ->where('school_id', $user->school_id)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();
    }

    private function dailySubject(int $schoolId): Subject
    {
        return Subject::query()->firstOrCreate(
            [
                'school_id' => $schoolId,
                'name' => 'Daily Attendance',
            ],
            [
                'code' => 'ATTN',
            ]
        );
    }

    private function summaryFromRecords(array $recordsByStudent): array
    {
        $records = collect($recordsByStudent);

        return [
            'present' => $records->where('status', AttendanceRecord::STATUS_PRESENT)->count(),
            'absent' => $records->where('status', AttendanceRecord::STATUS_ABSENT)->count(),
            'late' => $records->where('status', AttendanceRecord::STATUS_LATE)->count(),
            'half_day' => $records->where('status', AttendanceRecord::STATUS_HALF_DAY)->count(),
            'leave' => $records->where('status', AttendanceRecord::STATUS_LEAVE)->count(),
        ];
    }

    private function overviewChartData(?ClassTeacherAssignment $selectedAssignment, array $summary): array
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
        if (! $selectedAssignment) {
            return [
                'statusLabels' => $statusLabels,
                'statusValues' => $statusValues,
                'trendLabels' => $trendLabels,
                'trendPercentages' => $trendPercentages,
            ];
        }

        $start = now()->subDays(13)->startOfDay();
        $end = now()->startOfDay();
        $records = AttendanceRecord::query()
            ->whereHas('attendanceSession', function ($query) use ($selectedAssignment, $start, $end): void {
                $query->where('school_id', $selectedAssignment->school_id)
                    ->where('school_class_id', $selectedAssignment->school_class_id)
                    ->where('section_id', $selectedAssignment->section_id)
                    ->where('period_no', 1)
                    ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->with(['attendanceSession:id,attendance_date'])
            ->get();

        $byDate = [];
        foreach ($records as $record) {
            $date = $record->attendanceSession?->attendance_date?->format('Y-m-d');
            if (! $date) {
                continue;
            }
            $byDate[$date][] = $record->status;
        }

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
            $percentage = $denominator > 0 ? round(($effective / $denominator) * 100, 2) : 0.0;
            $trendLabels[] = $cursor->format('M d');
            $trendPercentages[] = $percentage;
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
    private function holidayCalendarForAssignment(int $schoolId, ?ClassTeacherAssignment $assignment): array
    {
        $events = $this->attendanceCalendarService->listBySchool($schoolId)
            ->where('is_active', true)
            ->where('event_type', SchoolCalendarEvent::TYPE_HOLIDAY);

        if ($assignment) {
            $events = $events->filter(function (SchoolCalendarEvent $event) use ($assignment): bool {
                $classMatches = $event->school_class_id === null || (int) $event->school_class_id === (int) $assignment->school_class_id;
                $sectionMatches = $event->section_id === null || (int) $event->section_id === (int) $assignment->section_id;

                return $classMatches && $sectionMatches;
            });
        }

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
