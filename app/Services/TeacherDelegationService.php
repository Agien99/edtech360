<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherDelegationService
{
    public function setStudentEditing(User $actor, User $teacherUser, bool $enabled): void
    {
        if (! $actor->is_active || ! $actor->hasAnyRole(['super_admin', 'school_admin'])
            || ! $actor->can('roles.assign')) {
            abort(403);
        }

        DB::transaction(function () use ($actor, $teacherUser, $enabled): void {
            $locked = User::query()->whereKey($teacherUser->id)->lockForUpdate()->firstOrFail();
            if (! $locked->is_active || ! $locked->hasRole('assistant_class_teacher')
                || $locked->teacherProfile?->status !== 'active') {
                throw ValidationException::withMessages([
                    'teacher' => 'An active assistant class teacher account is required.',
                ]);
            }

            // Only directly granted privilege is delegated; class assignment
            // restrictions are enforced independently in SchoolClassAccess.
            $hadPermission = $locked->hasDirectPermission('students.update');
            if ($hadPermission === $enabled) {
                return;
            }

            if ($enabled) {
                $locked->givePermissionTo('students.update');
            } else {
                $locked->revokePermissionTo('students.update');
            }

            DB::table('audit_logs')->insert([
                'actor_user_id' => $actor->id,
                'actor_identifier' => $actor->email,
                'actor_role_snapshot' => $actor->getRoleNames()->implode(', '),
                'event_category' => 'access_control',
                'action' => $enabled ? 'delegate_students_update' : 'revoke_students_update',
                'module' => 'roles',
                'auditable_type' => $locked->getMorphClass(),
                'auditable_id' => $locked->id,
                'old_values' => json_encode(['direct_students_update' => $hadPermission], JSON_THROW_ON_ERROR),
                'new_values' => json_encode(['direct_students_update' => $enabled], JSON_THROW_ON_ERROR),
                'metadata' => json_encode(['permission' => 'students.update'], JSON_THROW_ON_ERROR),
                'request_id' => (string) Str::uuid(),
                'outcome' => 'success',
                'created_at' => now(),
            ]);
        }, 3);
    }
}
