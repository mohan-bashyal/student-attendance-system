<?php

namespace App\Http\Controllers;

use App\Services\Portal\ParentPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentPortalController extends Controller
{
    public function __construct(private readonly ParentPortalService $portalService)
    {
    }

    public function index(Request $request): View
    {
        return view('parent.portal', $this->portalService->portalData($request->user()));
    }
}
