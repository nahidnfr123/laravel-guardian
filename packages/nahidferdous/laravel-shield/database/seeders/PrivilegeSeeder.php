<?php

namespace NahidFerdous\Shield\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use NahidFerdous\Shield\Models\Privilege;
use NahidFerdous\Shield\Models\Role;

class PrivilegeSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'name' => 'Generate Reports',
                'slug' => 'report.generate',
                'description' => 'Allows generating system-wide reports.',
                'roles' => ['admin', 'super-admin'],
            ],
            [
                'name' => 'Manage Users',
                'slug' => 'users.manage',
                'description' => 'Allows creating, editing, and deleting users.',
                'roles' => ['admin', 'super-admin'],
            ],
            [
                'name' => 'Manage Roles',
                'slug' => 'roles.manage',
                'description' => 'Allows editing Shield roles.',
                'roles' => ['super-admin'],
            ],
            [
                'name' => 'View Billing',
                'slug' => 'billing.view',
                'description' => 'Allows viewing billing statements.',
                'roles' => ['admin', 'user'],
            ],
            [
                'name' => 'Wildcard',
                'slug' => '*',
                'description' => 'Grants every privilege.',
                'roles' => ['*'],
            ],
        ];

        $roleMap = Role::query()->whereIn('slug', collect($definitions)->flatMap(fn ($definition) => $definition['roles'])->unique()->all())
            ->get()
            ->keyBy('slug');

        collect($definitions)->each(function (array $definition) use ($roleMap): void {
            $privilege = Privilege::updateOrCreate(
                ['slug' => $definition['slug']],
                Arr::only($definition, ['name', 'description'])
            );

            $roleIds = collect($definition['roles'])
                ->map(fn ($slug) => $roleMap->get($slug)?->id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (! empty($roleIds)) {
                $privilege->roles()->sync($roleIds);
            }
        });
    }
}
