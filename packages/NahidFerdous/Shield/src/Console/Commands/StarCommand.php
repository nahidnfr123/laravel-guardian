<?php

namespace NahidFerdous\Shield\Console\Commands;

class StarCommand extends BaseShieldCommand
{
    protected $signature = 'shield:star {--no-open : Only print the link instead of opening a browser}';

    protected $description = 'Open the Shield GitHub repository so you can star it';

    public function handle(): int
    {
        $url = 'https://github.com/nahidnfr123/laravel-shield';

        if (! $this->option('no-open') && $this->openUrl($url)) {
            $this->info('Opening the Shield repository in your default browser...');
        } else {
            $this->line('Give Shield a ⭐ at: '.$url);
        }

        return self::SUCCESS;
    }
}
