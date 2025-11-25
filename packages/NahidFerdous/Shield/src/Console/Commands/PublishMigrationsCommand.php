<?php

namespace NahidFerdous\Shield\Console\Commands;

class PublishMigrationsCommand extends BaseShieldCommand
{
    protected $signature = 'shield:publish-migrations {--force : Overwrite the existing migration files if they already exist}';

    protected $description = 'Publish Shield\'s migration files into your application';

    public function handle(): int
    {
        $options = [
            '--tag' => 'shield-migrations',
        ];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        $this->call('vendor:publish', $options);

        $this->info('Shield migrations (roles, privileges, suspension) published to database/migrations');

        return self::SUCCESS;
    }
}
