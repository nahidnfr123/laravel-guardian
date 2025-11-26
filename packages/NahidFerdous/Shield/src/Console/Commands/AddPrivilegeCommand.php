<?php

namespace NahidFerdous\Shield\Console\Commands;

use NahidFerdous\Shield\Models\Privilege;

class AddPrivilegeCommand extends BaseShieldCommand
{
    protected $signature = 'shield:add-privilege {slug? : Unique slug for the privilege}
        {--name= : Readable name for the privilege}
        {--description= : Optional description for the privilege}';

    protected $description = 'Create a new Shield privilege record';

    public function handle(): int
    {
        $slug = $this->argument('slug') ?? $this->ask('Privilege slug');

        if (! $slug) {
            $this->error('Slug is required.');

            return self::FAILURE;
        }

        if (Privilege::where('slug', $slug)->exists()) {
            $this->error("Privilege [{$slug}] already exists.");

            return self::FAILURE;
        }

        $name = $this->option('name') ?? $this->ask('Privilege name', $slug);
        $description = $this->option('description') ?? $this->ask('Description (optional)', '');

        $privilege = Privilege::create([
            'slug' => $slug,
            'name' => $name,
            'description' => $description ?: null,
        ]);

        $this->info("Privilege [{$privilege->slug}] created (ID {$privilege->id}).");

        return self::SUCCESS;
    }
}
