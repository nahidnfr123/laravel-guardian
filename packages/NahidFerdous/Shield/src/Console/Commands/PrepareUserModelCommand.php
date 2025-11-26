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

        // Remove old/incorrect imports
        $contents = $this->removeOldImports($contents, $driver);

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

        // Check if both traits are already present
        if ($tokenTraitName && Str::contains($classBody, $tokenTraitName) && Str::contains($classBody, 'HasShieldRoles')) {
            if (preg_match('/use\s+[^;]*'.$tokenTraitName.'[^;]*HasShieldRoles[^;]*;/', $classBody) ||
                preg_match('/use\s+[^;]*HasShieldRoles[^;]*'.$tokenTraitName.'[^;]*;/', $classBody)) {
                return $contents;
            }
        } elseif (! $tokenTraitName && Str::contains($classBody, 'HasShieldRoles')) {
            // JWT doesn't need HasApiTokens
            if (preg_match('/use\s+[^;]*HasShieldRoles[^;]*;/', $classBody)) {
                return $contents;
            }
        }

        // Remove old trait usage lines
        $contents = $this->removeOldTraitUsage($contents, $classStart, $driver);

        // Re-match class after removal
        if (! preg_match('/class\s+User[^\{]*\{/', $contents, $classMatch, PREG_OFFSET_CAPTURE)) {
            return $contents;
        }
        $classStart = $classMatch[0][1] + strlen($classMatch[0][0]);

        // Add new trait usage
        $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";

        if ($tokenTraitName) {
            $insertion = $lineEnding.'    use '.$tokenTraitName.', HasShieldRoles;'.$lineEnding.$lineEnding;
        } else {
            // JWT - only HasShieldRoles
            $insertion = $lineEnding.'    use HasShieldRoles;'.$lineEnding.$lineEnding;
        }

        return substr_replace($contents, $insertion, $classStart, 0);
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

    protected function removeOldTraitUsage(string $contents, int $classStart, string $driver): string
    {
        $classBody = substr($contents, $classStart);

        // Find all use statements in the class
        $patterns = [
            '/\s*use\s+HasApiTokens\s*,\s*HasShieldRoles\s*;/',
            '/\s*use\s+HasShieldRoles\s*,\s*HasApiTokens\s*;/',
            '/\s*use\s+HasApiTokens\s*;/',
            '/\s*use\s+HasShieldRoles\s*;/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $classBody, $match, PREG_OFFSET_CAPTURE)) {
                $matchStart = $classStart + $match[0][1];
                $matchLength = strlen($match[0][0]);
                $contents = substr_replace($contents, '', $matchStart, $matchLength);

                // After removing, we need to re-adjust
                break;
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
