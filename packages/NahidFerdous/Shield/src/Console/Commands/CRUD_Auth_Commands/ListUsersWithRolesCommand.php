<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;

class ListUsersWithRolesCommand extends BaseShieldCommand
{
    protected $signature = 'shield:users-with-roles';

    protected $description = 'Display users alongside their Shield roles';

    public function handle(): int
    {
        $userClass = $this->userClass();
        $userInstance = new $userClass;

        if (! method_exists($userInstance, 'roles')) {
            $this->error('The configured user model does not include the HasShieldRoles trait.');

            return self::FAILURE;
        }

        $users = $this->newUserQuery()->with('roles')->orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->warn('No users found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Email', 'Roles'],
            $users->map(function ($user) {
                $roles = $user->roles
                    ->map(fn ($role) => sprintf('%s%d %s (%s)', '#', $role->id, $role->slug, $role->name))
                    ->implode(', ');

                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $roles ?: '—',
                ];
            })->toArray()
        );

        return self::SUCCESS;
    }
}
