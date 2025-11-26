<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;
use NahidFerdous\Shield\Support\ShieldCache;

class AssignRoleCommand extends BaseShieldCommand
{
    protected $signature = 'shield:assign-role {--user=} {--role=}';

    protected $description = 'Attach a role to a user';

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

        $user->roles()->syncWithoutDetaching($role);
        ShieldCache::forgetUser($user);

        $this->info(sprintf('Role "%s" assigned to %s.', $role->slug, $user->email));

        return self::SUCCESS;
    }
}
