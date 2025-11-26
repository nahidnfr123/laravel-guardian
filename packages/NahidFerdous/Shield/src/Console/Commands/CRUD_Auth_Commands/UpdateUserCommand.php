<?php

namespace NahidFerdous\Shield\Console\Commands\CRUD_Auth_Commands;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use NahidFerdous\Shield\Console\Commands\BaseShieldCommand;

class UpdateUserCommand extends BaseShieldCommand
{
    protected $signature = 'shield:update-user {--user=} {--name=} {--email=} {--password=}';

    protected $description = "Update a user's name, email and password";

    public function handle(): int
    {
        $identifier = $this->option('user') ?? $this->ask('User ID or email');

        if (! $identifier) {
            $this->error('A user identifier is required.');

            return self::FAILURE;
        }

        $user = $this->findUser($identifier);

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $nameInput = $this->option('name');
        if ($nameInput === null) {
            $nameInput = $this->ask('Name', (string) ($user->name ?? ''));
        }
        $name = trim((string) ($nameInput ?? ''));
        if ($name === '') {
            $name = (string) ($user->name ?? '');
        }

        $emailInput = $this->option('email');
        if ($emailInput === null) {
            $emailInput = $this->ask('Email', (string) $user->email);
        }
        $email = trim((string) ($emailInput ?? ''));

        if ($email === '') {
            $this->error('Email is required.');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'email' => $email,
        ], [
            'email' => 'required|email|unique:'.$user->getTable().',email,'.$user->id,
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        $passwordInput = $this->option('password');

        if ($passwordInput === null && $this->input->isInteractive() && ! app()->runningUnitTests()) {
            $passwordInput = $this->secret('Password (leave blank to keep current)');
        }

        $passwordInput = $passwordInput !== null ? trim($passwordInput) : '';
        $password = $passwordInput === '' ? null : $passwordInput;

        if ($name === (string) ($user->name ?? '') && $email === $user->email && $password === null) {
            $this->info('No changes detected.');

            return self::SUCCESS;
        }

        $payload = [
            'name' => $name,
            'email' => $email,
        ];

        if ($password !== null) {
            $payload['password'] = Hash::make($password);
        }

        $user->forceFill($payload)->save();

        $this->info(sprintf('User %s updated.', $user->email));

        return self::SUCCESS;
    }
}
