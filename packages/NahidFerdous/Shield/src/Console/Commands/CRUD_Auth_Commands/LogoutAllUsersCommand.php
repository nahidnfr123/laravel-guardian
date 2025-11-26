<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use Laravel\Sanctum\PersonalAccessToken;
use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;

class LogoutAllUsersCommand extends BaseShieldCommand
{
    protected $signature = 'shield:logout-all-users {--force : Skip the confirmation prompt}';

    protected $description = 'Revoke every Sanctum token issued for all users';

    public function handle(): int
    {
        $count = PersonalAccessToken::count();

        if ($count === 0) {
            $this->info('No Sanctum tokens were found.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("This will revoke {$count} tokens for every user. Continue?")) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        PersonalAccessToken::query()->delete();

        $this->info(sprintf('Revoked %s tokens across all users.', $count));

        return self::SUCCESS;
    }
}
