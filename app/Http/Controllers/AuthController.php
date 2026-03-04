<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\Auth\DashboardService;
use App\Services\Auth\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginService $loginService,
        private readonly DashboardService $dashboardService
    ) {
    }

    public function create(): View
    {
        return view('auth.login', [
            'schools' => School::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['name', 'code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'school_code' => ['nullable', 'string', 'max:50'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $user = $this->loginService->authenticate(
            $credentials['email'],
            $credentials['password'],
            $credentials['school_code'] ?? null,
            (bool) ($credentials['remember'] ?? false),
        );

        if (! $user) {
            return back()
                ->withInput($request->only('email', 'school_code'))
                ->withErrors(['email' => 'Invalid login details or school code.']);
        }

        if ($user->must_change_password) {
            return redirect()->route('password.force.edit');
        }

        return redirect()->route($this->dashboardService->routeFor($user));
    }

    public function editForcePassword(Request $request): View
    {
        return view('auth.force-password', [
            'user' => $request->user(),
        ]);
    }

    public function updateForcePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $updateData = ['password' => $data['password']];
        if (Schema::hasColumn('users', 'must_change_password')) {
            $updateData['must_change_password'] = false;
        }
        $user->update($updateData);

        return redirect()->route($this->dashboardService->routeFor($user));
    }

    public function destroy(): RedirectResponse
    {
        $this->loginService->logout();

        return redirect()->route('login');
    }
}
