<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use App\Services\EnrollmentIntegrityService;
use App\Services\TeacherDelegationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BatchCSecurityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (app()->environment() !== 'testing'
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'edtech360_testing'
            || DB::connection()->getDatabaseName() !== 'edtech360_testing') {
            throw new RuntimeException('Unsafe test DB.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['dashboard.view', 'classes.view', 'students.view', 'students.update', 'roles.assign'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        foreach (['super_admin', 'school_admin', 'class_teacher', 'assistant_class_teacher'] as $r) {
            Role::findOrCreate($r, 'web');
        }
        Role::findByName('super_admin')->givePermissionTo(['dashboard.view','classes.view','students.view','students.update','roles.assign']);
        Role::findByName('school_admin')->givePermissionTo(['dashboard.view','classes.view','students.view','students.update','roles.assign']);
        Role::findByName('class_teacher')->givePermissionTo(['dashboard.view','classes.view','students.view','students.update']);
        Role::findByName('assistant_class_teacher')->givePermissionTo(['dashboard.view','classes.view','students.view']);
    }

    private function user(string $id, string $role): User
    {
        $u = User::create(['name' => $id, 'email' => $id.'@example.test', 'password' => 'Secret12345!', 'is_active' => true]);
        $u->assignRole($role);
        return $u;
    }

    private function fixtures(): array
    {
        $session = DB::table('academic_sessions')->insertGetId([
            'name' => '2026 Test', 'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(10)->toDateString(), 'status' => 'active',
        ]);
        $semester = DB::table('semesters')->insertGetId([
            'academic_session_id' => $session, 'number' => 1, 'name' => 'Sem 1',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(), 'status' => 'active',
        ]);
        $batch = DB::table('batches')->insertGetId([
            'code' => 'B2026', 'name' => 'Batch', 'intake_year' => 2026,
        ]);
        $one = DB::table('classes')->insertGetId([
            'academic_session_id' => $session, 'batch_id' => $batch,
            'name' => '6B1', 'code' => '6B1', 'status' => 'active',
        ]);
        $two = DB::table('classes')->insertGetId([
            'academic_session_id' => $session, 'batch_id' => $batch,
            'name' => '6B2', 'code' => '6B2', 'status' => 'active',
        ]);
        return [$one, $two, $semester];
    }

    private function teacherFor(User $user, int $classId, string $position): void
    {
        $id = DB::table('teacher_profiles')->insertGetId([
            'user_id' => $user->id, 'full_name' => $user->name, 'status' => 'active',
        ]);
        DB::table('teacher_class_assignments')->insert([
            'teacher_profile_id' => $id, 'class_id' => $classId,
            'position' => $position, 'start_date' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);
    }

    public function test_deactivated_existing_session_is_blocked(): void
    {
        $user = $this->user('inactive-session', 'school_admin');
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $user->forceFill(['is_active' => false])->save();
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_student_and_class_lists_are_scoped(): void
    {
        [$one, $two, $semester] = $this->fixtures();
        $teacher = $this->user('scoped-teacher', 'class_teacher');
        $this->teacherFor($teacher, $one, 'class_teacher');
        $first = StudentProfile::create(['student_number' => 'V001', 'full_name' => 'Visible One', 'status' => 'active']);
        $second = StudentProfile::create(['student_number' => 'H002', 'full_name' => 'Hidden Two', 'status' => 'active']);
        foreach ([[$first,$one],[$second,$two]] as [$student,$class]) {
            DB::table('student_class_enrollments')->insert([
                'student_profile_id' => $student->id,
                'academic_session_id' => DB::table('classes')->where('id',$class)->value('academic_session_id'),
                'class_id' => $class, 'semester_id' => $semester,
                'enrolled_at' => now()->subDay()->toDateString(), 'status' => 'active',
            ]);
        }
        $this->actingAs($teacher)->get(route('students.index'))
            ->assertOk()->assertSee('Visible One')->assertDontSee('Hidden Two');
        $this->get(route('classes.index'))
            ->assertOk()->assertSee('6B1')->assertDontSee('6B2');
    }

    public function test_unauthorized_delegation_is_forbidden(): void
    {
        [$one] = $this->fixtures();
        $assistant = $this->user('assistant-one', 'assistant_class_teacher');
        $this->teacherFor($assistant, $one, 'assistant_class_teacher');
        $other = $this->user('other-teacher', 'class_teacher');
        $this->actingAs($other)->patchJson(route('teachers.delegations.student-editing', $assistant), [
            'enabled' => true,
        ])->assertForbidden();
        $this->assertFalse($assistant->hasDirectPermission('students.update'));
    }

    public function test_admin_delegation_is_audited_and_revocable(): void
    {
        [$one] = $this->fixtures();
        $assistant = $this->user('assistant-two', 'assistant_class_teacher');
        $this->teacherFor($assistant, $one, 'assistant_class_teacher');
        $admin = $this->user('delegation-admin', 'school_admin');
        $this->actingAs($admin)->patchJson(route('teachers.delegations.student-editing', $assistant), [
            'enabled' => true,
        ])->assertOk();
        $this->assertTrue($assistant->hasDirectPermission('students.update'));
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id, 'module' => 'roles',
            'action' => 'delegate_students_update', 'auditable_id' => $assistant->id,
        ]);
        $this->patchJson(route('teachers.delegations.student-editing', $assistant), [
            'enabled' => false,
        ])->assertOk();
        $assistant->unsetRelation('permissions');
        $this->assertFalse($assistant->hasDirectPermission('students.update'));
    }

    public function test_shared_enrollment_writer_rejects_duplicate_active_semester(): void
    {
        [$one, $two, $semester] = $this->fixtures();
        $student = StudentProfile::create(['student_number'=>'E001','full_name'=>'Enrolled','status'=>'active']);
        $writer = app(EnrollmentIntegrityService::class);
        $writer->enroll($student, \App\Models\SchoolClass::findOrFail($one), $semester);
        try {
            $writer->enroll($student, \App\Models\SchoolClass::findOrFail($two), $semester);
            $this->fail('Duplicate enrollment must be rejected.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('enrollment', $e->errors());
        }
        $this->assertSame(1, DB::table('student_class_enrollments')->where('student_profile_id',$student->id)->count());
    }
}
