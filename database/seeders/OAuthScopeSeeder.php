<?php

namespace Database\Seeders;

use App\Models\OAuthScope;
use Illuminate\Database\Seeder;

class OAuthScopeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $scopes = [
            [
                'id' => 'openid',
                'description' => 'OpenID Connect authentication',
                'is_default' => false,
            ],
            [
                'id' => 'user:read',
                'description' => 'Read authenticated user information',
                'is_default' => true,
            ],
            [
                'id' => 'users:read',
                'description' => 'Read all users (admin only)',
                'is_default' => false,
            ],
            [
                'id' => 'resources:read',
                'description' => 'Read resources',
                'is_default' => false,
            ],
            [
                'id' => 'resources:write',
                'description' => 'Create, update, and delete resources',
                'is_default' => false,
            ],
        ];

        foreach ($scopes as $scope) {
            OAuthScope::updateOrCreate(
                ['id' => $scope['id']],
                $scope
            );
        }

        $this->command->info('OAuth scopes seeded successfully.');
    }
}
