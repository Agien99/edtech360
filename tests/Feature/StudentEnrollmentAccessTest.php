<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentEnrollmentAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use App\Models\SchoolClass;
use App\Services\StudentTransferService;
use Illuminate\Validation\ValidationException;

class StudentEnrollmentAccessTest extends TestCase
{
    use RefreshDatabase;

    private array $classes = [];
    private int $sessionId;
    private int $semesterOneId;
    private int $semesterTwoId;
    private int $batchId;
    private int $subjectId;

    protected function beforeRefreshingDatabase(): void
    {
        if (
            app()->environment() !== 'testing'
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'edtech360_testing'
            || DB::connection()->getDatabaseName() !== 'edtech360_testing'
        ) {
            throw new RuntimeException(
                'Unsafe test database connection.'
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        foreach ([
            'students.view',
            'students.update',
            'students.transfer',
            'classes.view',
        ] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $rolePermissions = [
            'super_admin' => [
                'students.view',
                'students.update',
                'students.transfer',
                'classes.view',
            ],
            'school_admin' => [
                'students.view',
                'students.update',
                'students.transfer',
                'classes.view',
            ],
            'class_teacher' => [
                'students.view',
                'students.update',
                'classes.view',
            ],
            'subject_teacher' => [
                'students.view',
                'classes.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')
                ->syncPermissions($permissions);
        }

        $this->createAcademicFixtures();
    }

    private function createAcademicFixtures(): void
    {
        $this->sessionId = DB::table('academic_sessions')->insertGetId([
            'name' => 'Test Session 2026',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(10)->toDateString(),
            'is_current' => true,
            'status' => 'active',
        ]);

        $this->semesterOneId = DB::table('semesters')->insertGetId([
            'academic_session_id' => $this->sessionId,
            'number' => 1,
            'name' => 'Semester 1',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'status' => 'active',
        ]);

        $this->semesterTwoId = DB::table('semesters')->insertGetId([
            'academic_session_id' => $this->sessionId,
            'number' => 2,
            'name' => 'Semester 2',
            'start_date' => now()->addMonths(4)->toDateString(),
            'end_date' => now()->addMonths(7)->toDateString(),
            'status' => 'planned',
        ]);

        $this->batchId = DB::table('batches')->insertGetId([
            'code' => 'TEST-BATCH',
            'name' => 'Test Form 6 Batch',
            'intake_year' => 2026,
            'status' => 'active',
        ]);

        foreach (['6B1', '6B2', '6B3'] as $code) {
            $this->classes[$code] = DB::table('classes')->insertGetId([
                'academic_session_id' => $this->sessionId,
                'batch_id' => $this->batchId,
                'code' => $code,
                'name' => 'Class ' . $code,
                'status' => 'active',
            ]);
        }

        $this->subjectId = DB::table('subjects')->insertGetId([
            'code' => 'TEST-PA',
            'name' => 'Pengajian Am',
            'is_active' => true,
        ]);
    }

    private function createUser(
        string $role,
        string $identifier
    ): User {
        $user = User::create([
            'name' => 'Test ' . $identifier,
            'email' => $identifier . '@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createTeacher(
        string $role,
        string $identifier
    ): array {
        $user = $this->createUser($role, $identifier);

        $teacherId = DB::table('teacher_profiles')->insertGetId([
            'user_id' => $user->id,
            'full_name' => 'Test Teacher ' . $identifier,
            'status' => 'active',
        ]);

        return [$user, $teacherId];
    }

    private function createStudent(string $number): StudentProfile
    {
        return StudentProfile::create([
            'student_number' => $number,
            'full_name' => 'Test Student ' . $number,
            'status' => 'active',
        ]);
    }

    private function enroll(
        StudentProfile $student,
        string $classCode,
        ?int $semesterId = null,
        string $status = 'active',
        ?string $endedAt = null
    ): int {
        return DB::table('student_class_enrollments')->insertGetId([
            'student_profile_id' => $student->id,
            'academic_session_id' => $this->sessionId,
            'class_id' => $this->classes[$classCode],
            'semester_id' => $semesterId ?? $this->semesterOneId,
            'enrolled_at' => now()->subDays(10)->toDateString(),
            'ended_at' => $endedAt,
            'status' => $status,
        ]);
    }

    private function assignClassTeacher(
        int $teacherId,
        string $classCode
    ): void {
        DB::table('teacher_class_assignments')->insert([
            'teacher_profile_id' => $teacherId,
            'class_id' => $this->classes[$classCode],
            'position' => 'class_teacher',
            'start_date' => now()->subDays(20)->toDateString(),
            'status' => 'active',
        ]);
    }

    private function assignSubjectTeacher(
        int $teacherId,
        string $classCode,
        int $semesterId
    ): void {
        DB::table('teacher_subject_class')->insert([
            'teacher_profile_id' => $teacherId,
            'subject_id' => $this->subjectId,
            'academic_session_id' => $this->sessionId,
            'class_id' => $this->classes[$classCode],
            'semester_id' => $semesterId,
            'start_date' => now()->subDays(20)->toDateString(),
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_view_students(): void
    {
        $admin = $this->createUser(
            'super_admin',
            'super-admin'
        );

        $student = $this->createStudent('ST001');
        $this->enroll($student, '6B1');

        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $student)
        );
    }

    public function test_school_admin_can_view_students(): void
    {
        $admin = $this->createUser(
            'school_admin',
            'school-admin'
        );

        $student = $this->createStudent('ST002');
        $this->enroll($student, '6B3');

        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $student)
        );
    }

    public function test_class_teacher_sees_assigned_students_only(): void
    {
        [$teacher, $teacherId] = $this->createTeacher(
            'class_teacher',
            'class-teacher'
        );

        $this->assignClassTeacher($teacherId, '6B1');

        $allowed = $this->createStudent('ST003');
        $denied = $this->createStudent('ST004');

        $this->enroll($allowed, '6B1');
        $this->enroll($denied, '6B3');

        $access = app(StudentEnrollmentAccess::class);

        $this->assertTrue($access->canView($teacher, $allowed));
        $this->assertFalse($access->canView($teacher, $denied));
        $this->assertSame(1, $access->visibleTo($teacher)->count());
    }

    public function test_subject_teacher_sees_matching_semester_students(): void
    {
        [$teacher, $teacherId] = $this->createTeacher(
            'subject_teacher',
            'subject-teacher'
        );

        $this->assignSubjectTeacher(
            $teacherId,
            '6B2',
            $this->semesterOneId
        );

        $allowed = $this->createStudent('ST005');
        $denied = $this->createStudent('ST006');

        $this->enroll($allowed, '6B2', $this->semesterOneId);
        $this->enroll($denied, '6B2', $this->semesterTwoId);

        $access = app(StudentEnrollmentAccess::class);

        $this->assertTrue($access->canView($teacher, $allowed));
        $this->assertFalse($access->canView($teacher, $denied));
    }

    public function test_transferred_student_is_hidden_from_old_teacher(): void
    {
        [$oldTeacher, $oldTeacherId] = $this->createTeacher(
            'class_teacher',
            'old-teacher'
        );

        [$newTeacher, $newTeacherId] = $this->createTeacher(
            'class_teacher',
            'new-teacher'
        );

        $this->assignClassTeacher($oldTeacherId, '6B1');
        $this->assignClassTeacher($newTeacherId, '6B2');

        $student = $this->createStudent('ST007');

        $this->enroll(
            $student,
            '6B1',
            null,
            'transferred',
            now()->subDay()->toDateString()
        );

        $this->enroll($student, '6B2');

        $access = app(StudentEnrollmentAccess::class);

        $this->assertFalse($access->canView($oldTeacher, $student));
        $this->assertTrue($access->canView($newTeacher, $student));
    }

    public function test_ended_enrollment_does_not_grant_access(): void
    {
        [$teacher, $teacherId] = $this->createTeacher(
            'class_teacher',
            'ended-enrollment-teacher'
        );

        $this->assignClassTeacher($teacherId, '6B1');

        $student = $this->createStudent('ST008');

        $this->enroll(
            $student,
            '6B1',
            null,
            'active',
            now()->subDay()->toDateString()
        );

        $this->assertFalse(
            app(StudentEnrollmentAccess::class)->canView(
                $teacher,
                $student
            )
        );
    }

    public function test_inactive_enrollment_does_not_grant_access(): void
    {
        [$teacher, $teacherId] = $this->createTeacher(
            'class_teacher',
            'inactive-enrollment-teacher'
        );

        $this->assignClassTeacher($teacherId, '6B1');

        $student = $this->createStudent('ST009');
        $this->enroll($student, '6B1', null, 'inactive');

        $this->assertFalse(
            app(StudentEnrollmentAccess::class)->canView(
                $teacher,
                $student
            )
        );
    }

    public function test_inactive_teacher_profile_is_denied(): void
    {
        [$teacher, $teacherId] = $this->createTeacher(
            'class_teacher',
            'inactive-profile-teacher'
        );

        $this->assignClassTeacher($teacherId, '6B1');

        $student = $this->createStudent('ST010');
        $this->enroll($student, '6B1');

        DB::table('teacher_profiles')
            ->where('id', $teacherId)
            ->update(['status' => 'inactive']);

        $teacher->unsetRelation('teacherProfile');

        $this->assertFalse(
            app(StudentEnrollmentAccess::class)->canView(
                $teacher,
                $student
            )
        );
    }

    public function test_user_without_permission_is_denied(): void
    {
        $user = User::create([
            'name' => 'No Permission',
            'email' => 'no-permission@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $student = $this->createStudent('ST011');
        $this->enroll($student, '6B1');

        $this->assertFalse(
            app(StudentEnrollmentAccess::class)->canView(
                $user,
                $student
            )
        );
    }

    public function test_inactive_user_is_denied(): void
    {
        $admin = $this->createUser(
            'school_admin',
            'inactive-admin'
        );

        $student = $this->createStudent('ST012');
        $this->enroll($student, '6B1');

        $admin->forceFill(['is_active' => false])->save();

        $this->assertFalse(
            app(StudentEnrollmentAccess::class)->canView(
                $admin,
                $student
            )
        );
    }

    public function test_class_teacher_can_update_assigned_student(): void
    {
        [$teacher, $teacherId] = $this->createTeacher(
            'class_teacher',
            'update-class-teacher'
        );

        $this->assignClassTeacher($teacherId, '6B1');

        $student = $this->createStudent('UPDATE001');
        $this->enroll($student, '6B1');

        $this->assertTrue(
            Gate::forUser($teacher)->allows('update', $student)
        );
    }

    public function test_subject_teacher_cannot_update_student(): void
    {
        [$teacher, $teacherId] = $this->createTeacher(
            'subject_teacher',
            'update-subject-teacher'
        );

        $this->assignSubjectTeacher(
            $teacherId,
            '6B2',
            $this->semesterOneId
        );

        $student = $this->createStudent('UPDATE002');
        $this->enroll($student, '6B2');

        $this->assertTrue(
            Gate::forUser($teacher)->allows('view', $student)
        );

        $this->assertFalse(
            Gate::forUser($teacher)->allows('update', $student)
        );
    }

    public function test_school_admin_can_transfer_student(): void
    {
        $admin = $this->createUser(
            'school_admin',
            'transfer-admin'
        );

        $student = $this->createStudent('TRANSFER001');
        $this->enroll($student, '6B1');

        $newEnrollment = app(StudentTransferService::class)
            ->transfer($admin, $student, $this->classes['6B2']);

        $this->assertSame(
            $this->classes['6B2'],
            $newEnrollment->class_id
        );

        $this->assertSame('active', $newEnrollment->status);

        $this->assertDatabaseHas('student_class_enrollments', [
            'student_profile_id' => $student->id,
            'class_id' => $this->classes['6B1'],
            'status' => 'transferred',
        ]);

        $this->assertSame(
            1,
            DB::table('student_class_enrollments')
                ->where('student_profile_id', $student->id)
                ->where('status', 'active')
                ->count()
        );
    }

    public function test_class_teacher_cannot_transfer_student(): void
    {
        [$teacher, $teacherId] = $this->createTeacher(
            'class_teacher',
            'transfer-class-teacher'
        );

        $this->assignClassTeacher($teacherId, '6B1');

        $student = $this->createStudent('TRANSFER002');
        $this->enroll($student, '6B1');

        $this->assertFalse(
            Gate::forUser($teacher)->allows('transfer', $student)
        );

        $this->assertDatabaseCount(
            'student_class_enrollments',
            1
        );
    }

    public function test_transfer_to_same_class_is_rejected(): void
    {
        $admin = $this->createUser(
            'school_admin',
            'same-class-admin'
        );

        $student = $this->createStudent('TRANSFER003');
        $this->enroll($student, '6B1');

        try {
            app(StudentTransferService::class)->transfer(
                $admin,
                $student,
                $this->classes['6B1']
            );

            $this->fail('Same-class transfer should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'class_id',
                $exception->errors()
            );
        }

        $this->assertDatabaseHas('student_class_enrollments', [
            'student_profile_id' => $student->id,
            'class_id' => $this->classes['6B1'],
            'status' => 'active',
        ]);
    }

    public function test_transfer_rejects_multiple_active_enrollments(): void
    {
        $admin = $this->createUser(
            'school_admin',
            'duplicate-admin'
        );

        $student = $this->createStudent('TRANSFER004');

        $this->enroll($student, '6B1');
        $this->enroll($student, '6B2');

        try {
            app(StudentTransferService::class)->transfer(
                $admin,
                $student,
                $this->classes['6B3']
            );

            $this->fail('Inconsistent enrollment records should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'enrollment',
                $exception->errors()
            );
        }

        $this->assertSame(
            2,
            DB::table('student_class_enrollments')
                ->where('student_profile_id', $student->id)
                ->where('status', 'active')
                ->count()
        );

        $this->assertDatabaseMissing('student_class_enrollments', [
            'student_profile_id' => $student->id,
            'class_id' => $this->classes['6B3'],
        ]);
    }

    public function test_student_transfer_restricts_previous_teacher(): void
    {
        $admin = $this->createUser(
            'school_admin',
            'history-admin'
        );

        [$oldTeacher, $oldTeacherId] = $this->createTeacher(
            'class_teacher',
            'history-old-teacher'
        );

        [$newTeacher, $newTeacherId] = $this->createTeacher(
            'class_teacher',
            'history-new-teacher'
        );

        $this->assignClassTeacher($oldTeacherId, '6B1');
        $this->assignClassTeacher($newTeacherId, '6B2');

        $student = $this->createStudent('TRANSFER005');
        $this->enroll($student, '6B1');

        app(StudentTransferService::class)->transfer(
            $admin,
            $student,
            $this->classes['6B2']
        );

        $access = app(StudentEnrollmentAccess::class);

        $this->assertFalse(
            $access->canView($oldTeacher, $student)
        );

        $this->assertTrue(
            $access->canView($newTeacher, $student)
        );
    }

    public function test_transfer_creates_central_audit_record(): void
    {
        $admin = $this->createUser(
            'school_admin',
            'audit-transfer-admin'
        );

        $student = $this->createStudent('AUDIT001');
        $this->enroll($student, '6B1');

        app(StudentTransferService::class)->transfer(
            $admin,
            $student,
            $this->classes['6B2']
        );

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'module' => 'students',
            'action' => 'transfer',
            'auditable_id' => $student->id,
            'outcome' => 'success',
        ]);

        $audit = DB::table('audit_logs')
            ->where('actor_user_id', $admin->id)
            ->where('action', 'transfer')
            ->first();

        $old = json_decode($audit->old_values, true);
        $new = json_decode($audit->new_values, true);

        $this->assertSame(
            $this->classes['6B1'],
            $old['class_id']
        );

        $this->assertSame(
            $this->classes['6B2'],
            $new['class_id']
        );
    }

    public function test_unauthorized_transfer_endpoint_is_denied(): void
    {
        [$teacher, $teacherId] = $this->createTeacher(
            'class_teacher',
            'endpoint-denied-teacher'
        );

        $this->assignClassTeacher($teacherId, '6B1');

        $student = $this->createStudent('AUDIT002');
        $this->enroll($student, '6B1');

        $this->actingAs($teacher)
            ->post(route('students.transfer', $student), [
                'class_id' => $this->classes['6B2'],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('student_class_enrollments', [
            'student_profile_id' => $student->id,
            'class_id' => $this->classes['6B1'],
            'status' => 'active',
        ]);
    }

    public function test_profile_update_rejects_unauthorized_fields(): void
    {
        $admin = $this->createUser(
            'school_admin',
            'profile-update-admin'
        );

        $student = $this->createStudent('AUDIT003');

        $this->actingAs($admin)
            ->patch(route('students.profile.update', $student), [
                'full_name' => 'Updated Student Name',
                'phone' => '0123456789',
                'status' => 'inactive',
                'student_number' => 'CHANGED',
            ])
            ->assertRedirect();

        $student->refresh();

        $this->assertSame(
            'Updated Student Name',
            $student->full_name
        );

        $this->assertSame('active', $student->status);
        $this->assertSame('AUDIT003', $student->student_number);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'module' => 'students',
            'action' => 'update',
            'auditable_id' => $student->id,
        ]);
    }

    public function test_failed_transfer_does_not_write_success_audit(): void
    {
        $admin = $this->createUser(
            'school_admin',
            'audit-failed-transfer'
        );

        $student = $this->createStudent('AUDIT004');
        $this->enroll($student, '6B1');

        try {
            app(StudentTransferService::class)->transfer(
                $admin,
                $student,
                $this->classes['6B1']
            );

            $this->fail('Transfer should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'class_id',
                $exception->errors()
            );
        }

        $this->assertDatabaseMissing('audit_logs', [
            'actor_user_id' => $admin->id,
            'module' => 'students',
            'action' => 'transfer',
            'auditable_id' => $student->id,
            'outcome' => 'success',
        ]);
    }

}