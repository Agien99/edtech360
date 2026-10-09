<?php

namespace App\Services;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentAuditService
{
    public function record(
        User $actor,
        StudentProfile $student,
        string $action,
        array $oldValues,
        array $newValues,
        array $metadata = []
    ): void {
        $request = app()->bound('request') ? request() : null;

        DB::table('audit_logs')->insert([
            'actor_user_id' => $actor->id,
            'actor_identifier' => $actor->email,
            'actor_role_snapshot' => $actor->getRoleNames()
                ->implode(', '),
            'event_category' => 'student_management',
            'action' => $action,
            'module' => 'students',
            'auditable_type' => $student->getMorphClass(),
            'auditable_id' => $student->id,
            'old_values' => json_encode(
                $oldValues,
                JSON_THROW_ON_ERROR
            ),
            'new_values' => json_encode(
                $newValues,
                JSON_THROW_ON_ERROR
            ),
            'metadata' => json_encode(
                $metadata,
                JSON_THROW_ON_ERROR
            ),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_id' => $request?->headers->get('X-Request-ID')
                ?? (string) Str::uuid(),
            'outcome' => 'success',
            'created_at' => now(),
        ]);
    }
}