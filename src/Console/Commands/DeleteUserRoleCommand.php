<?php

namespace NahidFerdous\Guardian\Console\Commands;

use NahidFerdous\Guardian\Support\GuardianCache;

class DeleteUserRoleCommand extends BaseTyroCommand
{
    protected $signature = 'tyro:delete-user-role {--user=} {--role=}';

    protected $description = 'Detach a role from a user';

    public function handle(): int
    {
        $userIdentifier = $this->option('user') ?? $this->ask('User ID or email');
        $roleIdentifier = $this->option('role') ?? $this->ask('Role ID or slug');

        $user = $this->findUser($userIdentifier);
        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        if (! method_exists($user, 'roles')) {
            $this->error('The configured user model does not use the HasGuardianRoles trait.');

            return self::FAILURE;
        }

        $role = $this->findRole($roleIdentifier);
        if (! $role) {
            $this->error('Role not found.');

            return self::FAILURE;
        }

        $detached = $user->roles()->detach($role);
        GuardianCache::forgetUser($user);

        if ($detached) {
            $this->info(sprintf('Role "%s" removed from %s.', $role->slug, $user->email));
        } else {
            $this->warn(sprintf('%s did not have the "%s" role.', $user->email, $role->slug));
        }

        return self::SUCCESS;
    }
}
