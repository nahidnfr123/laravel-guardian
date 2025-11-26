# Shield Package - Testing Guide

## Quick Test Commands

### Test Sanctum Driver

```bash
# Switch to Sanctum
php artisan shield:switch-driver sanctum --update-model

# Create a test user
php artisan shield:create-user

# Login via CLI
php artisan shield:login

# Test API
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

### Test Passport Driver

```bash
# Install Passport
composer require laravel/passport
php artisan passport:install

# Switch to Passport
php artisan shield:switch-driver passport --update-model

# Update .env with Passport credentials
# PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1
# PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=your-secret

# Test API
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

### Test JWT Driver

```bash
# Install JWT library
composer require firebase/php-jwt

# Switch to JWT
php artisan shield:switch-driver jwt --update-model

# Generate JWT secret
php -r "echo bin2hex(random_bytes(32));"

# Update .env
# JWT_SECRET=your-generated-secret
# JWT_TTL=60
# JWT_REFRESH_TTL=20160

# Test API
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

## Postman Collection Tests

### 1. Register User

```http
POST {{base_url}}/api/register
Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password"
}
```

### 2. Login (All Drivers)

```http
POST {{base_url}}/api/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password"
}
```

**Expected Response (Sanctum/Passport):**
```json
{
  "error": 0,
  "id": 1,
  "name": "Test User",
  "email": "test@example.com",
  "token": "1|xxxxxxxxxxx",
  "token_type": "Bearer"
}
```

**Expected Response (JWT):**
```json
{
  "error": 0,
  "id": 1,
  "name": "Test User",
  "email": "test@example.com",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 3600
}
```

### 3. Get Current User

```http
GET {{base_url}}/api/me
Authorization: Bearer {{token}}
```

### 4. Refresh Token (JWT/Passport)

```http
POST {{base_url}}/api/refresh
Authorization: Bearer {{token}}
```

### 5. Logout

```http
POST {{base_url}}/api/logout
Authorization: Bearer {{token}}
```

### 6. Social Login - Get Providers

```http
GET {{base_url}}/api/auth/providers
```

### 7. Social Login - Redirect

```http
GET {{base_url}}/api/auth/google/redirect
```

## Feature Testing Checklist

### ✅ Sanctum Driver
- [ ] User registration
- [ ] User login
- [ ] Get authenticated user (/me)
- [ ] Token authentication on protected routes
- [ ] Logout (token deletion)
- [ ] Multiple tokens per user
- [ ] Token abilities/scopes
- [ ] CSRF protection for SPA

### ✅ Passport Driver
- [ ] User registration
- [ ] User login
- [ ] Get authenticated user (/me)
- [ ] Token authentication on protected routes
- [ ] Token refresh
- [ ] Logout (token revocation)
- [ ] Token expiration
- [ ] Personal access tokens

### ✅ JWT Driver
- [ ] User registration
- [ ] User login with JWT
- [ ] Get authenticated user (/me)
- [ ] JWT authentication on protected routes
- [ ] Token refresh with refresh token
- [ ] Logout with token blacklisting
- [ ] Token expiration validation
- [ ] Blacklist verification

### ✅ Social Login
- [ ] List enabled providers
- [ ] Google OAuth redirect
- [ ] Google OAuth callback
- [ ] Facebook OAuth redirect
- [ ] Facebook OAuth callback
- [ ] GitHub OAuth redirect
- [ ] GitHub OAuth callback
- [ ] Auto-create user on first social login
- [ ] Link social account to existing user
- [ ] Auto-verify email for social users

### ✅ Role & Permission System
- [ ] Assign role to user
- [ ] Check user has role
- [ ] Check user has any of multiple roles
- [ ] Attach privilege to role
- [ ] Check user has privilege via role
- [ ] Role middleware protection
- [ ] Privilege middleware protection

## PHP Unit Tests

### Test User Model Preparation

```php
/** @test */
public function it_adds_correct_traits_for_sanctum()
{
    Artisan::call('shield:prepare-user-model', ['--driver' => 'sanctum']);
    
    $userModel = file_get_contents(app_path('Models/User.php'));
    
    $this->assertStringContainsString('use Laravel\Sanctum\HasApiTokens;', $userModel);
    $this->assertStringContainsString('use HasApiTokens, HasShieldRoles;', $userModel);
}

/** @test */
public function it_adds_correct_traits_for_passport()
{
    Artisan::call('shield:prepare-user-model', ['--driver' => 'passport']);
    
    $userModel = file_get_contents(app_path('Models/User.php'));
    
    $this->assertStringContainsString('use Laravel\Passport\HasApiTokens;', $userModel);
    $this->assertStringContainsString('use HasApiTokens, HasShieldRoles;', $userModel);
}

/** @test */
public function it_adds_only_shield_roles_for_jwt()
{
    Artisan::call('shield:prepare-user-model', ['--driver' => 'jwt']);
    
    $userModel = file_get_contents(app_path('Models/User.php'));
    
    $this->assertStringNotContainsString('use Laravel\Sanctum\HasApiTokens;', $userModel);
    $this->assertStringNotContainsString('use Laravel\Passport\HasApiTokens;', $userModel);
    $this->assertStringContainsString('use HasShieldRoles;', $userModel);
}
```

### Test Auth Service Switching

```php
/** @test */
public function it_creates_sanctum_service_when_configured()
{
    config(['shield.auth_driver' => 'sanctum']);
    
    $service = AuthServiceFactory::make();
    
    $this->assertInstanceOf(SanctumAuthService::class, $service);
}

/** @test */
public function it_creates_passport_service_when_configured()
{
    config(['shield.auth_driver' => 'passport']);
    
    $service = AuthServiceFactory::make();
    
    $this->assertInstanceOf(PassportAuthService::class, $service);
}

/** @test */
public function it_creates_jwt_service_when_configured()
{
    config(['shield.auth_driver' => 'jwt']);
    
    $service = AuthServiceFactory::make();
    
    $this->assertInstanceOf(JWTAuthService::class, $service);
}
```

### Test JWT Token Generation

```php
/** @test */
public function it_generates_valid_jwt_token()
{
    $user = User::factory()->create();
    $service = new JWTAuthService();
    
    $result = $service->login([
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    $this->assertArrayHasKey('token', $result);
    $this->assertArrayHasKey('refresh_token', $result);
    $this->assertTrue($service->validate($result['token']));
}

/** @test */
public function it_blacklists_jwt_token_on_logout()
{
    $user = User::factory()->create();
    $service = new JWTAuthService();
    
    $result = $service->login([
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    $token = $result['token'];
    $decoded = $service->decodeToken($token);
    
    // Simulate logout
    $service->logout($user);
    
    // Token should be blacklisted
    $this->assertFalse($service->validate($token));
}
```

### Test Social Login

```php
/** @test */
public function it_creates_user_from_google_oauth()
{
    config(['shield.social.enabled' => true]);
    config(['shield.social.providers.google.enabled' => true]);
    
    $socialUser = Mockery::mock('Laravel\Socialite\Contracts\User');
    $socialUser->shouldReceive('getName')->andReturn('John Doe');
    $socialUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $socialUser->shouldReceive('getId')->andReturn('google-123');
    $socialUser->shouldReceive('getAvatar')->andReturn('https://avatar.url');
    
    Socialite::shouldReceive('driver->user')->andReturn($socialUser);
    
    $service = new SocialAuthService();
    $result = $service->handleCallback('google');
    
    $this->assertEquals(0, $result['error']);
    $this->assertEquals('john@example.com', $result['email']);
    $this->assertArrayHasKey('token', $result);
}
```

## Performance Testing

### Load Testing with Apache Bench

```bash
# Test login endpoint
ab -n 1000 -c 10 -p login.json -T application/json http://localhost:8000/api/login

# Test authenticated endpoint
ab -n 1000 -c 10 -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/me
```

### JWT vs Sanctum Performance

```bash
# Sanctum
ab -n 5000 -c 50 -H "Authorization: Bearer SANCTUM_TOKEN" http://localhost:8000/api/me

# JWT
ab -n 5000 -c 50 -H "Authorization: Bearer JWT_TOKEN" http://localhost:8000/api/me

# Passport
ab -n 5000 -c 50 -H "Authorization: Bearer PASSPORT_TOKEN" http://localhost:8000/api/me
```

## Security Testing

### Test JWT Token Expiration

```bash
# Generate token
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}' | jq -r '.token')

# Wait for token to expire (set short TTL for testing)
sleep 65

# Try to use expired token
curl -X GET http://localhost:8000/api/me \
  -H "Authorization: Bearer $TOKEN"

# Should return 401 Unauthorized
```

### Test Token Blacklisting

```bash
# Login and get token
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}' | jq -r '.token')

# Logout (blacklist token)
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer $TOKEN"

# Try to use blacklisted token
curl -X GET http://localhost:8000/api/me \
  -H "Authorization: Bearer $TOKEN"

# Should return 401 Unauthorized
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Shield Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [8.1, 8.2, 8.3]
        laravel: [10.x, 11.x]
        driver: [sanctum, passport, jwt]
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      
      - name: Install dependencies
        run: composer install
      
      - name: Setup driver
        run: php artisan shield:switch-driver ${{ matrix.driver }}
      
      - name: Run tests
        run: vendor/bin/phpunit
```

## Common Issues & Solutions

### Issue: JWT tokens not validating

**Solution:**
1. Ensure JWT_SECRET is set in .env
2. Clear config cache: `php artisan config:clear`
3. Verify JWT library is installed: `composer require firebase/php-jwt`

### Issue: Passport tokens not working

**Solution:**
1. Run `php artisan passport:install`
2. Set PASSPORT_PERSONAL_ACCESS_CLIENT_ID and SECRET in .env
3. Ensure User model uses Laravel\Passport\HasApiTokens

### Issue: Social login redirect fails

**Solution:**
1. Verify OAuth credentials in .env
2. Check redirect URL matches OAuth app settings
3. Ensure SHIELD_SOCIAL_LOGIN_ENABLED=true

### Issue: Switching drivers breaks authentication

**Solution:**
1. Run `php artisan shield:switch-driver {driver} --update-model`
2. Clear all caches: `php artisan optimize:clear`
3. Verify User model has correct HasApiTokens trait
4. Check middleware in routes matches driver

## Monitoring & Debugging

### Enable Query Logging

```php
// In AppServiceProvider boot()
if (app()->environment('local')) {
    DB::listen(function($query) {
        Log::info($query->sql, $query->bindings);
    });
}
```

### Log Authentication Attempts

```php
// In config/shield.php
'logging' => [
    'enabled' => true,
    'channel' => 'shield',
],
```

### Monitor Token Usage

```bash
# Count active tokens
php artisan tinker
>>> DB::table('personal_access_tokens')->where('expires_at', '>', now())->count()

# Count blacklisted JWT tokens
>>> Cache::get('jwt_blacklist:*')
```
