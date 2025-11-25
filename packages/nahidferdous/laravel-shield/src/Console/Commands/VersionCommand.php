<?php

namespace NahidFerdous\Shield\Console\Commands;

class VersionCommand extends BaseShieldCommand
{
    protected $signature = 'shield:version';

    protected $description = 'Show the currently installed Shield version';

    public function handle(): int
    {
        $version = config('shield.version', 'unknown');
        $this->info('Shield v'.$version);

        return self::SUCCESS;
    }
}
