<?php

namespace App\Services\Admin;

use App\Models\ClassTeacherAssignment;
use App\Models\AdminAttendanceNotification;
use App\Models\ClassSectionMapping;
use App\Models\SchoolClass;
use App\Models\SchoolCalendarEvent;
use App\Models\SchoolDevice;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentChangeRequest;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Subscription\PlanFeatureService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminManagementService
{
    public function __construct(private readonly PlanFeatureService $planFeatureService)
    {
    }

    public function dashboardData(User $user): array
    {
        $schoolId = (int) $user->school_id;
        $canSeeAdminNotifications = $this->planFeatureService->isEnabledForSchoolId(
            $schoolId,
            PlanFeatureService::FEATURE_ADMIN_ATTENDANCE_NOTIFICATIONS
        );
        $canImportStudents = $this->planFeatureService->isEnabledForSchoolId(
            $schoolId,
            PlanFeatureService::FEATURE_STUDENT_IMPORT
        );
        $canUseDeviceAttendance = $this->planFeatureService->isEnabledForSchoolId(
            $schoolId,
            PlanFeatureService::FEATURE_DEVICE_ATTENDANCE
        );

        $classes = SchoolClass::query()->where('school_id', $schoolId)->orderBy('display_order')->orderBy('name')->get();
        $sections = Section::query()->where('school_id', $schoolId)->orderBy('name')->get();
        $this->ensureDefaultClassSectionMappings($schoolId, $classes, $sections);
        $teachers = Teacher::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();
        $students = Student::query()
            ->where('school_id', $schoolId)
            ->with(['schoolClass', 'section'])
            ->orderByDesc('id')
            ->get();
        $classSectionMappings = ClassSectionMapping::query()
            ->where('school_id', $schoolId)
            ->with(['schoolClass', 'section'])
            ->orderBy('school_class_id')
            ->orderBy('section_id')
            ->get();
        $activeSectionsByClass = $classSectionMappings
            ->where('is_active', true)
            ->groupBy('school_class_id')
            ->map(fn ($items) => $items->pluck('section_id')->values()->all())
            ->toArray();
        $attendanceNotifications = $canSeeAdminNotifications && Schema::hasTable('admin_attendance_notifications')
            ? AdminAttendanceNotification::query()
                ->where('school_id', $schoolId)
                ->where('updated_at', '>=', now()->subDay())
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get()
            : collect();

        return [
            'school' => $user->school,
            'classes' => $classes,
            'sections' => $sections,
            'teachers' => $teachers,
            'students' => $students,
            'classTeacherAssignments' => ClassTeacherAssignment::query()
                ->where('school_id', $schoolId)
                ->with(['teacher', 'schoolClass', 'section'])
                ->orderByDesc('id')
                ->get(),
            'classSectionMappings' => $classSectionMappings,
            'activeSectionsByClass' => $activeSectionsByClass,
            'attendanceNotifications' => $attendanceNotifications,
            'pendingStudentChangeRequests' => StudentChangeRequest::query()
                ->where('school_id', $schoolId)
                ->where('status', StudentChangeRequest::STATUS_PENDING)
                ->with(['teacher', 'schoolClass', 'section', 'student'])
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
            'devices' => SchoolDevice::query()
                ->where('school_id', $schoolId)
                ->orderByDesc('id')
                ->get(),
            'featureFlags' => [
                'student_import' => $canImportStudents,
                'teacher_import' => $canImportStudents,
                'admin_attendance_notifications' => $canSeeAdminNotifications,
                'device_attendance' => $canUseDeviceAttendance,
            ],
            'totalTeachers' => $teachers->count(),
            'totalStudents' => $students->count(),
        ];
    }

    public function calendarData(User $user): array
    {
        $data = $this->dashboardData($user);
        $schoolId = (int) $user->school_id;

        $data['calendarEvents'] = Schema::hasTable('school_calendar_events')
            ? SchoolCalendarEvent::query()
                ->where('school_id', $schoolId)
                ->with(['schoolClass', 'section'])
                ->orderByDesc('event_date')
                ->orderByDesc('id')
                ->get()
            : collect();
        $data['calendarEventTypes'] = SchoolCalendarEvent::TYPES;

        return $data;
    }

    public function createClass(User $user, array $data): void
    {
        SchoolClass::query()->firstOrCreate(
            ['school_id' => $this->schoolId($user), 'name' => $data['name']],
            ['display_order' => $data['display_order'] ?? null]
        );
    }

    public function updateClass(User $user, SchoolClass $schoolClass, array $data): void
    {
        $this->ensureOwnership($user, $schoolClass->school_id);
        $schoolClass->update($data);
    }

    public function deleteClass(User $user, SchoolClass $schoolClass): void
    {
        $this->ensureOwnership($user, $schoolClass->school_id);
        $schoolClass->delete();
    }

    public function createSection(User $user, array $data): void
    {
        Section::query()->firstOrCreate([
            'school_id' => $this->schoolId($user),
            'name' => $data['name'],
        ]);
    }

    public function updateSection(User $user, Section $section, array $data): void
    {
        $this->ensureOwnership($user, $section->school_id);
        $section->update($data);
    }

    public function deleteSection(User $user, Section $section): void
    {
        $this->ensureOwnership($user, $section->school_id);
        $section->delete();
    }

    public function createTeacher(User $user, array $data): void
    {
        Teacher::query()->create([
            ...$data,
            'school_id' => $this->schoolId($user),
            'has_attendance_access' => (bool) ($data['has_attendance_access'] ?? false),
        ]);
    }

    public function updateTeacher(User $user, Teacher $teacher, array $data): void
    {
        $this->ensureOwnership($user, $teacher->school_id);
        $teacher->update($data);
    }

    public function deleteTeacher(User $user, Teacher $teacher): void
    {
        $this->ensureOwnership($user, $teacher->school_id);
        $teacher->delete();
    }

    public function assignClassTeacher(User $user, array $data): void
    {
        $schoolId = $this->schoolId($user);
        $teacher = Teacher::query()->findOrFail($data['teacher_id']);
        $class = SchoolClass::query()->findOrFail($data['school_class_id']);
        $section = Section::query()->findOrFail($data['section_id']);

        $this->ensureOwnership($user, $teacher->school_id);
        if ((int) $class->school_id !== $schoolId || (int) $section->school_id !== $schoolId) {
            throw new AuthorizationException('Unauthorized class teacher assignment.');
        }
        $this->ensureClassSectionPairIsActive($schoolId, (int) $class->id, (int) $section->id);

        ClassTeacherAssignment::query()->updateOrCreate(
            [
                'school_id' => $schoolId,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
            ],
            [
                'teacher_id' => $teacher->id,
            ]
        );
    }

    public function deleteClassTeacherAssignment(User $user, ClassTeacherAssignment $assignment): void
    {
        $this->ensureOwnership($user, $assignment->school_id);
        $assignment->delete();
    }

    public function generateClassTeacherOneTimePassword(User $user, ClassTeacherAssignment $assignment): array
    {
        $this->ensureOwnership($user, $assignment->school_id);
        $assignment->loadMissing('teacher');
        $teacher = $assignment->teacher;

        if (! $teacher) {
            throw new AuthorizationException('Class teacher is not available for this assignment.');
        }

        $schoolId = $this->schoolId($user);
        $existingUser = User::query()->where('email', $teacher->email)->first();
        if ($existingUser && ((int) $existingUser->school_id !== $schoolId || $existingUser->role !== User::ROLE_TEACHER)) {
            throw new AuthorizationException('This email is already used by another role or school user.');
        }

        $plainPassword = Str::password(12);
        $userData = [
            'name' => $teacher->name,
            'role' => User::ROLE_TEACHER,
            'school_id' => $schoolId,
            'password' => $plainPassword,
        ];
        if (Schema::hasColumn('users', 'must_change_password')) {
            $userData['must_change_password'] = true;
        }

        $teacherUser = User::query()->updateOrCreate(
            ['email' => $teacher->email],
            $userData
        );

        if ((int) $teacher->user_id !== (int) $teacherUser->id) {
            $teacher->update(['user_id' => $teacherUser->id]);
        }

        return [
            'teacher_name' => $teacher->name,
            'teacher_email' => $teacherUser->email,
            'one_time_password' => $plainPassword,
        ];
    }

    public function updateTeacherAttendanceAccess(User $user, Teacher $teacher, bool $hasAccess): void
    {
        $this->ensureOwnership($user, $teacher->school_id);
        $teacher->update(['has_attendance_access' => $hasAccess]);
    }

    public function createStudent(User $user, array $data, ?UploadedFile $photo): void
    {
        $schoolId = $this->schoolId($user);
        $this->ensureStudentLimitNotExceeded($schoolId, 1);
        $this->ensureClassSectionOwnership($schoolId, $data['school_class_id'] ?? null, $data['section_id'] ?? null);

        $photoPath = $photo ? $photo->store('students', 'public') : null;

        Student::query()->create([
            ...$data,
            'school_id' => $schoolId,
            'student_id' => $this->generateStudentId($user),
            'photo_path' => $photoPath,
        ]);
    }

    public function updateStudent(User $user, Student $student, array $data, ?UploadedFile $photo): void
    {
        $schoolId = $this->schoolId($user);
        $this->ensureOwnership($user, $student->school_id);
        $this->ensureClassSectionOwnership($schoolId, $data['school_class_id'] ?? null, $data['section_id'] ?? null);

        if ($photo) {
            if (! empty($student->photo_path)) {
                Storage::disk('public')->delete($student->photo_path);
            }
            $data['photo_path'] = $photo->store('students', 'public');
        }

        $student->update($data);
    }

    public function deleteStudent(User $user, Student $student): void
    {
        $this->ensureOwnership($user, $student->school_id);
        if (! empty($student->photo_path)) {
            Storage::disk('public')->delete($student->photo_path);
        }
        $student->delete();
    }

    public function importStudents(User $user, UploadedFile $file): array
    {
        $schoolId = $this->schoolId($user);
        if (! $this->planFeatureService->isEnabledForSchoolId($schoolId, PlanFeatureService::FEATURE_STUDENT_IMPORT)) {
            throw ValidationException::withMessages([
                'file' => 'Student CSV import is not available in BASIC plan.',
            ]);
        }

        $imported = 0;
        $skipped = 0;
        $existingStudents = Student::query()->where('school_id', $schoolId)->count();
        $school = $user->school;
        $maxStudents = $school?->max_students;

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['imported' => 0, 'skipped' => 0];
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            return ['imported' => 0, 'skipped' => 0];
        }

        $headerMap = [];
        foreach ($header as $index => $column) {
            $headerMap[strtolower(trim((string) $column))] = $index;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if ($maxStudents !== null && ($existingStudents + $imported) >= $maxStudents) {
                $skipped++;
                continue;
            }

            $name = trim((string) ($row[$headerMap['name'] ?? -1] ?? ''));
            if ($name === '') {
                $skipped++;
                continue;
            }

            $email = trim((string) ($row[$headerMap['email'] ?? -1] ?? ''));
            if ($email !== '' && Student::query()->where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            $className = trim((string) ($row[$headerMap['class'] ?? -1] ?? ''));
            $sectionName = trim((string) ($row[$headerMap['section'] ?? -1] ?? ''));

            $class = $className !== '' ? SchoolClass::query()->firstOrCreate(['school_id' => $schoolId, 'name' => $className]) : null;
            $section = $sectionName !== '' ? Section::query()->firstOrCreate(['school_id' => $schoolId, 'name' => $sectionName]) : null;
            if ($class && $section) {
                ClassSectionMapping::query()->updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'school_class_id' => $class->id,
                        'section_id' => $section->id,
                    ],
                    [
                        'is_active' => true,
                    ]
                );
            }

            Student::query()->create([
                'school_id' => $schoolId,
                'school_class_id' => $class?->id,
                'section_id' => $section?->id,
                'student_id' => $this->generateStudentId($user),
                'device_identifier' => trim((string) ($row[$headerMap['device_identifier'] ?? -1] ?? '')) ?: null,
                'name' => $name,
                'email' => $email !== '' ? $email : null,
                'phone' => trim((string) ($row[$headerMap['phone'] ?? -1] ?? '')) ?: null,
                'date_of_birth' => trim((string) ($row[$headerMap['date_of_birth'] ?? -1] ?? '')) ?: null,
                'gender' => trim((string) ($row[$headerMap['gender'] ?? -1] ?? '')) ?: null,
            ]);

            $imported++;
        }

        fclose($handle);

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    public function approveStudentChangeRequest(User $user, StudentChangeRequest $changeRequest): void
    {
        $this->ensureOwnership($user, $changeRequest->school_id);
        if ($changeRequest->status !== StudentChangeRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'request' => 'Only pending requests can be approved.',
            ]);
        }

        DB::transaction(function () use ($user, $changeRequest): void {
            $payload = $changeRequest->payload ?? [];
            $schoolId = (int) $changeRequest->school_id;

            if ($changeRequest->action === StudentChangeRequest::ACTION_CREATE) {
                $this->ensureStudentLimitNotExceeded($schoolId, 1);

                $email = strtolower(trim((string) ($payload['email'] ?? '')));
                $deviceIdentifier = trim((string) ($payload['device_identifier'] ?? ''));
                if ($email !== '' && Student::query()->where('email', $email)->exists()) {
                    throw ValidationException::withMessages(['request' => 'Student email already exists.']);
                }
                if ($deviceIdentifier !== '' && Student::query()->where('school_id', $schoolId)->where('device_identifier', $deviceIdentifier)->exists()) {
                    throw ValidationException::withMessages(['request' => 'Device identifier already exists.']);
                }

                Student::query()->create([
                    'school_id' => $schoolId,
                    'school_class_id' => $changeRequest->school_class_id,
                    'section_id' => $changeRequest->section_id,
                    'student_id' => $this->generateStudentId($user),
                    'name' => trim((string) ($payload['name'] ?? '')),
                    'email' => $email !== '' ? $email : null,
                    'phone' => trim((string) ($payload['phone'] ?? '')) ?: null,
                    'date_of_birth' => trim((string) ($payload['date_of_birth'] ?? '')) ?: null,
                    'gender' => trim((string) ($payload['gender'] ?? '')) ?: null,
                    'device_identifier' => $deviceIdentifier !== '' ? $deviceIdentifier : null,
                ]);
            } elseif ($changeRequest->action === StudentChangeRequest::ACTION_UPDATE) {
                $student = $changeRequest->student;
                if (! $student || (int) $student->school_id !== $schoolId) {
                    throw ValidationException::withMessages(['request' => 'Student not found for update.']);
                }

                $email = strtolower(trim((string) ($payload['email'] ?? '')));
                $deviceIdentifier = trim((string) ($payload['device_identifier'] ?? ''));
                if ($email !== '' && Student::query()->where('email', $email)->where('id', '!=', $student->id)->exists()) {
                    throw ValidationException::withMessages(['request' => 'Student email already exists.']);
                }
                if ($deviceIdentifier !== '' && Student::query()->where('school_id', $schoolId)->where('device_identifier', $deviceIdentifier)->where('id', '!=', $student->id)->exists()) {
                    throw ValidationException::withMessages(['request' => 'Device identifier already exists.']);
                }

                $student->update([
                    'name' => trim((string) ($payload['name'] ?? $student->name)),
                    'email' => $email !== '' ? $email : null,
                    'phone' => trim((string) ($payload['phone'] ?? '')) ?: null,
                    'date_of_birth' => trim((string) ($payload['date_of_birth'] ?? '')) ?: null,
                    'gender' => trim((string) ($payload['gender'] ?? '')) ?: null,
                    'device_identifier' => $deviceIdentifier !== '' ? $deviceIdentifier : null,
                ]);
            } elseif ($changeRequest->action === StudentChangeRequest::ACTION_DELETE) {
                $student = $changeRequest->student;
                if (! $student || (int) $student->school_id !== $schoolId) {
                    throw ValidationException::withMessages(['request' => 'Student not found for delete.']);
                }
                if (! empty($student->photo_path)) {
                    Storage::disk('public')->delete($student->photo_path);
                }
                $student->delete();
            }

            $changeRequest->update([
                'status' => StudentChangeRequest::STATUS_APPROVED,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'review_note' => 'Approved by admin',
            ]);
        });
    }

    public function rejectStudentChangeRequest(User $user, StudentChangeRequest $changeRequest, ?string $note = null): void
    {
        $this->ensureOwnership($user, $changeRequest->school_id);
        if ($changeRequest->status !== StudentChangeRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'request' => 'Only pending requests can be rejected.',
            ]);
        }

        $changeRequest->update([
            'status' => StudentChangeRequest::STATUS_REJECTED,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_note' => $note ?: 'Rejected by admin',
        ]);
    }

    public function importTeachers(User $user, UploadedFile $file): array
    {
        $schoolId = $this->schoolId($user);
        if (! $this->planFeatureService->isEnabledForSchoolId($schoolId, PlanFeatureService::FEATURE_STUDENT_IMPORT)) {
            throw ValidationException::withMessages([
                'file' => 'Teacher CSV import is not available in BASIC plan.',
            ]);
        }

        $imported = 0;
        $skipped = 0;

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['imported' => 0, 'skipped' => 0];
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            return ['imported' => 0, 'skipped' => 0];
        }

        $headerMap = [];
        foreach ($header as $index => $column) {
            $headerMap[strtolower(trim((string) $column))] = $index;
        }

        while (($row = fgetcsv($handle)) !== false) {
            $name = trim((string) ($row[$headerMap['name'] ?? -1] ?? ''));
            $email = strtolower(trim((string) ($row[$headerMap['email'] ?? -1] ?? '')));
            $phone = trim((string) ($row[$headerMap['phone'] ?? -1] ?? ''));
            $attendanceAccessRaw = strtolower(trim((string) ($row[$headerMap['has_attendance_access'] ?? -1] ?? '')));

            if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            if (Teacher::query()->where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            $hasAttendanceAccess = in_array($attendanceAccessRaw, ['1', 'true', 'yes', 'y'], true);

            Teacher::query()->create([
                'school_id' => $schoolId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'has_attendance_access' => $hasAttendanceAccess,
            ]);

            $imported++;
        }

        fclose($handle);

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    public function createSchoolDevice(User $user, array $data): array
    {
        $schoolId = $this->schoolId($user);
        if (! $this->planFeatureService->isEnabledForSchoolId($schoolId, PlanFeatureService::FEATURE_DEVICE_ATTENDANCE)) {
            throw ValidationException::withMessages([
                'name' => 'Device attendance is available only in ENTERPRISE plan.',
            ]);
        }

        $token = 'dev_'.bin2hex(random_bytes(20));
        $device = SchoolDevice::query()->create([
            'school_id' => $schoolId,
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

    public function toggleSchoolDeviceStatus(User $user, SchoolDevice $device): bool
    {
        $this->ensureOwnership($user, $device->school_id);
        $device->update(['is_active' => ! $device->is_active]);

        return (bool) $device->is_active;
    }

    public function createCalendarEvent(User $user, array $data): void
    {
        if (! Schema::hasTable('school_calendar_events')) {
            throw ValidationException::withMessages([
                'title' => 'Holiday calendar table is missing. Please run: php artisan migrate',
            ]);
        }

        $schoolId = $this->schoolId($user);
        $this->ensureClassSectionOwnership($schoolId, $data['school_class_id'] ?? null, $data['section_id'] ?? null);

        SchoolCalendarEvent::query()->create([
            ...$data,
            'school_id' => $schoolId,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    public function updateCalendarEvent(User $user, SchoolCalendarEvent $event, array $data): void
    {
        if (! Schema::hasTable('school_calendar_events')) {
            throw ValidationException::withMessages([
                'title' => 'Holiday calendar table is missing. Please run: php artisan migrate',
            ]);
        }

        $schoolId = $this->schoolId($user);
        $this->ensureOwnership($user, $event->school_id);
        $this->ensureClassSectionOwnership($schoolId, $data['school_class_id'] ?? null, $data['section_id'] ?? null);

        $event->update($data);
    }

    public function deleteCalendarEvent(User $user, SchoolCalendarEvent $event): void
    {
        if (! Schema::hasTable('school_calendar_events')) {
            throw ValidationException::withMessages([
                'title' => 'Holiday calendar table is missing. Please run: php artisan migrate',
            ]);
        }

        $this->ensureOwnership($user, $event->school_id);
        $event->delete();
    }

    private function schoolId(User $user): int
    {
        return (int) $user->school_id;
    }

    private function ensureOwnership(User $user, ?int $entitySchoolId): void
    {
        if ((int) $entitySchoolId !== $this->schoolId($user)) {
            throw new AuthorizationException('Unauthorized school access.');
        }
    }

    private function ensureClassSectionOwnership(int $schoolId, ?int $classId, ?int $sectionId): void
    {
        if ($classId !== null) {
            $class = SchoolClass::query()->findOrFail($classId);
            if ((int) $class->school_id !== $schoolId) {
                throw new AuthorizationException('Unauthorized class access.');
            }
        }

        if ($sectionId !== null) {
            $section = Section::query()->findOrFail($sectionId);
            if ((int) $section->school_id !== $schoolId) {
                throw new AuthorizationException('Unauthorized section access.');
            }
        }

        if ($classId !== null && $sectionId !== null) {
            $this->ensureClassSectionPairIsActive($schoolId, $classId, $sectionId);
        }
    }

    private function generateStudentId(User $user): string
    {
        $schoolId = $this->schoolId($user);
        $schoolPrefix = $user->school?->code
            ? strtoupper(Str::of($user->school->code)->replaceMatches('/[^A-Za-z0-9]/', '')->substr(0, 4)->value())
            : 'SCHL';

        $year = now()->format('y');
        $running = Student::query()->where('school_id', $schoolId)->count() + 1;

        do {
            $studentId = $schoolPrefix.$year.str_pad((string) $running, 4, '0', STR_PAD_LEFT);
            $running++;
        } while (Student::query()->where('student_id', $studentId)->exists());

        return $studentId;
    }

    private function ensureStudentLimitNotExceeded(int $schoolId, int $incoming): void
    {
        $school = \App\Models\School::query()->find($schoolId);
        if (! $school || $school->max_students === null) {
            return;
        }

        $current = Student::query()->where('school_id', $schoolId)->count();
        if (($current + $incoming) > $school->max_students) {
            throw ValidationException::withMessages([
                'name' => "Student limit reached for this school plan (max: {$school->max_students}).",
            ]);
        }
    }

    public function upsertClassSectionMapping(User $user, array $data): void
    {
        $schoolId = $this->schoolId($user);
        $class = SchoolClass::query()->findOrFail($data['school_class_id']);
        $section = Section::query()->findOrFail($data['section_id']);
        if ((int) $class->school_id !== $schoolId || (int) $section->school_id !== $schoolId) {
            throw new AuthorizationException('Unauthorized class/section mapping.');
        }

        ClassSectionMapping::query()->updateOrCreate(
            [
                'school_id' => $schoolId,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
            ],
            [
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]
        );
    }

    public function toggleClassSectionMappingStatus(User $user, ClassSectionMapping $mapping): void
    {
        $this->ensureOwnership($user, $mapping->school_id);
        $mapping->update([
            'is_active' => ! $mapping->is_active,
        ]);
    }

    private function ensureClassSectionPairIsActive(int $schoolId, int $classId, int $sectionId): void
    {
        $mapping = ClassSectionMapping::query()
            ->where('school_id', $schoolId)
            ->where('school_class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('is_active', true)
            ->first();

        if (! $mapping) {
            throw ValidationException::withMessages([
                'section_id' => 'Selected section is not active for the selected class.',
            ]);
        }
    }

    private function ensureDefaultClassSectionMappings(int $schoolId, $classes, $sections): void
    {
        if ($classes->isEmpty() || $sections->isEmpty()) {
            return;
        }

        $hasAnyMapping = ClassSectionMapping::query()
            ->where('school_id', $schoolId)
            ->exists();

        if ($hasAnyMapping) {
            return;
        }

        foreach ($classes as $class) {
            foreach ($sections as $section) {
                ClassSectionMapping::query()->updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'school_class_id' => $class->id,
                        'section_id' => $section->id,
                    ],
                    [
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
