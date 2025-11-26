<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use Laravel\Sanctum\PersonalAccessToken;
use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;

class LogoutCommand extends BaseShieldCommand
{
    protected $signature = 'shield:logout {token?} {--token=}';

    protected $description = 'Delete a single Sanctum token (log out the corresponding user session)';

    public function handle(): int
    {
        $tokenInput = $this->argument('token')
            ?? $this->option('token')
            ?? $this->ask('Paste the full Sanctum token');

        if (! $tokenInput) {
            $this->error('A token is required.');

            return self::FAILURE;
        }

        $token = PersonalAccessToken::findToken($tokenInput);

        if (! $token) {
            $this->error('Token not found.');

            return self::FAILURE;
        }

        $user = $token->tokenable;
        $token->delete();

        $this->info(sprintf('Token "%s" revoked for %s (ID %s).', $token->name, $user?->email ?? 'unknown', $user?->id ?? 'N/A'));

        return self::SUCCESS;
    }
}
