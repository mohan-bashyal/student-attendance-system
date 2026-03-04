<?php

namespace App\Services\Teacher;

use App\Models\ClassTeacherAssignment;
use App\Models\Student;
use App\Models\StudentChangeRequest;
use App\Models\StudentUserProfile;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherStudentRequestService
{
    public function pageData(User $user, array $filters): array
    {
        $teacher = $this->resolveTeacher($user);
        $assignments = collect();
        $selectedAssignment = null;
        $students = collect();
        $requests = collect();

        if ($teacher) {
            $assignments = ClassTeacherAssignment::query()
                ->where('school_id', $user->school_id)
                ->where('teacher_id', $teacher->id)
                ->with(['schoolClass', 'section'])
                ->orderBy('school_class_id')
                ->orderBy('section_id')
                ->get();

            $assignmentId = isset($filters['assignment_id']) ? (int) $filters['assignment_id'] : (int) ($assignments->first()?->id ?? 0);
            $selectedAssignment = $assignmentId > 0 ? $assignments->firstWhere('id', $assignmentId) : null;

            if ($selectedAssignment) {
                $students = Student::query()
                    ->where('school_id', $selectedAssignment->school_id)
                    ->where('school_class_id', $selectedAssignment->school_class_id)
                    ->where('section_id', $selectedAssignment->section_id)
                    ->orderBy('name')
                    ->get();
            }

            $requests = StudentChangeRequest::query()
                ->where('school_id', $user->school_id)
                ->where('teacher_id', $teacher->id)
                ->with(['schoolClass', 'section', 'student', 'reviewedBy'])
                ->orderByDesc('id')
                ->limit(30)
                ->get();
        }

        return [
            'school' => $user->school,
            'teacher' => $teacher,
            'assignments' => $assignments,
            'selectedAssignment' => $selectedAssignment,
            'students' => $students,
            'requests' => $requests,
        ];
    }

    public function requestCreate(User $user, array $payload): void
    {
        $teacher = $this->resolveTeacherWithAccess($user);
        $assignment = $this->teacherAssignment($user, $teacher, (int) $payload['assignment_id']);

        StudentChangeRequest::query()->create([
            'school_id' => $assignment->school_id,
            'school_class_id' => $assignment->school_class_id,
            'section_id' => $assignment->section_id,
            'teacher_id' => $teacher->id,
            'student_id' => null,
            'requested_by' => $user->id,
            'action' => StudentChangeRequest::ACTION_CREATE,
            'payload' => $this->normalizedPayload($payload),
            'status' => StudentChangeRequest::STATUS_PENDING,
        ]);
    }

    public function requestUpdate(User $user, Student $student, array $payload): void
    {
        $teacher = $this->resolveTeacherWithAccess($user);
        $assignment = $this->teacherAssignment($user, $teacher, (int) $payload['assignment_id']);
        $this->ensureStudentInAssignment($student, $assignment);

        StudentChangeRequest::query()->create([
            'school_id' => $assignment->school_id,
            'school_class_id' => $assignment->school_class_id,
            'section_id' => $assignment->section_id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'requested_by' => $user->id,
            'action' => StudentChangeRequest::ACTION_UPDATE,
            'payload' => $this->normalizedPayload($payload),
            'status' => StudentChangeRequest::STATUS_PENDING,
        ]);
    }

    public function requestDelete(User $user, Student $student, array $payload): void
    {
        $teacher = $this->resolveTeacherWithAccess($user);
        $assignment = $this->teacherAssignment($user, $teacher, (int) $payload['assignment_id']);
        $this->ensureStudentInAssignment($student, $assignment);

        StudentChangeRequest::query()->create([
            'school_id' => $assignment->school_id,
            'school_class_id' => $assignment->school_class_id,
            'section_id' => $assignment->section_id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'requested_by' => $user->id,
            'action' => StudentChangeRequest::ACTION_DELETE,
            'payload' => [
                'reason' => trim((string) ($payload['reason'] ?? '')),
                'snapshot' => $student->only(['name', 'email', 'phone', 'date_of_birth', 'gender', 'device_identifier']),
            ],
            'status' => StudentChangeRequest::STATUS_PENDING,
        ]);
    }

    public function generateStudentOneTimePassword(User $user, Student $student, int $assignmentId): array
    {
        $teacher = $this->resolveTeacherWithAccess($user);
        $assignment = $this->teacherAssignment($user, $teacher, $assignmentId);
        $this->ensureStudentInAssignment($student, $assignment);

        $studentEmail = strtolower(trim((string) ($student->email ?? '')));
        if ($studentEmail === '') {
            throw ValidationException::withMessages([
                'email' => 'Student email is required to generate portal credentials.',
            ]);
        }

        $schoolId = (int) $user->school_id;
        $existingUser = User::query()->where('email', $studentEmail)->first();
        if ($existingUser && ($existingUser->role !== User::ROLE_STUDENT || (int) $existingUser->school_id !== $schoolId)) {
            throw new AuthorizationException('This email is already used by another role or school user.');
        }

        $plainPassword = Str::password(12);
        $userData = [
            'name' => $student->name,
            'role' => User::ROLE_STUDENT,
            'school_id' => $schoolId,
            'password' => $plainPassword,
        ];
        if (Schema::hasColumn('users', 'must_change_password')) {
            $userData['must_change_password'] = true;
        }

        $studentUser = User::query()->updateOrCreate(
            ['email' => $studentEmail],
            $userData
        );

        StudentUserProfile::query()->updateOrCreate(
            ['student_id' => $student->id],
            ['user_id' => $studentUser->id]
        );

        return [
            'student_name' => $student->name,
            'student_id' => $student->student_id,
            'student_email' => $studentUser->email,
            'one_time_password' => $plainPassword,
        ];
    }

    private function resolveTeacher(User $user): ?Teacher
    {
        return Teacher::query()
            ->where('school_id', $user->school_id)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();
    }

    private function resolveTeacherWithAccess(User $user): Teacher
    {
        $teacher = $this->resolveTeacher($user);
        if (! $teacher || ! $teacher->has_attendance_access) {
            throw new AuthorizationException('Teacher attendance access is disabled.');
        }

        return $teacher;
    }

    private function teacherAssignment(User $user, Teacher $teacher, int $assignmentId): ClassTeacherAssignment
    {
        return ClassTeacherAssignment::query()
            ->where('school_id', $user->school_id)
            ->where('teacher_id', $teacher->id)
            ->findOrFail($assignmentId);
    }

    private function ensureStudentInAssignment(Student $student, ClassTeacherAssignment $assignment): void
    {
        if (
            (int) $student->school_id !== (int) $assignment->school_id ||
            (int) $student->school_class_id !== (int) $assignment->school_class_id ||
            (int) $student->section_id !== (int) $assignment->section_id
        ) {
            throw ValidationException::withMessages([
                'student_id' => 'Student is not in your assigned class/section.',
            ]);
        }
    }

    private function normalizedPayload(array $payload): array
    {
        return [
            'name' => trim((string) $payload['name']),
            'email' => trim((string) ($payload['email'] ?? '')) ?: null,
            'phone' => trim((string) ($payload['phone'] ?? '')) ?: null,
            'date_of_birth' => trim((string) ($payload['date_of_birth'] ?? '')) ?: null,
            'gender' => trim((string) ($payload['gender'] ?? '')) ?: null,
            'device_identifier' => trim((string) ($payload['device_identifier'] ?? '')) ?: null,
        ];
    }
}
