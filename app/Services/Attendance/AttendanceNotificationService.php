<?php

namespace App\Services\Attendance;

use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\ParentStudentLink;
use App\Models\ParentNotificationDeliveryLog;
use App\Models\Student;
use App\Models\StudentAttendanceNotification;
use App\Services\Subscription\PlanFeatureService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class AttendanceNotificationService
{
    public function __construct(private readonly PlanFeatureService $planFeatureService)
    {
    }

    public function notifyAbsent(Student $student, AttendanceSession $session): void
    {
        if (! $this->planFeatureService->isEnabledForSchoolId((int) $session->school_id, PlanFeatureService::FEATURE_PARENT_ALERTS)) {
            return;
        }

        $parents = ParentStudentLink::query()
            ->where('student_id', $student->id)
            ->with('parent')
            ->get()
            ->pluck('parent')
            ->filter();
        $logEnabled = Schema::hasTable('parent_notification_delivery_logs');

        foreach ($parents as $parent) {
            $message = "{$student->name} is marked absent on {$session->attendance_date->format('Y-m-d')} ".
                "for period {$session->period_no}.";

            Log::info('SMS Alert (simulated)', [
                'to_parent' => $parent->name,
                'phone' => $parent->phone ?? 'N/A',
                'message' => $message,
            ]);
            if ($logEnabled) {
                ParentNotificationDeliveryLog::query()->create([
                    'school_id' => (int) $session->school_id,
                    'attendance_session_id' => (int) $session->id,
                    'student_id' => (int) $student->id,
                    'parent_user_id' => (int) $parent->id,
                    'channel' => ParentNotificationDeliveryLog::CHANNEL_SMS,
                    'status' => ParentNotificationDeliveryLog::STATUS_SENT,
                    'recipient' => (string) ($parent->phone ?? ''),
                    'message' => $message,
                    'sent_at' => now(),
                ]);
            }

            if (! empty($parent->email)) {
                try {
                    Mail::raw("Attendance Alert: {$message}", function ($mail) use ($parent): void {
                        $mail->to($parent->email)->subject('Student Absence Alert');
                    });

                    if ($logEnabled) {
                        ParentNotificationDeliveryLog::query()->create([
                            'school_id' => (int) $session->school_id,
                            'attendance_session_id' => (int) $session->id,
                            'student_id' => (int) $student->id,
                            'parent_user_id' => (int) $parent->id,
                            'channel' => ParentNotificationDeliveryLog::CHANNEL_EMAIL,
                            'status' => ParentNotificationDeliveryLog::STATUS_SENT,
                            'recipient' => (string) $parent->email,
                            'message' => $message,
                            'sent_at' => now(),
                        ]);
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Failed to send parent email alert', [
                        'parent_email' => $parent->email,
                        'error' => $exception->getMessage(),
                    ]);

                    if ($logEnabled) {
                        ParentNotificationDeliveryLog::query()->create([
                            'school_id' => (int) $session->school_id,
                            'attendance_session_id' => (int) $session->id,
                            'student_id' => (int) $student->id,
                            'parent_user_id' => (int) $parent->id,
                            'channel' => ParentNotificationDeliveryLog::CHANNEL_EMAIL,
                            'status' => ParentNotificationDeliveryLog::STATUS_FAILED,
                            'recipient' => (string) $parent->email,
                            'message' => $message,
                            'error_message' => $exception->getMessage(),
                        ]);
                    }
                }
            } elseif ($logEnabled) {
                ParentNotificationDeliveryLog::query()->create([
                    'school_id' => (int) $session->school_id,
                    'attendance_session_id' => (int) $session->id,
                    'student_id' => (int) $student->id,
                    'parent_user_id' => (int) $parent->id,
                    'channel' => ParentNotificationDeliveryLog::CHANNEL_EMAIL,
                    'status' => ParentNotificationDeliveryLog::STATUS_SKIPPED,
                    'recipient' => '',
                    'message' => $message,
                    'error_message' => 'Parent email is not available.',
                ]);
            }
        }
    }

    public function syncStudentPortalAbsentNotification(Student $student, AttendanceSession $session, string $status): void
    {
        if (! Schema::hasTable('student_attendance_notifications')) {
            return;
        }

        $lookup = [
            'school_id' => (int) $session->school_id,
            'attendance_session_id' => (int) $session->id,
            'student_id' => (int) $student->id,
        ];

        if ($status !== AttendanceRecord::STATUS_ABSENT) {
            StudentAttendanceNotification::query()->where($lookup)->delete();
            return;
        }

        $message = $session->attendance_date->isToday()
            ? 'Your class attendance is marked absent today.'
            : 'Your class attendance is marked absent on '.$session->attendance_date->format('Y-m-d').'.';

        StudentAttendanceNotification::query()->updateOrCreate(
            $lookup,
            [
                'message' => $message,
                'is_read' => false,
                'notified_at' => now(),
            ]
        );
    }
}
