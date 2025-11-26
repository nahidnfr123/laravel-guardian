<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;
use NahidFerdous\Shield\Models\Privilege;

class ListPrivilegesCommand extends BaseShieldCommand
{
    protected $signature = 'shield:privileges';

    protected $description = 'Display all Shield privileges and their roles';

    public function handle(): int
    {
        $privileges = Privilege::with('roles:id,name,slug')->get(['id', 'name', 'slug']);

        if ($privileges->isEmpty()) {
            $this->warn('No privileges found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Slug', 'Name', 'Roles'],
            $privileges->map(function (Privilege $privilege) {
                $roles = $privilege->roles->map(fn ($role) => sprintf('%s (#%d)', $role->slug, $role->id))->implode(', ');

                return [
                    $privilege->id,
                    $privilege->slug,
                    $privilege->name,
                    $roles ?: '—',
                ];
            })->toArray()
        );

        return self::SUCCESS;
    }
}
