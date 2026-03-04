<?php

namespace Tests\Feature;

use App\Models\AdminAttendanceNotification;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassTeacherAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_save_today_attendance(): void
    {
        [$school, $teacherUser, $assignment, $students] = $this->seedTeacherAttendanceContext();

        $response = $this->actingAs($teacherUser)->post(route('dashboard.teacher.store'), [
            'assignment_id' => $assignment->id,
            'records' => [
                $students[0]->id => ['status' => AttendanceRecord::STATUS_PRESENT, 'leave_type' => null, 'remark' => null],
                $students[1]->id => ['status' => AttendanceRecord::STATUS_ABSENT, 'leave_type' => AttendanceRecord::LEAVE_TYPE_MEDICAL, 'remark' => 'Sick'],
            ],
        ]);

        $response
            ->assertRedirect(route('dashboard.teacher', ['assignment_id' => $assignment->id]))
            ->assertSessionHas('status', 'Today attendance saved successfully.');

        $session = AttendanceSession::query()
            ->where('school_id', $school->id)
            ->where('school_class_id', $assignment->school_class_id)
            ->where('section_id', $assignment->section_id)
            ->where('period_no', 1)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        $this->assertNotNull($session);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $students[0]->id,
            'status' => AttendanceRecord::STATUS_PRESENT,
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $students[1]->id,
            'status' => AttendanceRecord::STATUS_ABSENT,
            'leave_type' => null,
            'remark' => 'Sick',
        ]);
    }

    public function test_admin_gets_notification_after_teacher_marks_attendance(): void
    {
        [$school, $teacherUser, $assignment, $students] = $this->seedTeacherAttendanceContext();
        $school->update(['subscription_plan' => 'pro']);
        $adminUser = User::factory()->create([
            'name' => 'School Admin',
            'email' => 'admin@alpha.test',
            'role' => User::ROLE_ADMIN,
            'school_id' => $school->id,
            'must_change_password' => false,
        ]);

        $this->actingAs($teacherUser)->post(route('dashboard.teacher.store'), [
            'assignment_id' => $assignment->id,
            'records' => [
                $students[0]->id => ['status' => AttendanceRecord::STATUS_PRESENT, 'leave_type' => null, 'remark' => null],
                $students[1]->id => ['status' => AttendanceRecord::STATUS_PRESENT, 'leave_type' => null, 'remark' => null],
            ],
        ])->assertRedirect();

        $notification = AdminAttendanceNotification::query()
            ->where('school_id', $school->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('Anita Sharma', $notification->teacher_name);
        $this->assertSame('Grade 1', $notification->class_name);
        $this->assertSame('A', $notification->section_name);

        $dashboardResponse = $this->actingAs($adminUser)->get(route('admin.index'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Attendance Notifications');
        $dashboardResponse->assertSee('Anita Sharma');
    }

    public function test_role_protection_for_teacher_and_admin_pages(): void
    {
        $school = School::query()->create([
            'name' => 'Alpha Public School',
            'code' => 'ALPHA',
            'domain' => 'alpha.test',
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin2@alpha.test',
            'role' => User::ROLE_ADMIN,
            'school_id' => $school->id,
            'must_change_password' => false,
        ]);
        $teacherUser = User::factory()->create([
            'name' => 'Teacher',
            'email' => 'teacher2@alpha.test',
            'role' => User::ROLE_TEACHER,
            'school_id' => $school->id,
            'must_change_password' => false,
        ]);

        $this->actingAs($adminUser)->get(route('dashboard.teacher'))->assertForbidden();
        $this->actingAs($teacherUser)->get(route('admin.index'))->assertForbidden();
    }

    private function seedTeacherAttendanceContext(): array
    {
        $school = School::query()->create([
            'name' => 'Alpha Public School',
            'code' => 'ALPHA',
            'domain' => 'alpha.test',
            'is_active' => true,
        ]);

        $teacherUser = User::factory()->create([
            'name' => 'Anita Sharma',
            'email' => 'anita.sharma@alpha.test',
            'role' => User::ROLE_TEACHER,
            'school_id' => $school->id,
            'must_change_password' => false,
        ]);

        $teacher = Teacher::query()->create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'name' => 'Anita Sharma',
            'email' => $teacherUser->email,
            'phone' => '9801000001',
            'has_attendance_access' => true,
        ]);

        $class = SchoolClass::query()->create([
            'school_id' => $school->id,
            'name' => 'Grade 1',
            'display_order' => 1,
        ]);
        $section = Section::query()->create([
            'school_id' => $school->id,
            'name' => 'A',
        ]);

        $assignment = ClassTeacherAssignment::query()->create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'section_id' => $section->id,
            'teacher_id' => $teacher->id,
        ]);

        $students = [
            Student::query()->create([
                'school_id' => $school->id,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'student_id' => 'ALP250001',
                'name' => 'Student One',
                'email' => 'student1@alpha.test',
            ]),
            Student::query()->create([
                'school_id' => $school->id,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'student_id' => 'ALP250002',
                'name' => 'Student Two',
                'email' => 'student2@alpha.test',
            ]),
        ];

        return [$school, $teacherUser, $assignment, $students];
    }
}
