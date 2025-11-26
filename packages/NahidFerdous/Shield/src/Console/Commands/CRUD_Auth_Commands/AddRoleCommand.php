<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use Illuminate\Support\Str;
use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;
use NahidFerdous\Shield\Models\Role;

class AddRoleCommand extends BaseShieldCommand
{
    protected $signature = 'shield:create-role {--name=} {--slug=}';

    protected $description = 'Create a new role';

    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Role name');
        if (! $name) {
            $this->error('Role name is required.');

            return self::FAILURE;
        }

        $slug = $this->option('slug') ?? $this->ask('Role slug (leave blank to use name)');
        $slug = $slug ? Str::slug($slug) : Str::slug($name);

        if (Role::where('slug', $slug)->exists()) {
            $this->error(sprintf('Role with slug "%s" already exists.', $slug));

            return self::FAILURE;
        }

        $role = Role::create([
            'name' => $name,
            'slug' => $slug,
        ]);

        $this->info(sprintf('Role "%s" (%s) created.', $role->name, $role->slug));

        return self::SUCCESS;
    }
}
