<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        $allowedRoutes = [
            'password.force.edit',
            'password.force.update',
            'logout',
        ];

        if (in_array((string) $request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()->route('password.force.edit');
    }
}
