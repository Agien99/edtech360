<?php

namespace App\Services;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class StudentEnrollmentAccess
{
    public function visibleTo(User $user): Builder
    {
        $query = StudentProfile::query();

        if (! $user->is_active || ! $user->can('students.view')) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasAnyRole(['super_admin', 'school_admin'])) {
            return $query;
        }

        $teacher = $user->teacherProfile;

        if (! $teacher || $teacher->status !== 'active') {
            return $query->whereRaw('1 = 0');
        }

        $teacherId = $teacher->id;
        $today = now()->toDateString();

        return $query
            ->where('status', 'active')
            ->whereHas(
                'classEnrollments',
                function (Builder $enrollments) use ($teacherId, $today) {
                    $enrollments
                        ->where('status', 'active')
                        ->whereDate('enrolled_at', '<=', $today)
                        ->where(function (Builder $dates) use ($today) {
                            $dates->whereNull('ended_at')
                                ->orWhereDate('ended_at', '>=', $today);
                        })
                        ->whereHas('schoolClass')
                        ->where(function (Builder $access) use (
                            $teacherId,
                            $today
                        ) {
                            $access->whereExists(
                                function (QueryBuilder $assignments) use (
                                    $teacherId,
                                    $today
                                ) {
                                    $assignments
                                        ->selectRaw('1')
                                        ->from('teacher_class_assignments as tca')
                                        ->whereColumn(
                                            'tca.class_id',
                                            'student_class_enrollments.class_id'
                                        )
                                        ->where(
                                            'tca.teacher_profile_id',
                                            $teacherId
                                        )
                                        ->where('tca.status', 'active')
                                        ->whereDate(
                                            'tca.start_date',
                                            '<=',
                                            $today
                                        )
                                        ->where(function (QueryBuilder $dates) use ($today) {
                                            $dates->whereNull('tca.end_date')
                                                ->orWhereDate(
                                                    'tca.end_date',
                                                    '>=',
                                                    $today
                                                );
                                        });
                                }
                            );

                            $access->orWhereExists(
                                function (QueryBuilder $assignments) use (
                                    $teacherId,
                                    $today
                                ) {
                                    $assignments
                                        ->selectRaw('1')
                                        ->from('teacher_subject_class as tsc')
                                        ->whereColumn(
                                            'tsc.class_id',
                                            'student_class_enrollments.class_id'
                                        )
                                        ->whereColumn(
                                            'tsc.academic_session_id',
                                            'student_class_enrollments.academic_session_id'
                                        )
                                        ->whereColumn(
                                            'tsc.semester_id',
                                            'student_class_enrollments.semester_id'
                                        )
                                        ->where(
                                            'tsc.teacher_profile_id',
                                            $teacherId
                                        )
                                        ->where('tsc.status', 'active')
                                        ->where(function (QueryBuilder $dates) use ($today) {
                                            $dates->whereNull('tsc.start_date')
                                                ->orWhereDate(
                                                    'tsc.start_date',
                                                    '<=',
                                                    $today
                                                );
                                        })
                                        ->where(function (QueryBuilder $dates) use ($today) {
                                            $dates->whereNull('tsc.end_date')
                                                ->orWhereDate(
                                                    'tsc.end_date',
                                                    '>=',
                                                    $today
                                                );
                                        });
                                }
                            );
                        });
                }
            );
    }

    public function canView(
        User $user,
        StudentProfile $student
    ): bool {
        if (! $student->exists) {
            return false;
        }

        return $this->visibleTo($user)
            ->whereKey($student->getKey())
            ->exists();
    }
}