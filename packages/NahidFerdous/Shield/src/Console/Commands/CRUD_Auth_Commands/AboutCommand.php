<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;

class AboutCommand extends BaseShieldCommand
{
    protected $signature = 'shield:about';

    protected $description = 'Show Shield\'s mission, version, and author details';

    public function handle(): int
    {
        $version = config('shield.version', 'unknown');

        $this->info('Shield for Laravel');
        $this->line(str_repeat('-', 40));
        $this->line('• Version: '.$version);
        $this->line('• Author: Nahid Ferdous (@NahidFerdous)');
        $this->line('• Description: Shield ships a production-ready Laravel API surface with authentication, authorization and powerful CLI commands in minutes.');
        $this->line('• Auth stack: login, registration, profile, roles, privileges, and Sanctum tokens with abilities auto-derived from role + privilege slugs.');
        $this->line('• Security rails: user suspension CLI + REST endpoints that revoke every active token the moment an account is frozen.');
        $this->line('• Automation toolbox: 40+ `shield:*` commands for onboarding, seeding, logouts, audits, and now quick-token safety checks.');
        $this->line('• Docs + samples: seeders, factories, a Postman collection, and a README packed with route + middleware examples.');
        $this->line('• GitHub: https://github.com/nahidnfr123/shield');

        return self::SUCCESS;
    }
}
