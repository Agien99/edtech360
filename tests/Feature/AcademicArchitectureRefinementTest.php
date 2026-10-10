<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\User;
use Tests\TestCase;

use App\Services\AcademicSessionService;

class AcademicArchitectureRefinementTest extends TestCase
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

    public function test_session_creation_generates_one_batch_and_two_audits(): void
    {
        $user=User::create(['name'=>'Admin','email'=>'arch-admin@example.test',
            'password'=>'TestPassword123!','is_active'=>true]);
        $session=app(AcademicSessionService::class)->create($user,[
            'name'=>'2028/2029','start_date'=>'2028-06-01','end_date'=>'2029-12-31',
        ]);
        $this->assertDatabaseHas('batches',['academic_session_id'=>$session->id,
            'intake_year'=>2028,'status'=>'active']);
        $this->assertSame(1,Batch::where('academic_session_id',$session->id)->count());
        $this->assertSame(2,DB::table('audit_logs')->where('actor_user_id',$user->id)->count());
    }
    public function test_new_batch_dates_are_derived_from_session(): void
    {
        $user=User::create(['name'=>'Admin','email'=>'arch-admin2@example.test',
            'password'=>'TestPassword123!','is_active'=>true]);
        $session=app(AcademicSessionService::class)->create($user,[
            'name'=>'2029/2030','start_date'=>'2029-06-01','end_date'=>'2030-12-31',
        ]);
        $this->assertNull($session->fresh()->batch->start_date);
        $this->assertSame('2029-06-01',$session->fresh()->start_date->toDateString());
    }
    public function test_multiple_sessions_create_distinct_batches(): void
    {
        $user=User::create(['name'=>'Admin','email'=>'multi-session@example.test',
            'password'=>'TestPassword123!','is_active'=>true]);
        $service=app(AcademicSessionService::class);
        $a=$service->create($user,['name'=>'2031/2032','start_date'=>'2031-06-01','end_date'=>'2032-12-31']);
        $b=$service->create($user,['name'=>'2032/2033','start_date'=>'2032-06-01','end_date'=>'2033-12-31']);
        $this->assertNotEquals($a->batch->id,$b->batch->id);
        $this->assertSame(2,Batch::count());
        $this->assertDatabaseHas('batches',['academic_session_id'=>$a->id]);
        $this->assertDatabaseHas('batches',['academic_session_id'=>$b->id]);
    }
    public function test_batch_creation_is_rolled_back_if_session_fails(): void
    {
        $user=User::create(['name'=>'Admin','email'=>'rollback-session@example.test',
            'password'=>'TestPassword123!','is_active'=>true]);
        $service=app(AcademicSessionService::class);
        $service->create($user,['name'=>'2034/2035','start_date'=>'2034-06-01','end_date'=>'2035-12-31']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        $service->create($user,['name'=>'2034/2035','start_date'=>'2034-06-01','end_date'=>'2035-12-31']);
    }

}
