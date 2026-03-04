<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAttendanceNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'attendance_session_id',
        'teacher_id',
        'teacher_name',
        'class_name',
        'section_name',
        'attendance_date',
        'total_students',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'total_students' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
