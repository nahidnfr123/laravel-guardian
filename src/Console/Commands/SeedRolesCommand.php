<?php

namespace NahidFerdous\Guardian\Console\Commands;

use NahidFerdous\Guardian\Database\Seeders\RoleSeeder;
use NahidFerdous\Guardian\Support\GuardianCache;

class SeedRolesCommand extends BaseTyroCommand
{
    protected $signature = 'tyro:seed-roles {--force : Skip confirmation even though this truncates the roles table}';

    protected $description = 'Re-seed the Tyro roles list (truncates the roles table)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will truncate the roles table before re-seeding. Continue?', false)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        /** @var RoleSeeder $seeder */
        $seeder = $this->laravel->make(RoleSeeder::class);
        $seeder->run();
        GuardianCache::forgetAllUsersWithRoles();

        $this->info('Default Tyro roles have been re-seeded.');

        return self::SUCCESS;
    }
}
