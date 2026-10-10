<?php
namespace App\Services;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicSessionService
{
    public function create(User $actor, array $data): AcademicSession
    {
        return DB::transaction(function () use ($actor, $data) {
            $session = AcademicSession::create([
                'name' => $data['name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_current' => false,
                'status' => 'planned',
            ]);
            // Intake year comes from actual session start. Name may span calendar years.
            $year = (int) substr($data['start_date'], 0, 4);
            $code = 'F6-' . $year;
            if (Batch::withTrashed()->where('code', $code)->exists()) {
                // Avoid overwriting an unrelated historic code.
                $code = 'F6-' . $year . '-S' . $session->id;
            }
            $batch = Batch::create([
                'academic_session_id' => $session->id,
                'code' => $code,
                'name' => 'Form 6 Intake ' . $year,
                'intake_year' => $year,
                'start_date' => null,
                'expected_end_date' => null,
                'status' => 'active',
            ]);
            $this->audit($actor, $session, 'academic_sessions', 'create');
            $this->audit($actor, $batch, 'batches', 'create');
            return $session;
        });
    }

    private function audit(User $actor, $model, string $module, string $action): void
    {
        $r = app()->bound('request') ? request() : null;
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actor->id, 'actor_identifier' => $actor->email,
            'actor_role_snapshot' => $actor->getRoleNames()->implode(', '),
            'event_category' => 'academic_management', 'action' => $action, 'module' => $module,
            'auditable_type' => $model->getMorphClass(), 'auditable_id' => $model->id,
            'old_values' => null, 'new_values' => json_encode($model->getAttributes(), JSON_THROW_ON_ERROR),
            'metadata' => null, 'ip_address' => $r?->ip(), 'user_agent' => $r?->userAgent(),
            'request_id' => $r?->headers->get('X-Request-ID') ?? (string) Str::uuid(),
            'outcome' => 'success', 'created_at' => now(),
        ]);
    }
}
