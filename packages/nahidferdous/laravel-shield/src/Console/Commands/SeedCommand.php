<?php

namespace NahidFerdous\Shield\Console\Commands;

use NahidFerdous\Shield\Database\Seeders\ShieldSeeder;
use NahidFerdous\Shield\Support\ShieldCache;

class SeedCommand extends BaseShieldCommand
{
    protected $signature = 'shield:seed {--force : Run without confirmation (will truncate users and roles tables)}';

    protected $description = 'Run the ShieldSeeder to recreate default roles and the bootstrap admin user';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('ShieldSeeder truncates your users/roles/privileges tables before seeding. Continue?', false)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        /** @var ShieldSeeder $seeder */
        $seeder = $this->laravel->make(ShieldSeeder::class);
        $seeder->setContainer($this->laravel)->setCommand($this);
        $seeder->run();
        ShieldCache::forgetAllUsersWithRoles();

        $this->info('ShieldSeeder completed. Default roles and admin user restored.');

        return self::SUCCESS;
    }
}
