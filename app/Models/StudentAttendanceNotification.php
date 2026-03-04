<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendanceNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'attendance_session_id',
        'student_id',
        'message',
        'is_read',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'notified_at' => 'datetime',
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
