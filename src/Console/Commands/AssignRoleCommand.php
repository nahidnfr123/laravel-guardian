<?php

namespace NahidFerdous\Guardian\Console\Commands;

use NahidFerdous\Guardian\Support\GuardianCache;

class AssignRoleCommand extends BaseTyroCommand
{
    protected $signature = 'tyro:assign-role {--user=} {--role=}';

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
            $this->error('The configured user model does not use the HasGuardianRoles trait.');

            return self::FAILURE;
        }

        $role = $this->findRole($roleIdentifier);
        if (! $role) {
            $this->error('Role not found.');

            return self::FAILURE;
        }

        $user->roles()->syncWithoutDetaching($role);
        GuardianCache::forgetUser($user);

        $this->info(sprintf('Role "%s" assigned to %s.', $role->slug, $user->email));

        return self::SUCCESS;
    }
}
