<?php

namespace NahidFerdous\Shield\Services\Auth;

use InvalidArgumentException;

class AuthServiceFactory
{
    /**
     * Create an auth service instance based on config
     */
    public static function make(?string $driver = null): AuthService
    {
        $driver = $driver ?? config('shield.auth_driver', 'sanctum');

        return match ($driver) {
            'sanctum' => new SanctumAuthService,
            'passport' => new PassportAuthService,
            'jwt' => new JWTAuthService,
            default => throw new InvalidArgumentException("Unsupported auth driver: {$driver}"),
        };
    }
}
