<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\Teacher\TeacherStudentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherStudentRequestController extends Controller
{
    public function __construct(private readonly TeacherStudentRequestService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'assignment_id' => ['nullable', 'integer'],
        ]);

        return view('teacher.students', $this->service->pageData($request->user(), $filters));
    }

    public function storeCreate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'assignment_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'device_identifier' => ['nullable', 'string', 'max:100'],
        ]);

        $this->service->requestCreate($request->user(), $data);

        return redirect()
            ->route('teacher.students.index', ['assignment_id' => $data['assignment_id']])
            ->with('status', 'Student create request sent to admin for approval.');
    }

    public function storeUpdate(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'assignment_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'device_identifier' => ['nullable', 'string', 'max:100'],
        ]);

        $this->service->requestUpdate($request->user(), $student, $data);

        return redirect()
            ->route('teacher.students.index', ['assignment_id' => $data['assignment_id']])
            ->with('status', 'Student update request sent to admin for approval.');
    }

    public function storeDelete(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'assignment_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->requestDelete($request->user(), $student, $data);

        return redirect()
            ->route('teacher.students.index', ['assignment_id' => $data['assignment_id']])
            ->with('status', 'Student delete request sent to admin for approval.');
    }

    public function generateStudentPassword(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'assignment_id' => ['required', 'integer'],
        ]);

        $credentials = $this->service->generateStudentOneTimePassword(
            $request->user(),
            $student,
            (int) $data['assignment_id']
        );

        return redirect()
            ->route('teacher.students.index', ['assignment_id' => $data['assignment_id']])
            ->with('status', 'Student one-time password generated successfully.')
            ->with('generated_student_credentials', $credentials);
    }
}
