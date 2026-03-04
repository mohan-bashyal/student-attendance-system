<?php

namespace App\Services\Attendance;

use App\Models\AttendanceAuditLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\SchoolDevice;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Subscription\PlanFeatureService;
use App\Services\Attendance\AttendanceCalendarService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DeviceAttendanceService
{
    public function __construct(
        private readonly PlanFeatureService $planFeatureService,
        private readonly AttendanceCalendarService $attendanceCalendarService
    )
    {
    }

    public function recordFromDevice(SchoolDevice $device, array $payload): array
    {
        if (! $this->planFeatureService->isEnabledForSchoolId((int) $device->school_id, PlanFeatureService::FEATURE_DEVICE_ATTENDANCE)) {
            throw ValidationException::withMessages([
                'device' => 'Device attendance is available only in ENTERPRISE plan.',
            ]);
        }

        $studentCode = (string) $payload['student_code'];
        $student = Student::query()
            ->where('school_id', $device->school_id)
            ->where(function ($query) use ($studentCode): void {
                $query->where('student_id', $studentCode)
                    ->orWhere('device_identifier', $studentCode);
            })
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'student_code' => 'Student not found for this device school.',
            ]);
        }

        if (! $student->school_class_id || ! $student->section_id) {
            throw ValidationException::withMessages([
                'student_code' => 'Student class/section is not assigned.',
            ]);
        }

        $status = $payload['status'] ?? AttendanceRecord::STATUS_PRESENT;
        if (! in_array($status, AttendanceRecord::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid attendance status.',
            ]);
        }

        $eventAt = isset($payload['event_at'])
            ? Carbon::parse((string) $payload['event_at'])
            : now();
        $attendanceDate = $eventAt->toDateString();

        if ($this->attendanceCalendarService->isHoliday(
            (int) $device->school_id,
            $attendanceDate,
            (int) $student->school_class_id,
            (int) $student->section_id
        )) {
            throw ValidationException::withMessages([
                'event_at' => 'Selected date is configured as holiday. Device attendance is locked for this class/section.',
            ]);
        }

        $subject = Subject::query()->firstOrCreate(
            [
                'school_id' => $device->school_id,
                'name' => 'Daily Attendance',
            ],
            [
                'code' => 'ATTN',
            ]
        );

        $session = AttendanceSession::query()->firstOrCreate(
            [
                'school_id' => $device->school_id,
                'school_class_id' => $student->school_class_id,
                'section_id' => $student->section_id,
                'subject_id' => $subject->id,
                'period_no' => 1,
                'attendance_date' => $attendanceDate,
            ],
            [
                'marked_by' => null,
            ]
        );

        $record = AttendanceRecord::query()->firstOrNew([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
        ]);

        $before = $record->exists ? clone $record : null;
        $record->fill([
            'status' => $status,
            'leave_type' => null,
            'remark' => 'Marked via device: '.$device->name,
        ]);
        $record->save();

        if ($this->planFeatureService->isEnabledForSchoolId((int) $device->school_id, PlanFeatureService::FEATURE_ATTENDANCE_AUDIT_LOGS)
            && Schema::hasTable('attendance_audit_logs')) {
            AttendanceAuditLog::query()->create([
                'school_id' => $device->school_id,
                'attendance_session_id' => $session->id,
                'student_id' => $student->id,
                'teacher_id' => null,
                'changed_by' => null,
                'action' => $before ? 'device_updated' : 'device_created',
                'previous_status' => $before?->status,
                'new_status' => $record->status,
                'previous_leave_type' => $before?->leave_type,
                'new_leave_type' => $record->leave_type,
                'previous_remark' => $before?->remark,
                'new_remark' => $record->remark,
                'changed_at' => now(),
            ]);
        }

        return [
            'student_id' => $student->student_id,
            'student_name' => $student->name,
            'status' => $record->status,
            'attendance_date' => $attendanceDate,
            'session_id' => $session->id,
        ];
    }
}
