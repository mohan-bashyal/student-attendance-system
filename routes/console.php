<?php

use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\ClassSectionMapping;
use App\Models\ClassTeacherAssignment;
use App\Models\ParentStudentLink;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentChangeRequest;
use App\Models\StudentUserProfile;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Attendance\DeviceAttendanceEventService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:retry-device-events {--limit=100 : Max events to retry}', function () {
    $limit = max(1, (int) $this->option('limit'));
    $result = app(DeviceAttendanceEventService::class)->retryFailed($limit);

    $this->info("Retried up to {$limit} queued device events.");
    $this->line("Processed: {$result['processed']}");
    $this->line("Failed: {$result['failed']}");
})->purpose('Retry failed/pending device attendance events with idempotency queue');

Schedule::command('attendance:retry-device-events --limit=200')->everyFiveMinutes();

Artisan::command('portal:provision-students {--school= : School code filter (e.g. ALPHA)} {--reset-passwords : Reset temporary password for already-linked student users}', function () {
    $schoolCode = $this->option('school');
    $resetPasswords = (bool) $this->option('reset-passwords');

    $studentsQuery = Student::query()
        ->with('school')
        ->whereNotNull('email')
        ->where('email', '!=', '');

    if (! empty($schoolCode)) {
        $studentsQuery->whereHas('school', function ($query) use ($schoolCode): void {
            $query->whereRaw('LOWER(code) = ?', [strtolower((string) $schoolCode)]);
        });
    }

    $students = $studentsQuery->orderBy('id')->get();

    if ($students->isEmpty()) {
        $this->warn('No students with emails found.');
        return;
    }

    $created = 0;
    $linked = 0;
    $reset = 0;
    $skipped = 0;
    $conflicts = 0;

    $rows = [[
        'student_id',
        'student_name',
        'student_email',
        'school_code',
        'user_id',
        'user_email',
        'temporary_password',
        'action',
    ]];

    foreach ($students as $student) {
        $email = strtolower(trim((string) $student->email));
        if ($email === '') {
            $skipped++;
            continue;
        }

        $profile = StudentUserProfile::query()->where('student_id', $student->id)->first();
        $user = null;
        $plainPassword = '';
        $action = 'skipped';

        if ($profile) {
            $user = User::query()->find($profile->user_id);
            if (! $user) {
                $profile->delete();
            }
        }

        if (! $user) {
            $existingByEmail = User::query()->where('email', $email)->first();

            if ($existingByEmail) {
                if ($existingByEmail->role !== User::ROLE_STUDENT || (int) $existingByEmail->school_id !== (int) $student->school_id) {
                    $conflicts++;
                    $action = 'email_conflict';
                    $rows[] = [
                        $student->student_id,
                        $student->name,
                        $email,
                        $student->school?->code ?? '',
                        $existingByEmail->id,
                        $existingByEmail->email,
                        '',
                        $action,
                    ];
                    continue;
                }

                $user = $existingByEmail;
                StudentUserProfile::query()->updateOrCreate(
                    ['student_id' => $student->id],
                    ['user_id' => $user->id]
                );
                $linked++;
                $action = 'linked_existing_user';
            } else {
                $plainPassword = Str::password(10);
                $user = User::query()->firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $student->name,
                        'password' => Hash::make($plainPassword),
                        'role' => User::ROLE_STUDENT,
                        'school_id' => $student->school_id,
                        'email_verified_at' => now(),
                    ]
                );

                if (! $user->wasRecentlyCreated && ($user->role !== User::ROLE_STUDENT || (int) $user->school_id !== (int) $student->school_id)) {
                    $conflicts++;
                    $action = 'email_conflict';
                    $rows[] = [
                        $student->student_id,
                        $student->name,
                        $email,
                        $student->school?->code ?? '',
                        $user->id,
                        $user->email,
                        '',
                        $action,
                    ];
                    continue;
                }

                StudentUserProfile::query()->updateOrCreate(
                    ['student_id' => $student->id],
                    ['user_id' => $user->id]
                );

                if ($user->wasRecentlyCreated) {
                    $created++;
                    $action = 'created_user';
                } else {
                    $linked++;
                    $plainPassword = '';
                    $action = 'linked_existing_user';
                }
            }
        } elseif ($resetPasswords) {
            $plainPassword = Str::password(10);
            $user->update(['password' => Hash::make($plainPassword)]);
            $reset++;
            $action = 'reset_password';
        } else {
            $skipped++;
            $action = 'already_linked';
        }

        $rows[] = [
            $student->student_id,
            $student->name,
            $email,
            $student->school?->code ?? '',
            $user?->id ?? '',
            $user?->email ?? '',
            $plainPassword,
            $action,
        ];
    }

    $dir = storage_path('app/exports');
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filePath = $dir.'/student-portal-credentials-'.now()->format('Ymd_His').'.csv';
    $handle = fopen($filePath, 'w');
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    $this->info("Processed: {$students->count()}");
    $this->info("Created users: {$created}");
    $this->info("Linked existing users: {$linked}");
    $this->info("Reset passwords: {$reset}");
    $this->info("Skipped: {$skipped}");
    $this->warn("Email conflicts: {$conflicts}");
    $this->info("Credentials CSV: {$filePath}");
})->purpose('Create/link student portal accounts and export temporary passwords');

Artisan::command('school:seed-alpha', function () {
    $school = School::query()
        ->whereRaw('LOWER(name) = ?', ['alpha public school'])
        ->orWhereRaw('LOWER(name) like ?', ['%alpha%public%school%'])
        ->orWhereRaw('LOWER(code) = ?', ['alpha'])
        ->orderBy('id')
        ->first();

    if (! $school) {
        $this->error('Alpha Public School not found.');
        return;
    }

    $schoolId = (int) $school->id;
    $schoolCode = strtoupper((string) ($school->code ?: 'ALPHA'));

    $classDefinitions = [
        ['name' => 'Nursery', 'order' => 1, 'age_min' => 3, 'age_max' => 4],
        ['name' => 'LKG', 'order' => 2, 'age_min' => 4, 'age_max' => 5],
        ['name' => 'UKG', 'order' => 3, 'age_min' => 5, 'age_max' => 6],
        ['name' => 'Grade 1', 'order' => 4, 'age_min' => 6, 'age_max' => 7],
        ['name' => 'Grade 2', 'order' => 5, 'age_min' => 7, 'age_max' => 8],
        ['name' => 'Grade 3', 'order' => 6, 'age_min' => 8, 'age_max' => 9],
        ['name' => 'Grade 4', 'order' => 7, 'age_min' => 9, 'age_max' => 10],
        ['name' => 'Grade 5', 'order' => 8, 'age_min' => 10, 'age_max' => 11],
        ['name' => 'Grade 6', 'order' => 9, 'age_min' => 11, 'age_max' => 12],
        ['name' => 'Grade 7', 'order' => 10, 'age_min' => 12, 'age_max' => 13],
        ['name' => 'Grade 8', 'order' => 11, 'age_min' => 13, 'age_max' => 14],
        ['name' => 'Grade 9', 'order' => 12, 'age_min' => 14, 'age_max' => 15],
        ['name' => 'Grade 10', 'order' => 13, 'age_min' => 15, 'age_max' => 16],
    ];

    $maleFirstNames = ['Aarav', 'Ritik', 'Saugat', 'Pranish', 'Niraj', 'Rohan', 'Siddhant', 'Bibek', 'Pratik', 'Sujal', 'Alok', 'Kiran', 'Roshan', 'Anish', 'Suman', 'Nabin', 'Bishal', 'Samir', 'Rupesh', 'Aayush'];
    $femaleFirstNames = ['Aarohi', 'Asmita', 'Sneha', 'Prakriti', 'Anusha', 'Swastika', 'Ankita', 'Riya', 'Srijana', 'Nisha', 'Aditi', 'Pooja', 'Muna', 'Sarita', 'Roshani', 'Sabina', 'Nirmala', 'Sushmita', 'Samjhana', 'Manisha'];
    $lastNames = ['Sharma', 'Adhikari', 'Khadka', 'Gurung', 'Tamang', 'Rai', 'Bhandari', 'Bhattarai', 'Poudel', 'Karki', 'Thapa', 'Joshi', 'Neupane', 'Shrestha', 'Basnet', 'Ghimire', 'Maharjan', 'Acharya', 'KC', 'Lama'];

    $teacherFirstNames = ['Rabin', 'Anita', 'Sagar', 'Sita', 'Bikash', 'Sunita', 'Dipesh', 'Nabina', 'Yogesh', 'Pramila', 'Arjun', 'Kabita', 'Hemanta', 'Saraswati', 'Madan', 'Rekha', 'Ramesh', 'Anju', 'Binod', 'Mina'];
    $teacherLastNames = ['Joshi', 'Sharma', 'Adhikari', 'Karki', 'Poudel', 'Bhattarai', 'Khadka', 'Bhandari', 'Gurung', 'Rai', 'Thapa', 'Shrestha'];

    DB::transaction(function () use (
        $schoolId,
        $schoolCode,
        $classDefinitions,
        $maleFirstNames,
        $femaleFirstNames,
        $lastNames,
        $teacherFirstNames,
        $teacherLastNames
    ): void {
        $studentIds = Student::query()->where('school_id', $schoolId)->pluck('id');
        $teacherIds = Teacher::query()->where('school_id', $schoolId)->pluck('id');

        if ($studentIds->isNotEmpty()) {
            ParentStudentLink::query()->whereIn('student_id', $studentIds)->delete();
            StudentUserProfile::query()->whereIn('student_id', $studentIds)->delete();
        }

        StudentChangeRequest::query()->where('school_id', $schoolId)->delete();
        AttendanceSession::query()->where('school_id', $schoolId)->delete();
        ClassTeacherAssignment::query()->where('school_id', $schoolId)->delete();
        ClassSectionMapping::query()->where('school_id', $schoolId)->delete();
        Subject::query()->where('school_id', $schoolId)->delete();
        Student::query()->where('school_id', $schoolId)->delete();
        Teacher::query()->where('school_id', $schoolId)->delete();
        SchoolClass::query()->where('school_id', $schoolId)->delete();
        Section::query()->where('school_id', $schoolId)->delete();

        if ($teacherIds->isNotEmpty()) {
            User::query()->where('role', User::ROLE_TEACHER)->whereIn('id', $teacherIds)->delete();
        }
        User::query()->where('role', User::ROLE_STUDENT)->where('school_id', $schoolId)->delete();

        $sections = collect(['A', 'B', 'C'])->mapWithKeys(function (string $name) use ($schoolId) {
            $section = Section::query()->create([
                'school_id' => $schoolId,
                'name' => $name,
            ]);
            return [$name => $section];
        });

        $studentRunning = 1;
        $teacherRunning = 1;

        foreach ($classDefinitions as $classDef) {
            $schoolClass = SchoolClass::query()->create([
                'school_id' => $schoolId,
                'name' => $classDef['name'],
                'display_order' => $classDef['order'],
            ]);

            $sectionNames = ['A', 'B', 'C'];
            shuffle($sectionNames);
            $selectedSectionNames = array_slice($sectionNames, 0, random_int(1, 3));

            foreach ($selectedSectionNames as $sectionName) {
                /** @var Section $section */
                $section = $sections[$sectionName];

                ClassSectionMapping::query()->create([
                    'school_id' => $schoolId,
                    'school_class_id' => $schoolClass->id,
                    'section_id' => $section->id,
                    'is_active' => true,
                ]);

                $teacherName = $teacherFirstNames[array_rand($teacherFirstNames)].' '.$teacherLastNames[array_rand($teacherLastNames)];
                $teacherEmail = Str::slug(str_replace(' ', '.', strtolower($teacherName))).$teacherRunning.'@alpha.school';
                $teacher = Teacher::query()->create([
                    'school_id' => $schoolId,
                    'name' => $teacherName,
                    'email' => $teacherEmail,
                    'phone' => '98'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'has_attendance_access' => true,
                ]);
                $teacherRunning++;

                ClassTeacherAssignment::query()->create([
                    'school_id' => $schoolId,
                    'school_class_id' => $schoolClass->id,
                    'section_id' => $section->id,
                    'teacher_id' => $teacher->id,
                ]);

                $studentCount = random_int(25, 32);
                for ($i = 0; $i < $studentCount; $i++) {
                    $gender = random_int(0, 1) === 0 ? 'Male' : 'Female';
                    $firstName = $gender === 'Male'
                        ? $maleFirstNames[array_rand($maleFirstNames)]
                        : $femaleFirstNames[array_rand($femaleFirstNames)];
                    $lastName = $lastNames[array_rand($lastNames)];
                    $name = "{$firstName} {$lastName}";

                    $studentId = $schoolCode.date('y').str_pad((string) $studentRunning, 4, '0', STR_PAD_LEFT);
                    $email = Str::slug(strtolower($firstName.'.'.$lastName)).$studentRunning.'@alpha.school';
                    $age = random_int((int) $classDef['age_min'], (int) $classDef['age_max']);
                    $birthYear = (int) date('Y') - $age;
                    $dob = sprintf('%04d-%02d-%02d', $birthYear, random_int(1, 12), random_int(1, 28));

                    Student::query()->create([
                        'school_id' => $schoolId,
                        'school_class_id' => $schoolClass->id,
                        'section_id' => $section->id,
                        'student_id' => $studentId,
                        'name' => $name,
                        'email' => $email,
                        'phone' => random_int(0, 100) > 20 ? '98'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT) : null,
                        'date_of_birth' => $dob,
                        'gender' => $gender,
                        'device_identifier' => 'CARD'.$studentRunning,
                    ]);

                    $studentRunning++;
                }
            }
        }
    });

    $classes = SchoolClass::query()->where('school_id', $schoolId)->count();
    $activeMappings = ClassSectionMapping::query()->where('school_id', $schoolId)->where('is_active', true)->count();
    $teachers = Teacher::query()->where('school_id', $schoolId)->count();
    $students = Student::query()->where('school_id', $schoolId)->count();

    $this->info("Alpha school reseeded successfully.");
    $this->line("Classes: {$classes}");
    $this->line("Active class-sections: {$activeMappings}");
    $this->line("Teachers (class teachers): {$teachers}");
    $this->line("Students: {$students}");
})->purpose('Reset Alpha school and seed realistic classes, sections, class teachers, and students');

Artisan::command('school:seed-alpha-attendance', function () {
    $school = School::query()
        ->whereRaw('LOWER(name) = ?', ['alpha public school'])
        ->orWhereRaw('LOWER(code) = ?', ['alpha'])
        ->orderBy('id')
        ->first();

    if (! $school) {
        $this->error('Alpha Public School not found.');
        return;
    }

    $schoolId = (int) $school->id;
    $startDate = now()->startOfYear()->toDateString();
    $endDate = now()->toDateString();

    $subject = Subject::query()->firstOrCreate(
        [
            'school_id' => $schoolId,
            'name' => 'Daily Attendance',
        ],
        [
            'code' => 'ATTN',
        ]
    );

    DB::transaction(function () use ($schoolId, $startDate, $endDate, $subject): void {
        $sessionIds = AttendanceSession::query()
            ->where('school_id', $schoolId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->pluck('id');

        if ($sessionIds->isNotEmpty()) {
            AttendanceRecord::query()->whereIn('attendance_session_id', $sessionIds)->delete();
        }
        AttendanceSession::query()
            ->where('school_id', $schoolId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->delete();

        $assignments = ClassTeacherAssignment::query()
            ->where('school_id', $schoolId)
            ->with('teacher')
            ->get();

        $dates = [];
        $cursor = \Illuminate\Support\Carbon::parse($startDate);
        $last = \Illuminate\Support\Carbon::parse($endDate);
        while ($cursor->lte($last)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        foreach ($assignments as $assignment) {
            $students = Student::query()
                ->where('school_id', $schoolId)
                ->where('school_class_id', $assignment->school_class_id)
                ->where('section_id', $assignment->section_id)
                ->get();

            if ($students->isEmpty()) {
                continue;
            }

            foreach ($dates as $date) {
                $session = AttendanceSession::query()->create([
                    'school_id' => $schoolId,
                    'school_class_id' => $assignment->school_class_id,
                    'section_id' => $assignment->section_id,
                    'subject_id' => $subject->id,
                    'period_no' => 1,
                    'attendance_date' => $date,
                    'marked_by' => $assignment->teacher?->user_id,
                ]);

                foreach ($students as $student) {
                    $roll = random_int(1, 100);
                    $status = match (true) {
                        $roll <= 78 => AttendanceRecord::STATUS_PRESENT,
                        $roll <= 88 => AttendanceRecord::STATUS_ABSENT,
                        $roll <= 94 => AttendanceRecord::STATUS_LATE,
                        $roll <= 97 => AttendanceRecord::STATUS_HALF_DAY,
                        default => AttendanceRecord::STATUS_LEAVE,
                    };

                    $leaveType = null;
                    if ($status === AttendanceRecord::STATUS_LEAVE) {
                        $leaveType = random_int(0, 1) === 0
                            ? AttendanceRecord::LEAVE_TYPE_MEDICAL
                            : AttendanceRecord::LEAVE_TYPE_APPROVED;
                    }

                    AttendanceRecord::query()->create([
                        'attendance_session_id' => $session->id,
                        'student_id' => $student->id,
                        'status' => $status,
                        'leave_type' => $leaveType,
                        'remark' => null,
                    ]);
                }
            }
        }
    });

    $sessionCount = AttendanceSession::query()
        ->where('school_id', $schoolId)
        ->whereBetween('attendance_date', [$startDate, $endDate])
        ->count();
    $recordCount = AttendanceRecord::query()
        ->whereHas('attendanceSession', function ($query) use ($schoolId, $startDate, $endDate): void {
            $query->where('school_id', $schoolId)
                ->whereBetween('attendance_date', [$startDate, $endDate]);
        })
        ->count();

    $this->info("Alpha attendance seeded from {$startDate} to {$endDate}.");
    $this->line("Sessions: {$sessionCount}");
    $this->line("Records: {$recordCount}");
})->purpose('Seed Alpha school random attendance from Jan 1 to today');
