<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AssignUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-role
                            {user : User email address or numeric ID}
                            {role : Role name (e.g. admin, developer, viewer)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a role to a user by email or ID';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = (string) $this->argument('user');
        $roleName = (string) $this->argument('role');

        $user = $this->resolveUser($userIdentifier);

        if (! $user) {
            $this->error("User not found for identifier: {$userIdentifier}");

            return self::FAILURE;
        }

        $role = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            $availableRoles = Role::query()
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->pluck('name')
                ->implode(', ');

            $this->error("Role '{$roleName}' does not exist for guard 'web'.");
            $this->line('Available roles: '.($availableRoles !== '' ? $availableRoles : '(none found)'));

            return self::FAILURE;
        }

        if ($user->hasRole($roleName)) {
            $this->info("User {$user->email} already has role '{$roleName}'.");

            return self::SUCCESS;
        }

        $user->assignRole($roleName);

        $currentRoles = $user->fresh()->getRoleNames()->implode(', ');

        $this->info("Assigned role '{$roleName}' to {$user->email}.");
        $this->line('Current roles: '.($currentRoles !== '' ? $currentRoles : '(none)'));

        return self::SUCCESS;
    }

    private function resolveUser(string $identifier): ?User
    {
        if (is_numeric($identifier)) {
            return User::query()->find((int) $identifier);
        }

        return User::query()->where('email', $identifier)->first();
    }
}
