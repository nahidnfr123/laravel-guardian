<?php

namespace NahidFerdous\Shield\Console\Commands;

use NahidFerdous\Shield\Database\Seeders\PrivilegeSeeder;
use NahidFerdous\Shield\Support\ShieldCache;

class SeedPrivilegesCommand extends BaseShieldCommand
{
    protected $signature = 'shield:seed-privileges {--force : Skip confirmation even though this overwrites privileges and role mappings}';

    protected $description = 'Re-seed Shield\'s default privilege definitions and role assignments';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will overwrite existing privilege definitions and role mappings. Continue?', false)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        /** @var PrivilegeSeeder $seeder */
        $seeder = $this->laravel->make(PrivilegeSeeder::class);
        $seeder->setContainer($this->laravel)->setCommand($this);
        $seeder->run();
        ShieldCache::forgetAllUsersWithRoles();

        $this->info('Default Shield privileges and role mappings have been re-seeded.');

        return self::SUCCESS;
    }
}
