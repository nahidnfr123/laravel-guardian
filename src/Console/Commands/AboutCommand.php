<?php

namespace NahidFerdous\Guardian\Console\Commands;

class AboutCommand extends BaseTyroCommand {
    protected $signature = 'tyro:about';

    protected $description = 'Show Tyro\'s mission, version, and author details';

    public function handle(): int {
        $version = config('tyro.version', 'unknown');

        $this->info('Tyro for Laravel');
        $this->line(str_repeat('-', 40));
        $this->line('• Version: ' . $version);
        $this->line('• Author: Hasin Hayder (@NahidFerdous)');
        $this->line('• Description: Tyro ships a production-ready Laravel API surface with authentication, authorization and powerful CLI commands in minutes.');
        $this->line('• Auth stack: login, registration, profile, roles, privileges, and Sanctum tokens with abilities auto-derived from role + privilege slugs.');
        $this->line('• Security rails: user suspension CLI + REST endpoints that revoke every active token the moment an account is frozen.');
        $this->line('• Automation toolbox: 40+ `tyro:*` commands for onboarding, seeding, logouts, audits, and now quick-token safety checks.');
        $this->line('• Docs + samples: seeders, factories, a Postman collection, and a README packed with route + middleware examples.');
        $this->line('• GitHub: https://github.com/NahidFerdous/tyro');

        return self::SUCCESS;
    }
}
