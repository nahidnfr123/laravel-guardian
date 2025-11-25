<?php

namespace NahidFerdous\Shield\Console\Commands;

use NahidFerdous\Shield\Models\Role;

class ListRolesCommand extends BaseShieldCommand
{
    protected $signature = 'shield:roles';

    protected $description = 'Display all Shield roles';

    public function handle(): int
    {
        $roles = Role::query()->withCount('users')->orderBy('id')->get(['id', 'name', 'slug']);

        if ($roles->isEmpty()) {
            $this->warn('No roles found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Slug', 'Users'],
            $roles->map(fn ($role) => [
                $role->id,
                $role->name,
                $role->slug,
                $role->users_count,
            ])->toArray()
        );

        return self::SUCCESS;
    }
}
