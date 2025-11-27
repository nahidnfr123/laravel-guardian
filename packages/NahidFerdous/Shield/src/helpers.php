<?php

// src/helpers.php

use Illuminate\Contracts\Auth\Authenticatable;
use NahidFerdous\Shield\Services\Auth\AuthService;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;

if (!function_exists('shield')) {
    /**
     * Get Shield auth service for a specific guard
     *
     * @param string|null $guard
     * @return AuthService
     */
    function shield(?string $guard = null): AuthService
    {
        return AuthServiceFactory::make($guard);
    }
}

if (!function_exists('shield_auth')) {
    /**
     * Get authenticated user for specific guard
     *
     * @param string|null $guard
     * @return Authenticatable|null
     */
    function shield_auth(?string $guard = null): ?Authenticatable
    {
        $guard = $guard ?? config('shield.default_guard', 'api');
        $guardConfig = config("shield.guards.{$guard}");
        $authGuard = $guardConfig['auth_guard'] ?? $guard;

        return auth($authGuard)->user();
    }
}

if (!function_exists('shield_guard')) {
    /**
     * Get current Shield guard from request
     *
     * @param \Illuminate\Http\Request|null $request
     * @return string
     */
    function shield_guard($request = null): string
    {
        $request = $request ?? request();

        return $request->attributes->get('shield_guard')
            ?? $request->header('X-Guard')
            ?? config('shield.default_guard', 'api');
    }
}

if (!function_exists('shield_check')) {
    /**
     * Check if a user is authenticated on specific guard
     *
     * @param string|null $guard
     * @return bool
     */
    function shield_check(?string $guard = null): bool
    {
        $guard = $guard ?? config('shield.default_guard', 'api');
        $guardConfig = config("shield.guards.{$guard}");
        $authGuard = $guardConfig['auth_guard'] ?? $guard;

        return auth($authGuard)->check();
    }
}

if (!function_exists('shield_user')) {
    /**
     * Get an authenticated user (alias for shield_auth)
     *
     * @param string|null $guard
     * @return Authenticatable|null
     */
    function shield_user(?string $guard = null): ?Authenticatable
    {
        return shield_auth($guard);
    }
}

if (!function_exists('shield_login')) {
    /**
     * Login user programmatically
     *
     * @param array $credentials
     * @param string|null $guard
     * @return array
     */
    function shield_login(array $credentials, ?string $guard = null): array
    {
        return shield($guard)->login($credentials);
    }
}

if (!function_exists('shield_logout')) {
    /**
     * Logout user programmatically
     *
     * @param mixed $user
     * @param string|null $guard
     * @return bool
     */
    function shield_logout($user = null, ?string $guard = null): bool
    {
        $user = $user ?? shield_auth($guard);

        if (!$user) {
            return false;
        }

        return shield($guard)->logout($user);
    }
}

if (!function_exists('shield_guards')) {
    /**
     * Get all available Shield guards
     *
     * @return array
     */
    function shield_guards(): array
    {
        return AuthServiceFactory::availableGuards();
    }
}
