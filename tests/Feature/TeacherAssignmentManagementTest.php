<?php
namespace Tests\Feature;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\SchoolClass;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\TeacherAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class TeacherAssignmentManagementTest extends TestCase
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
    private function fixture(): array
    {
        $actor=User::create(['name'=>'Admin','email'=>'teacher-admin@example.test',
            'password'=>'TestPassword123!','is_active'=>true]);
        $user=User::create(['name'=>'Teacher','email'=>'teacher-one@example.test',
            'password'=>'TestPassword123!','is_active'=>true]);
        $teacher=TeacherProfile::create(['user_id'=>$user->id,'full_name'=>'Teacher One','status'=>'active']);
        $session=AcademicSession::create(['name'=>'Teacher Assignment Session',
            'start_date'=>'2026-01-01','end_date'=>'2027-12-31','status'=>'planned','is_current'=>false]);
        $batch=Batch::create(['academic_session_id'=>$session->id,'code'=>'TEACH-2026',
            'name'=>'Teacher Test Batch','intake_year'=>2026,'status'=>'active']);
        $class=SchoolClass::create(['academic_session_id'=>$session->id,'batch_id'=>$batch->id,
            'code'=>'6B1','name'=>'Class 6B1','status'=>'active']);
        return [$actor,$teacher,$class];
    }
    public function test_class_assignment_is_created_and_audited(): void
    {
        [$actor,$teacher,$class]=$this->fixture();
        $assignment=app(TeacherAssignmentService::class)->assignClass($actor,[
            'teacher_profile_id'=>$teacher->id,'class_id'=>$class->id,'position'=>'class_teacher']);
        $this->assertDatabaseHas('teacher_class_assignments',['id'=>$assignment->id,
            'class_id'=>$class->id,'teacher_profile_id'=>$teacher->id,'status'=>'active']);
        $this->assertDatabaseHas('audit_logs',['module'=>'teacher_class_assignments',
            'action'=>'create','auditable_id'=>$assignment->id]);
    }
    public function test_duplicate_overlapping_class_teacher_is_rejected(): void
    {
        [$actor,$teacher,$class]=$this->fixture();
        $data=['teacher_profile_id'=>$teacher->id,'class_id'=>$class->id,'position'=>'class_teacher'];
        app(TeacherAssignmentService::class)->assignClass($actor,$data);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TeacherAssignmentService::class)->assignClass($actor,$data);
    }
    public function test_assignment_can_be_ended(): void
    {
        [$actor,$teacher,$class]=$this->fixture();
        $service=app(TeacherAssignmentService::class);
        $item=$service->assignClass($actor,['teacher_profile_id'=>$teacher->id,
            'class_id'=>$class->id,'position'=>'assistant_class_teacher']);
        $service->end($actor,'class',$item->id);
        $this->assertDatabaseHas('teacher_class_assignments',['id'=>$item->id,'status'=>'inactive']);
    }
    public function test_unprivileged_user_cannot_create_assignment_via_route(): void
    {
        [$actor,$teacher,$class]=$this->fixture();
        $this->actingAs($actor)->post(route('teacher-assignments.class.store'),[
            'teacher_profile_id'=>$teacher->id,'class_id'=>$class->id,'position'=>'class_teacher',
        ])->assertForbidden();
        $this->assertDatabaseCount('teacher_class_assignments',0);
    }
}
