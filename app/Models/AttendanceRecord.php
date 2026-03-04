<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_HALF_DAY = 'half_day';
    public const STATUS_LEAVE = 'leave';

    public const STATUSES = [
        self::STATUS_PRESENT,
        self::STATUS_ABSENT,
        self::STATUS_LATE,
        self::STATUS_HALF_DAY,
        self::STATUS_LEAVE,
    ];

    public const LEAVE_TYPE_MEDICAL = 'medical';
    public const LEAVE_TYPE_APPROVED = 'approved';

    public const LEAVE_TYPES = [
        self::LEAVE_TYPE_MEDICAL,
        self::LEAVE_TYPE_APPROVED,
    ];

    protected $fillable = [
        'attendance_session_id',
        'student_id',
        'status',
        'leave_type',
        'remark',
    ];

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
