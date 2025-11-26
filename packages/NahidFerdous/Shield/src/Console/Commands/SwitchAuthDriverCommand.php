<?php

namespace NahidFerdous\Shield\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Random\RandomException;

class SwitchAuthDriverCommand extends BaseShieldCommand
{
    protected $signature = 'shield:switch-driver
                            {driver : The auth driver to switch to (sanctum, passport, jwt)}
                            {--update-model : Update User model traits automatically}
                            {--no-cache-clear : Skip clearing cache}';

    protected $description = 'Switch between authentication drivers (Sanctum, Passport, JWT)';

    public function handle(): int
    {
        $driver = strtolower($this->argument('driver'));

        if (! in_array($driver, ['sanctum', 'passport', 'jwt'])) {
            $this->error('Invalid driver. Must be one of: sanctum, passport, jwt');

            return self::FAILURE;
        }

        $this->info("Switching to {$driver} driver...");
        $this->newLine();

        // Check requirements
        if (! $this->checkRequirements($driver)) {
            return self::FAILURE;
        }

        // Update config file
        if (! $this->updateConfigFile($driver)) {
            $this->warn('Could not automatically update config file.');
            $this->info('Please manually update config/shield.php:');
            $this->line("  'auth_driver' => '{$driver}',");
            $this->newLine();
        }

        // Update .env file
        $this->updateEnvFile($driver);

        // Update User model if requested
        if ($this->option('update-model')) {
            $this->info('Updating User model...');
            Artisan::call('shield:prepare-user-model', ['--driver' => $driver]);
            $this->line(Artisan::output());
        } else {
            $this->warn('User model not updated. Run the following to update:');
            $this->line("  php artisan shield:prepare-user-model --driver={$driver}");
            $this->newLine();
        }

        // Driver-specific setup
        $this->performDriverSetup($driver);

        // Driver-specific setup instructions
        $this->showDriverInstructions($driver);

        if ($driver === 'jwt') {
            Artisan::call('vendor:publish', ['--provider' => "Tymon\JWTAuth\Providers\LaravelServiceProvider"]);
            Artisan::call('jwt:secret');
        }

        // Clear cache
        if (! $this->option('no-cache-clear')) {
            $this->info('Clearing cache...');
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            $this->line('✓ Cache cleared');
        }

        $this->newLine();
        $this->info("✓ Successfully switched to {$driver} driver!");
        $this->newLine();

        return self::SUCCESS;
    }

    protected function checkRequirements(string $driver): bool
    {
        $requirements = [
            'sanctum' => [
                'package' => 'laravel/sanctum',
                'class' => 'Laravel\\Sanctum\\Sanctum',
            ],
            'passport' => [
                'package' => 'laravel/passport',
                'class' => 'Laravel\\Passport\\Passport',
            ],
            'jwt' => [
                'package' => 'firebase/php-jwt',
                'class' => 'Firebase\\JWT\\JWT',
            ],
        ];

        $requirement = $requirements[$driver];

        if (! class_exists($requirement['class'])) {
            $this->error("Required package '{$requirement['package']}' is not installed.");
            $this->newLine();
            $this->info('Install it with:');
            $this->line("  composer require {$requirement['package']}");
            $this->newLine();

            if ($driver === 'passport') {
                $this->info('Then run:');
                $this->line('  php artisan passport:install');
                $this->newLine();
            }

            return false;
        }

        return true;
    }

    protected function updateConfigFile(string $driver): bool
    {
        $configPath = config_path('shield.php');

        if (! file_exists($configPath)) {
            return false;
        }

        $contents = file_get_contents($configPath);

        // Update auth_driver value
        $pattern = "/'auth_driver'\s*=>\s*env\('SHIELD_AUTH_DRIVER',\s*'[^']+'\)/";
        $replacement = "'auth_driver' => env('SHIELD_AUTH_DRIVER', '{$driver}')";

        $updated = preg_replace($pattern, $replacement, $contents);

        if ($updated === $contents) {
            return false;
        }

        file_put_contents($configPath, $updated);

        return true;
    }

    protected function updateEnvFile(string $driver): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->warn('.env file not found. Please manually set:');
            $this->line("  SHIELD_AUTH_DRIVER={$driver}");
            $this->newLine();

            return;
        }

        $contents = file_get_contents($envPath);

        // Check if SHIELD_AUTH_DRIVER exists
        if (preg_match('/^SHIELD_AUTH_DRIVER=/m', $contents)) {
            // Update existing
            $updated = preg_replace(
                '/^SHIELD_AUTH_DRIVER=.*/m',
                "SHIELD_AUTH_DRIVER={$driver}",
                $contents
            );
        } else {
            // Add new
            $updated = $contents;
            if (! str_ends_with($updated, "\n")) {
                $updated .= "\n";
            }
            $updated .= "\n# Shield Auth Driver\nSHIELD_AUTH_DRIVER={$driver}\n";
        }

        file_put_contents($envPath, $updated);
        $this->line("✓ Updated .env: SHIELD_AUTH_DRIVER={$driver}");
        $this->newLine();
    }

    protected function showDriverInstructions(string $driver): void
    {
        $this->newLine();
        $this->info("📋 Next Steps for {$driver}:");
        $this->newLine();

        switch ($driver) {
            case 'sanctum':
                $this->line("1. Ensure your API routes use 'auth:sanctum' middleware");
                $this->line('2. Make sure User model uses Laravel\\Sanctum\\HasApiTokens');
                $this->line('3. Sanctum configuration is in config/sanctum.php');
                break;

            case 'passport':
                if (! $this->passportKeysExist()) {
                    $this->error('⚠️  Passport keys not found!');
                    $this->line('1. Run: php artisan passport:install');
                } else {
                    $this->info('✓ Passport keys found');
                }
                $this->line('2. Update your .env with Passport credentials:');
                $this->line('   PASSPORT_PERSONAL_ACCESS_CLIENT_ID=...');
                $this->line('   PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=...');
                $this->line('3. Make sure User model uses Laravel\\Passport\\HasApiTokens');
                $this->line("4. Ensure your API routes use 'auth:api' middleware");
                break;

            case 'jwt':
                $this->line('1. Set a secure JWT secret in .env:');
                $this->line('   JWT_SECRET='.bin2hex(random_bytes(32)));
                $this->line('2. Configure JWT settings in .env:');
                $this->line('   JWT_TTL=60 (token lifetime in minutes)');
                $this->line('   JWT_REFRESH_TTL=20160 (refresh token lifetime)');
                $this->line('3. User model should only use HasShieldRoles trait');
                $this->line("4. Ensure your API routes use 'jwt.auth' middleware");
                break;
        }

        $this->newLine();
    }

    /**
     * Perform driver-specific setup
     */
    protected function performDriverSetup(string $driver): void
    {
        if ($driver === 'passport') {
            $this->setupPassport();
        } elseif ($driver === 'jwt') {
            $this->setupJWT();
        }
    }

    /**
     * Setup Passport
     */
    protected function setupPassport(): void
    {
        if (! $this->passportKeysExist()) {
            if ($this->confirm('Passport keys not found. Generate them now?', true)) {
                $this->info('Running passport:install...');
                Artisan::call('passport:install', ['--force' => true]);
                $this->line(Artisan::output());

                $this->newLine();
                $this->warn('⚠️  Important: Copy the Client ID and Secret above to your .env file!');
                $this->newLine();
            } else {
                $this->warn('You must run "php artisan passport:install" before using Passport.');
            }
        }
    }

    /**
     * Setup JWT
     *
     * @throws RandomException
     */
    protected function setupJWT(): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $contents = file_get_contents($envPath);

        // Check if JWT_SECRET exists
        if (! preg_match('/^JWT_SECRET=/m', $contents)) {
            if ($this->confirm('JWT_SECRET not found in .env. Generate it now?', true)) {
                $secret = bin2hex(random_bytes(32));

                $updated = $contents;
                if (! str_ends_with($updated, "\n")) {
                    $updated .= "\n";
                }
                $updated .= "\n# JWT Configuration\n";
                $updated .= "JWT_SECRET={$secret}\n";
                $updated .= "JWT_TTL=60\n";
                $updated .= "JWT_REFRESH_TTL=20160\n";

                file_put_contents($envPath, $updated);

                $this->info("✓ Generated JWT_SECRET: {$secret}");
                $this->newLine();
            }
        }
    }

    /**
     * Check if Passport keys exist
     */
    protected function passportKeysExist(): bool
    {
        return file_exists(storage_path('oauth-private.key'))
            && file_exists(storage_path('oauth-public.key'));
    }
}
