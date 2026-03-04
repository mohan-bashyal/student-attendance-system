<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PermissionDemoController extends Controller
{
    public function reports(): JsonResponse
    {
        return response()->json([
            'message' => 'Report access granted.',
            'endpoint' => 'view_reports',
        ]);
    }

    public function markAttendance(): JsonResponse
    {
        return response()->json([
            'message' => 'Attendance marking access granted.',
            'endpoint' => 'mark_attendance',
        ]);
    }
}
