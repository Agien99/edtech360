<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;
use App\Services\SchoolClassAccess;

class SchoolClassPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active
            && $user->can('classes.view');
    }

    public function view(User $user, SchoolClass $schoolClass): bool
    {
        return app(SchoolClassAccess::class)
            ->canView($user, $schoolClass);
    }

    public function manageStudents(
        User $user,
        SchoolClass $schoolClass
    ): bool {
        return app(SchoolClassAccess::class)
            ->canManageStudents($user, $schoolClass);
    }

    public function teachSubjectInSemester(
        User $user,
        SchoolClass $schoolClass,
        int $subjectId,
        int $semesterId
    ): bool {
        return app(SchoolClassAccess::class)
            ->canTeachSubjectInSemester(
                $user,
                $schoolClass,
                $subjectId,
                $semesterId
            );
    }
}