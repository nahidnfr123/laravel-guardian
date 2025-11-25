<?php

use NahidFerdous\Shield\Models\Privilege;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Models\UserRole;

return [
    'version' => env('SHIELD_VERSION', '1.0.1'),

    'disable_commands' => env('SHIELD_DISABLE_COMMANDS', false),

    'guard' => env('SHIELD_GUARD', 'sanctum'),

    'route_prefix' => env('SHIELD_ROUTE_PREFIX', 'api'),
    'route_name_prefix' => env('SHIELD_ROUTE_NAME_PREFIX', 'shield.'),
    'route_middleware' => ['api'],
    'load_default_routes' => true,
    'disable_api' => env('SHIELD_DISABLE_API', false),

    'models' => [
        'user' => env('SHIELD_USER_MODEL', env('AUTH_MODEL', 'App\\Models\\User')),
        'role' => Role::class,
        'privilege' => Privilege::class,
        'pivot' => UserRole::class,
    ],

    'tables' => [
        'users' => env('SHIELD_USERS_TABLE', 'users'),
        'roles' => 'roles',
        'pivot' => 'user_roles',
        'privileges' => 'privileges',
        'role_privilege' => 'privilege_role',
    ],

    'default_user_role_slug' => env('DEFAULT_ROLE_SLUG', 'user'),

    'protected_role_slugs' => ['admin', 'super-admin'],

    'delete_previous_access_tokens_on_login' => env('DELETE_PREVIOUS_ACCESS_TOKENS_ON_LOGIN', false),

    'cache' => [
        'enabled' => env('SHIELD_CACHE_ENABLED', true),
        'store' => env('SHIELD_CACHE_STORE'),
        'ttl' => env('SHIELD_CACHE_TTL', 300),
    ],

    'abilities' => [
        'admin' => ['admin', 'super-admin'],
        'user_update' => ['admin', 'super-admin', 'user'],
    ],
];
