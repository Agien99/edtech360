<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (app()->environment() !== 'testing'
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'edtech360_testing'
            || DB::connection()->getDatabaseName() !== 'edtech360_testing') {
            throw new RuntimeException('Unsafe database: this test only runs against edtech360_testing.');
        }
    }

    private function actor(string $role = 'super_admin'): User
    {
        foreach (['students.create', 'students.view', 'students.update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('student', 'web');
        $assigned = Role::findOrCreate($role, 'web');
        if ($role === 'super_admin') {
            $assigned->givePermissionTo(['students.create', 'students.view', 'students.update']);
        }
        $user = User::create([
            'name' => 'Administrative Test Actor',
            'email' => $role.'@example.test',
            'password' => 'TestingPassword123!',
            'is_active' => true,
        ]);
        $user->assignRole($assigned);
        return $user;
    }

    private function sessionAndBatch(): array
    {
        $session = AcademicSession::create([
            'name' => '2033/2034',
            'start_date' => '2033-01-01',
            'end_date' => '2034-12-31',
            'status' => 'active',
            'is_current' => false,
        ]);
        $batch = Batch::create([
            'academic_session_id' => $session->id,
            'code' => 'F6-2033',
            'name' => 'Intake 2033',
            'intake_year' => 2033,
            'status' => 'active',
        ]);
        return [$session, $batch];
    }

    private function row(string $ic, string $email, string $name = 'Test Student'): array
    {
        return ['ic_number' => $ic, 'full_name' => $name, 'email' => $email];
    }

    private function registeredStudent(User $actor): StudentProfile
    {
        [$session] = $this->sessionAndBatch();
        app(StudentRegistrationService::class)->registerRows($actor, $session->id, [
            $this->row('030101010001', 'student1@example.test'),
        ]);
        return StudentProfile::where('ic_number', '030101010001')->firstOrFail();
    }

    public function test_registering_multiple_students_creates_accounts_intake_memberships_and_audits(): void
    {
        $admin = $this->actor();
        [$session, $batch] = $this->sessionAndBatch();
        $result = app(StudentRegistrationService::class)->registerRows($admin, $session->id, [
            $this->row('030101010001', 'first@example.test', 'First Student'),
            $this->row('030101010002', 'second@example.test', 'Second Student'),
        ]);

        $this->assertCount(2, $result);
        $this->assertDatabaseCount('student_profiles', 2);
        $this->assertDatabaseCount('student_batch', 2);
        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertDatabaseCount('student_class_enrollments', 0);
        foreach (['030101010001', '030101010002'] as $ic) {
            $student = StudentProfile::where('ic_number', $ic)->firstOrFail();
            $this->assertNull($student->student_number);
            $this->assertDatabaseHas('student_batch', [
                'student_profile_id' => $student->id,
                'batch_id' => $batch->id,
                'status' => 'active',
            ]);
            $this->assertTrue(Hash::check($ic, $student->user->password));
            $this->assertNotSame($ic, $student->user->getRawOriginal('password'));
            $this->assertTrue($student->user->hasRole('student'));
        }
    }

    public function test_duplicate_ic_rolls_back_entire_registration_batch(): void
    {
        $admin = $this->actor();
        [$session] = $this->sessionAndBatch();
        app(StudentRegistrationService::class)->registerRows($admin, $session->id, [
            $this->row('030101010001', 'existing@example.test'),
        ]);

        try {
            app(StudentRegistrationService::class)->registerRows($admin, $session->id, [
                $this->row('030101010003', 'new@example.test'),
                $this->row('030101010001', 'duplicate@example.test'),
            ]);
            $this->fail('Duplicate IC should reject the batch.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('students.1.ic_number', $e->errors());
        }
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
        $this->assertDatabaseCount('student_profiles', 1);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_duplicate_email_rolls_back_entire_registration_batch(): void
    {
        $admin = $this->actor();
        [$session] = $this->sessionAndBatch();
        app(StudentRegistrationService::class)->registerRows($admin, $session->id, [
            $this->row('030101010001', 'existing@example.test'),
        ]);
        try {
            app(StudentRegistrationService::class)->registerRows($admin, $session->id, [
                $this->row('030101010003', 'fresh@example.test'),
                $this->row('030101010004', 'existing@example.test'),
            ]);
            $this->fail('Duplicate email should reject the batch.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('students.1.email', $e->errors());
        }
        $this->assertDatabaseMissing('users', ['email' => 'fresh@example.test']);
        $this->assertDatabaseCount('student_profiles', 1);
    }

    public function test_admin_can_edit_a_student_with_null_student_number_and_records_audit(): void
    {
        $admin = $this->actor();
        $student = $this->registeredStudent($admin);
        $this->actingAs($admin)->patch(route('students.admin.update', $student), [
            'full_name' => 'Updated Student',
            'ic_number' => '030101010001',
            'email' => 'updated@example.test',
            'phone' => '0123456789',
            'student_number' => '',
        ])->assertRedirect();

        $student->refresh();
        $this->assertSame('Updated Student', $student->full_name);
        $this->assertNull($student->student_number);
        $this->assertSame('updated@example.test', $student->user->email);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $student->id, 'action' => 'admin_update']);
    }

    public function test_admin_can_deactivate_and_reactivate_student_and_audit_both_changes(): void
    {
        $admin = $this->actor();
        $student = $this->registeredStudent($admin);
        $this->actingAs($admin)->patch(route('students.admin.status', $student), [
            'status' => 'inactive',
        ])->assertRedirect();
        $this->assertSame('inactive', $student->fresh()->status);
        $this->assertFalse((bool) $student->user->fresh()->is_active);
        $this->actingAs($admin)->patch(route('students.admin.status', $student), [
            'status' => 'active',
        ])->assertRedirect();
        $this->assertSame('active', $student->fresh()->status);
        $this->assertTrue((bool) $student->user->fresh()->is_active);
        $this->assertSame(2, DB::table('audit_logs')->where('action', 'admin_status')->count());
    }

    public function test_user_without_administrative_role_cannot_update_or_deactivate_student(): void
    {
        $admin = $this->actor();
        $student = $this->registeredStudent($admin);
        $nonAdmin = $this->actor('student');
        $this->actingAs($nonAdmin)->patch(route('students.admin.update', $student), [
            'full_name' => 'Unauthorized Change',
            'ic_number' => '030101010001',
            'email' => 'unauthorized@example.test',
            'student_number' => '',
        ])->assertForbidden();
        $this->actingAs($nonAdmin)->patch(route('students.admin.status', $student), [
            'status' => 'inactive',
        ])->assertForbidden();
        $this->assertSame('active', $student->fresh()->status);
        $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.test']);
    }

    public function test_student_cannot_access_admin_registration_route(): void
    {
        $this->actor();
        $student = $this->actor('student');
        $this->actingAs($student)
            ->get(route('student-registrations.index'))
            ->assertForbidden();
    }
}
