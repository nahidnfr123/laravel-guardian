<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use Illuminate\Support\Facades\DB;
use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;
use NahidFerdous\Shield\Models\Privilege;
use NahidFerdous\Shield\Support\ShieldCache;

class PurgePrivilegesCommand extends BaseShieldCommand
{
    protected $signature = 'shield:purge-privileges {--force : Skip confirmation prompt}';

    protected $description = 'Delete every privilege record and detach them from roles';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will delete every privilege. Continue?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        DB::table(config('shield.tables.role_privilege', 'privilege_role'))->truncate();
        $deleted = Privilege::query()->delete();
        ShieldCache::forgetAllUsersWithRoles();

        $this->info("Deleted {$deleted} privilege(s).");

        return self::SUCCESS;
    }
}
