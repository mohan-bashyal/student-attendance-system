<?php

namespace App\Services\Auth;

use App\Models\User;

class DashboardService
{
    public function routeFor(User $user): string
    {
        return match ($user->role) {
            User::ROLE_SUPER_ADMIN => 'dashboard.super_admin',
            User::ROLE_ADMIN => 'dashboard.admin',
            User::ROLE_TEACHER => 'dashboard.teacher',
            User::ROLE_STUDENT => 'dashboard.student',
            User::ROLE_PARENT => 'dashboard.parent',
            User::ROLE_STAFF => 'dashboard.staff',
            default => 'dashboard.index',
        };
    }

    public function pageData(User $user): array
    {
        $baseCards = [
            [
                'title' => 'Attendance Insights',
                'description' => 'View attendance trends and identify risky absentee patterns.',
                'icon' => 'chart',
            ],
            [
                'title' => 'Announcements',
                'description' => 'Receive important school updates and scheduling alerts.',
                'icon' => 'megaphone',
            ],
        ];

        $roleCards = match ($user->role) {
            User::ROLE_SUPER_ADMIN => [
                ['title' => 'Tenant Management', 'description' => 'Create and manage multiple schools from one console.', 'icon' => 'building'],
                ['title' => 'Global Permissions', 'description' => 'Control role and policy templates across all schools.', 'icon' => 'shield'],
            ],
            User::ROLE_ADMIN => [
                ['title' => 'School Operations', 'description' => 'Manage staff, classes, sessions, and school settings.', 'icon' => 'cog'],
                ['title' => 'Teacher Oversight', 'description' => 'Track attendance submission rates by teacher.', 'icon' => 'users'],
            ],
            User::ROLE_TEACHER => [
                ['title' => 'Mark Attendance', 'description' => 'Record daily attendance quickly by section and period.', 'icon' => 'check'],
                ['title' => 'Classroom Snapshot', 'description' => 'See student attendance scores and recent notes.', 'icon' => 'clipboard'],
            ],
            User::ROLE_STUDENT => [
                ['title' => 'My Attendance', 'description' => 'Review your monthly attendance and late arrivals.', 'icon' => 'calendar'],
                ['title' => 'Performance Alerts', 'description' => 'Know when attendance may impact your assessment.', 'icon' => 'bell'],
            ],
            User::ROLE_PARENT => [
                ['title' => 'Child Attendance Feed', 'description' => 'Monitor your child attendance in real time.', 'icon' => 'heart'],
                ['title' => 'Parent Notifications', 'description' => 'Get alerts for absences and late check-ins.', 'icon' => 'message'],
            ],
            User::ROLE_STAFF => [
                ['title' => 'Front Desk Queue', 'description' => 'Handle check-ins, visitor logs, and daily records.', 'icon' => 'desk'],
                ['title' => 'Operational Reports', 'description' => 'Export attendance related office reports securely.', 'icon' => 'document'],
            ],
            default => [],
        };

        return [
            'heading' => ucwords(str_replace('_', ' ', $user->role)).' Dashboard',
            'subheading' => $user->school?->name
                ? "Welcome to {$user->school->name} attendance portal."
                : 'Welcome to the multi-school attendance command center.',
            'cards' => array_merge($baseCards, $roleCards),
        ];
    }
}
