<?php

namespace NahidFerdous\Shield\Console\Commands;

use Illuminate\Support\Str;

class PrepareUserModelCommand extends BaseShieldCommand
{
    protected $signature = 'shield:prepare-user-model {--path= : Override the location of the User model file} {--driver= : Override the auth driver (sanctum, passport, jwt)}';

    protected $description = 'Add HasApiTokens and HasShieldRoles traits to the default User model based on auth driver';

    public function handle(): int
    {
        $path = $this->option('path') ?: app_path('Models/User.php');

        if (! file_exists($path)) {
            $this->error(sprintf('User model not found at %s.', $path));

            return self::FAILURE;
        }

        // Get auth driver from option or config
        $driver = $this->option('driver') ?: config('shield.auth_driver', 'sanctum');

        $original = file_get_contents($path);
        $updated = $original;

        // STEP 1: Remove old traits and interfaces BEFORE adding new ones
        $updated = $this->removeOldTraitUsage($updated, $driver);
        $updated = $this->removeOldImports($updated, $driver);
        $updated = $this->removeOldInterfaces($updated, $driver);
        $updated = $this->removeJWTMethods($updated, $driver);

        // STEP 2: Add new imports, interfaces, traits, and methods
        $updated = $this->ensureImports($updated, $driver);
        $updated = $this->ensureInterfaces($updated, $driver);
        $updated = $this->ensureTraitUsage($updated, $driver);
        $updated = $this->ensureJWTMethods($updated, $driver);
        $updated = $this->ensurePasswordResetMethod($updated);

        if ($updated === $original) {
            $this->info('User model already prepared.');
        } else {
            file_put_contents($path, $updated);
            $this->info(sprintf('Updated User model at %s for %s driver.', $path, $driver));
        }

        // Update auth.php configuration
        $this->updateAuthConfig($driver);

        return self::SUCCESS;
    }

    protected function ensureImports(string $contents, string $driver): string
    {
        $imports = [
            'NahidFerdous\\Shield\\Concerns\\HasShieldRoles',
            'NahidFerdous\\Shield\\Notifications\\ResetPasswordNotification',
        ];

        // Add appropriate HasApiTokens based on driver
        $tokenTrait = $this->getTokenTraitForDriver($driver);
        if ($tokenTrait) {
            array_unshift($imports, $tokenTrait);
        }

        // Add JWT interface for JWT driver
        if ($driver === 'jwt') {
            $imports[] = 'Tymon\\JWTAuth\\Contracts\\JWTSubject';
        }

        // Find missing imports
        $missing = array_filter($imports, fn ($import) => ! Str::contains($contents, "use {$import};"));

        if (empty($missing)) {
            return $contents;
        }

        if (! preg_match('/namespace\s+[^;]+;\s*/', $contents, $namespaceMatch, PREG_OFFSET_CAPTURE)) {
            return $contents;
        }

        $namespaceEnd = $namespaceMatch[0][1] + strlen($namespaceMatch[0][0]);
        $classPosition = strpos($contents, 'class ');
        $insertionPoint = $namespaceEnd;

        // Find the last use statement
        if (preg_match_all('/\nuse\s+[^;]+;/', $contents, $useMatches, PREG_OFFSET_CAPTURE)) {
            foreach ($useMatches[0] as $match) {
                $position = $match[1];
                if ($position > $namespaceEnd && ($classPosition === false || $position < $classPosition)) {
                    $insertionPoint = $position + strlen($match[0]);
                }
            }
        }

        $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $insert = '';

        foreach ($missing as $import) {
            $insert .= 'use '.$import.';'.$lineEnding;
        }

        if ($insertionPoint === $namespaceEnd) {
            $insert = $lineEnding.$lineEnding.$insert;
        } elseif (! str_ends_with(substr($contents, 0, $insertionPoint), $lineEnding)) {
            $insert = $lineEnding.$insert;
        }

        return substr_replace($contents, $insert, $insertionPoint, 0);
    }

    protected function ensureInterfaces(string $contents, string $driver): string
    {
        if ($driver !== 'jwt') {
            return $contents;
        }

        // Check if JWTSubject is already implemented
        if (preg_match('/class\s+User[^{]*implements[^{]*JWTSubject/', $contents)) {
            return $contents;
        }

        // Find the class declaration with better pattern matching
        // This pattern captures: class User ... implements ... { or class User ... {
        if (preg_match('/(class\s+User[^{]*?)(implements\s+([^{]+?))?\s*(\{)/', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $fullMatch = $matches[0][0];
            $classStart = $matches[0][1];

            if (isset($matches[2]) && ! empty(trim($matches[2][0]))) {
                // Already has implements clause
                $implementsList = trim($matches[3][0]);

                // Split existing interfaces and check if JWTSubject is already there
                $interfaces = array_map('trim', explode(',', $implementsList));
                if (! in_array('JWTSubject', $interfaces)) {
                    // Add JWTSubject to the list
                    $newImplements = $implementsList.', JWTSubject';
                    $newDeclaration = str_replace(
                        'implements '.$implementsList,
                        'implements '.$newImplements,
                        $fullMatch
                    );
                    $contents = substr_replace($contents, $newDeclaration, $classStart, strlen($fullMatch));
                }
            } else {
                // No implements clause, add it before the opening brace
                $newDeclaration = str_replace(
                    $matches[4][0],
                    ' implements JWTSubject'.$matches[4][0],
                    $fullMatch
                );
                $contents = substr_replace($contents, $newDeclaration, $classStart, strlen($fullMatch));
            }
        }

        return $contents;
    }

    protected function removeOldInterfaces(string $contents, string $driver): string
    {
        if ($driver === 'jwt') {
            return $contents; // Keep JWTSubject for JWT
        }

        // For non-JWT drivers, remove JWTSubject from implements clause
        // Handle: implements JWTSubject, OtherInterface
        $contents = preg_replace('/implements\s+JWTSubject\s*,\s*/', 'implements ', $contents);

        // Handle: implements OtherInterface, JWTSubject
        $contents = preg_replace('/,\s*JWTSubject\s*/', '', $contents);

        // Handle: implements JWTSubject (only interface)
        $contents = preg_replace('/\s+implements\s+JWTSubject\s*/', ' ', $contents);

        return $contents;
    }

    protected function ensureJWTMethods(string $contents, string $driver): string
    {
        if ($driver !== 'jwt') {
            return $contents;
        }

        // Check if methods already exist
        if (str_contains($contents, 'function getJWTIdentifier()') &&
            str_contains($contents, 'function getJWTCustomClaims()')) {
            return $contents;
        }

        // Find the class closing brace
        if (! preg_match('/class\s+User[^{]*\{/', $contents, $classMatch, PREG_OFFSET_CAPTURE)) {
            return $contents;
        }

        // Find the last closing brace (end of class)
        $lastBrace = strrpos($contents, '}');
        if ($lastBrace === false) {
            return $contents;
        }

        $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";

        $methods = $lineEnding.'    /**'.$lineEnding;
        $methods .= '     * Get the identifier that will be stored in the subject claim of the JWT.'.$lineEnding;
        $methods .= '     *'.$lineEnding;
        $methods .= '     * @return mixed'.$lineEnding;
        $methods .= '     */'.$lineEnding;
        $methods .= '    public function getJWTIdentifier(): mixed'.$lineEnding;
        $methods .= '    {'.$lineEnding;
        $methods .= '        return $this->getKey();'.$lineEnding;
        $methods .= '    }'.$lineEnding;
        $methods .= $lineEnding;
        $methods .= '    /**'.$lineEnding;
        $methods .= '     * Return a key value array, containing any custom claims to be added to the JWT.'.$lineEnding;
        $methods .= '     *'.$lineEnding;
        $methods .= '     * @return array'.$lineEnding;
        $methods .= '     */'.$lineEnding;
        $methods .= '    public function getJWTCustomClaims(): array'.$lineEnding;
        $methods .= '    {'.$lineEnding;
        $methods .= '        return [];'.$lineEnding;
        $methods .= '    }'.$lineEnding;

        return substr_replace($contents, $methods, $lastBrace, 0);
    }

    protected function removeJWTMethods(string $contents, string $driver): string
    {
        if ($driver === 'jwt') {
            return $contents; // Keep methods for JWT
        }

        // Remove getJWTIdentifier method
        $contents = preg_replace(
            '/\/\*\*[^}]*?\*\/\s*public\s+function\s+getJWTIdentifier\(\)[^}]*\{[^}]*\}\s*\n?/s',
            '',
            $contents
        );

        // Remove getJWTCustomClaims method
        $contents = preg_replace(
            '/\/\*\*[^}]*?\*\/\s*public\s+function\s+getJWTCustomClaims\(\)[^}]*\{[^}]*\}\s*\n?/s',
            '',
            $contents
        );

        return $contents;
    }

    protected function ensureTraitUsage(string $contents, string $driver): string
    {
        if (! preg_match('/class\s+User[^\{]*\{/', $contents, $classMatch, PREG_OFFSET_CAPTURE)) {
            return $contents;
        }

        $classStart = $classMatch[0][1] + strlen($classMatch[0][0]);
        $classBody = substr($contents, $classStart);

        $tokenTraitName = $this->getTokenTraitName($driver);

        // Check if traits are already correctly present
        if ($tokenTraitName && $this->hasCorrectTraits($classBody, $tokenTraitName)) {
            return $contents;
        }

        if (! $tokenTraitName && Str::contains($classBody, 'use HasShieldRoles;')) {
            return $contents;
        }

        // Add new trait usage
        $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";

        if ($tokenTraitName) {
            $insertion = $lineEnding.'    use '.$tokenTraitName.', HasShieldRoles;'.$lineEnding;
        } else {
            // JWT - only HasShieldRoles
            $insertion = $lineEnding.'    use HasShieldRoles;'.$lineEnding;
        }

        return substr_replace($contents, $insertion, $classStart, 0);
    }

    protected function hasCorrectTraits(string $classBody, string $tokenTraitName): bool
    {
        // Check if both traits are present in a single use statement
        if (preg_match('/use\s+[^;]*'.$tokenTraitName.'[^;]*,\s*HasShieldRoles[^;]*;/', $classBody) ||
            preg_match('/use\s+[^;]*HasShieldRoles[^;]*,\s*'.$tokenTraitName.'[^;]*;/', $classBody)) {
            return true;
        }

        // Check if both traits are present in separate use statements
        if (preg_match('/use\s+'.$tokenTraitName.'\s*;/', $classBody) &&
            preg_match('/use\s+HasShieldRoles\s*;/', $classBody)) {
            return true;
        }

        return false;
    }

    protected function removeOldImports(string $contents, string $driver): string
    {
        $currentTrait = $this->getTokenTraitForDriver($driver);
        $allTraits = [
            'Laravel\\Sanctum\\HasApiTokens',
            'Laravel\\Passport\\HasApiTokens',
        ];

        // Remove imports for other drivers
        foreach ($allTraits as $trait) {
            if ($trait !== $currentTrait) {
                $contents = preg_replace('/use\s+'.preg_quote($trait, '/').';\s*\n?/', '', $contents);
            }
        }

        // Remove JWTSubject import for non-JWT drivers
        if ($driver !== 'jwt') {
            $contents = preg_replace('/use\s+Tymon\\\\JWTAuth\\\\Contracts\\\\JWTSubject;\s*\n?/', '', $contents);
        }

        return $contents;
    }

    protected function removeOldTraitUsage(string $contents, string $driver): string
    {
        // Find the class body
        if (! preg_match('/class\s+User[^\{]*\{/', $contents, $classMatch, PREG_OFFSET_CAPTURE)) {
            return $contents;
        }

        $classStart = $classMatch[0][1] + strlen($classMatch[0][0]);

        // Patterns to match various trait usage formats
        $patterns = [
            // use HasApiTokens, HasShieldRoles;
            '/(\s*)use\s+HasApiTokens\s*,\s*HasShieldRoles\s*;\s*\n?/',
            // use HasShieldRoles, HasApiTokens;
            '/(\s*)use\s+HasShieldRoles\s*,\s*HasApiTokens\s*;\s*\n?/',
            // use HasApiTokens;
            '/(\s*)use\s+HasApiTokens\s*;\s*\n?/',
            // use HasShieldRoles;
            '/(\s*)use\s+HasShieldRoles\s*;\s*\n?/',
        ];

        $tokenTraitName = $this->getTokenTraitName($driver);

        // Remove all matching patterns within the class body
        foreach ($patterns as $pattern) {
            // Keep removing until no more matches found
            while (preg_match($pattern, substr($contents, $classStart), $match, PREG_OFFSET_CAPTURE)) {
                $matchStart = $classStart + $match[0][1];
                $matchLength = strlen($match[0][0]);

                // Only remove if it's not the pattern we want to keep
                $shouldRemove = true;

                if ($tokenTraitName === 'HasApiTokens') {
                    // For Sanctum/Passport, remove individual uses but we'll add them back combined
                    $shouldRemove = true;
                } elseif ($tokenTraitName === null) {
                    // For JWT, remove HasApiTokens but keep checking for HasShieldRoles
                    if (str_contains($match[0][0], 'HasApiTokens')) {
                        $shouldRemove = true;
                    } elseif (str_contains($match[0][0], 'HasShieldRoles') &&
                        ! str_contains($match[0][0], 'HasApiTokens')) {
                        // Keep standalone HasShieldRoles for JWT
                        $shouldRemove = false;
                    }
                }

                if ($shouldRemove) {
                    $contents = substr_replace($contents, '', $matchStart, $matchLength);
                } else {
                    break; // Don't remove this one, move to next pattern
                }
            }
        }

        return $contents;
    }

    protected function getTokenTraitForDriver(string $driver): ?string
    {
        return match ($driver) {
            // 'sanctum' => 'Laravel\\Sanctum\\HasApiTokens',
            'passport' => 'Laravel\\Passport\\HasApiTokens',
            'jwt' => null, // JWT doesn't need HasApiTokens
            default => 'Laravel\\Sanctum\\HasApiTokens',
        };
    }

    protected function getTokenTraitName(string $driver): ?string
    {
        return match ($driver) {
            'sanctum', 'passport' => 'HasApiTokens',
            'jwt' => null,
            default => 'HasApiTokens',
        };
    }

    /**
     * Update auth.php configuration for the driver
     */
    protected function updateAuthConfig(string $driver): void
    {
        $authConfigPath = config_path('auth.php');

        if (! file_exists($authConfigPath)) {
            $this->warn('config/auth.php not found. Please manually configure your auth guard.');
            $this->showManualAuthConfig($driver);

            return;
        }

        $contents = file_get_contents($authConfigPath);
        $original = $contents;

        // Check if 'api' guard exists
        $guardConfig = $this->getGuardConfigForDriver($driver);
        if (preg_match("/'api'\s*=>\s*\[/", $contents)) {
            // Update existing 'api' guard
            $pattern = "/'api'\s*=>\s*\[[^\]]*\]/s";
            $updated = preg_replace($pattern, $guardConfig, $contents);
        } else {
            // Add 'api' guard after 'web' guard
            $updated = $this->addApiGuard($contents, $guardConfig);
        }

        if ($updated && $updated !== $original) {
            file_put_contents($authConfigPath, $updated);
            $this->info("✓ Updated config/auth.php 'api' guard for {$driver} driver");
            $this->newLine();
        } else {
            $this->warn('Could not automatically update config/auth.php');
            $this->showManualAuthConfig($driver);
        }
    }

    /**
     * Add 'api' guard to auth.php after 'web' guard
     */
    protected function addApiGuard(string $contents, string $guardConfig): string
    {
        // Find the 'web' guard closing bracket
        if (preg_match("/'web'\s*=>\s*\[[^\]]*\]/s", $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $webGuardEnd = $matches[0][1] + strlen($matches[0][0]);

            // Determine line ending
            $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";

            // Insert the 'api' guard after 'web' guard
            $insertion = ','.$lineEnding.$lineEnding.'        '.$guardConfig;

            return substr_replace($contents, $insertion, $webGuardEnd, 0);
        }

        return $contents;
    }

    /**
     * Get the guard configuration for the driver
     */
    protected function getGuardConfigForDriver(string $driver): string
    {
        return match ($driver) {
            'passport' => "'api' => [
            'driver' => 'passport',
            'provider' => 'users',
            'hash' => false,
        ]",
            'sanctum' => "'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
            'hash' => false,
        ]",
            'jwt' => "'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
            'hash' => false,
        ]",
            default => "'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
            'hash' => false,
        ]",
        };
    }

    /**
     * Show manual configuration instructions
     */
    protected function showManualAuthConfig(string $driver): void
    {
        $this->newLine();
        $this->info("Please manually update config/auth.php 'guards' array:");
        $this->newLine();
        $this->line($this->getGuardConfigForDriver($driver));
        $this->newLine();
    }

    /**
     * Ensure sendPasswordResetNotification method exists in a User model
     */
    protected function ensurePasswordResetMethod(string $contents): string
    {
        // Check if method already exists
        if (str_contains($contents, 'function sendPasswordResetNotification')) {
            return $contents;
        }

        // Find the class closing brace
        if (! preg_match('/class\s+User[^{]*\{/', $contents, $classMatch, PREG_OFFSET_CAPTURE)) {
            return $contents;
        }

        // Find the last closing brace (end of class)
        $lastBrace = strrpos($contents, '}');
        if ($lastBrace === false) {
            return $contents;
        }

        $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";

        $method = $lineEnding.'    /**'.$lineEnding;
        $method .= '     * Send the password reset notification.'.$lineEnding;
        $method .= '     *'.$lineEnding;
        $method .= '     * @param  string  $token'.$lineEnding;
        $method .= '     * @return void'.$lineEnding;
        $method .= '     */'.$lineEnding;
        $method .= '    public function sendPasswordResetNotification($token): void'.$lineEnding;
        $method .= '    {'.$lineEnding;
        $method .= '        $this->notify(new ResetPasswordNotification($token));'.$lineEnding;
        $method .= '    }'.$lineEnding;

        return substr_replace($contents, $method, $lastBrace, 0);
    }
}
