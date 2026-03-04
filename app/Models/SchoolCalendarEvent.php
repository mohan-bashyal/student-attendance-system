<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolCalendarEvent extends Model
{
    use HasFactory;

    public const TYPE_HOLIDAY = 'holiday';
    public const TYPE_EXAM_DAY = 'exam_day';
    public const TYPE_EVENT_DAY = 'event_day';

    public const TYPES = [
        self::TYPE_HOLIDAY,
        self::TYPE_EXAM_DAY,
        self::TYPE_EVENT_DAY,
    ];

    protected $fillable = [
        'school_id',
        'school_class_id',
        'section_id',
        'event_date',
        'event_type',
        'title',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}

