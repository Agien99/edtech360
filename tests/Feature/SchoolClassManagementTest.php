<?php
namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SchoolClassManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (app()->environment() !== 'testing'
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'edtech360_testing'
            || DB::connection()->getDatabaseName() !== 'edtech360_testing') {
            throw new RuntimeException('Unsafe test database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['classes.view', 'classes.create', 'classes.update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('school_admin', 'web')->syncPermissions([
            'classes.view', 'classes.create', 'classes.update',
        ]);
    }

    private function admin(): User
    {
        $user = User::create([
            'name' => 'Class Admin', 'email' => 'class-admin@example.test',
            'password' => 'TestPassword123!', 'is_active' => true,
        ]);
        $user->assignRole('school_admin');
        return $user;
    }

    private function fixtures(): array
    {
        $session = AcademicSession::create([
            'name' => 'Class Test Session', 'start_date' => '2026-01-01',
            'end_date' => '2027-12-31', 'is_current' => false, 'status' => 'planned',
        ]);
        $batch = Batch::create([
            'academic_session_id' => $session->id,
            'code' => 'CLASS-2026', 'name' => 'Class Test Batch',
            'intake_year' => 2026, 'status' => 'active',
        ]);
        return [$session, $batch];
    }

    private function payload(AcademicSession $session, Batch $batch, array $overrides = []): array
    {
        return array_merge([
            'academic_session_id' => $session->id,
            'code' => ' 6b1 ', 'name' => 'Form 6 B1',
            'description' => 'Test class',
        ], $overrides);
    }

    public function test_school_admin_registers_class_and_audit(): void
    {
        [$session, $batch] = $this->fixtures();
        $this->actingAs($this->admin())
            ->post(route('classes.store'), $this->payload($session, $batch))
            ->assertRedirect(route('classes.index'))->assertSessionHas('success');
        $this->assertDatabaseHas('classes', [
            'academic_session_id' => $session->id, 'batch_id' => $batch->id,
            'code' => '6B1', 'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'classes', 'action' => 'create', 'outcome' => 'success']);
    }

    public function test_duplicate_code_in_same_session_is_rejected(): void
    {
        [$session, $batch] = $this->fixtures();
        SchoolClass::create([
            'academic_session_id' => $session->id, 'batch_id' => $batch->id,
            'code' => '6B1', 'name' => 'Existing', 'status' => 'active',
        ]);
        $this->actingAs($this->admin())->post(route('classes.store'), $this->payload($session, $batch))
            ->assertSessionHasErrors('code');
        $this->assertDatabaseCount('classes', 1);
    }

    public function test_closed_session_cannot_receive_class(): void
    {
        [$session, $batch] = $this->fixtures();
        $session->update(['status' => 'closed']);
        $this->actingAs($this->admin())->post(route('classes.store'), $this->payload($session, $batch))
            ->assertSessionHasErrors('academic_session_id');
        $this->assertDatabaseCount('classes', 0);
    }

    public function test_user_without_permission_cannot_register(): void
    {
        [$session, $batch] = $this->fixtures();
        $user = User::create([
            'name' => 'Unauthorized', 'email' => 'no-class@example.test',
            'password' => 'TestPassword123!', 'is_active' => true,
        ]);
        $this->actingAs($user)->post(route('classes.store'), $this->payload($session, $batch))
            ->assertForbidden();
        $this->assertDatabaseCount('classes', 0);
    }

    public function test_school_admin_updates_class_with_audit(): void
    {
        [$session, $batch] = $this->fixtures();
        $class = SchoolClass::create([
            'academic_session_id' => $session->id, 'batch_id' => $batch->id,
            'code' => '6B1', 'name' => 'Old Name', 'status' => 'active',
        ]);
        $this->actingAs($this->admin())->patch(route('classes.update', $class),
            $this->payload($session, $batch, ['name' => 'Updated Class']))
            ->assertRedirect(route('classes.index'));
        $this->assertDatabaseHas('classes', ['id' => $class->id, 'name' => 'Updated Class']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'classes', 'action' => 'update', 'auditable_id' => $class->id]);
    }

    public function test_class_with_active_enrollment_cannot_be_deactivated(): void
    {
        [$session, $batch] = $this->fixtures();
        $semesterId = DB::table('semesters')->insertGetId([
            'academic_session_id' => $session->id,
            'number' => 1, 'name' => 'Semester 1',
            'start_date' => '2026-01-01', 'end_date' => '2026-06-30',
            'status' => 'planned', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $class = SchoolClass::create([
            'academic_session_id' => $session->id, 'batch_id' => $batch->id,
            'code' => '6B1', 'name' => 'Active Class', 'status' => 'active',
        ]);
        $student = StudentProfile::create([
            'student_number' => 'CLS-001', 'full_name' => 'Test Student', 'status' => 'active',
        ]);
        DB::table('student_class_enrollments')->insert([
            'student_profile_id' => $student->id, 'academic_session_id' => $session->id,
            'class_id' => $class->id, 'semester_id' => $semesterId,
            'enrolled_at' => '2026-01-05', 'status' => 'active',
        ]);
        $this->actingAs($this->admin())->patch(route('classes.deactivate', $class))
            ->assertSessionHasErrors('status');
        $this->assertDatabaseHas('classes', ['id' => $class->id, 'status' => 'active']);
    }

    public function test_class_without_dependencies_can_be_deactivated(): void
    {
        [$session, $batch] = $this->fixtures();
        $class = SchoolClass::create([
            'academic_session_id' => $session->id, 'batch_id' => $batch->id,
            'code' => '6B1', 'name' => 'Empty Class', 'status' => 'active',
        ]);
        $this->actingAs($this->admin())->patch(route('classes.deactivate', $class))
            ->assertRedirect(route('classes.index'));
        $this->assertDatabaseHas('classes', ['id' => $class->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'classes', 'action' => 'change_status', 'auditable_id' => $class->id]);
    }
    public function test_second_session_uses_its_own_batch(): void
    {
        [$first, $firstBatch] = $this->fixtures();
        $second = AcademicSession::create([
            'name' => 'Second Class Session', 'start_date' => '2027-06-01',
            'end_date' => '2028-12-31', 'is_current' => false, 'status' => 'planned',
        ]);
        $secondBatch = Batch::create([
            'academic_session_id' => $second->id, 'code' => 'CLASS-2027',
            'name' => 'Second Intake', 'intake_year' => 2027, 'status' => 'active',
        ]);
        $this->actingAs($this->admin())->post(route('classes.store'), [
            'academic_session_id' => $second->id, 'code' => '6B1', 'name' => 'Second Intake Class',
            'batch_id' => $firstBatch->id, // forged/obsolete form field must be ignored
        ])->assertRedirect(route('classes.index'));
        $this->assertDatabaseHas('classes', [
            'academic_session_id' => $second->id, 'batch_id' => $secondBatch->id,
            'code' => '6B1',
        ]);
    }

    public function test_unlinked_session_cannot_register_class(): void
    {
        $session = AcademicSession::create([
            'name' => 'No Batch Session', 'start_date' => '2026-01-01',
            'end_date' => '2027-12-31', 'is_current' => false, 'status' => 'planned',
        ]);
        $this->actingAs($this->admin())->post(route('classes.store'), [
            'academic_session_id' => $session->id, 'code' => '6B2', 'name' => 'No Batch',
        ])->assertSessionHasErrors('academic_session_id');
        $this->assertDatabaseCount('classes', 0);
    }

    public function test_inactive_batch_cannot_receive_class(): void
    {
        [$session, $batch] = $this->fixtures();
        $batch->update(['status' => 'completed']);
        $this->actingAs($this->admin())->post(route('classes.store'), [
            'academic_session_id' => $session->id, 'code' => '6B2', 'name' => 'Invalid Batch',
        ])->assertSessionHasErrors('academic_session_id');
        $this->assertDatabaseCount('classes', 0);
    }

    public function test_class_code_can_be_reused_in_different_sessions(): void
    {
        [$first, $firstBatch] = $this->fixtures();
        $second = AcademicSession::create([
            'name' => 'Other Intake', 'start_date' => '2027-06-01',
            'end_date' => '2028-12-31', 'is_current' => false, 'status' => 'planned',
        ]);
        $secondBatch = Batch::create([
            'academic_session_id' => $second->id, 'code' => 'OTHER-2027',
            'name' => 'Other Batch', 'intake_year' => 2027, 'status' => 'active',
        ]);
        SchoolClass::create(['academic_session_id'=>$first->id,'batch_id'=>$firstBatch->id,
            'code'=>'6B1','name'=>'Old class','status'=>'active']);
        $this->actingAs($this->admin())->post(route('classes.store'), [
            'academic_session_id'=>$second->id,'code'=>'6B1','name'=>'New class',
        ])->assertRedirect(route('classes.index'));
        $this->assertDatabaseHas('classes',['academic_session_id'=>$second->id,
            'batch_id'=>$secondBatch->id,'code'=>'6B1']);
        $this->assertDatabaseCount('classes',2);
    }

}
