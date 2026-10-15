<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\StudentClassEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StudentSelfClassChangeService
{
    public function change(User $actor, int $targetClassId): StudentClassEnrollment
    {
        abort_unless($actor->is_active && $actor->hasRole('student'), 403);
        return DB::transaction(function () use ($actor, $targetClassId) {
            $student = StudentProfile::query()->where('user_id', $actor->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('viewOwn', $student);
            $now = now();
            $today = $now->toDateString();
            $current = StudentClassEnrollment::query()->where('student_profile_id', $student->id)
                ->where('status', 'active')->whereDate('enrolled_at', '<=', $today)
                ->where(function ($q) use ($today) { $q->whereNull('ended_at')->orWhereDate('ended_at', '>=', $today); })
                ->lockForUpdate()->get();
            if ($current->count() !== 1) {
                throw ValidationException::withMessages(['class_id' => 'Exactly one current class enrollment is required.']);
            }
            $enrollment = $current->first();
            $window = DB::table('class_change_windows')->where('academic_session_id', $enrollment->academic_session_id)->first();
            if (! $window || ! $window->is_enabled || $now->lt($window->opens_at) || $now->gt($window->closes_at)) {
                throw ValidationException::withMessages(['class_id' => 'The class change period is not open for your intake.']);
            }
            $target = SchoolClass::query()->whereKey($targetClassId)->where('status', 'active')->first();
            if (! $target || (int) $target->academic_session_id !== (int) $enrollment->academic_session_id ||
                (int) $target->batch_id !== (int) $enrollment->schoolClass->batch_id) {
                throw ValidationException::withMessages(['class_id' => 'Choose an active class in your current intake.']);
            }
            if ((int) $target->id === (int) $enrollment->class_id) {
                throw ValidationException::withMessages(['class_id' => 'You are already in that class.']);
            }
            $other = StudentClassEnrollment::query()->where('student_profile_id', $student->id)
                ->where('semester_id', $enrollment->semester_id)->where('status', 'active')->whereKeyNot($enrollment->id)->exists();
            if ($other) {
                throw ValidationException::withMessages(['class_id' => 'Conflicting active enrollment found. Ask your administrator.']);
            }
            $old = $enrollment->only(['id', 'class_id', 'semester_id', 'status']);
            $enrollment->update(['status' => 'transferred', 'ended_at' => $today]);
            $replacement = StudentClassEnrollment::create([
                'student_profile_id' => $student->id,
                'academic_session_id' => $enrollment->academic_session_id,
                'class_id' => $target->id,
                'semester_id' => $enrollment->semester_id,
                'enrolled_at' => $today, 'ended_at' => null, 'status' => 'active',
            ]);
            app(StudentAuditService::class)->record($actor, $student, 'self_class_change', $old,
                ['id' => $replacement->id, 'class_id' => $target->id, 'semester_id' => $replacement->semester_id, 'status' => 'active'],
                ['academic_session_id' => $enrollment->academic_session_id, 'window_id' => $window->id]);
            return $replacement;
        }, 3);
    }
}
