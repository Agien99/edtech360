<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcademicSessionService
{
    public function create(
        User $actor,
        array $data
    ): AcademicSession {
        return DB::transaction(function () use ($actor, $data) {
            $session = AcademicSession::create([
                'name' => $data['name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_current' => false,
                'status' => 'planned',
            ]);

            $request = app()->bound('request')
                ? request()
                : null;

            DB::table('audit_logs')->insert([
                'actor_user_id' => $actor->id,
                'actor_identifier' => $actor->email,
                'actor_role_snapshot' => $actor
                    ->getRoleNames()
                    ->implode(', '),
                'event_category' => 'academic_management',
                'action' => 'create',
                'module' => 'academic_sessions',
                'auditable_type' => $session->getMorphClass(),
                'auditable_id' => $session->id,
                'old_values' => null,
                'new_values' => json_encode(
                    [
                        'name' => $session->name,
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'is_current' => false,
                        'status' => 'planned',
                    ],
                    JSON_THROW_ON_ERROR
                ),
                'metadata' => null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'request_id' => $request?->headers
                    ->get('X-Request-ID')
                    ?? (string) Str::uuid(),
                'outcome' => 'success',
                'created_at' => now(),
            ]);

            return $session;
        });
    }
}