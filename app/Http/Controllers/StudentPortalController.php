<?php

namespace App\Http\Controllers;

use App\Services\Portal\StudentPortalService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class StudentPortalController extends Controller
{
    public function __construct(private readonly StudentPortalService $portalService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['present', 'absent', 'late', 'half_day', 'leave'])],
            'overview_chart' => ['nullable', Rule::in(['doughnut', 'bar', 'line'])],
        ]);

        return view('student.portal', $this->portalService->portalData($request->user(), $filters));
    }

    public function downloadMonthlyReport(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = (string) ($validated['month'] ?? now()->format('Y-m'));
        $rows = $this->portalService->monthlyCsvRows($request->user(), $month);

        $filename = "student-attendance-{$month}.csv";

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
