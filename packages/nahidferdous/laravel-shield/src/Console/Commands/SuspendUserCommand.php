<?php

namespace NahidFerdous\Shield\Console\Commands;

use Illuminate\Support\Carbon;

class SuspendUserCommand extends BaseShieldCommand
{
    protected $signature = 'shield:suspend-user
        {--user= : User ID or email address}
        {--reason= : Optional suspension reason}
        {--unsuspend : Lift the current suspension instead of applying one}
        {--force : Skip confirmation prompts}';

    protected $description = 'Suspend or unsuspend a Shield user';

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

        if ($this->option('unsuspend')) {
            return $this->liftSuspension($user);
        }

        $reason = $this->option('reason');
        if ($reason === null && $this->input->isInteractive()) {
            $reason = $this->ask('Reason (optional)', '');
        }

        $reason = $reason !== null ? trim($reason) : null;

        if (! $this->option('force')) {
            if (! $this->confirm(sprintf('Suspend %s now?', $user->email))) {
                $this->warn('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $user->forceFill([
            'suspended_at' => Carbon::now(),
            'suspension_reason' => $reason ?: null,
        ])->save();

        $this->revokeTokens($user);

        $this->info(sprintf('User %s suspended%s.', $user->email, $reason ? ' ('.$reason.')' : ''));

        return self::SUCCESS;
    }

    protected function liftSuspension($user): int
    {
        $isSuspended = method_exists($user, 'isSuspended')
            ? $user->isSuspended()
            : (bool) ($user->suspended_at ?? false);

        if (! $isSuspended) {
            $this->info(sprintf('User %s is not suspended.', $user->email));

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            if (! $this->confirm(sprintf('Un-suspend %s now?', $user->email))) {
                $this->warn('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $user->forceFill([
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        $this->info(sprintf('User %s is no longer suspended.', $user->email));

        return self::SUCCESS;
    }

    protected function revokeTokens($user): void
    {
        if (! method_exists($user, 'tokens')) {
            return;
        }

        $deleted = $user->tokens()->delete();

        if ($deleted > 0) {
            $this->warn(sprintf('Revoked %d existing token%s.', $deleted, $deleted === 1 ? '' : 's'));
        }
    }
}
