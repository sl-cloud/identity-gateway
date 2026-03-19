<?php

namespace Tests\Feature\Dashboard;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Token;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditRecordCreationTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            'clients:read', 'clients:create', 'clients:update', 'clients:revoke',
            'api-keys:read', 'api-keys:create', 'api-keys:revoke',
            'tokens:read', 'tokens:revoke', 'tokens:introspect',
            'audit-logs:read', 'audit-logs:export',
            'users:read', 'users:update', 'users:manage-roles',
            'system:manage-keys', 'system:configure',
        ] as $perm) {
            Permission::create(['name' => $perm, 'guard_name' => 'web']);
        }

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    // -------------------------------------------------------------------
    // Client CRUD audit records
    // -------------------------------------------------------------------

    public function test_client_creation_creates_audit_record(): void
    {
        $this->actingAs($this->admin)->post('/dashboard/clients', [
            'name' => 'Test Client',
            'redirect' => 'https://example.com/callback',
            'confidential' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CLIENT_CREATED->value,
            'user_id' => $this->admin->id,
            'entity_type' => 'oauth_client',
        ]);
    }

    public function test_client_update_creates_audit_record(): void
    {
        $clientRepo = app(ClientRepository::class);
        $client = $clientRepo->create(
            $this->admin->id, 'Existing Client', 'https://example.com/callback',
            null, true, false, false
        );

        $this->actingAs($this->admin)->put("/dashboard/clients/{$client->id}", [
            'name' => 'Updated Client Name',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CLIENT_UPDATED->value,
            'user_id' => $this->admin->id,
            'entity_type' => 'oauth_client',
            'entity_id' => $client->id,
        ]);
    }

    public function test_client_revocation_creates_audit_record(): void
    {
        $clientRepo = app(ClientRepository::class);
        $client = $clientRepo->create(
            $this->admin->id, 'Client To Revoke', 'https://example.com/callback',
            null, true, false, false
        );

        $this->actingAs($this->admin)->delete("/dashboard/clients/{$client->id}");

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::CLIENT_REVOKED->value,
            'user_id' => $this->admin->id,
            'entity_type' => 'oauth_client',
            'entity_id' => $client->id,
        ]);
    }

    // -------------------------------------------------------------------
    // Token audit records
    // -------------------------------------------------------------------

    public function test_token_revocation_creates_audit_record(): void
    {
        $clientRepo = app(ClientRepository::class);
        $client = $clientRepo->create(
            $this->admin->id, 'Token Client', 'https://example.com/callback',
            null, true, false, false
        );

        $token = Token::create([
            'id' => 'test-token-id-123',
            'user_id' => $this->admin->id,
            'client_id' => $client->id,
            'scopes' => ['openid'],
            'revoked' => false,
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($this->admin)->delete("/dashboard/tokens/{$token->id}");

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::TOKEN_REVOKED->value,
            'user_id' => $this->admin->id,
        ]);
    }

    // -------------------------------------------------------------------
    // Verify no audit records for read-only operations
    // -------------------------------------------------------------------

    public function test_listing_clients_does_not_create_audit_record(): void
    {
        $countBefore = AuditLog::count();

        $this->actingAs($this->admin)->get('/dashboard/clients');

        $this->assertEquals($countBefore, AuditLog::count());
    }

    public function test_listing_audit_logs_does_not_create_audit_record(): void
    {
        $countBefore = AuditLog::count();

        $this->actingAs($this->admin)->get('/dashboard/audit-logs');

        $this->assertEquals($countBefore, AuditLog::count());
    }
}
