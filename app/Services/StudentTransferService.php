<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\StudentClassEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StudentTransferService
{
    public function __construct(
        private readonly StudentAuditService $audit
    ) {}

    public function transfer(
        User $actor,
        StudentProfile $student,
        int $targetClassId
    ): StudentClassEnrollment {
        Gate::forUser($actor)->authorize('transfer', $student);

        return DB::transaction(function () use (
            $actor,
            $student,
            $targetClassId
        ): StudentClassEnrollment {
            // Lock the parent student row, which must also be locked
            // by other enrollment-writing workflows.
            $lockedStudent = StudentProfile::query()
                ->whereKey($student->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedStudent->status !== 'active') {
                throw ValidationException::withMessages([
                    'student' => 'The student is not active.',
                ]);
            }

            $today = now()->toDateString();

            $activeEnrollments = StudentClassEnrollment::query()
                ->where('student_profile_id', $lockedStudent->id)
                ->where('status', 'active')
                ->whereDate('enrolled_at', '<=', $today)
                ->where(function (Builder $query) use ($today) {
                    $query->whereNull('ended_at')
                        ->orWhereDate('ended_at', '>=', $today);
                })
                ->lockForUpdate()
                ->get();

            if ($activeEnrollments->count() !== 1) {
                throw ValidationException::withMessages([
                    'enrollment' => 'The student must have exactly one current active enrollment.',
                ]);
            }

            $current = $activeEnrollments->first();

            $targetClass = SchoolClass::query()
                ->whereKey($targetClassId)
                ->where('status', 'active')
                ->first();

            if (! $targetClass) {
                throw ValidationException::withMessages([
                    'class_id' => 'The target class is unavailable.',
                ]);
            }

            if ((int) $current->class_id === (int) $targetClass->id) {
                throw ValidationException::withMessages([
                    'class_id' => 'The student is already in this class.',
                ]);
            }

            if (
                (int) $current->academic_session_id
                !== (int) $targetClass->academic_session_id
            ) {
                throw ValidationException::withMessages([
                    'class_id' => 'Both classes must belong to the same academic session.',
                ]);
            }

            if (
                (int) $current->schoolClass->batch_id
                !== (int) $targetClass->batch_id
            ) {
                throw ValidationException::withMessages([
                    'class_id' => 'Both classes must belong to the same batch.',
                ]);
            }

            $duplicate = StudentClassEnrollment::query()
                ->where('student_profile_id', $lockedStudent->id)
                ->where('academic_session_id', $current->academic_session_id)
                ->where('semester_id', $current->semester_id)
                ->where('class_id', $targetClass->id)
                ->where('status', 'active')
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'class_id' => 'An active target enrollment already exists.',
                ]);
            }

            $previousEnrollmentId = $current->id;
            $previousClassId = $current->class_id;

            $current->update([
                'status' => 'transferred',
                'ended_at' => $today,
            ]);

            $newEnrollment = StudentClassEnrollment::create([
                'student_profile_id' => $lockedStudent->id,
                'academic_session_id' => $current->academic_session_id,
                'class_id' => $targetClass->id,
                'semester_id' => $current->semester_id,
                'enrolled_at' => $today,
                'ended_at' => null,
                'status' => 'active',
            ]);

            $this->audit->record(
                $actor,
                $lockedStudent,
                'transfer',
                [
                    'enrollment_id' => $previousEnrollmentId,
                    'class_id' => $previousClassId,
                    'status' => 'active',
                ],
                [
                    'enrollment_id' => $newEnrollment->id,
                    'class_id' => $targetClass->id,
                    'status' => 'active',
                ],
                [
                    'academic_session_id' => $current->academic_session_id,
                    'semester_id' => $current->semester_id,
                    'transfer_date' => $today,
                ]
            );

            return $newEnrollment;
        }, 3);
    }
}