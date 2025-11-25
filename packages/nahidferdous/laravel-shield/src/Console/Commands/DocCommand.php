<?php

namespace NahidFerdous\Shield\Console\Commands;

class DocCommand extends BaseShieldCommand
{
    protected $signature = 'shield:doc {--no-open : Only print the docs URL}';

    protected $description = 'Open the Shield documentation in your browser';

    public function handle(): int
    {
        $url = 'https://github.com/NahidFerdous/shield';

        if (! $this->option('no-open') && $this->openUrl($url)) {
            $this->info('Opening Shield documentation...');
        } else {
            $this->line('Docs: '.$url);
        }

        return self::SUCCESS;
    }
}
