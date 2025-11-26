<?php

namespace NahidFerdous\Shield\Console\Commands;

use NahidFerdous\Shield\Support\ShieldCache;

class AttachPrivilegeCommand extends BaseShieldCommand
{
    protected $signature = 'shield:attach-privilege {privilege? : Privilege ID or slug}
        {role? : Role ID or slug}';

    protected $description = 'Attach a privilege to a Shield role';

    public function handle(): int
    {
        $privilegeIdentifier = $this->argument('privilege');
        $roleIdentifier = $this->argument('role');

        if (! $privilegeIdentifier) {
            $privilegeIdentifier = trim((string) $this->ask('Which privilege slug or ID should be attached?')) ?: null;
        }

        if (! $roleIdentifier) {
            $roleIdentifier = trim((string) $this->ask('Which role slug or ID should receive the privilege?')) ?: null;
        }

        $privilege = $this->findPrivilege($privilegeIdentifier);
        $role = $this->findRole($roleIdentifier);
        $displayPrivilege = $privilegeIdentifier ?? 'N/A';
        $displayRole = $roleIdentifier ?? 'N/A';

        if (! $privilege || ! $privilegeIdentifier) {
            $this->error("Privilege [{$displayPrivilege}] not found.");

            return self::FAILURE;
        }

        if (! $role || ! $roleIdentifier) {
            $this->error("Role [{$displayRole}] not found.");

            return self::FAILURE;
        }

        $role->privileges()->syncWithoutDetaching($privilege);
        ShieldCache::forgetUsersByRole($role);

        $this->info("Privilege [{$privilege->slug}] attached to role [{$role->slug}].");

        return self::SUCCESS;
    }
}
