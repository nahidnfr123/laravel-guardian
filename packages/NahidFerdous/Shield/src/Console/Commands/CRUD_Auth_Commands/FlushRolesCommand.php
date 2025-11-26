<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;
use NahidFerdous\Shield\Support\ShieldCache;

class FlushRolesCommand extends BaseShieldCommand
{
    protected $signature = 'shield:purge-roles {--force : Run without confirmation}';

    protected $description = 'Truncate the roles and pivot tables without re-seeding them';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will truncate roles and user role assignments. Continue?', false)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        $rolesTable = config('shield.tables.roles', 'roles');
        $pivotTable = config('shield.tables.pivot', 'user_roles');

        Schema::disableForeignKeyConstraints();
        DB::table($pivotTable)->truncate();
        DB::table($rolesTable)->truncate();
        Schema::enableForeignKeyConstraints();
        ShieldCache::forgetAllUsersWithRoles();

        $this->info('Roles and pivot tables truncated.');

        return self::SUCCESS;
    }
}
