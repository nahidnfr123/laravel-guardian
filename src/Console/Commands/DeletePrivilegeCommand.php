<?php

namespace NahidFerdous\Guardian\Console\Commands;

use NahidFerdous\Guardian\Support\GuardianCache;

class DeletePrivilegeCommand extends BaseTyroCommand
{
    protected $signature = 'tyro:delete-privilege {privilege? : Privilege ID or slug}
        {--force : Skip confirmation prompt}';

    protected $description = 'Delete a Tyro privilege record';

    public function handle(): int
    {
        $identifier = $this->argument('privilege');

        if (! $identifier) {
            $identifier = trim((string) $this->ask('Which privilege slug or ID should be deleted?')) ?: null;
        }

        if (! $identifier) {
            $this->error('A privilege identifier is required.');

            return self::FAILURE;
        }

        $privilege = $this->findPrivilege($identifier);

        if (! $privilege) {
            $this->error("Privilege [{$identifier}] not found.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Delete privilege {$privilege->slug}?")) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        GuardianCache::forgetUsersByPrivilege($privilege);
        $privilege->roles()->detach();
        $privilege->delete();

        $this->info("Privilege [{$privilege->slug}] deleted.");

        return self::SUCCESS;
    }
}
