<?php
namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentPlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class StudentPlacementManagementTest extends TestCase
{
    use RefreshDatabase;
    protected function beforeRefreshingDatabase(): void
    {
        if (app()->environment() !== 'testing' || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'edtech360_testing'
            || DB::connection()->getDatabaseName() !== 'edtech360_testing') {
            throw new RuntimeException('Unsafe testing database.');
        }
    }
    private function data(): array
    {
        $user = User::create(['name'=>'Admin', 'email'=>'placement-admin@example.test', 'password'=>'Password123!', 'is_active'=>true]);
        $session = AcademicSession::create(['name'=>'2031/2032','start_date'=>'2031-01-01','end_date'=>'2032-12-31','status'=>'active','is_current'=>false]);
        $batch = Batch::create(['academic_session_id'=>$session->id,'code'=>'F6-2031','name'=>'Intake 2031','intake_year'=>2031,'status'=>'active']);
        $class = SchoolClass::create(['academic_session_id'=>$session->id,'batch_id'=>$batch->id,'code'=>'6B1','name'=>'Form 6 B1','status'=>'active']);
        $semester = Semester::create(['academic_session_id'=>$session->id,'number'=>1,'name'=>'Semester 1','start_date'=>'2031-01-01','end_date'=>'2031-06-30','status'=>'active']);
        $student = StudentProfile::create(['student_number'=>'ENR-1','full_name'=>'Test Student','status'=>'active']);
        return [$user,$session,$batch,$class,$semester,$student];
    }
    public function test_placing_student_creates_intake_membership_and_enrollment(): void
    {
        [$user,$session,$batch,$class,$semester,$student]=$this->data();
        app(StudentPlacementService::class)->place($user,['student_profile_id'=>$student->id,'class_id'=>$class->id,'semester_id'=>$semester->id,'enrolled_at'=>'2031-02-01']);
        $this->assertDatabaseHas('student_batch',['student_profile_id'=>$student->id,'batch_id'=>$batch->id]);
        $this->assertDatabaseHas('student_class_enrollments',['student_profile_id'=>$student->id,'class_id'=>$class->id,'semester_id'=>$semester->id,'status'=>'active']);
        $this->assertSame(2,DB::table('audit_logs')->count());
    }
    public function test_duplicate_active_placement_is_rejected_without_new_membership(): void
    {
        [$user,$session,$batch,$class,$semester,$student]=$this->data();
        $payload=['student_profile_id'=>$student->id,'class_id'=>$class->id,'semester_id'=>$semester->id,'enrolled_at'=>'2031-02-01'];
        app(StudentPlacementService::class)->place($user,$payload);
        try {
            app(StudentPlacementService::class)->place($user,$payload);
            $this->fail('Duplicate placement should have failed.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('student_profile_id',$e->errors());
        }
        $this->assertDatabaseCount('student_batch',1);
        $this->assertDatabaseCount('student_class_enrollments',1);
    }
    public function test_other_session_semester_is_rejected(): void
    {
        [$user,$session,$batch,$class,$semester,$student]=$this->data();
        $other=AcademicSession::create(['name'=>'2032/2033','start_date'=>'2032-01-01','end_date'=>'2033-12-31','status'=>'active','is_current'=>false]);
        $otherSemester=Semester::create(['academic_session_id'=>$other->id,'number'=>1,'name'=>'Semester 1','start_date'=>'2032-01-01','end_date'=>'2032-06-30','status'=>'active']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(StudentPlacementService::class)->place($user,['student_profile_id'=>$student->id,'class_id'=>$class->id,'semester_id'=>$otherSemester->id,'enrolled_at'=>'2031-02-01']);
    }
}
