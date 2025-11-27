<?php

namespace NahidFerdous\Shield\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class InstallCommand extends BaseShieldCommand
{
    protected $signature = 'shield:install
        {--force : Pass the --force flag to migrate}
        {--driver= : Choose authentication driver (sanctum, passport, jwt)}
        {--dry-run : Print the steps without executing install:api or migrate}';

    protected $description = 'Run install:api followed by migrate to bootstrap Shield\'s requirements';

    public function handle(): int
    {
        $this->info('🛡️  Installing Laravel Shield...');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('Dry run: skipped install:api and migrate.');

            return self::SUCCESS;
        }

        // Step 1: Choose authentication driver
        $driver = $this->chooseAuthDriver();

        // Step 2: Install Laravel Sanctum API
        if (!$this->runRequiredCommand('install:api')) {
            return self::FAILURE;
        }

        // Step 3.5: Driver-specific setup (Passport keys, JWT secret)
        $this->performDriverSetup($driver);

        // Step 3: Prepare User model for the chosen driver
        if (!$this->runRequiredCommand('shield:prepare-user-model', ['--driver' => $driver])) {
            return self::FAILURE;
        }

        // Step 3.6: Publish global exception handler
        if ($this->confirm('Do you want to add/publish the global exception handler?', true)) {
            if (!$this->runRequiredCommand('shield:publish-exceptions', ['--force' => true])) {
                return self::FAILURE;
            }
        }

        // Step 4: Run migrations
        $arguments = [];
        if ($this->option('force')) {
            $arguments['--force'] = true;
        }

        if (!$this->runRequiredCommand('migrate', $arguments)) {
            return self::FAILURE;
        }

        // Step 5: Seed Shield data
        if ($this->confirm('Seed Shield roles, privileges, and the bootstrap admin user now?', true)) {
            if (!$this->runRequiredCommand('shield:seed', ['--force' => true])) {
                return self::FAILURE;
            }
        }

        // Step 6: Show a completion message
        $this->showCompletionMessage($driver);

        return self::SUCCESS;
    }

    /**
     * Choose an authentication driver
     */
    protected function chooseAuthDriver(): string
    {
        // Check if driver is specified via option
        if ($driver = $this->option('driver')) {
            if (in_array($driver, ['sanctum', 'passport', 'jwt'])) {
                $this->info("Using {$driver} driver (from --driver option)");
                $this->newLine();

                return $driver;
            }
            $this->warn("Invalid driver '{$driver}'. Falling back to interactive selection.");
        }

        // Interactive selection
        $driver = $this->choice(
            'Which authentication driver would you like to use? Depending on your choice some existing configuration may be overwritten.',
            [
                'sanctum' => 'Sanctum - Token-based auth for SPAs and mobile apps (Recommended)',
                'passport' => 'Passport - Full OAuth2 server implementation',
                'jwt' => 'JWT - JSON Web Tokens for stateless authentication',
            ],
            'sanctum',
            'Choose one of the available authentication drivers or press enter to use Sanctum.',
        );

        $this->info("Selected: {$driver}");
        $this->newLine();

        // Update .env file
        $this->updateEnvDriver($driver);

        return $driver;
    }

    /**
     * Update .env file with selected driver
     */
    protected function updateEnvDriver(string $driver): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->warn('⚠ .env file not found. Please manually set SHIELD_AUTH_DRIVER');

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
            if (!str_ends_with($updated, "\n")) {
                $updated .= "\n";
            }
            $updated .= "\n# Shield Authentication Driver\nSHIELD_AUTH_DRIVER={$driver}\n";
        }

        file_put_contents($envPath, $updated);
        $this->line("✓ Updated .env: SHIELD_AUTH_DRIVER={$driver}");
        $this->newLine();
    }

    /**
     * Show completion message with next steps
     */
    protected function showCompletionMessage(string $driver): void
    {
        $this->newLine();
        $this->info('✅ Shield installation complete!');
        $this->newLine();

        $this->line('📋 <fg=bright-white>What\'s been set up:</>');
        $this->line('   ✓ API routes configured');
        $this->line('   ✓ User model prepared with necessary traits');
        $this->line('   ✓ Database migrations run');
        $this->line('   ✓ Roles and privileges seeded');
        $this->line('   ✓ Admin user created');
        $this->newLine();

        $this->line('👤 <fg=bright-white>Default Admin Credentials:</>');
        $this->line('   Email: <fg=yellow>admin@example.com</>');
        $this->line('   Password: <fg=yellow>password</>');
        $this->warn('   ⚠ Please change the admin password immediately!');
        $this->newLine();

        // Driver-specific instructions
        $this->showDriverInstructions($driver);

        $this->line('📚 <fg=bright-white>Quick Commands:</>');
        $this->line('   • List users: <fg=cyan>php artisan shield:list-users</>');
        $this->line('   • Create user: <fg=cyan>php artisan shield:create-user</>');
        $this->line('   • Switch driver: <fg=cyan>php artisan shield:switch-driver [sanctum|passport|jwt]</>');
        $this->line('   • View all commands: <fg=cyan>php artisan shield</>');
        $this->newLine();

        $this->line('🚀 <fg=bright-white>Test Your API:</>');
        $this->line('   POST /api/login - Login and get token');
        $this->line('   GET  /api/me - Get authenticated user');
        $this->newLine();

        $this->info('Happy coding! 🛡️');
    }

    /**
     * Show driver-specific setup instructions
     */
    protected function showDriverInstructions(string $driver): void
    {
        $this->line('⚙️  <fg=bright-white>Authentication Driver: ' . strtoupper($driver) . '</>');
        $this->newLine();

        switch ($driver) {
            case 'sanctum':
                $this->line('   ✓ Sanctum is ready to use!');
                $this->line('   • Config file: <fg=cyan>config/sanctum.php</>');
                $this->line('   • Middleware: <fg=cyan>auth:sanctum</>');
                break;

            case 'passport':
                if ($this->passportKeysExist()) {
                    $this->line('   ✓ Passport keys generated successfully!');
                } else {
                    $this->warn('   ⚠ Passport keys not found!');
                    $this->line('   Run: <fg=cyan>php artisan passport:install</>');
                }
                $this->line('   • Don\'t forget to add Client ID and Secret to .env');
                $this->line('   • Middleware: <fg=cyan>auth:api</>');
                break;

            case 'jwt':
                $this->line('   ✓ JWT configuration added to .env');
                $this->line('   • Middleware: <fg=cyan>jwt.auth</>');
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
        $this->newLine();

        if (!$this->passportKeysExist()) {
            $this->info('Passport requires encryption keys...');

            if ($this->confirm('Generate Passport keys now?', true)) {
                $this->runRequiredCommand('passport:install', ['--force' => true]);
                $this->newLine();
                $this->warn('⚠️  IMPORTANT: Copy the Client ID and Secret from above to your .env file:');
                $this->line('   PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1');
                $this->line('   PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=xxxxx');
                $this->newLine();
            } else {
                $this->warn('⚠️  You must run "php artisan passport:install" before using Passport.');
            }
        } else {
            $this->info('✓ Passport keys already exist');
        }

        $this->newLine();
    }

    /**
     * Setup JWT
     */
    protected function setupJWT(): void
    {
        $this->newLine();
        $this->info('Setting up JWT...');

        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->warn('⚠️  .env file not found');

            return;
        }

        $contents = file_get_contents($envPath);

        // Check if JWT_SECRET exists
        if (!preg_match('/^JWT_SECRET=/m', $contents)) {
            $secret = bin2hex(random_bytes(32));

            $updated = $contents;
            if (!str_ends_with($updated, "\n")) {
                $updated .= "\n";
            }
            $updated .= "\n# JWT Configuration\n";
            $updated .= "JWT_SECRET={$secret}\n";
            $updated .= "JWT_TTL=60\n";
            $updated .= "JWT_REFRESH_TTL=20160\n";
            $updated .= "JWT_BLACKLIST_ENABLED=true\n";

            file_put_contents($envPath, $updated);

            $this->info('✓ Generated JWT_SECRET and added to .env');
        } else {
            $this->info('✓ JWT_SECRET already exists in .env');
        }

        $this->newLine();
    }

    /**
     * Check if Passport keys exist
     */
    protected function passportKeysExist(): bool
    {
        return file_exists(storage_path('oauth-private.key'))
            && file_exists(storage_path('oauth-public.key'));
    }

    /**
     * Run a required artisan command
     */
    protected function runRequiredCommand(string $command, array $arguments = []): bool
    {
        $this->info(sprintf('Running %s...', $command));

        $arguments = array_merge(['--no-interaction' => true], $arguments);

        try {
            $exitCode = Artisan::call($command, $arguments);
        } catch (CommandNotFoundException $e) {
            $this->error(sprintf('Command "%s" is not available in this application.', $command));

            return false;
        }

        $capturedOutput = Artisan::output();

        if (trim($capturedOutput) !== '') {
            $this->line($capturedOutput);
        }

        if ($exitCode !== 0) {
            $this->error(sprintf('Command "%s" exited with code %s.', $command, $exitCode));

            return false;
        }

        return true;
    }
}
