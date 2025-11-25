<?php

namespace NahidFerdous\Guardian\Console\Commands;

use NahidFerdous\Guardian\Database\Seeders\PrivilegeSeeder;
use NahidFerdous\Guardian\Support\GuardianCache;

class SeedPrivilegesCommand extends BaseTyroCommand {
    protected $signature = 'tyro:seed-privileges {--force : Skip confirmation even though this overwrites privileges and role mappings}';

    protected $description = 'Re-seed Tyro\'s default privilege definitions and role assignments';

    public function handle(): int {
        if (!$this->option('force') && !$this->confirm('This will overwrite existing privilege definitions and role mappings. Continue?', false)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        /** @var PrivilegeSeeder $seeder */
        $seeder = $this->laravel->make(PrivilegeSeeder::class);
        $seeder->setContainer($this->laravel)->setCommand($this);
        $seeder->run();
        GuardianCache::forgetAllUsersWithRoles();

        $this->info('Default Tyro privileges and role mappings have been re-seeded.');

        return self::SUCCESS;
    }
}
