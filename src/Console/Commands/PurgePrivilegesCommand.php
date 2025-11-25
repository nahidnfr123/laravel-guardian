<?php

namespace NahidFerdous\Guardian\Console\Commands;

use NahidFerdous\Guardian\Models\Privilege;
use NahidFerdous\Guardian\Support\GuardianCache;
use Illuminate\Support\Facades\DB;

class PurgePrivilegesCommand extends BaseTyroCommand
{
    protected $signature = 'tyro:purge-privileges {--force : Skip confirmation prompt}';

    protected $description = 'Delete every privilege record and detach them from roles';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will delete every privilege. Continue?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        DB::table(config('tyro.tables.role_privilege', 'privilege_role'))->truncate();
        $deleted = Privilege::query()->delete();
        GuardianCache::forgetAllUsersWithRoles();

        $this->info("Deleted {$deleted} privilege(s).");

        return self::SUCCESS;
    }
}
