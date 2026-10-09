<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\SchoolClassAccess;
use App\Services\StudentEnrollmentAccess;
use Illuminate\Database\Eloquent\Builder;

class StudentProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active
            && $user->can('students.view');
    }

    public function view(User $user, StudentProfile $student): bool
    {
        return app(StudentEnrollmentAccess::class)
            ->canView($user, $student);
    }

    public function update(User $user, StudentProfile $student): bool
    {
        if (
            ! $student->exists
            || ! $user->is_active
            || ! $user->can('students.update')
        ) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'school_admin'])) {
            return true;
        }

        if (! app(StudentEnrollmentAccess::class)->canView($user, $student)) {
            return false;
        }

        $today = now()->toDateString();
        $classAccess = app(SchoolClassAccess::class);

        $enrollments = $student->classEnrollments()
            ->where('status', 'active')
            ->whereDate('enrolled_at', '<=', $today)
            ->where(function (Builder $query) use ($today) {
                $query->whereNull('ended_at')
                    ->orWhereDate('ended_at', '>=', $today);
            })
            ->get();

        foreach ($enrollments as $enrollment) {
            $schoolClass = SchoolClass::find($enrollment->class_id);

            if (
                $schoolClass
                && $classAccess->canManageStudents($user, $schoolClass)
            ) {
                return true;
            }
        }

        return false;
    }

    public function transfer(User $user, StudentProfile $student): bool
    {
        return $student->exists
            && $student->status === 'active'
            && (bool) $user->is_active
            && $user->can('students.transfer')
            && $user->hasAnyRole(['super_admin', 'school_admin']);
    }
}