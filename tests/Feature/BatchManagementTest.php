<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BatchManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (
            app()->environment() !== 'testing'
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'edtech360_testing'
            || DB::connection()->getDatabaseName() !== 'edtech360_testing'
        ) {
            throw new RuntimeException('Unsafe test database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('batches.view', 'web');
        Permission::findOrCreate('batches.manage', 'web');

        $role = Role::findOrCreate('school_admin', 'web');

        $role->syncPermissions([
            'batches.view',
            'batches.manage',
        ]);
    }

    private function administrator(): User
    {
        $user = User::create([
            'name' => 'Batch Administrator',
            'email' => 'batch-admin@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $user->assignRole('school_admin');

        return $user;
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'code' => 'FORM6-2026',
            'name' => 'Form 6 Intake 2026',
            'intake_year' => 2026,
            'start_date' => '2026-06-01',
            'expected_end_date' => '2027-12-31',
            'description' => 'Development test batch',
        ], $overrides);
    }

    public function test_administrator_can_register_batch(): void
    {
        $this->actingAs($this->administrator())
            ->post(route('batches.store'), $this->validData())
            ->assertRedirect(route('batches.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('batches', [
            'code' => 'FORM6-2026',
            'name' => 'Form 6 Intake 2026',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'batches',
            'action' => 'create',
            'outcome' => 'success',
        ]);
    }

    public function test_batch_codes_are_normalized(): void
    {
        $this->actingAs($this->administrator())
            ->post(
                route('batches.store'),
                $this->validData(['code' => ' form6-2026 '])
            )
            ->assertRedirect();

        $this->assertDatabaseHas('batches', [
            'code' => 'FORM6-2026',
        ]);
    }

    public function test_duplicate_batch_code_is_rejected(): void
    {
        Batch::create([
            'code' => 'FORM6-2026',
            'name' => 'Existing Batch',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $this->actingAs($this->administrator())
            ->from(route('batches.index'))
            ->post(
                route('batches.store'),
                $this->validData(['code' => 'form6-2026'])
            )
            ->assertSessionHasErrors('code');

        $this->assertSame(1, Batch::count());
    }

    public function test_invalid_batch_dates_are_rejected(): void
    {
        $this->actingAs($this->administrator())
            ->from(route('batches.index'))
            ->post(
                route('batches.store'),
                $this->validData([
                    'start_date' => '2027-12-31',
                    'expected_end_date' => '2026-06-01',
                ])
            )
            ->assertSessionHasErrors('expected_end_date');

        $this->assertDatabaseCount('batches', 0);
    }

    public function test_unauthorized_user_cannot_register_batch(): void
    {
        $user = User::create([
            'name' => 'Unauthorized User',
            'email' => 'unauthorized-batch@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('batches.store'), $this->validData())
            ->assertForbidden();

        $this->assertDatabaseCount('batches', 0);
    }

    public function test_administrator_can_update_batch(): void
    {
        $batch = Batch::create([
            'code' => 'FORM6-2026',
            'name' => 'Original Batch',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $this->actingAs($this->administrator())
            ->patch(
                route('batches.update', $batch),
                $this->validData([
                    'code' => 'form6-2026-updated',
                    'name' => 'Updated Form 6 Batch',
                ])
            )
            ->assertRedirect(route('batches.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'code' => 'FORM6-2026-UPDATED',
            'name' => 'Updated Form 6 Batch',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'batches',
            'action' => 'update',
            'auditable_id' => $batch->id,
            'outcome' => 'success',
        ]);
    }

    public function test_duplicate_batch_code_is_rejected_on_update(): void
    {
        Batch::create([
            'code' => 'FORM6-2025',
            'name' => 'Existing Batch',
            'intake_year' => 2025,
            'status' => 'active',
        ]);

        $batch = Batch::create([
            'code' => 'FORM6-2026',
            'name' => 'Batch to Update',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $this->actingAs($this->administrator())
            ->from(route('batches.index'))
            ->patch(
                route('batches.update', $batch),
                $this->validData([
                    'code' => 'form6-2025',
                ])
            )
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'code' => 'FORM6-2026',
        ]);
    }

    public function test_unauthorized_user_cannot_update_batch(): void
    {
        $batch = Batch::create([
            'code' => 'FORM6-2026',
            'name' => 'Original Batch',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Unauthorized User',
            'email' => 'unauthorized-update@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->patch(
                route('batches.update', $batch),
                $this->validData(['name' => 'Unauthorized Change'])
            )
            ->assertForbidden();

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'name' => 'Original Batch',
        ]);
    }

    public function test_administrator_can_complete_active_batch(): void
    {
        $batch = Batch::create([
            'code' => 'FORM6-COMPLETE',
            'name' => 'Batch Ready for Completion',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $this->actingAs($this->administrator())
            ->patch(route('batches.change-status', $batch), [
                'status' => 'completed',
            ])
            ->assertRedirect(route('batches.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'batches',
            'action' => 'change_status',
            'auditable_id' => $batch->id,
            'outcome' => 'success',
        ]);
    }

    public function test_administrator_can_deactivate_active_batch(): void
    {
        $batch = Batch::create([
            'code' => 'FORM6-INACTIVE',
            'name' => 'Batch Ready for Deactivation',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $this->actingAs($this->administrator())
            ->patch(route('batches.change-status', $batch), [
                'status' => 'inactive',
            ])
            ->assertRedirect(route('batches.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'status' => 'inactive',
        ]);
    }

    public function test_completed_batch_cannot_change_status_again(): void
    {
        $batch = Batch::create([
            'code' => 'FORM6-LOCKED',
            'name' => 'Completed Batch',
            'intake_year' => 2025,
            'status' => 'completed',
        ]);

        $this->actingAs($this->administrator())
            ->from(route('batches.index'))
            ->patch(route('batches.change-status', $batch), [
                'status' => 'inactive',
            ])
            ->assertRedirect(route('batches.index'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'status' => 'completed',
        ]);
    }

    public function test_invalid_batch_status_is_rejected(): void
    {
        $batch = Batch::create([
            'code' => 'FORM6-INVALID',
            'name' => 'Active Batch',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $this->actingAs($this->administrator())
            ->from(route('batches.index'))
            ->patch(route('batches.change-status', $batch), [
                'status' => 'archived',
            ])
            ->assertRedirect(route('batches.index'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'status' => 'active',
        ]);
    }

    public function test_unauthorized_user_cannot_change_batch_status(): void
    {
        $batch = Batch::create([
            'code' => 'FORM6-PROTECTED',
            'name' => 'Protected Batch',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Unauthorized Status User',
            'email' => 'unauthorized-status@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('batches.change-status', $batch), [
                'status' => 'completed',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'status' => 'active',
        ]);
    }

    public function test_batch_with_active_student_membership_cannot_be_completed(): void
    {
        $batch = Batch::create([
            'code' => 'FORM6-ACTIVE-STUDENTS',
            'name' => 'Batch With Active Students',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $student = StudentProfile::create([
            'student_number' => 'BATCH-STUDENT-001',
            'full_name' => 'Batch Test Student',
            'status' => 'active',
        ]);

        DB::table('student_batch')->insert([
            'student_profile_id' => $student->id,
            'batch_id' => $batch->id,
            'joined_at' => now()->subDays(10)->toDateString(),
            'left_at' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->administrator())
            ->from(route('batches.index'))
            ->patch(route('batches.change-status', $batch), [
                'status' => 'completed',
            ])
            ->assertRedirect(route('batches.index'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseCount('student_batch', 1);

        $this->assertDatabaseMissing('audit_logs', [
            'module' => 'batches',
            'action' => 'change_status',
            'auditable_id' => $batch->id,
            'outcome' => 'success',
        ]);
    }

    public function test_batch_with_active_class_cannot_be_deactivated(): void
    {
        $batch = Batch::create([
            'code' => 'FORM6-ACTIVE-CLASS',
            'name' => 'Batch With Active Class',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $session = \App\Models\AcademicSession::create([
            'name' => 'Academic Session 2026/2027',
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'is_current' => false,
            'status' => 'planned',
        ]);

        $class = \App\Models\SchoolClass::create([
            'academic_session_id' => $session->id,
            'batch_id' => $batch->id,
            'code' => '6B1',
            'name' => 'Form 6 B1',
            'status' => 'active',
        ]);

        $this->actingAs($this->administrator())
            ->from(route('batches.index'))
            ->patch(route('batches.change-status', $batch), [
                'status' => 'inactive',
            ])
            ->assertRedirect(route('batches.index'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'batch_id' => $batch->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'module' => 'batches',
            'action' => 'change_status',
            'auditable_id' => $batch->id,
            'outcome' => 'success',
        ]);
    }
    
    public function test_batch_with_active_class_enrollment_cannot_be_completed(): void
    {
        $batch = Batch::create([
            'code' => 'FORM6-ACTIVE-ENROLLMENT',
            'name' => 'Batch With Active Enrollment',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $session = \App\Models\AcademicSession::create([
            'name' => 'Enrollment Test Session 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'is_current' => false,
            'status' => 'planned',
        ]);

        $semester = \App\Models\Semester::create([
            'academic_session_id' => $session->id,
            'number' => 1,
            'name' => 'Semester 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => 'planned',
        ]);

        $class = \App\Models\SchoolClass::create([
            'academic_session_id' => $session->id,
            'batch_id' => $batch->id,
            'code' => '6B1',
            'name' => 'Form 6 B1',
            'status' => 'inactive',
        ]);

        $student = \App\Models\StudentProfile::create([
            'student_number' => 'ENROLLMENT-001',
            'full_name' => 'Enrollment Test Student',
            'status' => 'active',
        ]);

        \Illuminate\Support\Facades\DB::table(
            'student_class_enrollments'
        )->insert([
            'student_profile_id' => $student->id,
            'academic_session_id' => $session->id,
            'class_id' => $class->id,
            'semester_id' => $semester->id,
            'enrolled_at' => '2026-01-05',
            'ended_at' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->administrator())
            ->from(route('batches.index'))
            ->patch(route('batches.change-status', $batch), [
                'status' => 'completed',
            ])
            ->assertRedirect(route('batches.index'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('student_class_enrollments', [
            'student_profile_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'ended_at' => null,
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'module' => 'batches',
            'action' => 'change_status',
            'auditable_id' => $batch->id,
            'outcome' => 'success',
        ]);
    }

}