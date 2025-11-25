<?php

namespace NahidFerdous\Shield\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncateShieldTables();

        $this->call([
            RoleSeeder::class,
            PrivilegeSeeder::class,
            UsersSeeder::class,
        ]);
    }

    protected function truncateShieldTables(): void
    {
        $userClass = config('shield.models.user', config('auth.providers.users.model', 'App\\Models\\User'));
        $userTable = (new $userClass)->getTable();
        $rolesTable = config('shield.tables.roles', 'roles');
        $pivotTable = config('shield.tables.pivot', 'user_roles');
        $privilegesTable = config('shield.tables.privileges', 'privileges');
        $rolePrivilegesTable = config('shield.tables.role_privilege', 'privilege_role');

        Schema::disableForeignKeyConstraints();

        foreach ([$rolePrivilegesTable, $privilegesTable, $pivotTable, $userTable, $rolesTable] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
