<?php

namespace Database\Seeders;

use App\Models\ParentStudentLink;
use App\Models\Student;
use App\Models\StudentUserProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class PortalLinkSeeder extends Seeder
{
    public function run(): void
    {
        $studentUser = User::query()->where('email', 'student@alpha.test')->first();
        $parentUser = User::query()->where('email', 'parent@alpha.test')->first();

        $targetStudent = Student::query()
            ->where('student_id', 'ALP-01A-01')
            ->orWhere('email', 'student@alpha.test')
            ->first();

        if (! $targetStudent) {
            return;
        }

        if ($studentUser) {
            StudentUserProfile::query()->updateOrCreate(
                ['user_id' => $studentUser->id],
                ['student_id' => $targetStudent->id]
            );
        }

        if ($parentUser) {
            ParentStudentLink::query()->updateOrCreate(
                [
                    'parent_user_id' => $parentUser->id,
                    'student_id' => $targetStudent->id,
                ],
                []
            );
        }
    }
}
