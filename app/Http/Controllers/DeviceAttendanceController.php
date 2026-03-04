<?php

namespace App\Http\Controllers;

use App\Models\SchoolDevice;
use App\Services\Attendance\DeviceAttendanceEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class DeviceAttendanceController extends Controller
{
    public function __construct(private readonly DeviceAttendanceEventService $deviceAttendanceEventService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        /** @var SchoolDevice|null $device */
        $device = $request->attributes->get('device');
        if (! $device) {
            return response()->json(['message' => 'Device authentication failed.'], 401);
        }

        $data = $request->validate([
            'student_code' => ['required', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['present', 'late'])],
            'event_at' => ['nullable', 'date'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);

        $idempotencyKey = $request->header('X-Idempotency-Key') ?? ($data['idempotency_key'] ?? null);
        unset($data['idempotency_key']);

        try {
            $result = $this->deviceAttendanceEventService->ingest($device, $data, $idempotencyKey);
        } catch (\Throwable $exception) {
            $this->touchDeviceRealtimeState($device, 'failed', $exception->getMessage());
            throw $exception;
        }

        $this->touchDeviceRealtimeState($device, 'success', 'Attendance synced');

        return response()->json([
            'message' => (bool) ($result['idempotent_replay'] ?? false)
                ? 'Attendance already processed for this idempotency key.'
                : 'Attendance recorded from device.',
            'data' => $result,
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        /** @var SchoolDevice|null $device */
        $device = $request->attributes->get('device');
        if (! $device) {
            return response()->json(['message' => 'Device authentication failed.'], 401);
        }

        $this->touchDeviceRealtimeState($device, 'heartbeat', 'Device is online');

        return response()->json([
            'message' => 'Heartbeat received.',
            'device' => [
                'id' => (int) $device->id,
                'name' => (string) $device->name,
                'server_time' => now()->toDateTimeString(),
                'next_heartbeat_in_seconds' => 30,
            ],
        ]);
    }

    private function touchDeviceRealtimeState(SchoolDevice $device, string $status, string $message): void
    {
        if (! Schema::hasColumn('school_devices', 'last_seen_at')) {
            return;
        }

        $updates = [
            'last_seen_at' => now(),
        ];
        if (Schema::hasColumn('school_devices', 'last_event_at')) {
            $updates['last_event_at'] = now();
        }
        if (Schema::hasColumn('school_devices', 'last_event_status')) {
            $updates['last_event_status'] = $status;
        }
        if (Schema::hasColumn('school_devices', 'last_event_message')) {
            $updates['last_event_message'] = $message;
        }

        $device->forceFill($updates)->save();
    }
}
