<?php

namespace App\Services\Attendance;

use App\Models\SchoolCalendarEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AttendanceCalendarService
{
    public function eventForDate(int $schoolId, string $date, ?int $classId = null, ?int $sectionId = null): ?SchoolCalendarEvent
    {
        if (! Schema::hasTable('school_calendar_events')) {
            return null;
        }

        return SchoolCalendarEvent::query()
            ->where('school_id', $schoolId)
            ->whereDate('event_date', $date)
            ->where('is_active', true)
            ->where(function ($query) use ($classId): void {
                $query->whereNull('school_class_id');
                if ($classId !== null) {
                    $query->orWhere('school_class_id', $classId);
                }
            })
            ->where(function ($query) use ($sectionId): void {
                $query->whereNull('section_id');
                if ($sectionId !== null) {
                    $query->orWhere('section_id', $sectionId);
                }
            })
            ->orderByRaw('CASE WHEN school_class_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN section_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByDesc('id')
            ->first();
    }

    public function isHoliday(int $schoolId, string $date, ?int $classId = null, ?int $sectionId = null): bool
    {
        $event = $this->eventForDate($schoolId, $date, $classId, $sectionId);

        return (bool) $event && $event->event_type === SchoolCalendarEvent::TYPE_HOLIDAY;
    }

    /**
     * @return Collection<int, SchoolCalendarEvent>
     */
    public function listBySchool(int $schoolId): Collection
    {
        if (! Schema::hasTable('school_calendar_events')) {
            return collect();
        }

        return SchoolCalendarEvent::query()
            ->where('school_id', $schoolId)
            ->with(['schoolClass', 'section'])
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get();
    }
}
