<?php

namespace App\Services\SuperAdmin;

use App\Models\School;
use App\Models\SchoolDevice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminManagementService
{
    public function dashboardData(): array
    {
        $schools = School::query()
            ->withCount([
                'users as students_count' => fn ($query) => $query->where('role', User::ROLE_STUDENT),
                'users as admins_count' => fn ($query) => $query->where('role', User::ROLE_ADMIN),
                'devices as devices_count',
            ])
            ->orderBy('name')
            ->get();

        return [
            'schools' => $schools,
            'totalSchools' => School::query()->count(),
            'totalStudents' => User::query()->where('role', User::ROLE_STUDENT)->count(),
            'planOptions' => School::ENABLED_SUBSCRIPTION_PLANS,
            'statusOptions' => School::SUBSCRIPTION_STATUSES,
            'devices' => SchoolDevice::query()->with('school')->orderByDesc('id')->limit(30)->get(),
        ];
    }

    public function createSchool(array $data): School
    {
        $payload = [
            ...$data,
            'code' => strtoupper($data['code']),
            'is_active' => true,
        ];
        if (($payload['subscription_plan'] ?? null) === School::SUBSCRIPTION_PLAN_BASIC) {
            $payload['max_students'] = School::BASIC_MAX_STUDENTS;
        } elseif (($payload['subscription_plan'] ?? null) === School::SUBSCRIPTION_PLAN_PRO && empty($payload['max_students'])) {
            $payload['max_students'] = School::PRO_MAX_STUDENTS;
        }

        return School::query()->create($payload);
    }

    public function toggleSchoolStatus(School $school): bool
    {
        $school->update([
            'is_active' => ! $school->is_active,
        ]);

        return $school->is_active;
    }

    public function createSchoolAdmin(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            return User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_ADMIN,
                'school_id' => $data['school_id'],
                'email_verified_at' => now(),
            ]);
        });
    }

    public function updateSubscription(School $school, array $data): void
    {
        if (($data['subscription_plan'] ?? null) === School::SUBSCRIPTION_PLAN_BASIC) {
            $data['max_students'] = School::BASIC_MAX_STUDENTS;
        } elseif (($data['subscription_plan'] ?? null) === School::SUBSCRIPTION_PLAN_PRO && empty($data['max_students'])) {
            $data['max_students'] = School::PRO_MAX_STUDENTS;
        }

        $school->update($data);
    }

    public function createSchoolDevice(array $data): array
    {
        $token = 'dev_'.bin2hex(random_bytes(20));

        $device = SchoolDevice::query()->create([
            'school_id' => (int) $data['school_id'],
            'name' => $data['name'],
            'device_code' => $data['device_code'] ?? null,
            'token' => $token,
            'is_active' => true,
        ]);

        return [
            'id' => $device->id,
            'name' => $device->name,
            'token' => $token,
        ];
    }

    public function toggleDeviceStatus(SchoolDevice $device): bool
    {
        $device->update(['is_active' => ! $device->is_active]);

        return $device->is_active;
    }
}
