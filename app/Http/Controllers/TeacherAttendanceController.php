<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\Teacher\TeacherAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherAttendanceController extends Controller
{
    public function __construct(private readonly TeacherAttendanceService $attendanceService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'assignment_id' => ['nullable', 'integer'],
            'attendance_date' => ['nullable', 'date'],
            'overview_chart' => ['nullable', Rule::in(['doughnut', 'bar', 'line'])],
        ]);

        return view('teacher.attendance', $this->attendanceService->pageData($request->user(), $filters));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'assignment_id' => ['required', 'integer'],
            'attendance_date' => ['nullable', 'date'],
            'records' => ['required', 'array'],
            'records.*.status' => ['required', Rule::in(AttendanceRecord::STATUSES)],
            'records.*.leave_type' => ['nullable', Rule::in(AttendanceRecord::LEAVE_TYPES)],
            'records.*.remark' => ['nullable', 'string', 'max:255'],
        ]);

        $this->attendanceService->markToday($request->user(), $data);

        $redirectParams = ['assignment_id' => $data['assignment_id']];
        if (! empty($data['attendance_date'])) {
            $redirectParams['attendance_date'] = $data['attendance_date'];
        }

        return redirect()
            ->route('dashboard.teacher', $redirectParams)
            ->with('status', 'Today attendance saved successfully.');
    }
}
