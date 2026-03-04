<?php

namespace App\Services\Attendance;

use App\Models\DeviceAttendanceEvent;
use App\Models\SchoolDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeviceAttendanceEventService
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly DeviceAttendanceService $deviceAttendanceService)
    {
    }

    public function ingest(SchoolDevice $device, array $payload, ?string $idempotencyKey): array
    {
        $key = $this->resolveIdempotencyKey($device, $payload, $idempotencyKey);

        /** @var DeviceAttendanceEvent $event */
        $event = DeviceAttendanceEvent::query()->firstOrCreate(
            [
                'school_id' => (int) $device->school_id,
                'idempotency_key' => $key,
            ],
            [
                'school_device_id' => (int) $device->id,
                'payload' => $payload,
                'status' => DeviceAttendanceEvent::STATUS_PENDING,
            ]
        );

        if ($event->status === DeviceAttendanceEvent::STATUS_PROCESSED && is_array($event->response_json)) {
            return $this->formatResult($event, true);
        }

        if (! $event->wasRecentlyCreated && $event->status === DeviceAttendanceEvent::STATUS_PROCESSING) {
            return $this->formatResult($event, true);
        }

        $event = $this->process($event, $device);

        return $this->formatResult($event, false);
    }

    /**
     * @return array{processed:int,failed:int}
     */
    public function retryFailed(int $limit = 100): array
    {
        $processed = 0;
        $failed = 0;

        DeviceAttendanceEvent::query()
            ->whereIn('status', [DeviceAttendanceEvent::STATUS_FAILED, DeviceAttendanceEvent::STATUS_PENDING])
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->orderBy('updated_at')
            ->limit($limit)
            ->chunkById(50, function ($events) use (&$processed, &$failed): void {
                foreach ($events as $event) {
                    $device = $event->schoolDevice;
                    if (! $device) {
                        $event->update([
                            'status' => DeviceAttendanceEvent::STATUS_FAILED,
                            'last_error' => 'Linked school device is missing.',
                            'last_attempt_at' => now(),
                        ]);
                        $failed++;
                        continue;
                    }

                    try {
                        $this->process($event, $device);
                        $processed++;
                    } catch (\Throwable) {
                        $failed++;
                    }
                }
            });

        return [
            'processed' => $processed,
            'failed' => $failed,
        ];
    }

    private function process(DeviceAttendanceEvent $event, SchoolDevice $device): DeviceAttendanceEvent
    {
        return DB::transaction(function () use ($event, $device): DeviceAttendanceEvent {
            $event->refresh();
            $event->fill([
                'status' => DeviceAttendanceEvent::STATUS_PROCESSING,
                'attempts' => ((int) $event->attempts) + 1,
                'last_attempt_at' => now(),
            ])->save();

            try {
                $result = $this->deviceAttendanceService->recordFromDevice($device, (array) $event->payload);

                $event->fill([
                    'status' => DeviceAttendanceEvent::STATUS_PROCESSED,
                    'response_json' => $result,
                    'processed_at' => now(),
                    'last_error' => null,
                ])->save();

                return $event;
            } catch (\Throwable $exception) {
                $event->fill([
                    'status' => DeviceAttendanceEvent::STATUS_FAILED,
                    'last_error' => Str::limit($exception->getMessage(), 2000),
                ])->save();

                throw $exception;
            }
        });
    }

    private function resolveIdempotencyKey(SchoolDevice $device, array $payload, ?string $idempotencyKey): string
    {
        $raw = trim((string) $idempotencyKey);
        if ($raw !== '') {
            return Str::limit($raw, 120, '');
        }

        $canonicalPayload = [
            'student_code' => (string) ($payload['student_code'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'present'),
            'event_at' => (string) ($payload['event_at'] ?? ''),
        ];

        return hash('sha256', (int) $device->school_id.'|'.(int) $device->id.'|'.json_encode($canonicalPayload));
    }

    private function formatResult(DeviceAttendanceEvent $event, bool $replay): array
    {
        return [
            'event_id' => (int) $event->id,
            'idempotency_key' => (string) $event->idempotency_key,
            'queue_status' => (string) $event->status,
            'attempts' => (int) $event->attempts,
            'idempotent_replay' => $replay,
            'result' => $event->response_json,
        ];
    }
}

