<?php

namespace NahidFerdous\Guardian\Console\Commands;

class StarCommand extends BaseTyroCommand
{
    protected $signature = 'tyro:star {--no-open : Only print the link instead of opening a browser}';

    protected $description = 'Open the Tyro GitHub repository so you can star it';

    public function handle(): int
    {
        $url = 'https://github.com/NahidFerdous/tyro';

        if (! $this->option('no-open') && $this->openUrl($url)) {
            $this->info('Opening the Tyro repository in your default browser...');
        } else {
            $this->line('Give Tyro a ⭐ at: '.$url);
        }

        return self::SUCCESS;
    }
}
