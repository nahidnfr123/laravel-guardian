<?php

namespace NahidFerdous\Shield\Database\Seeders;

use Illuminate\Database\Seeder;
use NahidFerdous\Shield\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin'],
            ['name' => 'User', 'slug' => 'user'],
            ['name' => 'Customer', 'slug' => 'customer'],
            ['name' => 'Editor', 'slug' => 'editor'],
            ['name' => 'All', 'slug' => '*'],
            ['name' => 'Super Admin', 'slug' => 'super-admin'],
        ];

        collect($roles)->each(fn ($role) => Role::create($role));
    }
}
