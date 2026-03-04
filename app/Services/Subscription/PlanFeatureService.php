<?php

namespace App\Services\Subscription;

use App\Models\School;

class PlanFeatureService
{
    public const FEATURE_STUDENT_IMPORT = 'student_import';
    public const FEATURE_ADMIN_ATTENDANCE_NOTIFICATIONS = 'admin_attendance_notifications';
    public const FEATURE_PARENT_ALERTS = 'parent_alerts';
    public const FEATURE_ADVANCED_ATTENDANCE_REPORTS = 'advanced_attendance_reports';
    public const FEATURE_ATTENDANCE_CORRECTION_WINDOW = 'attendance_correction_window';
    public const FEATURE_ENTERPRISE_ATTENDANCE_EXPORT = 'enterprise_attendance_export';
    public const FEATURE_ATTENDANCE_AUDIT_LOGS = 'attendance_audit_logs';
    public const FEATURE_EXTENDED_CORRECTION_WINDOW = 'extended_correction_window';
    public const FEATURE_DEVICE_ATTENDANCE = 'device_attendance';

    public function isEnabledForSchoolId(int $schoolId, string $feature): bool
    {
        $school = School::query()->find($schoolId);
        if (! $school) {
            return false;
        }

        return $this->isEnabledForPlan((string) $school->subscription_plan, $feature);
    }

    public function isEnabledForPlan(string $plan, string $feature): bool
    {
        $matrix = [
            School::SUBSCRIPTION_PLAN_BASIC => [
                self::FEATURE_STUDENT_IMPORT => false,
                self::FEATURE_ADMIN_ATTENDANCE_NOTIFICATIONS => false,
                self::FEATURE_PARENT_ALERTS => false,
                self::FEATURE_ADVANCED_ATTENDANCE_REPORTS => false,
                self::FEATURE_ATTENDANCE_CORRECTION_WINDOW => false,
                self::FEATURE_ENTERPRISE_ATTENDANCE_EXPORT => false,
                self::FEATURE_ATTENDANCE_AUDIT_LOGS => false,
                self::FEATURE_EXTENDED_CORRECTION_WINDOW => false,
                self::FEATURE_DEVICE_ATTENDANCE => false,
            ],
            School::SUBSCRIPTION_PLAN_PRO => [
                self::FEATURE_STUDENT_IMPORT => true,
                self::FEATURE_ADMIN_ATTENDANCE_NOTIFICATIONS => true,
                self::FEATURE_PARENT_ALERTS => true,
                self::FEATURE_ADVANCED_ATTENDANCE_REPORTS => true,
                self::FEATURE_ATTENDANCE_CORRECTION_WINDOW => true,
                self::FEATURE_ENTERPRISE_ATTENDANCE_EXPORT => false,
                self::FEATURE_ATTENDANCE_AUDIT_LOGS => false,
                self::FEATURE_EXTENDED_CORRECTION_WINDOW => false,
                self::FEATURE_DEVICE_ATTENDANCE => false,
            ],
            School::SUBSCRIPTION_PLAN_ENTERPRISE => [
                self::FEATURE_STUDENT_IMPORT => true,
                self::FEATURE_ADMIN_ATTENDANCE_NOTIFICATIONS => true,
                self::FEATURE_PARENT_ALERTS => true,
                self::FEATURE_ADVANCED_ATTENDANCE_REPORTS => true,
                self::FEATURE_ATTENDANCE_CORRECTION_WINDOW => true,
                self::FEATURE_ENTERPRISE_ATTENDANCE_EXPORT => true,
                self::FEATURE_ATTENDANCE_AUDIT_LOGS => true,
                self::FEATURE_EXTENDED_CORRECTION_WINDOW => true,
                self::FEATURE_DEVICE_ATTENDANCE => true,
            ],
        ];

        return $matrix[$plan][$feature] ?? true;
    }
}
