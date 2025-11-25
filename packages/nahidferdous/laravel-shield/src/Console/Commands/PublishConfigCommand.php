<?php

namespace NahidFerdous\Shield\Console\Commands;

class PublishConfigCommand extends BaseShieldCommand
{
    protected $signature = 'shield:publish-config {--force : Overwrite the existing config file if it already exists}';

    protected $description = 'Publish Shield\'s configuration file into your application';

    public function handle(): int
    {
        $options = [
            '--provider' => 'NahidFerdous\\Shield\\Providers\\ShieldServiceProvider',
            '--tag' => 'shield-config',
        ];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        $this->call('vendor:publish', $options);

        $this->info('Shield configuration published to config/shield.php');

        return self::SUCCESS;
    }
}
