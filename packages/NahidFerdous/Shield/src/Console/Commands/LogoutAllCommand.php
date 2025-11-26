<?php

namespace NahidFerdous\Shield\Console\Commands;

class LogoutAllCommand extends BaseShieldCommand
{
    protected $signature = 'shield:logout-all {--user=} {--force}';

    protected $description = 'Delete every Sanctum token for a specific user';

    public function handle(): int
    {
        $identifier = $this->option('user') ?? $this->ask('User ID or email');

        if (! $identifier) {
            $this->error('A user identifier is required.');

            return self::FAILURE;
        }

        $user = $this->findUser($identifier);
        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $tokenCount = method_exists($user, 'tokens') ? $user->tokens()->count() : null;

        if ($tokenCount === null) {
            $this->error('The configured user model does not use Sanctum\'s HasApiTokens trait.');

            return self::FAILURE;
        }

        if ($tokenCount === 0) {
            $this->warn(sprintf('%s has no active tokens.', $user->email));

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(sprintf('Revoke %s token(s) for %s?', $tokenCount, $user->email))) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        $user->tokens()->delete();

        $this->info(sprintf('All tokens revoked for %s.', $user->email));

        return self::SUCCESS;
    }
}
