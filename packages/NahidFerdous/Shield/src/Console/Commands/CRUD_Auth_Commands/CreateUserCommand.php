<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;
use NahidFerdous\Shield\Support\ShieldCache;

class CreateUserCommand extends BaseShieldCommand
{
    protected $signature = 'shield:create-user {--name=} {--email=} {--password=}';

    protected $description = 'Create a new user and attach Shield\'s default role';

    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Name (optional)', null);
        $email = $this->option('email') ?? $this->ask('Email');

        if (! $email) {
            $this->error('Email is required.');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'email' => $email,
        ], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        $userClass = $this->userClass();

        if ($userClass::where('email', $email)->exists()) {
            $this->error('A user with that email already exists.');

            return self::FAILURE;
        }

        $password = $this->option('password') ?? $this->secret('Password (leave blank to auto-generate)');
        $generatedPassword = false;

        if (! $password) {
            $password = Str::random(16);
            $generatedPassword = true;
        }

        /** @var \Illuminate\Database\Eloquent\Model $user */
        $user = $userClass::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        if (method_exists($user, 'roles')) {
            $defaultRole = $this->defaultRole();
            if ($defaultRole) {
                $user->roles()->syncWithoutDetaching($defaultRole);
                ShieldCache::forgetUser($user);
            }
        }

        $this->info(sprintf('User %s created (ID: %s)', $user->email, $user->id));

        if ($generatedPassword) {
            $this->warn(sprintf('Generated password: %s', $password));
        }

        return self::SUCCESS;
    }
}
