<?php

namespace NahidFerdous\Guardian\Console\Commands;

use NahidFerdous\Guardian\Database\Seeders\TyroSeeder;
use NahidFerdous\Guardian\Support\GuardianCache;

class SeedCommand extends BaseTyroCommand
{
    protected $signature = 'tyro:seed {--force : Run without confirmation (will truncate users and roles tables)}';

    protected $description = 'Run the TyroSeeder to recreate default roles and the bootstrap admin user';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('TyroSeeder truncates your users/roles/privileges tables before seeding. Continue?', false)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        /** @var TyroSeeder $seeder */
        $seeder = $this->laravel->make(TyroSeeder::class);
        $seeder->setContainer($this->laravel)->setCommand($this);
        $seeder->run();
        GuardianCache::forgetAllUsersWithRoles();

        $this->info('TyroSeeder completed. Default roles and admin user restored.');

        return self::SUCCESS;
    }
}
