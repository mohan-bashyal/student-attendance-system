<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleBasedAccessSeeder extends Seeder
{
    public function run(): void
    {
        $alpha = School::query()->updateOrCreate(
            ['code' => 'ALPHA'],
            ['name' => 'Alpha Public School', 'domain' => 'alpha.school.local']
        );

        $beta = School::query()->updateOrCreate(
            ['code' => 'BETA'],
            ['name' => 'Beta Model School', 'domain' => 'beta.school.local']
        );

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@attendance.test',
                'role' => User::ROLE_SUPER_ADMIN,
                'school_id' => null,
            ],
            [
                'name' => 'School Admin',
                'email' => 'admin@alpha.test',
                'role' => User::ROLE_ADMIN,
                'school_id' => $alpha->id,
            ],
            [
                'name' => 'Teacher One',
                'email' => 'teacher@alpha.test',
                'role' => User::ROLE_TEACHER,
                'school_id' => $alpha->id,
            ],
            [
                'name' => 'Student One',
                'email' => 'student@alpha.test',
                'role' => User::ROLE_STUDENT,
                'school_id' => $alpha->id,
            ],
            [
                'name' => 'Parent One',
                'email' => 'parent@alpha.test',
                'role' => User::ROLE_PARENT,
                'school_id' => $alpha->id,
            ],
            [
                'name' => 'Staff One',
                'email' => 'staff@beta.test',
                'role' => User::ROLE_STAFF,
                'school_id' => $beta->id,
            ],
        ];

        foreach ($users as $data) {
            User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => $data['role'],
                    'school_id' => $data['school_id'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
