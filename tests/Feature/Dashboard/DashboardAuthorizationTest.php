<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    private array $allPermissions = [
        'clients:read',
        'clients:create',
        'clients:update',
        'clients:revoke',
        'api-keys:read',
        'api-keys:create',
        'api-keys:revoke',
        'tokens:read',
        'tokens:introspect',
        'tokens:revoke',
        'audit-logs:read',
    ];

    private array $allRoutes = [
        ['GET', '/dashboard/clients'],
        ['GET', '/dashboard/clients/create'],
        ['POST', '/dashboard/clients'],
        ['GET', '/dashboard/clients/nonexistent-id'],
        ['PUT', '/dashboard/clients/nonexistent-id'],
        ['DELETE', '/dashboard/clients/nonexistent-id'],
        ['GET', '/dashboard/api-keys'],
        ['POST', '/dashboard/api-keys'],
        ['DELETE', '/dashboard/api-keys/nonexistent-id'],
        ['GET', '/dashboard/tokens'],
        ['POST', '/dashboard/tokens/inspect'],
        ['DELETE', '/dashboard/tokens/nonexistent-id'],
        ['GET', '/dashboard/audit-logs'],
        ['GET', '/dashboard/audit-logs/nonexistent-id'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->allPermissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    // ---------------------------------------------------------------
    // Negative tests: user with NO permissions gets 403 everywhere
    // ---------------------------------------------------------------

    public function test_user_without_permissions_cannot_access_get_clients(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard/clients')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_get_clients_create(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard/clients/create')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_post_clients(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/clients')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_get_client(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard/clients/nonexistent-id')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_put_client(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/dashboard/clients/nonexistent-id')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_delete_client(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/dashboard/clients/nonexistent-id')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_get_api_keys(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard/api-keys')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_post_api_keys(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/api-keys')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_delete_api_key(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/dashboard/api-keys/nonexistent-id')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_get_tokens(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard/tokens')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_post_tokens_inspect(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/tokens/inspect')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_delete_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/dashboard/tokens/nonexistent-id')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_get_audit_logs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard/audit-logs')->assertStatus(403);
    }

    public function test_user_without_permissions_cannot_access_get_audit_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard/audit-logs/nonexistent-id')->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Role boundary tests: viewer role has limited read-only access
    // ---------------------------------------------------------------

    private function createViewerUser(): User
    {
        $viewerRole = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerRole->givePermissionTo([
            'clients:read',
            'api-keys:read',
            'audit-logs:read',
        ]);

        $user = User::factory()->create();
        $user->assignRole('viewer');

        return $user;
    }

    public function test_viewer_can_access_get_clients(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->get('/dashboard/clients')->assertStatus(200);
    }

    public function test_viewer_can_access_get_client(): void
    {
        $user = $this->createViewerUser();

        $response = $this->actingAs($user)->get('/dashboard/clients/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_viewer_can_access_get_api_keys(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->get('/dashboard/api-keys')->assertStatus(200);
    }

    public function test_viewer_can_access_get_audit_logs(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->get('/dashboard/audit-logs')->assertStatus(200);
    }

    public function test_viewer_can_access_get_audit_log(): void
    {
        $user = $this->createViewerUser();

        $response = $this->actingAs($user)->get('/dashboard/audit-logs/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_viewer_cannot_access_get_clients_create(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->get('/dashboard/clients/create')->assertStatus(403);
    }

    public function test_viewer_cannot_access_post_clients(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->post('/dashboard/clients')->assertStatus(403);
    }

    public function test_viewer_cannot_access_put_client(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->put('/dashboard/clients/nonexistent-id')->assertStatus(403);
    }

    public function test_viewer_cannot_access_delete_client(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->delete('/dashboard/clients/nonexistent-id')->assertStatus(403);
    }

    public function test_viewer_cannot_access_post_api_keys(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->post('/dashboard/api-keys')->assertStatus(403);
    }

    public function test_viewer_cannot_access_delete_api_key(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->delete('/dashboard/api-keys/nonexistent-id')->assertStatus(403);
    }

    public function test_viewer_cannot_access_get_tokens(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->get('/dashboard/tokens')->assertStatus(403);
    }

    public function test_viewer_cannot_access_post_tokens_inspect(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->post('/dashboard/tokens/inspect')->assertStatus(403);
    }

    public function test_viewer_cannot_access_delete_token(): void
    {
        $user = $this->createViewerUser();

        $this->actingAs($user)->delete('/dashboard/tokens/nonexistent-id')->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Positive tests: developer role with specific permissions
    // ---------------------------------------------------------------

    private function createDeveloperUser(): User
    {
        $developerRole = Role::create(['name' => 'developer', 'guard_name' => 'web']);
        $developerRole->givePermissionTo([
            'clients:read',
            'clients:create',
            'clients:update',
            'clients:revoke',
            'api-keys:read',
            'api-keys:create',
            'api-keys:revoke',
            'tokens:read',
            'tokens:revoke',
            'tokens:introspect',
            'audit-logs:read',
        ]);

        $user = User::factory()->create();
        $user->assignRole('developer');

        return $user;
    }

    public function test_developer_can_access_get_clients(): void
    {
        $user = $this->createDeveloperUser();

        $this->actingAs($user)->get('/dashboard/clients')->assertStatus(200);
    }

    public function test_developer_can_access_get_clients_create(): void
    {
        $user = $this->createDeveloperUser();

        $this->actingAs($user)->get('/dashboard/clients/create')->assertStatus(200);
    }

    public function test_developer_can_access_post_clients(): void
    {
        $user = $this->createDeveloperUser();

        $response = $this->actingAs($user)->post('/dashboard/clients');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_developer_can_access_get_client(): void
    {
        $user = $this->createDeveloperUser();

        $response = $this->actingAs($user)->get('/dashboard/clients/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_developer_can_access_put_client(): void
    {
        $user = $this->createDeveloperUser();

        $response = $this->actingAs($user)->put('/dashboard/clients/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_developer_can_access_get_api_keys(): void
    {
        $user = $this->createDeveloperUser();

        $this->actingAs($user)->get('/dashboard/api-keys')->assertStatus(200);
    }

    public function test_developer_can_access_post_api_keys(): void
    {
        $user = $this->createDeveloperUser();

        $response = $this->actingAs($user)->post('/dashboard/api-keys');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_developer_can_access_get_tokens(): void
    {
        $user = $this->createDeveloperUser();

        $this->actingAs($user)->get('/dashboard/tokens')->assertStatus(200);
    }

    public function test_developer_can_access_post_tokens_inspect(): void
    {
        $user = $this->createDeveloperUser();

        $response = $this->actingAs($user)->post('/dashboard/tokens/inspect');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_developer_can_access_get_audit_logs(): void
    {
        $user = $this->createDeveloperUser();

        $this->actingAs($user)->get('/dashboard/audit-logs')->assertStatus(200);
    }

    public function test_developer_can_access_delete_client(): void
    {
        $user = $this->createDeveloperUser();

        $response = $this->actingAs($user)->delete('/dashboard/clients/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_developer_can_access_delete_api_key(): void
    {
        $user = $this->createDeveloperUser();

        $response = $this->actingAs($user)->delete('/dashboard/api-keys/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_developer_can_access_delete_token(): void
    {
        $user = $this->createDeveloperUser();

        $response = $this->actingAs($user)->delete('/dashboard/tokens/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    // ---------------------------------------------------------------
    // Admin tests: user with all permissions can access everything
    // ---------------------------------------------------------------

    private function createAdminUser(): User
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($this->allPermissions);

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_access_get_clients(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->get('/dashboard/clients')->assertStatus(200);
    }

    public function test_admin_can_access_get_clients_create(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->get('/dashboard/clients/create')->assertStatus(200);
    }

    public function test_admin_can_access_post_clients(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post('/dashboard/clients');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_admin_can_access_get_client(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get('/dashboard/clients/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_admin_can_access_put_client(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->put('/dashboard/clients/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_admin_can_access_delete_client(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->delete('/dashboard/clients/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_admin_can_access_get_api_keys(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->get('/dashboard/api-keys')->assertStatus(200);
    }

    public function test_admin_can_access_post_api_keys(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post('/dashboard/api-keys');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_admin_can_access_delete_api_key(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->delete('/dashboard/api-keys/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_admin_can_access_get_tokens(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->get('/dashboard/tokens')->assertStatus(200);
    }

    public function test_admin_can_access_post_tokens_inspect(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post('/dashboard/tokens/inspect');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_admin_can_access_delete_token(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->delete('/dashboard/tokens/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_admin_can_access_get_audit_logs(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->get('/dashboard/audit-logs')->assertStatus(200);
    }

    public function test_admin_can_access_get_audit_log(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get('/dashboard/audit-logs/nonexistent-id');
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
