<?php

namespace App\Http\Controllers;

use App\Services\Auth\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(Request $request): View
    {
        return view('dashboard.index', $this->dashboardService->pageData($request->user()));
    }
}
