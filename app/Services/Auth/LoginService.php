<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginService
{
    public function authenticate(string $email, string $password, ?string $schoolCode, bool $remember): ?User
    {
        $userQuery = User::query()->with('school')->where('email', $email);

        if (! empty($schoolCode)) {
            $userQuery->whereHas('school', function ($query) use ($schoolCode): void {
                $query->whereRaw('LOWER(code) = ?', [strtolower($schoolCode)]);
            });
        }

        $user = $userQuery->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->role !== User::ROLE_SUPER_ADMIN && $user->school && ! $user->school->is_active) {
            return null;
        }

        Auth::login($user, $remember);
        request()->session()->regenerate();

        return $user;
    }

    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
