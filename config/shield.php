<?php

use NahidFerdous\Shield\Models\Privilege;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Models\UserRole;

return [
    'version' => env('SHIELD_VERSION', '1.0.1'),

    'disable_commands' => env('SHIELD_DISABLE_COMMANDS', false),

    // Authentication driver: 'sanctum', 'passport', 'jwt'
    'auth_driver' => env('SHIELD_AUTH_DRIVER', 'jwt'),

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

    'auth' => [
        'throttle_attempts' => 6,
        'check_verified' => env('SHIELD_CHECK_EMAIL_VERIFIED', false),
        'create_user' => [
            'request_class' => \NahidFerdous\Shield\Http\Requests\ShieldCreateUserRequest::class,
            'send_verification_email' => env('SHIELD_SEND_VERIFICATION_EMAIL', true),
        ],
        'login' => [
            'request_class' => \NahidFerdous\Shield\Http\Requests\ShieldLoginRequest::class,
            'credential_field' => 'email', // username, email|phone, email|phone|username,
            'verification_field' => 'email_verified_at', // make sure to add this field to your user model
        ],
    ],

    'default_user_role_slug' => env('DEFAULT_ROLE_SLUG', 'user'),

    'protected_role_slugs' => ['admin', 'super-admin'],

    'delete_previous_access_tokens_on_login' => env('DELETE_PREVIOUS_ACCESS_TOKENS_ON_LOGIN', false),

    // Set custom views for emails, or leave null to use default templates
    'emails' => [
        'verify_email' => [
            'subject' => 'Verify Email Address',
            'template' => null, // e.g., 'emails.verify-email'
            'expiration' => 6, // in hrs
            'redirect_url' => env('APP_URL').'/verify-email', // frontend url
        ],
        'reset_password' => [
            'subject' => 'Reset Password Notification',
            'template' => null, // e.g., 'emails.reset-password'
            'expiration' => 6, // in hrs
            'redirect_url' => env('APP_URL').'/reset-password', // frontend url
        ],
    ],

    // JWT Configuration
    'jwt' => [
        'secret' => env('JWT_SECRET'),
        'ttl' => (int) env('JWT_TTL', 60), // minutes
        'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 20160), // minutes (2 weeks)
        'algo' => env('JWT_ALGO', 'HS256'),
        'required_claims' => ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti'],
        'blacklist_enabled' => (bool) env('JWT_BLACKLIST_ENABLED', true),
        'blacklist_grace_period' => (int) env('JWT_BLACKLIST_GRACE_PERIOD', 0),
    ],

    // Passport Configuration
    'passport' => [
        'personal_access_client_id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'personal_access_client_secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
        'token_expiration' => env('PASSPORT_TOKEN_EXPIRATION', 365), // days
    ],

    // Social Login Configuration
    'social' => [
        'enabled' => env('SHIELD_SOCIAL_LOGIN_ENABLED', false),
        'redirect_url' => env('SHIELD_SOCIAL_REDIRECT_URL', '/auth/callback'),

        'providers' => [
            'google' => [
                'enabled' => env('GOOGLE_LOGIN_ENABLED', false),
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect' => env('GOOGLE_REDIRECT_URL'),
            ],
            'facebook' => [
                'enabled' => env('FACEBOOK_LOGIN_ENABLED', false),
                'client_id' => env('FACEBOOK_CLIENT_ID'),
                'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
                'redirect' => env('FACEBOOK_REDIRECT_URL'),
            ],
            'github' => [
                'enabled' => env('GITHUB_LOGIN_ENABLED', false),
                'client_id' => env('GITHUB_CLIENT_ID'),
                'client_secret' => env('GITHUB_CLIENT_SECRET'),
                'redirect' => env('GITHUB_REDIRECT_URL'),
            ],
            'twitter' => [
                'enabled' => env('TWITTER_LOGIN_ENABLED', false),
                'client_id' => env('TWITTER_CLIENT_ID'),
                'client_secret' => env('TWITTER_CLIENT_SECRET'),
                'redirect' => env('TWITTER_REDIRECT_URL'),
            ],
            'linkedin' => [
                'enabled' => env('LINKEDIN_LOGIN_ENABLED', false),
                'client_id' => env('LINKEDIN_CLIENT_ID'),
                'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
                'redirect' => env('LINKEDIN_REDIRECT_URL'),
            ],
        ],

        // Auto-create user if not exists
        'auto_create_user' => env('SHIELD_SOCIAL_AUTO_CREATE_USER', true),

        // Auto-verify email for social logins
        'auto_verify_email' => env('SHIELD_SOCIAL_AUTO_VERIFY_EMAIL', true),
    ],

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
