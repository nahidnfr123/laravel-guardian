<?php

namespace NahidFerdous\Guardian\Console\Commands;

use NahidFerdous\Guardian\Support\GuardianCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FlushRolesCommand extends BaseTyroCommand {
    protected $signature = 'tyro:purge-roles {--force : Run without confirmation}';

    protected $description = 'Truncate the roles and pivot tables without re-seeding them';

    public function handle(): int {
        if (!$this->option('force') && !$this->confirm('This will truncate roles and user role assignments. Continue?', false)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        $rolesTable = config('tyro.tables.roles', 'roles');
        $pivotTable = config('tyro.tables.pivot', 'user_roles');

        Schema::disableForeignKeyConstraints();
        DB::table($pivotTable)->truncate();
        DB::table($rolesTable)->truncate();
        Schema::enableForeignKeyConstraints();
        GuardianCache::forgetAllUsersWithRoles();

        $this->info('Roles and pivot tables truncated.');

        return self::SUCCESS;
    }
}
