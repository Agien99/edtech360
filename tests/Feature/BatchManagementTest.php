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

class BatchManagementTest extends TestCase
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

    public function test_legacy_independent_batch_registration_is_not_available(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('batches.store'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('batches.update'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('batches.change-status'));
    }
    public function test_one_batch_per_session_is_enforced_by_unique_index(): void
    {
        $session=AcademicSession::create(['name'=>'Unique session','start_date'=>'2026-01-01',
            'end_date'=>'2027-12-31','is_current'=>false,'status'=>'planned']);
        Batch::create(['academic_session_id'=>$session->id,'code'=>'UNQ-1',
            'name'=>'One','intake_year'=>2026,'status'=>'active']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        Batch::create(['academic_session_id'=>$session->id,'code'=>'UNQ-2',
            'name'=>'Two','intake_year'=>2026,'status'=>'active']);
    }
    public function test_read_only_batch_list_shows_session_name(): void
    {
        Permission::findOrCreate('batches.view','web');
        $role=Role::findOrCreate('school_admin','web');
        $role->givePermissionTo('batches.view');
        $user=User::create(['name'=>'Batch Viewer','email'=>'batch-viewer@example.test',
            'password'=>'TestPassword123!','is_active'=>true]);
        $user->assignRole($role);
        $session=AcademicSession::create(['name'=>'Form 6 Session 2030','start_date'=>'2030-06-01',
            'end_date'=>'2031-12-31','is_current'=>false,'status'=>'planned']);
        Batch::create(['academic_session_id'=>$session->id,'code'=>'F6-2030',
            'name'=>'Intake 2030','intake_year'=>2030,'status'=>'active']);
        $this->actingAs($user)->get(route('batches.index'))->assertOk()
            ->assertSee('Form 6 Session 2030')->assertSee('F6-2030');
    }
    public function test_batch_cannot_duplicate_another_session_batch(): void
    {
        $session=AcademicSession::create(['name'=>'One-to-One','start_date'=>'2026-01-01',
            'end_date'=>'2027-12-31','is_current'=>false,'status'=>'planned']);
        Batch::create(['academic_session_id'=>$session->id,'code'=>'COHORT-A',
            'name'=>'A','intake_year'=>2026,'status'=>'active']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        Batch::create(['academic_session_id'=>$session->id,'code'=>'COHORT-B',
            'name'=>'B','intake_year'=>2026,'status'=>'active']);
    }

}
