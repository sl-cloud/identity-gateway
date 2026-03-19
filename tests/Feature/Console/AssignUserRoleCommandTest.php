<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignUserRoleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_assigns_role_by_email(): void
    {
        $user = User::factory()->create(['email' => 'person@example.com']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $this->artisan('user:assign-role', [
            'user' => $user->email,
            'role' => 'admin',
        ])->assertExitCode(0);

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_it_assigns_role_by_numeric_id(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'developer', 'guard_name' => 'web']);

        $this->artisan('user:assign-role', [
            'user' => (string) $user->id,
            'role' => 'developer',
        ])->assertExitCode(0);

        $this->assertTrue($user->fresh()->hasRole('developer'));
    }

    public function test_it_fails_when_user_does_not_exist(): void
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $this->artisan('user:assign-role', [
            'user' => 'missing@example.com',
            'role' => 'admin',
        ])->assertExitCode(1);
    }

    public function test_it_fails_when_role_does_not_exist(): void
    {
        $user = User::factory()->create();

        $this->artisan('user:assign-role', [
            'user' => $user->email,
            'role' => 'not-a-role',
        ])->assertExitCode(1);
    }

    public function test_it_is_idempotent_if_role_already_assigned(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'viewer', 'guard_name' => 'web']);

        $user->assignRole('viewer');

        $this->artisan('user:assign-role', [
            'user' => $user->email,
            'role' => 'viewer',
        ])->assertExitCode(0);

        $this->assertTrue($user->fresh()->hasRole('viewer'));
    }
}
