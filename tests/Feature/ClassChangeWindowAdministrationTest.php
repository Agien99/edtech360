<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassChangeWindowAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (app()->environment() !== 'testing'
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'edtech360_testing'
            || DB::connection()->getDatabaseName() !== 'edtech360_testing') {
            throw new RuntimeException('Unsafe database: only edtech360_testing is permitted.');
        }
    }

    private function actor(string $role = 'super_admin', bool $canManage = true): User
    {
        Permission::findOrCreate('academic_sessions.manage', 'web');
        $roleModel = Role::findOrCreate($role, 'web');
        if ($canManage) {
            $roleModel->givePermissionTo('academic_sessions.manage');
        }
        $user = User::create([
            'name' => 'Window Test '.$role,
            'email' => $role.'@window-test.example',
            'password' => 'TestingPassword123!',
            'is_active' => true,
        ]);
        $user->assignRole($roleModel);
        return $user;
    }

    private function createAcademicSession(string $name = '2033/2034'): AcademicSession
    {
        return AcademicSession::create([
            'name' => $name,
            'start_date' => '2033-01-01',
            'end_date' => '2034-12-31',
            'status' => 'active',
            'is_current' => false,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'opens_at' => '2033-02-01T09:00',
            'closes_at' => '2033-02-10T17:00',
            'is_enabled' => '1',
        ], $overrides);
    }

    public function test_admin_can_create_window_and_creation_is_audited(): void
    {
        $admin = $this->actor();
        $session = $this->createAcademicSession();
        $this->actingAs($admin)
            ->put(route('class-change-windows.update', $session), $this->payload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('class_change_windows', [
            'academic_session_id' => $session->id,
            'opens_at' => '2033-02-01 09:00:00',
            'closes_at' => '2033-02-10 17:00:00',
            'is_enabled' => 1,
            'updated_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'class_change_windows',
            'action' => 'create',
            'auditable_id' => $session->id,
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_updating_window_replaces_same_row_and_creates_update_audit(): void
    {
        $admin = $this->actor();
        $session = $this->createAcademicSession();
        $url = route('class-change-windows.update', $session);
        $this->actingAs($admin)->put($url, $this->payload())->assertRedirect();
        $this->actingAs($admin)->put($url, $this->payload([
            'opens_at' => '2033-03-01T08:00',
            'closes_at' => '2033-03-05T18:00',
            'is_enabled' => '0',
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('class_change_windows')->where('academic_session_id', $session->id)->count());
        $this->assertDatabaseHas('class_change_windows', [
            'academic_session_id' => $session->id,
            'opens_at' => '2033-03-01 08:00:00',
            'is_enabled' => 0,
        ]);
        $this->assertSame(1, DB::table('audit_logs')->where('module', 'class_change_windows')->where('action', 'create')->count());
        $this->assertSame(1, DB::table('audit_logs')->where('module', 'class_change_windows')->where('action', 'update')->count());
    }

    public function test_closing_time_must_be_after_opening_time(): void
    {
        $admin = $this->actor();
        $session = $this->createAcademicSession();
        $this->actingAs($admin)->put(route('class-change-windows.update', $session), $this->payload([
            'closes_at' => '2033-02-01T09:00',
        ]))->assertSessionHasErrors('closes_at');
        $this->assertDatabaseCount('class_change_windows', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_window_must_fit_inside_academic_session_dates(): void
    {
        $admin = $this->actor();
        $session = $this->createAcademicSession();
        $url = route('class-change-windows.update', $session);
        $this->actingAs($admin)->put($url, $this->payload([
            'opens_at' => '2032-12-31T08:00',
        ]))->assertSessionHasErrors('opens_at');
        $this->actingAs($admin)->put($url, $this->payload([
            'closes_at' => '2035-01-01T08:00',
        ]))->assertSessionHasErrors('opens_at');
        $this->assertDatabaseCount('class_change_windows', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_each_academic_session_has_its_own_window(): void
    {
        $admin = $this->actor();
        $first = $this->createAcademicSession('2033-A');
        $second = $this->createAcademicSession('2033-B');
        $this->actingAs($admin)->put(route('class-change-windows.update', $first), $this->payload())->assertRedirect();
        $this->actingAs($admin)->put(route('class-change-windows.update', $second), $this->payload(['is_enabled' => '0']))->assertRedirect();
        $this->assertDatabaseCount('class_change_windows', 2);
        $this->assertDatabaseHas('class_change_windows', ['academic_session_id' => $first->id, 'is_enabled' => 1]);
        $this->assertDatabaseHas('class_change_windows', ['academic_session_id' => $second->id, 'is_enabled' => 0]);
    }

    public function test_student_cannot_view_or_change_window_configuration(): void
    {
        $this->actor();
        $student = $this->actor('student', false);
        $session = $this->createAcademicSession();
        $this->actingAs($student)->get(route('class-change-windows.index'))->assertForbidden();
        $this->actingAs($student)->put(route('class-change-windows.update', $session), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('class_change_windows', 0);
    }

    public function test_guest_cannot_access_window_configuration(): void
    {
        $session = $this->createAcademicSession();
        $this->get(route('class-change-windows.index'))->assertRedirect();
        $this->put(route('class-change-windows.update', $session), $this->payload())->assertRedirect();
        $this->assertDatabaseCount('class_change_windows', 0);
    }
}
