<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceAttendanceEvent extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'school_id',
        'school_device_id',
        'idempotency_key',
        'payload',
        'status',
        'attempts',
        'last_error',
        'response_json',
        'processed_at',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response_json' => 'array',
            'processed_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolDevice(): BelongsTo
    {
        return $this->belongsTo(SchoolDevice::class, 'school_device_id');
    }
}
