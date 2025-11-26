# Shield Package - Implementation Guide

## Package Structure

```
src/
├── Console/
│   └── Commands/
├── Http/
│   ├── Controllers/
│   │   ├── UserController.php (✅ Updated)
│   │   ├── SocialAuthController.php (🆕 New)
│   │   ├── RoleController.php
│   │   └── PrivilegeController.php
│   ├── Middleware/
│   │   ├── JWTAuthenticate.php (🆕 New)
│   │   ├── EnsureShieldRole.php
│   │   └── EnsureShieldPrivilege.php
│   └── Requests/
│       ├── ShieldCreateUserRequest.php
│       └── ShieldLoginRequest.php
├── Models/
│   ├── Role.php
│   ├── Privilege.php
│   └── UserRole.php
├── Providers/
│   └── ShieldServiceProvider.php (✅ Updated)
├── Services/
│   ├── Auth/
│   │   ├── AuthService.php (🆕 Abstract)
│   │   ├── SanctumAuthService.php (🆕 New)
│   │   ├── PassportAuthService.php (🆕 New)
│   │   ├── JWTAuthService.php (🆕 New)
│   │   └── AuthServiceFactory.php (🆕 New)
│   └── SocialAuthService.php (🆕 New)
├── Support/
│   └── ShieldCache.php
└── Traits/
    └── HasRoles.php

config/
└── shield.php (✅ Updated)

database/
├── migrations/
│   ├── create_roles_table.php
│   ├── create_privileges_table.php
│   ├── create_user_roles_table.php
│   ├── create_privilege_role_table.php
│   └── add_social_login_fields_to_users.php (🆕 New)
└── seeders/
    ├── RoleSeeder.php
    └── PrivilegeSeeder.php

routes/
└── api.php (✅ Updated)
```

## New Files Created

### 1. Auth Services (src/Services/Auth/)

- **AuthService.php** - Abstract base class for all auth drivers
- **SanctumAuthService.php** - Sanctum implementation
- **PassportAuthService.php** - Passport implementation
- **JWTAuthService.php** - JWT implementation
- **AuthServiceFactory.php** - Factory to create auth services

### 2. Social Authentication

- **SocialAuthService.php** - Handles social login logic
- **SocialAuthController.php** - API endpoints for social auth

### 3. Middleware

- **JWTAuthenticate.php** - JWT token validation middleware

### 4. Migration

- **add_social_login_fields_to_users.php** - Adds provider, provider_id, avatar fields

## Installation Steps for Package Users

### Step 1: Install Package

```bash
composer require nahidferdous/shield
```

### Step 2: Run Installation Command

```bash
php artisan shield:install
```

This will:
- Publish config file
- Run migrations
- Seed default roles
- Prepare User model

### Step 3: Choose & Configure Auth Driver

#### Option A: Sanctum (Default)

```bash
# Already installed with Laravel
# No additional steps needed
```

#### Option B: Passport

```bash
composer require laravel/passport
php artisan passport:install
```

Update `.env`:
```env
SHIELD_AUTH_DRIVER=passport
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=your-secret
```

Update `User` model:
```php
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles;
}
```

#### Option C: JWT

```bash
composer require firebase/php-jwt
```

Update `.env`:
```env
SHIELD_AUTH_DRIVER=jwt
JWT_SECRET=your-secret-key
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

### Step 4: Configure Social Login (Optional)

```bash
composer require laravel/socialite
```

For additional providers:
```bash
composer require socialiteproviders/manager
```

Update `.env`:
```env
SHIELD_SOCIAL_LOGIN_ENABLED=true

GOOGLE_LOGIN_ENABLED=true
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URL="${APP_URL}/api/auth/google/callback"

FACEBOOK_LOGIN_ENABLED=true
FACEBOOK_CLIENT_ID=your-facebook-app-id
FACEBOOK_CLIENT_SECRET=your-facebook-secret
```

## Testing the Features

### Test Sanctum Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

### Test JWT Login

Change `.env`:
```env
SHIELD_AUTH_DRIVER=jwt
```

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

### Test Social Login

```bash
# Get redirect URL
curl http://localhost:8000/api/auth/google/redirect

# After OAuth callback
curl http://localhost:8000/api/auth/google/callback?code=OAUTH_CODE
```

## Key Features Implemented

### ✅ Multi-Driver Authentication

The package now supports three authentication drivers that can be switched via config:

1. **Sanctum** - Token-based auth for SPAs
2. **Passport** - OAuth2 server implementation
3. **JWT** - JSON Web Token authentication

### ✅ Social Login

Supports 5 major providers out of the box:
- Google
- Facebook
- GitHub
- Twitter
- LinkedIn

Features:
- Auto-create users
- Auto-verify email
- Link social accounts to existing users
- Store provider info and avatar

### ✅ Unified API

Regardless of auth driver, the API endpoints remain the same:
- `/api/login` - Login
- `/api/logout` - Logout
- `/api/refresh` - Refresh token
- `/api/me` - Get current user

### ✅ Easy Switching

Change auth drivers without code changes:
```env
SHIELD_AUTH_DRIVER=sanctum  # or passport, jwt
```

## Configuration Options

### Main Config (config/shield.php)

```php
'auth_driver' => 'sanctum',  // sanctum, passport, jwt

'social' => [
    'enabled' => true,
    'auto_create_user' => true,
    'auto_verify_email' => true,
    'providers' => [...]
],

'jwt' => [
    'secret' => env('JWT_SECRET'),
    'ttl' => 60,
    'refresh_ttl' => 20160,
    'blacklist_enabled' => true,
],
```

## Next Steps

1. **Testing** - Create comprehensive tests for all auth drivers
2. **Documentation** - Add detailed API documentation
3. **Examples** - Create example frontend integrations
4. **CI/CD** - Set up GitHub Actions for automated testing
5. **Package Publishing** - Publish to Packagist

## Troubleshooting

### Issue: JWT tokens not working
**Solution**: Ensure JWT_SECRET is set in `.env` and cache is cleared

### Issue: Social login redirecting to wrong URL
**Solution**: Update redirect URLs in OAuth provider dashboard

### Issue: Passport tokens not working
**Solution**: Run `php artisan passport:install` and configure client credentials

## Support & Contributions

For issues, feature requests, or contributions, please visit the GitHub repository.
