<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;
use NahidFerdous\Shield\Support\ShieldCache;

class DeleteUserRoleCommand extends BaseShieldCommand
{
    protected $signature = 'shield:delete-user-role {--user=} {--role=}';

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
            $this->error('The configured user model does not use the HasShieldRoles trait.');

            return self::FAILURE;
        }

        $role = $this->findRole($roleIdentifier);
        if (! $role) {
            $this->error('Role not found.');

            return self::FAILURE;
        }

        $detached = $user->roles()->detach($role);
        ShieldCache::forgetUser($user);

        if ($detached) {
            $this->info(sprintf('Role "%s" removed from %s.', $role->slug, $user->email));
        } else {
            $this->warn(sprintf('%s did not have the "%s" role.', $user->email, $role->slug));
        }

        return self::SUCCESS;
    }
}
