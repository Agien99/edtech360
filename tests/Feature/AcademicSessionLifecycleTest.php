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

class AcademicSessionLifecycleTest extends TestCase
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

    private function admin(): User
    {
        Permission::findOrCreate('academic_sessions.manage','web');
        $role=Role::findOrCreate('school_admin','web');
        $role->givePermissionTo('academic_sessions.manage');
        $user=User::create(['name'=>'Session Admin','email'=>'session-admin@example.test',
            'password'=>'TestPassword123!','is_active'=>true]);
        $user->assignRole($role);
        return $user;
    }
    private function fixture(string $name, string $start): AcademicSession
    {
        $session=AcademicSession::create(['name'=>$name,'start_date'=>$start,
            'end_date'=>'2029-12-31','status'=>'planned','is_current'=>false]);
        Batch::create(['academic_session_id'=>$session->id,'code'=>'F6-'.$session->id,
            'name'=>$name,'intake_year'=>(int)substr($start,0,4),'status'=>'active']);
        foreach ([1,2,3] as $number) {
            $year=(int)substr($start,0,4);
            $first=$number===1?"$year-01-01":($number===2?"$year-07-01":($year+1)."-01-01");
            $last=$number===1?"$year-06-30":($number===2?"$year-12-31":($year+1)."-06-30");
            $session->semesters()->create(['number'=>$number,'name'=>"Semester $number",
                'start_date'=>$first,'end_date'=>$last,'status'=>'planned']);
        }
        return $session;
    }
    public function test_two_sessions_can_be_active_and_progress_independently(): void
    {
        $this->actingAs($this->admin());
        $a=$this->fixture('A','2026-01-01');
        $b=$this->fixture('B','2027-01-01');
        $this->post(route('academic-sessions.activate',$a))->assertRedirect();
        $this->post(route('academic-sessions.activate',$b))->assertRedirect();
        $this->assertSame(2,AcademicSession::where('status','active')->count());
        $this->post(route('academic-sessions.advance-semester',$a))->assertRedirect();
        $this->assertDatabaseHas('semesters',['academic_session_id'=>$a->id,'number'=>2,'status'=>'active']);
        $this->assertDatabaseHas('semesters',['academic_session_id'=>$b->id,'number'=>1,'status'=>'active']);
    }
    public function test_close_completes_its_own_batch(): void
    {
        $this->actingAs($this->admin());
        $session=$this->fixture('C','2026-01-01');
        $this->post(route('academic-sessions.activate',$session));
        for ($i=0;$i<3;$i++) $this->post(route('academic-sessions.advance-semester',$session))->assertRedirect();
        $this->post(route('academic-sessions.close',$session))->assertRedirect();
        $this->assertDatabaseHas('academic_sessions',['id'=>$session->id,'status'=>'closed']);
        $this->assertDatabaseHas('batches',['academic_session_id'=>$session->id,'status'=>'completed']);
    }
    public function test_unauthorized_user_cannot_activate(): void
    {
        $user=User::create(['name'=>'No Permission','email'=>'no-permission@example.test',
            'password'=>'TestPassword123!','is_active'=>true]);
        $session=$this->fixture('D','2026-01-01');
        $this->actingAs($user)->post(route('academic-sessions.activate',$session))->assertForbidden();
    }
    public function test_cannot_close_until_all_semesters_closed(): void
    {
        $this->actingAs($this->admin());
        $s=$this->fixture('E','2026-01-01');
        $this->post(route('academic-sessions.activate',$s))->assertRedirect();
        $this->post(route('academic-sessions.close',$s))->assertSessionHasErrors('lifecycle');
        $this->assertDatabaseHas('academic_sessions',['id'=>$s->id,'status'=>'active']);
    }
    public function test_activation_requires_three_semesters(): void
    {
        $this->actingAs($this->admin());
        $s=$this->fixture('F','2026-01-01');
        $s->semesters()->where('number',3)->delete();
        $this->post(route('academic-sessions.activate',$s))->assertSessionHasErrors('lifecycle');
        $this->assertDatabaseHas('academic_sessions',['id'=>$s->id,'status'=>'planned']);
    }
    public function test_session_advancement_does_not_affect_other_active_session(): void
    {
        $this->actingAs($this->admin());
        $a=$this->fixture('G','2026-01-01');
        $b=$this->fixture('H','2027-01-01');
        $this->post(route('academic-sessions.activate',$a))->assertRedirect();
        $this->post(route('academic-sessions.activate',$b))->assertRedirect();
        $this->post(route('academic-sessions.advance-semester',$a))->assertRedirect();
        $this->post(route('academic-sessions.advance-semester',$a))->assertRedirect();
        $this->assertDatabaseHas('semesters',['academic_session_id'=>$a->id,'number'=>3,'status'=>'active']);
        $this->assertDatabaseHas('semesters',['academic_session_id'=>$b->id,'number'=>1,'status'=>'active']);
    }
    public function test_cannot_advance_after_all_three_semesters_closed(): void
    {
        $this->actingAs($this->admin());
        $s=$this->fixture('I','2026-01-01');
        $this->post(route('academic-sessions.activate',$s))->assertRedirect();
        for($i=0;$i<3;$i++) $this->post(route('academic-sessions.advance-semester',$s))->assertRedirect();
        $this->post(route('academic-sessions.advance-semester',$s))->assertSessionHasErrors('lifecycle');
    }
    public function test_closing_one_session_keeps_other_active(): void
    {
        $this->actingAs($this->admin());
        $a=$this->fixture('J','2026-01-01');
        $b=$this->fixture('K','2027-01-01');
        $this->post(route('academic-sessions.activate',$a))->assertRedirect();
        $this->post(route('academic-sessions.activate',$b))->assertRedirect();
        for($i=0;$i<3;$i++) $this->post(route('academic-sessions.advance-semester',$a))->assertRedirect();
        $this->post(route('academic-sessions.close',$a))->assertRedirect();
        $this->assertDatabaseHas('academic_sessions',['id'=>$b->id,'status'=>'active']);
        $this->assertDatabaseHas('batches',['academic_session_id'=>$b->id,'status'=>'active']);
    }

    public function test_activation_rejects_session_without_batch(): void
    {
        $this->actingAs($this->admin());
        $session=$this->fixture('Missing Batch','2026-01-01');
        $session->batch()->delete();
        $this->post(route('academic-sessions.activate',$session))->assertSessionHasErrors('lifecycle');
        $this->assertDatabaseHas('academic_sessions',['id'=>$session->id,'status'=>'planned']);
    }

}
