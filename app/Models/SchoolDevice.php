<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'device_code',
        'token',
        'is_active',
        'last_seen_at',
        'last_event_at',
        'last_event_status',
        'last_event_message',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'last_event_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
