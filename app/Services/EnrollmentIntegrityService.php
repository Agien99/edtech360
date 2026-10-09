<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\StudentClassEnrollment;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentIntegrityService
{
    // A shared writer for future enrollment-creation controllers.
    // All competing enrollment writers MUST lock the same student row.
    public function enroll(StudentProfile $student, SchoolClass $schoolClass, int $semesterId): StudentClassEnrollment
    {
        return DB::transaction(function () use ($student, $schoolClass, $semesterId): StudentClassEnrollment {
            $locked = StudentProfile::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'active' || $schoolClass->status !== 'active'
                || ! DB::table('semesters')->where('id', $semesterId)
                    ->where('academic_session_id', $schoolClass->academic_session_id)->exists()) {
                throw ValidationException::withMessages(['enrollment' => 'Invalid student, class or semester.']);
            }

            $today = now()->toDateString();
            $overlap = StudentClassEnrollment::query()
                ->where('student_profile_id', $locked->id)
                ->where('academic_session_id', $schoolClass->academic_session_id)
                ->where('semester_id', $semesterId)
                ->where('status', 'active')
                ->where(function ($q) use ($today) {
                    $q->whereNull('ended_at')->orWhereDate('ended_at', '>=', $today);
                })
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'enrollment' => 'An active enrollment already exists in this semester.',
                ]);
            }

            return StudentClassEnrollment::create([
                'student_profile_id' => $locked->id,
                'academic_session_id' => $schoolClass->academic_session_id,
                'class_id' => $schoolClass->id,
                'semester_id' => $semesterId,
                'enrolled_at' => $today,
                'ended_at' => null,
                'status' => 'active',
            ]);
        }, 3);
    }
}
