<?php
namespace App\Services;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicSessionLifecycleService
{
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['lifecycle' => $message]);
    }
    private function run(AcademicSession $session, callable $operation): void
    {
        DB::transaction(function () use ($session, $operation) {
            $session = AcademicSession::query()->lockForUpdate()->findOrFail($session->id);
            $operation($session);
        }, 3);
    }
    public function activate(User $actor, AcademicSession $session): void
    {
        $this->run($session, function (AcademicSession $session) use ($actor) {
            if ($session->status !== 'planned') $this->fail('Only planned sessions can be activated.');
            $batch = $session->batch()->lockForUpdate()->first();
            if (!$batch || $batch->status !== 'active') $this->fail('An active linked batch is required.');
            $semesters = $session->semesters()->orderBy('number')->lockForUpdate()->get();
            if ($semesters->count() !== 3 || $semesters->pluck('number')->all() !== [1, 2, 3]) {
                $this->fail('Configure Semesters 1, 2 and 3 before activation.');
            }
            $previous = null;
            foreach ($semesters as $semester) {
                if ($semester->status !== 'planned'
                    || $semester->start_date->lt($session->start_date)
                    || $semester->end_date->gt($session->end_date)
                    || $semester->start_date->gt($semester->end_date)
                    || ($previous && $semester->start_date->lte($previous))) {
                    $this->fail('Semester dates are invalid, overlapping or already started.');
                }
                $previous = $semester->end_date;
            }
            $old = $session->getAttributes();
            // is_current is a UI default marker, not a lifecycle lock.
            $session->update(['status' => 'active', 'is_current' => false]);
            $this->audit($actor, $session, 'activate', 'academic_sessions', $old);
            $first = $semesters->first();
            $old = $first->getAttributes();
            $first->update(['status' => 'active']);
            $this->audit($actor, $first, 'activate', 'semesters', $old);
        });
    }
    public function advanceSemester(User $actor, AcademicSession $session): void
    {
        $this->run($session, function (AcademicSession $session) use ($actor) {
            if ($session->status !== 'active') $this->fail('Session must be active.');
            $semesters = $session->semesters()->orderBy('number')->lockForUpdate()->get();
            if ($semesters->count() !== 3 || $semesters->pluck('number')->all() !== [1, 2, 3]) {
                $this->fail('Expected three semesters.');
            }
            $active = $semesters->where('status', 'active');
            if ($active->count() !== 1) $this->fail('Exactly one active semester is required.');
            $current = $active->first();
            $next = $semesters->firstWhere('number', $current->number + 1);
            if ($next && $next->status !== 'planned') $this->fail('Next semester must be planned.');
            $old = $current->getAttributes();
            $current->update(['status' => 'closed']);
            $this->audit($actor, $current, 'close', 'semesters', $old);
            if ($next) {
                $old = $next->getAttributes();
                $next->update(['status' => 'active']);
                $this->audit($actor, $next, 'activate', 'semesters', $old);
            }
        });
    }
    public function close(User $actor, AcademicSession $session): void
    {
        $this->run($session, function (AcademicSession $session) use ($actor) {
            if ($session->status !== 'active') $this->fail('Only active sessions can be closed.');
            if ($session->semesters()->count() !== 3 || $session->semesters()->where('status', '!=', 'closed')->exists()) {
                $this->fail('Close all three semesters first.');
            }
            $batch = $session->batch()->lockForUpdate()->first();
            if (!$batch) $this->fail('Linked batch is missing.');
            // Retain previous protection: no active membership, enrollment, or class.
            if ($batch->studentMemberships()->where('status', 'active')->whereNull('left_at')->exists()
                || $batch->schoolClasses()->where('status', 'active')->exists()
                || DB::table('student_class_enrollments')->join('classes', 'classes.id', '=', 'student_class_enrollments.class_id')
                    ->where('classes.batch_id', $batch->id)->where('student_class_enrollments.status', 'active')
                    ->whereNull('student_class_enrollments.ended_at')->exists()) {
                $this->fail('Close active batch memberships, class enrollments and classes first.');
            }
            if ($batch->status !== 'active' && $batch->status !== 'completed') {
                $this->fail('Batch must be active or completed.');
            }
            if ($batch->status === 'active') {
                $old = $batch->getAttributes();
                $batch->update(['status' => 'completed']);
                $this->audit($actor, $batch, 'change_status', 'batches', $old);
            }
            $old = $session->getAttributes();
            $session->update(['status' => 'closed', 'is_current' => false]);
            $this->audit($actor, $session, 'close', 'academic_sessions', $old);
        });
    }
    private function audit(User $actor, $model, string $action, string $module, array $old): void
    {
        $r = app()->bound('request') ? request() : null;
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actor->id, 'actor_identifier' => $actor->email,
            'actor_role_snapshot' => $actor->getRoleNames()->implode(', '),
            'event_category' => 'academic_management', 'action' => $action, 'module' => $module,
            'auditable_type' => $model->getMorphClass(), 'auditable_id' => $model->id,
            'old_values' => json_encode($old, JSON_THROW_ON_ERROR),
            'new_values' => json_encode($model->getAttributes(), JSON_THROW_ON_ERROR),
            'metadata' => null, 'ip_address' => $r?->ip(), 'user_agent' => $r?->userAgent(),
            'request_id' => $r?->headers->get('X-Request-ID') ?? (string) Str::uuid(),
            'outcome' => 'success', 'created_at' => now(),
        ]);
    }
}
