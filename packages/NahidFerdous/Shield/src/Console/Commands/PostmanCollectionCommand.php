<?php

namespace NahidFerdous\Shield\Console\Commands;

class PostmanCollectionCommand extends BaseShieldCommand
{
    protected $signature = 'shield:postman-collection {--no-open : Only print the Postman collection URL}';

    protected $description = 'Open the Shield Postman collection in your browser';

    private const COLLECTION_URL = 'https://github.com/nahidnfr123/shield/blob/main/Shield.postman_collection.json';

    public function handle(): int
    {
        if (! $this->option('no-open') && $this->openUrl(self::COLLECTION_URL)) {
            $this->info('Opening the Shield Postman collection...');
        } else {
            $this->line('Postman collection: '.self::COLLECTION_URL);
        }

        return self::SUCCESS;
    }
}
