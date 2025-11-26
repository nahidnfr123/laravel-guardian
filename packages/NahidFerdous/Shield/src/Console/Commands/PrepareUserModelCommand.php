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

        // IMPORTANT: Remove old traits BEFORE adding new imports
        $updated = $this->removeOldTraitUsage($updated, $driver);
        $updated = $this->removeOldImports($updated, $driver);
        $updated = $this->ensureImports($updated, $driver);
        $updated = $this->ensureTraitUsage($updated, $driver);

        if ($updated === $original) {
            $this->info('User model already prepared.');

            return self::SUCCESS;
        }

        file_put_contents($path, $updated);

        $this->info(sprintf('Updated User model at %s for %s driver.', $path, $driver));

        return self::SUCCESS;
    }

    protected function ensureImports(string $contents, string $driver): string
    {
        $imports = [
            'NahidFerdous\\Shield\\Concerns\\HasShieldRoles',
        ];

        // Add appropriate HasApiTokens based on driver
        $tokenTrait = $this->getTokenTraitForDriver($driver);
        if ($tokenTrait) {
            array_unshift($imports, $tokenTrait);
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
            'sanctum' => 'Laravel\\Sanctum\\HasApiTokens',
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
}
