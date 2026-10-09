<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SchoolClassAccess
{
    public function visibleTo(User $user): Builder
    {
        $query = SchoolClass::query();

        if (! $this->canUse($user, 'classes.view')) {
            return $this->denyAll($query);
        }

        if ($user->hasAnyRole(['super_admin', 'school_admin'])) {
            return $query;
        }

        $teacher = $user->teacherProfile;

        if (! $teacher || $teacher->status !== 'active') {
            return $this->denyAll($query);
        }

        $today = now()->toDateString();

        return $query->where(function (Builder $classes) use (
            $teacher,
            $today
        ) {
            $classes->whereHas(
                'teacherClassAssignments',
                function (Builder $assignments) use ($teacher, $today) {
                    $assignments
                        ->where('teacher_profile_id', $teacher->id)
                        ->where('status', 'active')
                        ->whereDate('start_date', '<=', $today)
                        ->where(function (Builder $dates) use ($today) {
                            $dates->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $today);
                        });
                }
            );

            $classes->orWhereHas(
                'teachingAssignments',
                function (Builder $assignments) use ($teacher, $today) {
                    $assignments
                        ->where('teacher_profile_id', $teacher->id)
                        ->where('status', 'active')
                        ->whereColumn(
                            'teacher_subject_class.academic_session_id',
                            'classes.academic_session_id'
                        )
                        ->where(function (Builder $dates) use ($today) {
                            $dates->whereNull('start_date')
                                ->orWhereDate('start_date', '<=', $today);
                        })
                        ->where(function (Builder $dates) use ($today) {
                            $dates->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $today);
                        });
                }
            );
        });
    }

    public function canView(User $user, SchoolClass $schoolClass): bool
    {
        if (! $schoolClass->exists) {
            return false;
        }

        return $this->visibleTo($user)
            ->whereKey($schoolClass->getKey())
            ->exists();
    }

    public function canManageStudents(
        User $user,
        SchoolClass $schoolClass
    ): bool {
        if (! $schoolClass->exists) {
            return false;
        }

        if (! $this->canUse($user, 'students.update')) {
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

        return $teacher->classAssignments()
            ->where('class_id', $schoolClass->id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->where(function (Builder $dates) use ($today) {
                $dates->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->where(function (Builder $positions) use ($user) {
                $positions->where('position', 'class_teacher');

                if (
                    $user->hasRole('assistant_class_teacher')
                    && $user->hasDirectPermission('students.update')
                ) {
                    $positions->orWhere(
                        'position',
                        'assistant_class_teacher'
                    );
                }
            })
            ->exists();
    }

    public function canTeachSubjectInSemester(
        User $user,
        SchoolClass $schoolClass,
        int $subjectId,
        int $semesterId
    ): bool {
        if (! $schoolClass->exists) {
            return false;
        }

        if (
            ! $this->canUse($user, 'classes.view')
            || ! $user->can('subjects.view')
        ) {
            return false;
        }

        $teacher = $user->teacherProfile;

        if (! $teacher || $teacher->status !== 'active') {
            return false;
        }

        $semesterMatches = DB::table('semesters')
            ->where('id', $semesterId)
            ->where(
                'academic_session_id',
                $schoolClass->academic_session_id
            )
            ->exists();

        if (! $semesterMatches) {
            return false;
        }

        $today = now()->toDateString();

        return $teacher->teachingAssignments()
            ->where('class_id', $schoolClass->id)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->where(
                'academic_session_id',
                $schoolClass->academic_session_id
            )
            ->where('status', 'active')
            ->where(function (Builder $dates) use ($today) {
                $dates->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $today);
            })
            ->where(function (Builder $dates) use ($today) {
                $dates->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->exists();
    }

    private function canUse(User $user, string $permission): bool
    {
        return (bool) $user->is_active
            && $user->can($permission);
    }

    private function denyAll(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}