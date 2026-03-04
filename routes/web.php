<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\PermissionDemoController;
use App\Http\Controllers\PublicLandingController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TeacherAttendanceController;
use App\Http\Controllers\TeacherStudentRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicLandingController::class, 'index'])->name('public.landing');

Route::middleware('guest')->group(function (): void {
    Route::post('/checkout', [PublicLandingController::class, 'checkout'])->name('public.checkout');
    Route::get('/checkout/success', [PublicLandingController::class, 'checkoutSuccess'])->name('public.checkout.success');
    Route::get('/register/admin', [PublicLandingController::class, 'registerForm'])->name('public.register.admin');
    Route::post('/register/admin', [PublicLandingController::class, 'registerStore'])->name('public.register.admin.store');
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'password.changed'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/force-password', [AuthController::class, 'editForcePassword'])->name('password.force.edit');
    Route::post('/force-password', [AuthController::class, 'updateForcePassword'])->name('password.force.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/super-admin', [SuperAdminController::class, 'index'])->middleware('role:super_admin')->name('dashboard.super_admin');
    Route::get('/dashboard/admin', [AdminController::class, 'index'])->middleware('role:admin')->name('dashboard.admin');
    Route::get('/dashboard/teacher', [TeacherAttendanceController::class, 'index'])->middleware('role:teacher')->name('dashboard.teacher');
    Route::post('/dashboard/teacher', [TeacherAttendanceController::class, 'store'])->middleware('role:teacher')->name('dashboard.teacher.store');
    Route::get('/dashboard/student', [StudentPortalController::class, 'index'])->middleware('role:student')->name('dashboard.student');
    Route::get('/dashboard/parent', [ParentPortalController::class, 'index'])->middleware('role:parent')->name('dashboard.parent');
    Route::get('/dashboard/staff', [DashboardController::class, 'index'])->middleware('role:staff')->name('dashboard.staff');

    Route::get('/access/reports', [PermissionDemoController::class, 'reports'])
        ->middleware('permission:view_reports')
        ->name('access.reports');

    Route::get('/access/mark-attendance', [PermissionDemoController::class, 'markAttendance'])
        ->middleware('permission:mark_attendance')
        ->name('access.mark_attendance');

    Route::prefix('/super-admin')->middleware('role:super_admin')->name('super_admin.')->group(function (): void {
        Route::get('/', [SuperAdminController::class, 'index'])->name('index');
        Route::post('/schools', [SuperAdminController::class, 'storeSchool'])->name('schools.store');
        Route::patch('/schools/{school}/status', [SuperAdminController::class, 'updateSchoolStatus'])->name('schools.status');
        Route::post('/school-admins', [SuperAdminController::class, 'storeSchoolAdmin'])->name('school_admins.store');
        Route::patch('/schools/{school}/subscription', [SuperAdminController::class, 'updateSubscription'])->name('schools.subscription');
        Route::post('/school-devices', [SuperAdminController::class, 'storeSchoolDevice'])->name('school_devices.store');
        Route::patch('/school-devices/{device}/status', [SuperAdminController::class, 'toggleDeviceStatus'])->name('school_devices.status');
    });

    Route::prefix('/admin')->middleware('role:admin')->name('admin.')->group(function (): void {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/class-sections', [AdminController::class, 'classSections'])->name('class_sections.index');
        Route::get('/teachers', [AdminController::class, 'teachers'])->name('teachers.index');
        Route::get('/students', [AdminController::class, 'students'])->name('students.index');
        Route::get('/devices', [AdminController::class, 'devices'])->name('devices.index');
        Route::get('/holidays', [AdminController::class, 'holidays'])->name('holidays.index');
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');
        Route::post('/classes', [AdminController::class, 'storeClass'])->name('classes.store');
        Route::patch('/classes/{schoolClass}', [AdminController::class, 'updateClass'])->name('classes.update');
        Route::delete('/classes/{schoolClass}', [AdminController::class, 'destroyClass'])->name('classes.destroy');
        Route::post('/sections', [AdminController::class, 'storeSection'])->name('sections.store');
        Route::patch('/sections/{section}', [AdminController::class, 'updateSection'])->name('sections.update');
        Route::delete('/sections/{section}', [AdminController::class, 'destroySection'])->name('sections.destroy');
        Route::post('/class-section-mappings', [AdminController::class, 'storeClassSectionMapping'])->name('class_section_mappings.store');
        Route::patch('/class-section-mappings/{mapping}/status', [AdminController::class, 'toggleClassSectionMappingStatus'])->name('class_section_mappings.status');
        Route::post('/teachers', [AdminController::class, 'storeTeacher'])->name('teachers.store');
        Route::patch('/teachers/{teacher}', [AdminController::class, 'updateTeacher'])->name('teachers.update');
        Route::delete('/teachers/{teacher}', [AdminController::class, 'destroyTeacher'])->name('teachers.destroy');
        Route::post('/teachers/import', [AdminController::class, 'importTeachers'])->name('teachers.import');
        Route::post('/student-change-requests/{changeRequest}/approve', [AdminController::class, 'approveStudentChangeRequest'])->name('student_change_requests.approve');
        Route::post('/student-change-requests/{changeRequest}/reject', [AdminController::class, 'rejectStudentChangeRequest'])->name('student_change_requests.reject');
        Route::post('/class-teacher-assignments', [AdminController::class, 'storeClassTeacherAssignment'])->name('class_teachers.store');
        Route::delete('/class-teacher-assignments/{assignment}', [AdminController::class, 'destroyClassTeacherAssignment'])->name('class_teachers.destroy');
        Route::post('/class-teacher-assignments/{assignment}/one-time-password', [AdminController::class, 'generateClassTeacherPassword'])->name('class_teachers.generate_password');
        Route::patch('/teachers/{teacher}/attendance-access', [AdminController::class, 'updateTeacherAttendanceAccess'])->name('teachers.attendance_access');
        Route::post('/students', [AdminController::class, 'storeStudent'])->name('students.store');
        Route::patch('/students/{student}', [AdminController::class, 'updateStudent'])->name('students.update');
        Route::delete('/students/{student}', [AdminController::class, 'destroyStudent'])->name('students.destroy');
        Route::post('/students/import', [AdminController::class, 'importStudents'])->name('students.import');
        Route::post('/school-devices', [AdminController::class, 'storeSchoolDevice'])->name('school_devices.store');
        Route::patch('/school-devices/{device}/status', [AdminController::class, 'toggleSchoolDeviceStatus'])->name('school_devices.status');
        Route::post('/holidays', [AdminController::class, 'storeHoliday'])->name('holidays.store');
        Route::patch('/holidays/{event}', [AdminController::class, 'updateHoliday'])->name('holidays.update');
        Route::delete('/holidays/{event}', [AdminController::class, 'destroyHoliday'])->name('holidays.destroy');
    });

    Route::prefix('/student-portal')->middleware('role:student')->name('student_portal.')->group(function (): void {
        Route::get('/', [StudentPortalController::class, 'index'])->name('index');
        Route::get('/monthly-report', [StudentPortalController::class, 'downloadMonthlyReport'])->name('monthly_report');
    });

    Route::prefix('/parent-portal')->middleware('role:parent')->name('parent_portal.')->group(function (): void {
        Route::get('/', [ParentPortalController::class, 'index'])->name('index');
    });

    Route::prefix('/teacher-attendance')->middleware('role:teacher')->name('teacher.attendance.')->group(function (): void {
        Route::get('/', [TeacherAttendanceController::class, 'index'])->name('index');
        Route::post('/', [TeacherAttendanceController::class, 'store'])->name('store');
    });

    Route::prefix('/teacher-students')->middleware('role:teacher')->name('teacher.students.')->group(function (): void {
        Route::get('/', [TeacherStudentRequestController::class, 'index'])->name('index');
        Route::post('/requests/create', [TeacherStudentRequestController::class, 'storeCreate'])->name('requests.create');
        Route::post('/requests/{student}/update', [TeacherStudentRequestController::class, 'storeUpdate'])->name('requests.update');
        Route::post('/requests/{student}/delete', [TeacherStudentRequestController::class, 'storeDelete'])->name('requests.delete');
        Route::post('/students/{student}/one-time-password', [TeacherStudentRequestController::class, 'generateStudentPassword'])->name('generate_password');
    });
});
