<?php

namespace NahidFerdous\Shield\Console\Commands;

class UserRolesCommand extends BaseShieldCommand
{
    protected $signature = 'shield:user-roles {user? : User ID or email}';

    protected $description = 'Display a user\'s Shield roles and their attached privileges';

    public function handle(): int
    {
        $identifier = $this->argument('user') ?? $this->ask('User ID or email');

        if (! $identifier) {
            $this->error('A user identifier is required.');

            return self::FAILURE;
        }

        $user = $this->findUser($identifier);

        if (! $user) {
            $this->error("User [{$identifier}] not found.");

            return self::FAILURE;
        }

        if (! method_exists($user, 'roles')) {
            $this->error('The configured user model does not include Shield roles.');

            return self::FAILURE;
        }

        $user->loadMissing('roles.privileges');

        $this->info(sprintf('User: %s <%s>', $user->name ?? 'N/A', $user->email));

        $roles = $user->roles;

        if ($roles->isEmpty()) {
            $this->warn('No roles assigned.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Slug', 'Name', 'Privileges'],
            $roles->map(function ($role) {
                $privileges = $role->privileges
                    ->map(fn ($privilege) => sprintf('#%d %s', $privilege->id, $privilege->slug))
                    ->implode(', ');

                return [
                    $role->id,
                    $role->slug,
                    $role->name,
                    $privileges ?: '—',
                ];
            })->toArray()
        );

        return self::SUCCESS;
    }
}
