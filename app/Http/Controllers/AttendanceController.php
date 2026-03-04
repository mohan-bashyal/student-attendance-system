<?php

namespace App\Http\Controllers;

use App\Services\Admin\AttendanceManagementService;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceManagementService $attendanceService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'attendance_date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'school_class_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(AttendanceRecord::STATUSES)],
        ]);

        return view('admin.attendance', $this->attendanceService->reportData($request->user(), $filters));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'attendance_date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'school_class_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(AttendanceRecord::STATUSES)],
        ]);

        $rows = $this->attendanceService->exportCsvRows($request->user(), $filters);
        $filename = 'attendance-report-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
