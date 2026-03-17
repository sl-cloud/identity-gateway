<?php

namespace App\Console\Commands;

use App\Services\SigningKeyService;
use Illuminate\Console\Command;

class RotateSigningKey extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jwt:rotate';

    /**
     * The console command description.
     */
    protected $description = 'Rotate JWT signing keys';

    /**
     * Execute the console command.
     */
    public function handle(SigningKeyService $signingKeyService): int
    {
        $this->info('Rotating JWT signing keys...');

        try {
            $newKey = $signingKeyService->rotateKeys();

            $this->info("New signing key created: {$newKey->id}");
            $this->info("Key activated at: {$newKey->activated_at}");
            $this->info("Key expires at: {$newKey->expires_at}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to rotate keys: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
