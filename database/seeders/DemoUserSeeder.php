<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo user
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@identitygateway.test'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
            ]
        );

        // Create confidential client (for Authorization Code + Client Credentials)
        $confidentialClient = Client::firstOrCreate(
            ['name' => 'Demo Confidential Client'],
            [
                'user_id' => $demoUser->id,
                'redirect' => 'http://localhost:8000/demo/callback',
                'personal_access_client' => false,
                'password_client' => false,
                'revoked' => false,
            ]
        );

        // Generate secret for confidential client if it doesn't exist
        if (! $confidentialClient->secret) {
            $confidentialClient->secret = Str::random(40);
            $confidentialClient->save();
        }

        // Create public client (for PKCE flow)
        $publicClient = Client::firstOrCreate(
            ['name' => 'Demo Public Client (PKCE)'],
            [
                'user_id' => $demoUser->id,
                'secret' => null,
                'redirect' => 'http://localhost:8000/demo/callback',
                'personal_access_client' => false,
                'password_client' => false,
                'revoked' => false,
            ]
        );

        $this->command->info('Demo user created: demo@identitygateway.test / password');
        $this->command->info('Confidential Client ID: '.$confidentialClient->id);
        $this->command->info('Confidential Client Secret: '.$confidentialClient->secret);
        $this->command->info('Public Client ID: '.$publicClient->id);
    }
}
