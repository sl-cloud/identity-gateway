<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // OAuth Client permissions
            'clients:read',
            'clients:create',
            'clients:update',
            'clients:delete',
            'clients:revoke',

            // API Key permissions
            'api-keys:read',
            'api-keys:create',
            'api-keys:revoke',

            // Token permissions
            'tokens:read',
            'tokens:revoke',
            'tokens:introspect',

            // Audit log permissions
            'audit-logs:read',
            'audit-logs:export',

            // User management permissions
            'users:read',
            'users:update',
            'users:manage-roles',

            // System permissions
            'system:manage-keys',
            'system:configure',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions

        // Admin role - has all permissions
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        // Developer role - can manage their own clients, API keys, and tokens
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

        // Viewer role - read-only access
        $viewerRole = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerRole->givePermissionTo([
            'clients:read',
            'api-keys:read',
            'audit-logs:read',
        ]);

        // Assign admin role to the first user if exists
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->assignRole('admin');
        }
    }
}
