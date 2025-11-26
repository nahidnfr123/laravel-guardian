<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use Illuminate\Support\Str;
use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Support\ShieldCache;

class UpdateRoleCommand extends BaseShieldCommand
{
    protected $signature = 'shield:update-role {--role=} {--name=} {--slug=}';

    protected $description = 'Update a role name or slug';

    public function handle(): int
    {
        $identifier = $this->option('role') ?? $this->ask('Role ID or slug');

        if (! $identifier) {
            $this->error('A role identifier is required.');

            return self::FAILURE;
        }

        $role = $this->findRole($identifier);

        if (! $role) {
            $this->error('Role not found.');

            return self::FAILURE;
        }

        $nameInput = $this->option('name');
        if ($nameInput === null) {
            $nameInput = $this->ask('Role name', $role->name);
        }
        $name = trim((string) ($nameInput ?? ''));
        if ($name === '') {
            $name = $role->name;
        }

        $slugInput = $this->option('slug');
        if ($slugInput === null) {
            $slugInput = $this->ask('Role slug', $role->slug);
        }
        $slugInput = trim((string) ($slugInput ?? ''));
        if ($slugInput === '') {
            $slug = $role->slug;
        } elseif ($slugInput === '*') {
            $slug = '*';
        } else {
            $slug = Str::slug($slugInput);
        }

        if ($slug === '') {
            $this->error('Role slug is required.');

            return self::FAILURE;
        }

        $protected = config('shield.protected_role_slugs', ['admin', 'super-admin']);
        if ($slug !== $role->slug && in_array($role->slug, $protected, true)) {
            $this->error('This role slug is protected and cannot be changed.');

            return self::FAILURE;
        }

        $duplicate = Role::where('slug', $slug)
            ->where('id', '!=', $role->id)
            ->exists();

        if ($duplicate) {
            $this->error(sprintf('Role slug "%s" is already in use.', $slug));

            return self::FAILURE;
        }

        if ($name === $role->name && $slug === $role->slug) {
            $this->info('No changes detected.');

            return self::SUCCESS;
        }

        $role->update([
            'name' => $name,
            'slug' => $slug,
        ]);

        ShieldCache::forgetUsersByRole($role);

        $this->info(sprintf('Role "%s" updated.', $role->slug));

        return self::SUCCESS;
    }
}
