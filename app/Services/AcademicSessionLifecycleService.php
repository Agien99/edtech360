<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AcademicSessionLifecycleService
{
    private const LOCK_NAME = 'edtech360_academic_lifecycle';

    private function synchronized(callable $callback): mixed
    {
        $result = DB::selectOne(
            'SELECT GET_LOCK(?, 10) AS acquired',
            [self::LOCK_NAME]
        );

        if ((int) ($result->acquired ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'lifecycle' => 'Another academic operation is in progress. Please retry.',
            ]);
        }

        try {
            return DB::transaction($callback, 3);
        } finally {
            DB::selectOne(
                'SELECT RELEASE_LOCK(?) AS released',
                [self::LOCK_NAME]
            );
        }
    }

    public function activate(User $actor, AcademicSession $session): void
    {
        $this->synchronized(function () use ($actor, $session) {
            $session = AcademicSession::query()
                ->lockForUpdate()
                ->findOrFail($session->id);

            if ($session->status !== 'planned') {
                $this->fail('Only planned sessions can be activated.');
            }

            if (AcademicSession::query()
                ->where('is_current', true)
                ->whereKeyNot($session->id)
                ->exists()) {
                $this->fail('Close the current academic session first.');
            }

            $semesters = $session->semesters()
                ->orderBy('number')
                ->lockForUpdate()
                ->get();

            if (
                $semesters->count() !== 3
                || $semesters->pluck('number')->all() !== [1, 2, 3]
            ) {
                $this->fail('Configure Semesters 1, 2 and 3 before activation.');
            }

            $start = $session->start_date->toDateString();
            $end = $session->end_date->toDateString();
            $previousEnd = null;

            foreach ($semesters as $semester) {
                $semesterStart = $semester->start_date->toDateString();
                $semesterEnd = $semester->end_date->toDateString();

                if ($semester->status !== 'planned') {
                    $this->fail('All semesters must be planned before activation.');
                }

                if (
                    $semesterStart < $start
                    || $semesterEnd > $end
                    || $semesterStart > $semesterEnd
                    || ($previousEnd !== null && $semesterStart <= $previousEnd)
                ) {
                    $this->fail('Semester dates are invalid or overlapping.');
                }

                $previousEnd = $semesterEnd;
            }

            $old = $this->snapshot($session);

            $session->update([
                'status' => 'active',
                'is_current' => true,
            ]);

            $this->audit(
                $actor,
                $session,
                'activate',
                'academic_sessions',
                $old,
                $this->snapshot($session)
            );

            $first = $semesters->first();
            $oldSemester = $this->snapshot($first);

            $first->update(['status' => 'active']);

            $this->audit(
                $actor,
                $first,
                'activate',
                'semesters',
                $oldSemester,
                $this->snapshot($first)
            );
        });
    }

    public function advanceSemester(
        User $actor,
        AcademicSession $session
    ): void {
        $this->synchronized(function () use ($actor, $session) {
            $session = AcademicSession::query()
                ->lockForUpdate()
                ->findOrFail($session->id);

            if ($session->status !== 'active' || ! $session->is_current) {
                $this->fail('The academic session must be current and active.');
            }

            $semesters = $session->semesters()
                ->orderBy('number')
                ->lockForUpdate()
                ->get();

            if ($semesters->count() !== 3) {
                $this->fail('Expected exactly three semesters.');
            }

            $active = $semesters->firstWhere('status', 'active');

            if (! $active) {
                $this->fail('There is no active semester to advance.');
            }

            $next = $semesters->firstWhere('number', $active->number + 1);

            if ($semesters->where('status', 'active')->count() !== 1) {
                $this->fail('Only one semester may be active.');
            }

            if ($next && $next->status !== 'planned') {
                $this->fail('The next semester is not planned.');
            }

            $oldActive = $this->snapshot($active);
            $active->update(['status' => 'closed']);

            $this->audit(
                $actor, $active, 'close',
                'semesters', $oldActive, $this->snapshot($active)
            );

            if ($next) {
                $oldNext = $this->snapshot($next);
                $next->update(['status' => 'active']);

                $this->audit(
                    $actor, $next, 'activate',
                    'semesters', $oldNext, $this->snapshot($next)
                );
            }
        });
    }

    public function close(User $actor, AcademicSession $session): void
    {
        $this->synchronized(function () use ($actor, $session) {
            $session = AcademicSession::query()
                ->lockForUpdate()
                ->findOrFail($session->id);

            if ($session->status !== 'active' || ! $session->is_current) {
                $this->fail('Only the current active session can be closed.');
            }

            if (
                $session->semesters()->count() !== 3
                || $session->semesters()->where('status', '!=', 'closed')->exists()
            ) {
                $this->fail('All three semesters must be closed first.');
            }

            $old = $this->snapshot($session);

            $session->update([
                'status' => 'closed',
                'is_current' => false,
            ]);

            $this->audit(
                $actor,
                $session,
                'close',
                'academic_sessions',
                $old,
                $this->snapshot($session)
            );
        });
    }

    private function snapshot($model): array
    {
        return $model->getAttributes();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'lifecycle' => $message,
        ]);
    }

    private function audit(
        User $actor,
        $model,
        string $action,
        string $module,
        array $old,
        array $new
    ): void {
        $request = app()->bound('request') ? request() : null;

        DB::table('audit_logs')->insert([
            'actor_user_id' => $actor->id,
            'actor_identifier' => $actor->email,
            'actor_role_snapshot' => $actor->getRoleNames()->implode(', '),
            'event_category' => 'academic_management',
            'action' => $action,
            'module' => $module,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->id,
            'old_values' => json_encode($old, JSON_THROW_ON_ERROR),
            'new_values' => json_encode($new, JSON_THROW_ON_ERROR),
            'metadata' => null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_id' => $request?->headers->get('X-Request-ID')
                ?? (string) Str::uuid(),
            'outcome' => 'success',
            'created_at' => now(),
        ]);
    }
}