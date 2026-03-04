<?php

namespace App\Services\Admin;

use App\Models\AdminAttendanceNotification;
use App\Models\AttendanceSession;
use App\Models\ClassTeacherAssignment;
use App\Models\Teacher;
use App\Services\Subscription\PlanFeatureService;
use Illuminate\Support\Facades\Schema;

class AdminDashboardNotificationService
{
    public function __construct(private readonly PlanFeatureService $planFeatureService)
    {
    }

    public function notifyAttendanceMarked(
        AttendanceSession $session,
        ClassTeacherAssignment $assignment,
        Teacher $teacher,
        int $totalStudents
    ): void {
        if (! $this->planFeatureService->isEnabledForSchoolId((int) $session->school_id, PlanFeatureService::FEATURE_ADMIN_ATTENDANCE_NOTIFICATIONS)) {
            return;
        }

        if (! Schema::hasTable('admin_attendance_notifications')) {
            return;
        }

        $assignment->loadMissing(['schoolClass', 'section']);

        $className = (string) ($assignment->schoolClass?->name ?? 'Class');
        $sectionName = (string) ($assignment->section?->name ?? 'Section');
        $date = $session->attendance_date->format('Y-m-d');
        $message = "{$teacher->name} marked attendance for {$className} - {$sectionName} on {$date}.";

        AdminAttendanceNotification::query()->updateOrCreate(
            [
                'school_id' => $session->school_id,
                'attendance_session_id' => $session->id,
            ],
            [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'class_name' => $className,
                'section_name' => $sectionName,
                'attendance_date' => $session->attendance_date,
                'total_students' => $totalStudents,
                'message' => $message,
            ]
        );
    }
}
