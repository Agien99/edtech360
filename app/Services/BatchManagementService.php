<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BatchManagementService
{
    public function create(User $actor, array $data): Batch
    {
        return DB::transaction(function () use ($actor, $data) {
            $batch = Batch::create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'intake_year' => $data['intake_year'],
                'start_date' => $data['start_date'] ?? null,
                'expected_end_date' => $data['expected_end_date'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => 'active',
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
                'module' => 'batches',
                'auditable_type' => $batch->getMorphClass(),
                'auditable_id' => $batch->id,
                'old_values' => null,
                'new_values' => json_encode(
                    $batch->getAttributes(),
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

            return $batch;
        });
    }

    /**
     * Update an existing student batch.
     */
    public function update(
        User $actor,
        Batch $batch,
        array $data
    ): Batch {
        return DB::transaction(function () use ($actor, $batch, $data) {
            $batch = Batch::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            if ($batch->status !== 'active') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'batch' =>
                        'Only active batches can be edited.',
                ]);
            }

            $oldValues = [
                'code' => $batch->code,
                'name' => $batch->name,
                'intake_year' => $batch->intake_year,
                'start_date' => $batch->start_date?->toDateString(),
                'expected_end_date' =>
                    $batch->expected_end_date?->toDateString(),
                'description' => $batch->description,
            ];

            $batch->update([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'intake_year' => $data['intake_year'],
                'start_date' => $data['start_date'] ?? null,
                'expected_end_date' =>
                    $data['expected_end_date'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            $newValues = [
                'code' => $batch->code,
                'name' => $batch->name,
                'intake_year' => $batch->intake_year,
                'start_date' => $batch->start_date?->toDateString(),
                'expected_end_date' =>
                    $batch->expected_end_date?->toDateString(),
                'description' => $batch->description,
            ];

            $request = app()->bound('request')
                ? request()
                : null;

            DB::table('audit_logs')->insert([
                'actor_user_id' => $actor->id,
                'actor_identifier' => $actor->email,
                'actor_role_snapshot' =>
                    $actor->getRoleNames()->implode(', '),
                'event_category' => 'academic_management',
                'action' => 'update',
                'module' => 'batches',
                'auditable_type' => $batch->getMorphClass(),
                'auditable_id' => $batch->id,
                'old_values' => json_encode(
                    $oldValues,
                    JSON_THROW_ON_ERROR
                ),
                'new_values' => json_encode(
                    $newValues,
                    JSON_THROW_ON_ERROR
                ),
                'metadata' => null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'request_id' =>
                    $request?->headers->get('X-Request-ID')
                    ?? (string) \Illuminate\Support\Str::uuid(),
                'outcome' => 'success',
                'created_at' => now(),
            ]);

            return $batch;
        });
    }

    /**
     * Change an active batch to completed or inactive.
     */
    public function changeStatus(
        User $actor,
        Batch $batch,
        string $status
    ): Batch {
        return DB::transaction(function () use ($actor, $batch, $status) {
            $batch = Batch::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            if (! in_array($status, ['completed', 'inactive'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Invalid batch status.',
                ]);
            }

            if ($batch->status !== 'active') {
                throw ValidationException::withMessages([
                    'status' => 'Only active batches can change status.',
                ]);
            }

            // Check active student batch memberships.
            $hasActiveMembers = $batch->studentMemberships()
                ->where('status', 'active')
                ->whereNull('left_at')
                ->exists();

            if ($hasActiveMembers) {
                throw ValidationException::withMessages([
                    'status' =>
                        'This batch has active student memberships.',
                ]);
            }

            // Check active class enrollments belonging to this batch.
            $hasActiveEnrollments = DB::table('student_class_enrollments')
                ->join(
                    'classes',
                    'student_class_enrollments.class_id',
                    '=',
                    'classes.id'
                )
                ->where('classes.batch_id', $batch->id)
                ->where('student_class_enrollments.status', 'active')
                ->whereNull('student_class_enrollments.ended_at')
                ->exists();

            if ($hasActiveEnrollments) {
                throw ValidationException::withMessages([
                    'status' =>
                        'This batch has active class enrollments.',
                ]);
            }

            // Prevent disabling batches with active classes.
            $hasActiveClasses = $batch->schoolClasses()
                ->where('status', 'active')
                ->exists();

            if ($hasActiveClasses) {
                throw ValidationException::withMessages([
                    'status' =>
                        'This batch still has active classes.',
                ]);
            }

            $oldValues = [
                'status' => $batch->status,
            ];

            $batch->update([
                'status' => $status,
            ]);

            $request = app()->bound('request')
                ? request()
                : null;

            DB::table('audit_logs')->insert([
                'actor_user_id' => $actor->id,
                'actor_identifier' => $actor->email,
                'actor_role_snapshot' =>
                    $actor->getRoleNames()->implode(', '),
                'event_category' => 'academic_management',
                'action' => 'change_status',
                'module' => 'batches',
                'auditable_type' => $batch->getMorphClass(),
                'auditable_id' => $batch->id,
                'old_values' => json_encode(
                    $oldValues,
                    JSON_THROW_ON_ERROR
                ),
                'new_values' => json_encode(
                    ['status' => $batch->status],
                    JSON_THROW_ON_ERROR
                ),
                'metadata' => null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'request_id' =>
                    $request?->headers->get('X-Request-ID')
                    ?? (string) \Illuminate\Support\Str::uuid(),
                'outcome' => 'success',
                'created_at' => now(),
            ]);

            return $batch;
        });
    }

}