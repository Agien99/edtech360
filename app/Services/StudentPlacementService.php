<?php
namespace App\Services;

use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\StudentBatch;
use App\Models\StudentClassEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentPlacementService
{
    public function place(User $actor, array $data): StudentClassEnrollment
    {
        return DB::transaction(function () use ($actor, $data) {
            $student = StudentProfile::query()->lockForUpdate()->findOrFail($data['student_profile_id']);
            if ($student->status !== 'active') {
                throw ValidationException::withMessages(['student_profile_id' => 'An active student is required.']);
            }
            $class = SchoolClass::query()->findOrFail($data['class_id']);
            $semester = Semester::query()->findOrFail($data['semester_id']);
            $session = AcademicSession::query()->findOrFail($class->academic_session_id);
            $batch = $session->batch;
            if ($class->status !== 'active' || !in_array($session->status, ['planned', 'active'], true)
                || !$batch || $batch->status !== 'active' || (int)$class->batch_id !== (int)$batch->id) {
                throw ValidationException::withMessages(['class_id' => 'Select an active class belonging to an open session and its batch.']);
            }
            if ((int)$semester->academic_session_id !== (int)$session->id || $semester->status === 'closed') {
                throw ValidationException::withMessages(['semester_id' => 'The semester must belong to this class and must not be closed.']);
            }
            $date = $data['enrolled_at'];
            if ($date < $session->start_date->toDateString() || $date > $session->end_date->toDateString()
                || $date < $semester->start_date->toDateString() || $date > $semester->end_date->toDateString()) {
                throw ValidationException::withMessages(['enrolled_at' => 'Enrollment date must fall inside the chosen semester.']);
            }
            $membership = StudentBatch::query()->where('student_profile_id', $student->id)->where('batch_id', $batch->id)->lockForUpdate()->first();
            if ($membership && ($membership->status !== 'active' || $membership->left_at !== null)) {
                throw ValidationException::withMessages(['student_profile_id' => 'Existing historical batch membership cannot be reactivated automatically.']);
            }
            if (!$membership) {
                $membership = StudentBatch::create(['student_profile_id' => $student->id, 'batch_id' => $batch->id,
                    'joined_at' => $date, 'left_at' => null, 'status' => 'active']);
                $this->audit($actor, $membership, 'student_batch', 'create');
            } elseif ($date < $membership->joined_at->toDateString()) {
                throw ValidationException::withMessages(['enrolled_at' => 'Enrollment cannot precede the student joining this intake.']);
            }
            // One active enrollment in a semester, regardless of class.
            if (StudentClassEnrollment::query()->where('student_profile_id', $student->id)
                ->where('semester_id', $semester->id)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['student_profile_id' => 'This student already has an active enrollment in the chosen semester.']);
            }
            $enrollment = StudentClassEnrollment::create(['student_profile_id' => $student->id,
                'academic_session_id' => $session->id, 'class_id' => $class->id,
                'semester_id' => $semester->id, 'enrolled_at' => $date,
                'ended_at' => null, 'status' => 'active']);
            $this->audit($actor, $enrollment, 'student_class_enrollments', 'create');
            return $enrollment;
        }, 3);
    }

    private function audit(User $actor, $model, string $module, string $action): void
    {
        $request = app()->bound('request') ? request() : null;
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actor->id, 'actor_identifier' => $actor->email,
            'actor_role_snapshot' => $actor->getRoleNames()->implode(', '),
            'event_category' => 'academic_management', 'action' => $action, 'module' => $module,
            'auditable_type' => $model->getMorphClass(), 'auditable_id' => $model->id,
            'old_values' => null, 'new_values' => json_encode($model->getAttributes(), JSON_THROW_ON_ERROR),
            'metadata' => null, 'ip_address' => $request?->ip(), 'user_agent' => $request?->userAgent(),
            'request_id' => $request?->headers->get('X-Request-ID') ?? (string) Str::uuid(),
            'outcome' => 'success', 'created_at' => now(),
        ]);
    }
}
