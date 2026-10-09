<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

use App\Models\SchoolClass;
use App\Models\User;
use App\Services\SchoolClassAccess;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SchoolClassAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (app()->environment() !== 'testing') {
            throw new RuntimeException(
                'Unsafe test environment: expected testing.'
            );
        }

        if (config('database.default') !== 'mysql') {
            throw new RuntimeException(
                'Unsafe database driver: expected mysql.'
            );
        }

        if (
            config('database.connections.mysql.database')
                !== 'edtech360_testing'
        ) {
            throw new RuntimeException(
                'Unsafe database configuration.'
            );
        }

        if (
            DB::connection()->getDatabaseName()
                !== 'edtech360_testing'
        ) {
            throw new RuntimeException(
                'Unsafe database connection.'
            );
        }
    }

    public function test_class_tables_are_available(): void
    {
        $this->assertTrue(Schema::hasTable('classes'));

        $this->assertTrue(
            Schema::hasTable('teacher_class_assignments')
        );

        $this->assertTrue(
            Schema::hasTable('teacher_subject_class')
        );

        $this->assertTrue(
            Schema::hasTable('student_class_enrollments')
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('classes.view', 'web');

        foreach ([
            'classes.view',
            'students.update',
            'subjects.view',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolePermissions = [
            'super_admin' => [
                'classes.view',
                'students.update',
                'subjects.view',
            ],
            'school_admin' => [
                'classes.view',
                'students.update',
                'subjects.view',
            ],
            'class_teacher' => [
                'classes.view',
                'students.update',
            ],
            'subject_teacher' => [
                'classes.view',
                'subjects.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        $this->createAcademicFixtures();
    }

    private function createAcademicFixtures(): void
    {
        $today = now()->toDateString();

        $sessionId = DB::table('academic_sessions')->insertGetId([
            'name' => 'Test Session 2026',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(10)->toDateString(),
            'is_current' => true,
            'status' => 'active',
        ]);

        $semesterId = DB::table('semesters')->insertGetId([
            'academic_session_id' => $sessionId,
            'number' => 1,
            'name' => 'Semester 1',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'status' => 'active',
        ]);

        $batchId = DB::table('batches')->insertGetId([
            'code' => 'TEST-BATCH-2026',
            'name' => 'Test Form 6 Batch',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        $this->classIds = [];

        foreach (['6B1', '6B2', '6B3'] as $code) {
            $this->classIds[$code] = DB::table('classes')->insertGetId([
                'academic_session_id' => $sessionId,
                'batch_id' => $batchId,
                'code' => $code,
                'name' => 'Class ' . $code,
                'status' => 'active',
            ]);
        }

        $this->sessionId = $sessionId;
        $this->semesterId = $semesterId;

        $this->subjectId = DB::table('subjects')->insertGetId([
            'code' => 'TEST-PA',
            'name' => 'Pengajian Am',
            'is_active' => true,
        ]);
    }

    private array $classIds = [];

    private int $sessionId;

    private int $semesterId;

    private int $subjectId;

    private function createTeacher(array $roles): array
    {
        $user = User::create([
            'name' => 'Test Teacher',
            'email' => 'teacher-' . uniqid() . '@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $user->assignRole($roles);

        $teacherId = DB::table('teacher_profiles')->insertGetId([
            'user_id' => $user->id,
            'full_name' => 'Test Teacher',
            'status' => 'active',
        ]);

        return [$user, $teacherId];
    }

    private function assignClassTeacher(
        int $teacherId,
        string $classCode,
        string $status = 'active',
        ?string $startDate = null,
        ?string $endDate = null
    ): void {
        DB::table('teacher_class_assignments')->insert([
            'teacher_profile_id' => $teacherId,
            'class_id' => $this->classIds[$classCode],
            'position' => 'class_teacher',
            'start_date' => $startDate ?? now()->subDays(5)->toDateString(),
            'end_date' => $endDate,
            'status' => $status,
        ]);
    }

    private function assignSubjectTeacher(
        int $teacherId,
        string $classCode
    ): void {
        DB::table('teacher_subject_class')->insert([
            'teacher_profile_id' => $teacherId,
            'subject_id' => $this->subjectId,
            'academic_session_id' => $this->sessionId,
            'class_id' => $this->classIds[$classCode],
            'semester_id' => $this->semesterId,
            'start_date' => now()->subDays(5)->toDateString(),
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_view_all_classes(): void
    {
        $user = User::create([
            'name' => 'Test Super Admin',
            'email' => 'superadmin@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $user->assignRole('super_admin');

        $this->assertSame(
            3,
            app(SchoolClassAccess::class)->visibleTo($user)->count()
        );
    }

    public function test_school_admin_can_view_all_classes(): void
    {
        $user = User::create([
            'name' => 'Test School Admin',
            'email' => 'schooladmin@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $user->assignRole('school_admin');

        $this->assertSame(
            3,
            app(SchoolClassAccess::class)->visibleTo($user)->count()
        );
    }

    public function test_class_teacher_sees_only_assigned_class(): void
    {
        [$user, $teacherId] = $this->createTeacher(['class_teacher']);

        $this->assignClassTeacher($teacherId, '6B1');

        $visible = app(SchoolClassAccess::class)
            ->visibleTo($user)
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing(
            [$this->classIds['6B1']],
            $visible
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'view',
                SchoolClass::findOrFail($this->classIds['6B1'])
            )
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'view',
                SchoolClass::findOrFail($this->classIds['6B3'])
            )
        );
    }

    public function test_subject_teacher_sees_assigned_teaching_class(): void
    {
        [$user, $teacherId] = $this->createTeacher(['subject_teacher']);

        $this->assignSubjectTeacher($teacherId, '6B2');

        $visible = app(SchoolClassAccess::class)
            ->visibleTo($user)
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing(
            [$this->classIds['6B2']],
            $visible
        );
    }

    public function test_teacher_with_multiple_roles_sees_combined_assignments(): void
    {
        [$user, $teacherId] = $this->createTeacher([
            'class_teacher',
            'subject_teacher',
        ]);

        $this->assignClassTeacher($teacherId, '6B1');
        $this->assignSubjectTeacher($teacherId, '6B2');

        $visible = app(SchoolClassAccess::class)
            ->visibleTo($user)
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing(
            [
                $this->classIds['6B1'],
                $this->classIds['6B2'],
            ],
            $visible
        );
    }

    public function test_inactive_assignment_does_not_grant_access(): void
    {
        [$user, $teacherId] = $this->createTeacher(['class_teacher']);

        $this->assignClassTeacher($teacherId, '6B1', 'inactive');

        $this->assertSame(
            0,
            app(SchoolClassAccess::class)->visibleTo($user)->count()
        );
    }

    public function test_expired_assignment_does_not_grant_access(): void
    {
        [$user, $teacherId] = $this->createTeacher(['class_teacher']);

        $this->assignClassTeacher(
            $teacherId,
            '6B1',
            'active',
            now()->subDays(30)->toDateString(),
            now()->subDay()->toDateString()
        );

        $this->assertSame(
            0,
            app(SchoolClassAccess::class)->visibleTo($user)->count()
        );
    }

    public function test_future_assignment_does_not_grant_access(): void
    {
        [$user, $teacherId] = $this->createTeacher(['class_teacher']);

        $this->assignClassTeacher(
            $teacherId,
            '6B1',
            'active',
            now()->addDay()->toDateString()
        );

        $this->assertSame(
            0,
            app(SchoolClassAccess::class)->visibleTo($user)->count()
        );
    }

    public function test_user_without_permission_cannot_view_classes(): void
    {
        $user = User::create([
            'name' => 'Unassigned User',
            'email' => 'unassigned@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $this->assertSame(
            0,
            app(SchoolClassAccess::class)->visibleTo($user)->count()
        );
    }

    public function test_class_teacher_can_manage_assigned_class_students(): void
    {
        [$user, $teacherId] = $this->createTeacher(['class_teacher']);

        $this->assignClassTeacher($teacherId, '6B1');

        $class = SchoolClass::findOrFail($this->classIds['6B1']);

        $this->assertTrue(
            Gate::forUser($user)->allows('manageStudents', $class)
        );
    }

    public function test_subject_teacher_cannot_manage_class_students(): void
    {
        [$user, $teacherId] = $this->createTeacher(['subject_teacher']);

        $this->assignSubjectTeacher($teacherId, '6B2');

        $class = SchoolClass::findOrFail($this->classIds['6B2']);

        $this->assertTrue(
            Gate::forUser($user)->allows('view', $class)
        );

        $this->assertFalse(
            Gate::forUser($user)->allows('manageStudents', $class)
        );
    }

    public function test_subject_teaching_access_matches_exact_semester(): void
    {
        [$user, $teacherId] = $this->createTeacher(['subject_teacher']);

        $this->assignSubjectTeacher($teacherId, '6B2');

        $class = SchoolClass::findOrFail($this->classIds['6B2']);

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'teachSubjectInSemester',
                [$class, $this->subjectId, $this->semesterId]
            )
        );

        $semesterTwoId = DB::table('semesters')->insertGetId([
            'academic_session_id' => $this->sessionId,
            'number' => 2,
            'name' => 'Semester 2',
            'start_date' => now()->addMonths(4)->toDateString(),
            'end_date' => now()->addMonths(7)->toDateString(),
            'status' => 'planned',
        ]);

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'teachSubjectInSemester',
                [$class, $this->subjectId, $semesterTwoId]
            )
        );
    }

    public function test_unassigned_subject_is_not_accessible(): void
    {
        [$user, $teacherId] = $this->createTeacher(['subject_teacher']);

        $this->assignSubjectTeacher($teacherId, '6B2');

        $otherSubjectId = DB::table('subjects')->insertGetId([
            'code' => 'TEST-MATH',
            'name' => 'Mathematics',
            'is_active' => true,
        ]);

        $class = SchoolClass::findOrFail($this->classIds['6B2']);

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'teachSubjectInSemester',
                [$class, $otherSubjectId, $this->semesterId]
            )
        );
    }

    public function test_inactive_teacher_profile_loses_class_visibility(): void
    {
        [$user, $teacherId] = $this->createTeacher(['class_teacher']);

        $this->assignClassTeacher($teacherId, '6B1');

        DB::table('teacher_profiles')
            ->where('id', $teacherId)
            ->update(['status' => 'inactive']);

        $user->unsetRelation('teacherProfile');

        $this->assertSame(
            0,
            app(SchoolClassAccess::class)->visibleTo($user)->count()
        );
    }

    public function test_inactive_account_cannot_manage_students(): void
    {
        [$user, $teacherId] = $this->createTeacher(['class_teacher']);

        $this->assignClassTeacher($teacherId, '6B1');

        $user->forceFill(['is_active' => false])->save();

        $class = SchoolClass::findOrFail($this->classIds['6B1']);

        $this->assertFalse(
            Gate::forUser($user)->allows('manageStudents', $class)
        );
    }

}