![logo.png](logo.png)

# Laravel Shield - Complete Authentication Package

A comprehensive Laravel package for authentication (Sanctum, Passport, JWT) and role/permission management with social login support.

## Features

- 🔐 Multiple authentication drivers (Sanctum, Passport, JWT)
- 👥 Social login (Google, Facebook, GitHub, Twitter, LinkedIn)
- 🛡️ Role-based access control (RBAC)
- 🔑 Permission/Privilege management
- 💾 Caching support
- 🚀 Production-ready out of the box
- 📝 Comprehensive CLI commands

## Installation

```bash
composer require nahidferdous/shield
```

## Quick Start

### 1. Install Shield

```bash
php artisan shield:install
```

This will:
- Publish configuration file
- Run migrations
- Prepare your User model
- Seed default roles

### 2. Choose Authentication Driver

Edit `.env`:

```env
SHIELD_AUTH_DRIVER=sanctum  # Options: sanctum, passport, jwt
```

### 3. Configure Authentication Driver

#### For Sanctum (Default)
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

#### For Passport
```bash
php artisan passport:install
composer require laravel/passport
```

Add to `.env`:
```env
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=your-client-id
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=your-client-secret
```

#### For JWT
```bash
composer require firebase/php-jwt
```

Add to `.env`:
```env
JWT_SECRET=your-secret-key
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

### 4. Enable Social Login (Optional)

```bash
composer require laravel/socialite socialiteproviders/manager
```

Edit `.env`:
```env
SHIELD_SOCIAL_LOGIN_ENABLED=true

# Google
GOOGLE_LOGIN_ENABLED=true
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URL="${APP_URL}/api/auth/google/callback"

# Facebook
FACEBOOK_LOGIN_ENABLED=true
FACEBOOK_CLIENT_ID=your-app-id
FACEBOOK_CLIENT_SECRET=your-app-secret

# GitHub
GITHUB_LOGIN_ENABLED=true
GITHUB_CLIENT_ID=your-client-id
GITHUB_CLIENT_SECRET=your-client-secret
```

## API Endpoints

### Authentication

#### Register
```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

Response:
```json
{
  "error": 0,
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "token": "your-access-token",
  "token_type": "Bearer"
}
```

#### Logout
```http
POST /api/logout
Authorization: Bearer your-access-token
```

#### Refresh Token
```http
POST /api/refresh
Authorization: Bearer your-access-token
```

#### Get Current User
```http
GET /api/me
Authorization: Bearer your-access-token
```

### Social Authentication

#### Get Enabled Providers
```http
GET /api/auth/providers
```

Response:
```json
{
  "error": 0,
  "providers": ["google", "facebook", "github"]
}
```

#### Redirect to Provider
```http
GET /api/auth/{provider}/redirect
```

Example: `GET /api/auth/google/redirect`

#### Handle Callback
```http
GET /api/auth/{provider}/callback
```

This endpoint is called automatically by the OAuth provider.

### User Management

#### List Users
```http
GET /api/users
Authorization: Bearer your-access-token
```

#### Get User
```http
GET /api/users/{id}
Authorization: Bearer your-access-token
```

#### Update User
```http
PUT /api/users/{id}
Authorization: Bearer your-access-token
Content-Type: application/json

{
  "name": "Updated Name",
  "email": "updated@example.com"
}
```

#### Delete User
```http
DELETE /api/users/{id}
Authorization: Bearer your-access-token
```

### Role Management

#### List Roles
```http
GET /api/roles
Authorization: Bearer your-access-token
```

#### Create Role
```http
POST /api/roles
Authorization: Bearer your-access-token
Content-Type: application/json

{
  "name": "Editor",
  "slug": "editor",
  "description": "Can edit content"
}
```

#### Assign Role to User
```http
POST /api/roles/{roleId}/users/{userId}
Authorization: Bearer your-access-token
```

#### Remove Role from User
```http
DELETE /api/roles/{roleId}/users/{userId}
Authorization: Bearer your-access-token
```

### Privilege Management

#### List Privileges
```http
GET /api/privileges
Authorization: Bearer your-access-token
```

#### Create Privilege
```http
POST /api/privileges
Authorization: Bearer your-access-token
Content-Type: application/json

{
  "name": "Edit Posts",
  "slug": "edit-posts",
  "description": "Can edit blog posts"
}
```

#### Attach Privilege to Role
```http
POST /api/privileges/{privilegeId}/roles/{roleId}
Authorization: Bearer your-access-token
```

## CLI Commands

### User Management
```bash
php artisan shield:create-user           # Create a new user
php artisan shield:list-users            # List all users
php artisan shield:update-user           # Update user details
php artisan shield:delete-user           # Delete a user
php artisan shield:suspend-user          # Suspend a user
php artisan shield:unsuspend-user        # Unsuspend a user
php artisan shield:login                 # Login via CLI
php artisan shield:logout                # Logout current session
```

### Role Management
```bash
php artisan shield:add-role              # Create a new role
php artisan shield:list-roles            # List all roles
php artisan shield:update-role           # Update role details
php artisan shield:delete-role           # Delete a role
php artisan shield:assign-role           # Assign role to user
php artisan shield:delete-user-role      # Remove role from user
```

### Privilege Management
```bash
php artisan shield:add-privilege         # Create a privilege
php artisan shield:list-privileges       # List all privileges
php artisan shield:update-privilege      # Update privilege
php artisan shield:delete-privilege      # Delete privilege
php artisan shield:attach-privilege      # Attach privilege to role
php artisan shield:detach-privilege      # Detach privilege from role
```

## Middleware

### Role-Based Middleware

```php
// Single role
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Admin only routes
});

// Multiple roles (any)
Route::middleware(['auth:sanctum', 'roles:admin,moderator'])->group(function () {
    // Admin or Moderator routes
});
```

### Privilege-Based Middleware

```php
// Single privilege
Route::middleware(['auth:sanctum', 'privilege:edit-posts'])->group(function () {
    // Routes for users with edit-posts privilege
});

// Multiple privileges (any)
Route::middleware(['auth:sanctum', 'privileges:edit-posts,delete-posts'])->group(function () {
    // Routes for users with any of these privileges
});
```

## Configuration

Publish and edit `config/shield.php`:

```php
return [
    // Authentication driver
    'auth_driver' => env('SHIELD_AUTH_DRIVER', 'sanctum'),
    
    // Default user role
    'default_user_role_slug' => env('DEFAULT_ROLE_SLUG', 'user'),
    
    // Delete previous tokens on login
    'delete_previous_access_tokens_on_login' => env('DELETE_PREVIOUS_ACCESS_TOKENS_ON_LOGIN', false),
    
    // Social login
    'social' => [
        'enabled' => env('SHIELD_SOCIAL_LOGIN_ENABLED', false),
        'auto_create_user' => true,
        'auto_verify_email' => true,
    ],
    
    // JWT configuration
    'jwt' => [
        'secret' => env('JWT_SECRET'),
        'ttl' => env('JWT_TTL', 60),
        'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),
    ],
    
    // Cache
    'cache' => [
        'enabled' => env('SHIELD_CACHE_ENABLED', true),
        'ttl' => env('SHIELD_CACHE_TTL', 300),
    ],
];
```

## Switching Between Authentication Drivers

Simply change the `SHIELD_AUTH_DRIVER` in your `.env`:

```env
# Use Sanctum
SHIELD_AUTH_DRIVER=sanctum

# Use Passport
SHIELD_AUTH_DRIVER=passport

# Use JWT
SHIELD_AUTH_DRIVER=jwt
```

No code changes required! Shield handles the rest automatically.

## User Model Setup

Your User model should use the Shield traits:

```php
use NahidFerdous\Shield\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;  // or Laravel\Passport\HasApiTokens for Passport

class User extends Authenticatable
{
    use HasApiTokens, HasRoles;
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'avatar',
    ];
}
```

## Testing Social Login Locally

Use ngrok or similar tool to expose your local server:

```bash
ngrok http 8000
```

Then update your OAuth app redirect URLs to use the ngrok URL.

## Security

- Always use HTTPS in production
- Keep your JWT secret secure
- Rotate tokens regularly
- Enable token blacklisting for JWT
- Implement rate limiting on login endpoints

## License

MIT License

## Support

For issues and questions, please open an issue on GitHub.
