<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SemesterManagementService
{
    public function create(
        User $actor,
        AcademicSession $session,
        array $data
    ): Semester {
        return DB::transaction(function () use (
            $actor, $session, $data
        ) {
            $session = AcademicSession::query()
                ->lockForUpdate()
                ->findOrFail($session->id);

            $this->ensureEditableSession($session);
            $this->validatePeriod($session, $data);

            $this->validateSchedule($session, $data);

            $semester = $session->semesters()->create([
                'number' => (int) $data['number'],
                'name' => 'Semester ' . $data['number'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'planned',
            ]);

            $this->audit(
                $actor,
                $semester,
                'create',
                null,
                $semester->getAttributes()
            );

            return $semester;
        });
    }

    public function update(
        User $actor,
        AcademicSession $session,
        Semester $semester,
        array $data
    ): Semester {
        return DB::transaction(function () use (
            $actor, $session, $semester, $data
        ) {
            $session = AcademicSession::query()
                ->lockForUpdate()
                ->findOrFail($session->id);

            $semester = Semester::query()
                ->where('academic_session_id', $session->id)
                ->lockForUpdate()
                ->findOrFail($semester->id);

            $this->ensureEditableSession($session);

            if ($semester->status !== 'planned') {
                throw ValidationException::withMessages([
                    'semester' =>
                        'Only planned semesters can be edited.',
                ]);
            }

            if (
                $semester->studentClassEnrollments()->exists()
                || $semester->teachingAssignments()->exists()
                || $semester->timetables()->exists()
            ) {
                throw ValidationException::withMessages([
                    'semester' =>
                        'This semester has associated academic records and cannot be edited.',
                ]);
            }

            $this->validatePeriod($session, $data);

            $this->validateSchedule(
                $session,
                $data,
                $semester->id
            );

            $old = $semester->getAttributes();

            $semester->update([
                'number' => (int) $data['number'],
                'name' => 'Semester ' . $data['number'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            $this->audit(
                $actor,
                $semester,
                'update',
                $old,
                $semester->getAttributes()
            );

            return $semester;
        });
    }

    private function ensureEditableSession(
        AcademicSession $session
    ): void {
        if ($session->status !== 'planned') {
            throw ValidationException::withMessages([
                'academic_session' =>
                    'Semester configuration is only allowed while the academic session is planned.',
            ]);
        }
    }

    private function validatePeriod(
        AcademicSession $session,
        array $data
    ): void {
        $sessionStart = $session->start_date->toDateString();
        $sessionEnd = $session->end_date->toDateString();

        if (
            $data['start_date'] < $sessionStart
            || $data['end_date'] > $sessionEnd
        ) {
            throw ValidationException::withMessages([
                'start_date' =>
                    'Semester dates must fall within the academic session.',
            ]);
        }
    }

    private function validateSchedule(
        AcademicSession $session,
        array $data,
        ?int $exceptId = null
    ): void {
        $number = (int) $data['number'];

        $siblings = $session->semesters()
            ->when(
                $exceptId !== null,
                fn ($query) => $query->whereKeyNot($exceptId)
            )
            ->get();

        if ($siblings->contains('number', $number)) {
            throw ValidationException::withMessages([
                'number' =>
                    'This semester number already exists in the session.',
            ]);
        }

        foreach ($siblings as $other) {
            $overlaps =
                $data['start_date'] <= $other->end_date->toDateString()
                && $data['end_date'] >= $other->start_date->toDateString();

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'start_date' =>
                        'Semester dates overlap with another semester.',
                ]);
            }

            if (
                ($number < $other->number
                    && $data['end_date'] >= $other->start_date->toDateString())
                || ($number > $other->number
                    && $data['start_date'] <= $other->end_date->toDateString())
            ) {
                throw ValidationException::withMessages([
                    'start_date' =>
                        'Semester dates must follow semester number order.',
                ]);
            }
        }
    }

    private function audit(
        User $actor,
        Semester $semester,
        string $action,
        ?array $oldValues,
        array $newValues
    ): void {
        $request = app()->bound('request')
            ? request()
            : null;

        DB::table('audit_logs')->insert([
            'actor_user_id' => $actor->id,
            'actor_identifier' => $actor->email,
            'actor_role_snapshot' =>
                $actor->getRoleNames()->implode(', '),
            'event_category' => 'academic_management',
            'action' => $action,
            'module' => 'semesters',
            'auditable_type' => $semester->getMorphClass(),
            'auditable_id' => $semester->id,
            'old_values' => $oldValues === null
                ? null
                : json_encode($oldValues, JSON_THROW_ON_ERROR),
            'new_values' => json_encode(
                $newValues,
                JSON_THROW_ON_ERROR
            ),
            'metadata' => null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_id' =>
                $request?->headers->get('X-Request-ID')
                ?? (string) Str::uuid(),
            'outcome' => 'success',
            'created_at' => now(),
        ]);
    }
}