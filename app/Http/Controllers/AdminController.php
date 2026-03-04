<?php

namespace App\Http\Controllers;

use App\Models\ClassTeacherAssignment;
use App\Models\ClassSectionMapping;
use App\Models\SchoolClass;
use App\Models\SchoolCalendarEvent;
use App\Models\SchoolDevice;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentChangeRequest;
use App\Models\Teacher;
use App\Services\Admin\AdminManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(private readonly AdminManagementService $managementService)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.index', $this->managementService->dashboardData($request->user()));
    }

    public function classSections(Request $request): View
    {
        return view('admin.class-sections', $this->managementService->dashboardData($request->user()));
    }

    public function teachers(Request $request): View
    {
        return view('admin.teachers', $this->managementService->dashboardData($request->user()));
    }

    public function students(Request $request): View
    {
        $schoolId = (int) $request->user()->school_id;
        $filters = $request->validate([
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
        ]);

        $data = $this->managementService->dashboardData($request->user());
        $selectedClassId = isset($filters['school_class_id']) ? (int) $filters['school_class_id'] : null;
        $selectedSectionId = isset($filters['section_id']) ? (int) $filters['section_id'] : null;

        $students = $data['students'];
        if ($selectedClassId) {
            $students = $students->where('school_class_id', $selectedClassId);
        }
        if ($selectedSectionId) {
            $students = $students->where('section_id', $selectedSectionId);
        }

        $data['students'] = $students->values();
        $data['selectedClassId'] = $selectedClassId;
        $data['selectedSectionId'] = $selectedSectionId;

        return view('admin.students', $data);
    }

    public function devices(Request $request): View
    {
        return view('admin.devices', $this->managementService->dashboardData($request->user()));
    }

    public function holidays(Request $request): View
    {
        return view('admin.holidays', $this->managementService->calendarData($request->user()));
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'display_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->managementService->createClass($request->user(), $data);

        return redirect()->route('admin.class_sections.index')->with('status', 'Class created.');
    }

    public function updateClass(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'display_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->managementService->updateClass($request->user(), $schoolClass, $data);

        return redirect()->route('admin.class_sections.index')->with('status', 'Class updated.');
    }

    public function destroyClass(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->managementService->deleteClass($request->user(), $schoolClass);

        return redirect()->route('admin.class_sections.index')->with('status', 'Class deleted.');
    }

    public function storeSection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $this->managementService->createSection($request->user(), $data);

        return redirect()->route('admin.class_sections.index')->with('status', 'Section created.');
    }

    public function updateSection(Request $request, Section $section): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $this->managementService->updateSection($request->user(), $section, $data);

        return redirect()->route('admin.class_sections.index')->with('status', 'Section updated.');
    }

    public function destroySection(Request $request, Section $section): RedirectResponse
    {
        $this->managementService->deleteSection($request->user(), $section);

        return redirect()->route('admin.class_sections.index')->with('status', 'Section deleted.');
    }

    public function storeClassSectionMapping(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'section_id' => ['required', Rule::exists('sections', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->managementService->upsertClassSectionMapping($request->user(), $data);

        return redirect()->route('admin.class_sections.index')->with('status', 'Class-section mapping saved.');
    }

    public function toggleClassSectionMappingStatus(Request $request, ClassSectionMapping $mapping): RedirectResponse
    {
        $this->managementService->toggleClassSectionMappingStatus($request->user(), $mapping);

        return redirect()->route('admin.class_sections.index')->with('status', 'Class-section status updated.');
    }

    public function storeTeacher(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:teachers,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'has_attendance_access' => ['nullable', 'boolean'],
        ]);

        $this->managementService->createTeacher($request->user(), $data);

        return redirect()->route('admin.teachers.index')->with('status', 'Teacher added.');
    }

    public function updateTeacher(Request $request, Teacher $teacher): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:teachers,email,'.$teacher->id],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $this->managementService->updateTeacher($request->user(), $teacher, $data);

        return redirect()->route('admin.teachers.index')->with('status', 'Teacher updated.');
    }

    public function destroyTeacher(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->managementService->deleteTeacher($request->user(), $teacher);

        return redirect()->route('admin.teachers.index')->with('status', 'Teacher deleted.');
    }

    public function updateTeacherAttendanceAccess(Request $request, Teacher $teacher): RedirectResponse
    {
        $data = $request->validate([
            'has_attendance_access' => ['required', 'boolean'],
        ]);

        $this->managementService->updateTeacherAttendanceAccess($request->user(), $teacher, (bool) $data['has_attendance_access']);

        return redirect()->route('admin.teachers.index')->with('status', 'Attendance access updated.');
    }

    public function storeClassTeacherAssignment(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'teacher_id' => ['required', Rule::exists('teachers', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'section_id' => ['required', Rule::exists('sections', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
        ]);

        $this->managementService->assignClassTeacher($request->user(), $data);

        return redirect()->route('admin.teachers.index')->with('status', 'Class teacher assigned.');
    }

    public function destroyClassTeacherAssignment(Request $request, ClassTeacherAssignment $assignment): RedirectResponse
    {
        $this->managementService->deleteClassTeacherAssignment($request->user(), $assignment);

        return redirect()->route('admin.teachers.index')->with('status', 'Class teacher assignment removed.');
    }

    public function generateClassTeacherPassword(Request $request, ClassTeacherAssignment $assignment): RedirectResponse
    {
        $credentials = $this->managementService->generateClassTeacherOneTimePassword($request->user(), $assignment);

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'One-time password generated for class teacher.')
            ->with('generated_teacher_credentials', $credentials);
    }

    public function importTeachers(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $extension = strtolower((string) $request->file('file')?->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'txt'], true)) {
            return redirect()->route('admin.teachers.index')->withErrors(['file' => 'Use CSV file.']);
        }

        $result = $this->managementService->importTeachers($request->user(), $request->file('file'));

        return redirect()->route('admin.teachers.index')->with('status', "Teacher import done. Imported: {$result['imported']}, Skipped: {$result['skipped']}");
    }

    public function approveStudentChangeRequest(Request $request, StudentChangeRequest $changeRequest): RedirectResponse
    {
        $this->managementService->approveStudentChangeRequest($request->user(), $changeRequest);

        return redirect()->route('admin.index')->with('status', 'Student request approved.');
    }

    public function rejectStudentChangeRequest(Request $request, StudentChangeRequest $changeRequest): RedirectResponse
    {
        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->managementService->rejectStudentChangeRequest($request->user(), $changeRequest, $data['review_note'] ?? null);

        return redirect()->route('admin.index')->with('status', 'Student request rejected.');
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:students,email'],
            'device_identifier' => ['nullable', 'string', 'max:100', Rule::unique('students', 'device_identifier')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->managementService->createStudent($request->user(), $data, $request->file('photo'));

        return redirect()->route('admin.students.index')->with('status', 'Student added.');
    }

    public function updateStudent(Request $request, Student $student): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:students,email,'.$student->id],
            'device_identifier' => ['nullable', 'string', 'max:100', Rule::unique('students', 'device_identifier')->where(fn ($query) => $query->where('school_id', $schoolId))->ignore($student->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->managementService->updateStudent($request->user(), $student, $data, $request->file('photo'));

        return redirect()->route('admin.students.index')->with('status', 'Student updated.');
    }

    public function destroyStudent(Request $request, Student $student): RedirectResponse
    {
        $this->managementService->deleteStudent($request->user(), $student);

        return redirect()->route('admin.students.index')->with('status', 'Student deleted.');
    }

    public function importStudents(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $extension = strtolower((string) $request->file('file')?->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'txt'], true)) {
            return redirect()->route('admin.students.index')->withErrors(['file' => 'Use CSV file.']);
        }

        $result = $this->managementService->importStudents($request->user(), $request->file('file'));

        return redirect()->route('admin.students.index')->with('status', "Import done. Imported: {$result['imported']}, Skipped: {$result['skipped']}");
    }

    public function storeSchoolDevice(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'device_code' => ['nullable', 'string', 'max:100', Rule::unique('school_devices', 'device_code')->where(fn ($query) => $query->where('school_id', $schoolId))],
        ]);

        $device = $this->managementService->createSchoolDevice($request->user(), $data);

        return redirect()
            ->route('admin.devices.index')
            ->with('status', 'Device created successfully.')
            ->with('generated_device_token', $device);
    }

    public function toggleSchoolDeviceStatus(Request $request, SchoolDevice $device): RedirectResponse
    {
        $isActive = $this->managementService->toggleSchoolDeviceStatus($request->user(), $device);

        return redirect()
            ->route('admin.devices.index')
            ->with('status', $isActive ? 'Device activated.' : 'Device deactivated.');
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'event_date' => ['required', 'date'],
            'event_type' => ['required', Rule::in(SchoolCalendarEvent::TYPES)],
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->managementService->createCalendarEvent($request->user(), $data);

        return redirect()->route('admin.holidays.index')->with('status', 'Calendar event created.');
    }

    public function updateHoliday(Request $request, SchoolCalendarEvent $event): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'event_date' => ['required', 'date'],
            'event_type' => ['required', Rule::in(SchoolCalendarEvent::TYPES)],
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where(fn ($query) => $query->where('school_id', $schoolId))],
            'note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $this->managementService->updateCalendarEvent($request->user(), $event, $data);

        return redirect()->route('admin.holidays.index')->with('status', 'Calendar event updated.');
    }

    public function destroyHoliday(Request $request, SchoolCalendarEvent $event): RedirectResponse
    {
        $this->managementService->deleteCalendarEvent($request->user(), $event);

        return redirect()->route('admin.holidays.index')->with('status', 'Calendar event deleted.');
    }
}
