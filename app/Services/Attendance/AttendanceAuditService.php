<?php

namespace App\Services\Attendance;

use App\Models\AttendanceAuditLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Subscription\PlanFeatureService;
use Illuminate\Support\Facades\Schema;

class AttendanceAuditService
{
    public function __construct(private readonly PlanFeatureService $planFeatureService)
    {
    }

    public function logRecordChange(
        User $actor,
        Teacher $teacher,
        AttendanceSession $session,
        int $studentId,
        ?AttendanceRecord $before,
        AttendanceRecord $after
    ): void {
        if (! $this->planFeatureService->isEnabledForSchoolId((int) $session->school_id, PlanFeatureService::FEATURE_ATTENDANCE_AUDIT_LOGS)) {
            return;
        }

        if (! Schema::hasTable('attendance_audit_logs')) {
            return;
        }

        AttendanceAuditLog::query()->create([
            'school_id' => $session->school_id,
            'attendance_session_id' => $session->id,
            'student_id' => $studentId,
            'teacher_id' => $teacher->id,
            'changed_by' => $actor->id,
            'action' => $before === null ? 'created' : 'updated',
            'previous_status' => $before?->status,
            'new_status' => $after->status,
            'previous_leave_type' => $before?->leave_type,
            'new_leave_type' => $after->leave_type,
            'previous_remark' => $before?->remark,
            'new_remark' => $after->remark,
            'changed_at' => now(),
        ]);
    }
}
