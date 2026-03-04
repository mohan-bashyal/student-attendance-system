<?php

namespace App\Http\Middleware;

use App\Models\SchoolDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSchoolDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) ($request->header('X-Device-Token') ?? '');
        if ($token === '') {
            return response()->json(['message' => 'Device token is required.'], 401);
        }

        $device = SchoolDevice::query()
            ->where('token', $token)
            ->where('is_active', true)
            ->first();

        if (! $device) {
            return response()->json(['message' => 'Invalid or inactive device token.'], 401);
        }

        if (Schema::hasColumn('school_devices', 'last_seen_at')) {
            $device->forceFill([
                'last_seen_at' => now(),
            ])->save();
        }

        $request->attributes->set('device', $device);

        return $next($request);
    }
}
