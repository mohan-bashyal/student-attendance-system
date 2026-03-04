<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolDevice;
use App\Services\SuperAdmin\SuperAdminManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function __construct(private readonly SuperAdminManagementService $managementService)
    {
    }

    public function index(): View
    {
        return view('super-admin.index', $this->managementService->dashboardData());
    }

    public function storeSchool(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:schools,code'],
            'domain' => ['nullable', 'string', 'max:255'],
            'subscription_plan' => ['required', 'in:'.implode(',', School::ENABLED_SUBSCRIPTION_PLANS)],
            'subscription_status' => ['required', 'in:'.implode(',', School::SUBSCRIPTION_STATUSES)],
            'subscription_ends_at' => ['nullable', 'date'],
            'max_students' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->managementService->createSchool($data);

        return redirect()->route('super_admin.index')->with('status', 'School created successfully.');
    }

    public function updateSchoolStatus(School $school): RedirectResponse
    {
        $isActive = $this->managementService->toggleSchoolStatus($school);
        $message = $isActive ? 'School activated successfully.' : 'School deactivated successfully.';

        return redirect()->route('super_admin.index')->with('status', $message);
    }

    public function storeSchoolAdmin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $this->managementService->createSchoolAdmin($data);

        return redirect()->route('super_admin.index')->with('status', 'School admin created successfully.');
    }

    public function updateSubscription(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'subscription_plan' => ['required', 'in:'.implode(',', School::ENABLED_SUBSCRIPTION_PLANS)],
            'subscription_status' => ['required', 'in:'.implode(',', School::SUBSCRIPTION_STATUSES)],
            'subscription_ends_at' => ['nullable', 'date'],
            'max_students' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->managementService->updateSubscription($school, $data);

        return redirect()->route('super_admin.index')->with('status', 'Subscription updated successfully.');
    }

    public function storeSchoolDevice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'name' => ['required', 'string', 'max:255'],
            'device_code' => ['nullable', 'string', 'max:100'],
        ]);

        $device = $this->managementService->createSchoolDevice($data);

        return redirect()
            ->route('super_admin.index')
            ->with('status', 'School device created successfully.')
            ->with('generated_device_token', $device);
    }

    public function toggleDeviceStatus(SchoolDevice $device): RedirectResponse
    {
        $isActive = $this->managementService->toggleDeviceStatus($device);
        $message = $isActive ? 'Device activated successfully.' : 'Device deactivated successfully.';

        return redirect()->route('super_admin.index')->with('status', $message);
    }
}
