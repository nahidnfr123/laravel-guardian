<?php

namespace NahidFerdous\Shield\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use NahidFerdous\Shield\Models\Privilege;
use NahidFerdous\Shield\Models\Role;

class ShieldCache
{
    protected static array $runtimeVersions = [];

    public static function rememberRoleSlugs($userId, callable $resolver): array
    {
        if (! static::enabled() || ! $userId) {
            return $resolver();
        }

        return static::remember(static::rolesKey($userId), $resolver);
    }

    public static function rememberPrivilegeSlugs($userId, callable $resolver): array
    {
        if (! static::enabled() || ! $userId) {
            return $resolver();
        }

        return static::remember(static::privilegesKey($userId), $resolver);
    }

    public static function forgetUser($user): void
    {
        if (! $user) {
            return;
        }

        $key = $user instanceof \Illuminate\Contracts\Auth\Authenticatable ? $user->getAuthIdentifier() : $user;

        static::cacheStore()->forget(static::rolesKey($key));
        static::cacheStore()->forget(static::privilegesKey($key));
        static::bumpRuntimeVersion($key);
    }

    public static function forgetUsers(iterable $userIds): void
    {
        foreach ($userIds as $userId) {
            static::forgetUser($userId);
        }
    }

    public static function forgetUsersByRole(Role $role): void
    {
        static::forgetUsersByRoleIds([$role->getKey()]);
    }

    public static function forgetUsersByPrivilege(Privilege $privilege): void
    {
        $roleIds = DB::table(config('shield.tables.role_privilege', 'privilege_role'))
            ->where('privilege_id', $privilege->getKey())
            ->pluck('role_id');

        static::forgetUsersByRoleIds($roleIds);
    }

    public static function forgetUsersByRoleIds(iterable $roleIds): void
    {
        $roleIds = collect($roleIds)->filter()->unique();

        if ($roleIds->isEmpty()) {
            return;
        }

        $userIds = DB::table(config('shield.tables.pivot', 'user_roles'))
            ->whereIn('role_id', $roleIds->all())
            ->pluck('user_id');

        static::forgetUsers($userIds);
    }

    public static function forgetAllUsersWithRoles(): void
    {
        $userIds = DB::table(config('shield.tables.pivot', 'user_roles'))->pluck('user_id');

        static::forgetUsers($userIds);
    }

    public static function runtimeVersion($user): int
    {
        if (! $user) {
            return 0;
        }

        $key = $user instanceof \Illuminate\Contracts\Auth\Authenticatable ? $user->getAuthIdentifier() : $user;

        return static::$runtimeVersions[$key] ?? 0;
    }

    protected static function remember(string $key, callable $resolver): array
    {
        $callback = function () use ($resolver) {
            $resolved = $resolver();

            if ($resolved instanceof \Illuminate\Support\Collection) {
                $resolved = $resolved->all();
            }

            return array_values(array_filter((array) $resolved));
        };

        $ttl = static::ttl();

        if ($ttl === null) {
            return static::cacheStore()->rememberForever($key, $callback);
        }

        return static::cacheStore()->remember($key, $ttl, $callback);
    }

    protected static function cacheStore(): CacheRepository
    {
        $store = config('shield.cache.store');

        return $store ? Cache::store($store) : Cache::store();
    }

    protected static function ttl(): ?int
    {
        $ttl = config('shield.cache.ttl', 300);

        if ($ttl === null) {
            return null;
        }

        $ttl = (int) $ttl;

        return $ttl > 0 ? $ttl : null;
    }

    protected static function enabled(): bool
    {
        return (bool) config('shield.cache.enabled', true);
    }

    protected static function rolesKey($userId): string
    {
        return sprintf('shield:user:%s:roles', $userId);
    }

    protected static function privilegesKey($userId): string
    {
        return sprintf('shield:user:%s:privileges', $userId);
    }

    protected static function bumpRuntimeVersion($user): void
    {
        if (! $user) {
            return;
        }

        $key = $user instanceof \Illuminate\Contracts\Auth\Authenticatable ? $user->getAuthIdentifier() : $user;

        static::$runtimeVersions[$key] = (static::$runtimeVersions[$key] ?? 0) + 1;
    }
}
