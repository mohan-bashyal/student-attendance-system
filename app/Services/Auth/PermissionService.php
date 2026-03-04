<?php

namespace App\Services\Auth;

use App\Models\User;

class PermissionService
{
    private const MATRIX = [
        User::ROLE_SUPER_ADMIN => ['*'],
        User::ROLE_ADMIN => [
            'manage_school',
            'manage_teachers',
            'manage_students',
            'view_reports',
        ],
        User::ROLE_TEACHER => [
            'mark_attendance',
            'view_students',
            'view_reports',
        ],
        User::ROLE_STUDENT => [
            'view_attendance',
            'view_profile',
        ],
        User::ROLE_PARENT => [
            'view_child_attendance',
            'view_reports',
        ],
        User::ROLE_STAFF => [
            'manage_operations',
            'view_reports',
        ],
    ];

    public function hasPermission(User $user, string $permission): bool
    {
        $permissions = self::MATRIX[$user->role] ?? [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
