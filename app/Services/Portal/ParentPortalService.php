<?php

namespace App\Services\Portal;

use App\Models\AttendanceRecord;
use App\Models\ParentStudentLink;
use App\Models\User;
use Illuminate\Support\Collection;

class ParentPortalService
{
    public function portalData(User $parent): array
    {
        $links = ParentStudentLink::query()
            ->where('parent_user_id', $parent->id)
            ->with('student')
            ->get();

        $children = $links->pluck('student')->filter();
        $childData = [];

        foreach ($children as $child) {
            $records = AttendanceRecord::query()
                ->where('student_id', $child->id)
                ->with(['attendanceSession.schoolClass', 'attendanceSession.section'])
                ->latest()
                ->take(40)
                ->get();

            $childData[] = [
                'student' => $child,
                'records' => $records,
                'percentage' => $this->percentage($records),
            ];
        }

        return [
            'children' => $childData,
        ];
    }

    private function percentage(Collection $records): float
    {
        if ($records->count() === 0) {
            return 0;
        }

        $effective = 0.0;
        $denominator = 0.0;

        foreach ($records as $record) {
            if ($record->status === AttendanceRecord::STATUS_LEAVE) {
                continue;
            }

            $denominator += 1;

            if (in_array($record->status, [AttendanceRecord::STATUS_PRESENT, AttendanceRecord::STATUS_LATE], true)) {
                $effective += 1;
            } elseif ($record->status === AttendanceRecord::STATUS_HALF_DAY) {
                $effective += 0.5;
            }
        }

        if ($denominator <= 0) {
            return 0;
        }

        return ($effective / $denominator) * 100;
    }
}
