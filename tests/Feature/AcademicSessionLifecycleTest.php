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

class AcademicSessionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (
            app()->environment() !== 'testing'
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'edtech360_testing'
            || DB::connection()->getDatabaseName() !== 'edtech360_testing'
        ) {
            throw new RuntimeException('Unsafe test database.');
        }
    }

    private function administrator(): User
    {
        Permission::findOrCreate('academic_sessions.manage', 'web');

        $role = Role::findOrCreate('school_admin', 'web');
        $role->givePermissionTo('academic_sessions.manage');

        $user = User::create([
            'name' => 'Academic Administrator',
            'email' => 'academic-admin@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function academicSession(string $name): AcademicSession
    {
        $session = AcademicSession::create([
            'name' => $name,
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'status' => 'planned',
            'is_current' => false,
        ]);

        foreach ([1, 2, 3] as $number) {
            $start = match ($number) {
                1 => '2026-01-01',
                2 => '2026-07-01',
                3 => '2027-01-01',
            };

            $end = match ($number) {
                1 => '2026-06-30',
                2 => '2026-12-31',
                3 => '2027-06-30',
            };

            $session->semesters()->create([
                'number' => $number,
                'name' => "Semester {$number}",
                'start_date' => $start,
                'end_date' => $end,
                'status' => 'planned',
            ]);
        }

        return $session;
    }

    public function test_administrator_can_activate_session(): void
    {
        $this->actingAs($this->administrator());

        $session = $this->academicSession('Session A');

        $this->post(route('academic-sessions.activate', $session))
            ->assertRedirect();

        $this->assertDatabaseHas('academic_sessions', [
            'id' => $session->id,
            'status' => 'active',
            'is_current' => true,
        ]);

        $this->assertDatabaseHas('semesters', [
            'academic_session_id' => $session->id,
            'number' => 1,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'academic_sessions',
            'action' => 'activate',
            'auditable_id' => $session->id,
        ]);
    }

    public function test_second_session_cannot_activate_while_current_exists(): void
    {
        $this->actingAs($this->administrator());

        $first = $this->academicSession('Session A');
        $second = $this->academicSession('Session B');

        $this->post(route('academic-sessions.activate', $first))
            ->assertRedirect();

        $this->from(route('academic-sessions.index'))
            ->post(route('academic-sessions.activate', $second))
            ->assertSessionHasErrors('lifecycle');

        $this->assertSame(
            1,
            AcademicSession::where('is_current', true)->count()
        );
    }

    public function test_semester_progression_and_session_closure(): void
    {
        $this->actingAs($this->administrator());

        $session = $this->academicSession('Session A');

        $this->post(route('academic-sessions.activate', $session));

        $advance = route(
            'academic-sessions.advance-semester',
            $session
        );

        $this->post($advance)->assertRedirect();

        $this->assertDatabaseHas('semesters', [
            'academic_session_id' => $session->id,
            'number' => 1,
            'status' => 'closed',
        ]);

        $this->assertDatabaseHas('semesters', [
            'academic_session_id' => $session->id,
            'number' => 2,
            'status' => 'active',
        ]);

        $this->post($advance)->assertRedirect();

        $this->post($advance)->assertRedirect();

        $this->post(route('academic-sessions.close', $session))
            ->assertRedirect();

        $this->assertDatabaseHas('academic_sessions', [
            'id' => $session->id,
            'status' => 'closed',
            'is_current' => false,
        ]);

        $this->assertSame(
            3,
            $session->semesters()->where('status', 'closed')->count()
        );
    }

    public function test_cannot_close_before_all_semesters_are_closed(): void
    {
        $this->actingAs($this->administrator());

        $session = $this->academicSession('Session A');

        $this->post(route('academic-sessions.activate', $session));

        $this->from(route('academic-sessions.index'))
            ->post(route('academic-sessions.close', $session))
            ->assertSessionHasErrors('lifecycle');

        $this->assertDatabaseHas('academic_sessions', [
            'id' => $session->id,
            'status' => 'active',
            'is_current' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_activate(): void
    {
        $user = User::create([
            'name' => 'Unauthorized User',
            'email' => 'unauthorized@example.test',
            'password' => 'TestPassword123!',
            'is_active' => true,
        ]);

        $session = $this->academicSession('Session A');

        $this->actingAs($user)
            ->post(route('academic-sessions.activate', $session))
            ->assertForbidden();

        $this->assertDatabaseHas('academic_sessions', [
            'id' => $session->id,
            'status' => 'planned',
            'is_current' => false,
        ]);
    }
}