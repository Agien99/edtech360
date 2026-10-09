<?php
namespace App\Services;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SchoolClassManagementService
{
    public function create(User $actor, array $data): SchoolClass
    {
        return DB::transaction(function () use ($actor, $data) {
            $session = AcademicSession::query()->lockForUpdate()->findOrFail($data['academic_session_id']);
            $batch = Batch::query()->lockForUpdate()->findOrFail($data['batch_id']);
            $this->ensureEligible($session, $batch);
            $code = strtoupper(trim($data['code']));
            $this->ensureUniqueCode((int) $session->id, $code);

            $schoolClass = SchoolClass::create([
                'academic_session_id' => $session->id,
                'batch_id' => $batch->id,
                'code' => $code,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'status' => 'active',
            ]);
            $this->audit($actor, $schoolClass, 'create', null, $schoolClass->getAttributes());
            return $schoolClass;
        });
    }

    public function update(User $actor, SchoolClass $schoolClass, array $data): SchoolClass
    {
        return DB::transaction(function () use ($actor, $schoolClass, $data) {
            // Match create() lock order: academic session, batch, then class.
            $session = AcademicSession::query()->lockForUpdate()->findOrFail($data['academic_session_id']);
            $batch = Batch::query()->lockForUpdate()->findOrFail($data['batch_id']);
            $schoolClass = SchoolClass::query()->lockForUpdate()->findOrFail($schoolClass->id);
            if ($schoolClass->status !== 'active') {
                throw ValidationException::withMessages(['school_class' => 'Only active classes can be edited.']);
            }
            $this->ensureEligible($session, $batch);
            $relationshipChanged = (int) $schoolClass->academic_session_id !== (int) $session->id
                || (int) $schoolClass->batch_id !== (int) $batch->id;
            if ($relationshipChanged && $this->hasAcademicDependencies($schoolClass)) {
                throw ValidationException::withMessages(['academic_session_id' => 'Cannot reassign a class with existing academic records.']);
            }
            $code = strtoupper(trim($data['code']));
            $this->ensureUniqueCode((int) $session->id, $code, $schoolClass->id);
            $before = $schoolClass->getAttributes();
            $schoolClass->update([
                'academic_session_id' => $session->id,
                'batch_id' => $batch->id,
                'code' => $code,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
            ]);
            $this->audit($actor, $schoolClass, 'update', $before, $schoolClass->getAttributes());
            return $schoolClass;
        });
    }

    public function deactivate(User $actor, SchoolClass $schoolClass): SchoolClass
    {
        return DB::transaction(function () use ($actor, $schoolClass) {
            $schoolClass = SchoolClass::query()->lockForUpdate()->findOrFail($schoolClass->id);
            if ($schoolClass->status !== 'active') {
                throw ValidationException::withMessages(['status' => 'Only active classes can be deactivated.']);
            }
            // Active enrollments and assignments must be closed first.
            $hasActiveEnrollment = $schoolClass->studentEnrollments()->where('status', 'active')->whereNull('ended_at')->exists();
            $hasActiveClassAssignment = $schoolClass->teacherClassAssignments()->where('status', 'active')->exists();
            $hasActiveTeachingAssignment = $schoolClass->teachingAssignments()->where('status', 'active')->exists();
            if ($hasActiveEnrollment || $hasActiveClassAssignment || $hasActiveTeachingAssignment) {
                throw ValidationException::withMessages(['status' => 'Close active student enrollments and teacher assignments before deactivating this class.']);
            }
            $before = $schoolClass->getAttributes();
            $schoolClass->update(['status' => 'inactive']);
            $this->audit($actor, $schoolClass, 'change_status', $before, $schoolClass->getAttributes());
            return $schoolClass;
        });
    }

    private function ensureEligible(AcademicSession $session, Batch $batch): void
    {
        if (! in_array($session->status, ['planned', 'active'], true)) {
            throw ValidationException::withMessages(['academic_session_id' => 'The academic session must be planned or active.']);
        }
        if ($batch->status !== 'active') {
            throw ValidationException::withMessages(['batch_id' => 'The batch must be active.']);
        }
    }

    private function ensureUniqueCode(int $sessionId, string $code, ?int $exceptId = null): void
    {
        $found = SchoolClass::withTrashed()->where('academic_session_id', $sessionId)
            ->where('code', $code)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
        if ($found) {
            throw ValidationException::withMessages(['code' => 'This class code already exists in the selected academic session.']);
        }
    }

    private function hasAcademicDependencies(SchoolClass $schoolClass): bool
    {
        return $schoolClass->studentEnrollments()->exists()
            || $schoolClass->teacherClassAssignments()->exists()
            || $schoolClass->teachingAssignments()->exists()
            || $schoolClass->attendanceSessions()->exists()
            || $schoolClass->timetables()->exists();
    }

    private function audit(User $actor, SchoolClass $schoolClass, string $action, ?array $before, array $after): void
    {
        $request = app()->bound('request') ? request() : null;
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actor->id,
            'actor_identifier' => $actor->email,
            'actor_role_snapshot' => $actor->getRoleNames()->implode(', '),
            'event_category' => 'academic_management',
            'action' => $action,
            'module' => 'classes',
            'auditable_type' => $schoolClass->getMorphClass(),
            'auditable_id' => $schoolClass->id,
            'old_values' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'new_values' => json_encode($after, JSON_THROW_ON_ERROR),
            'metadata' => null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_id' => $request?->headers->get('X-Request-ID') ?? (string) Str::uuid(),
            'outcome' => 'success',
            'created_at' => now(),
        ]);
    }
}
