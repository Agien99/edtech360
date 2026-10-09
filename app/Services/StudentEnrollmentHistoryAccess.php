<?php

namespace App\Services;

use App\Models\StudentClassEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentEnrollmentHistoryAccess
{
    public function canView(
        User $user,
        StudentClassEnrollment $enrollment
    ): bool {
        if (
            ! $enrollment->exists
            || ! $user->is_active
            || ! $user->can('students.view_history')
        ) {
            return false;
        }

        if (! $enrollment->student()->exists()) {
            return false;
        }

        if (
            ! $enrollment->schoolClass()->where(
                'academic_session_id',
                $enrollment->academic_session_id
            )->exists()
        ) {
            return false;
        }

        if (! $enrollment->semester()->where(
            'academic_session_id',
            $enrollment->academic_session_id
        )->exists()) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'school_admin'])) {
            return true;
        }

        $teacher = $user->teacherProfile;

        if (! $teacher || $teacher->status !== 'active') {
            return false;
        }

        $today = now()->toDateString();
        $start = $enrollment->enrolled_at?->toDateString();
        $end = $enrollment->ended_at?->toDateString()
            ?? $today;

        if (! $start || $start > $today || $end < $start) {
            return false;
        }

        // A former class teacher may inspect a specific historical
        // enrollment only if the assignment overlapped its dates.
        $classAssignment = $teacher->classAssignments()
            ->where('class_id', $enrollment->class_id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $end)
            ->where(function (Builder $dates) use ($start) {
                $dates->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $start);
            })
            ->exists();

        if ($classAssignment) {
            return true;
        }

        // Subject teachers must additionally match the semester
        // and academic session of the historical enrollment.
        return $teacher->teachingAssignments()
            ->where('class_id', $enrollment->class_id)
            ->where(
                'academic_session_id',
                $enrollment->academic_session_id
            )
            ->where('semester_id', $enrollment->semester_id)
            ->where('status', 'active')
            ->where(function (Builder $dates) use ($end) {
                $dates->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $end);
            })
            ->where(function (Builder $dates) use ($start) {
                $dates->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $start);
            })
            ->exists();
    }
}