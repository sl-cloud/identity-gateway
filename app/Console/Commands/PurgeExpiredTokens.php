<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

class PurgeExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oauth:purge-expired-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge expired OAuth access tokens and refresh tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Purging expired tokens...');

        // Delete expired access tokens
        $expiredTokensCount = Token::where('expires_at', '<', now())->delete();
        $this->info("Deleted {$expiredTokensCount} expired access tokens");

        // Delete expired refresh tokens
        $expiredRefreshCount = RefreshToken::where('expires_at', '<', now())->delete();
        $this->info("Deleted {$expiredRefreshCount} expired refresh tokens");

        $this->info('Token purge completed successfully');

        return Command::SUCCESS;
    }
}
