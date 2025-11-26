<?php

namespace NahidFerdous\Shield\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;
use NahidFerdous\Shield\Services\Auth\JWTAuthService;
use NahidFerdous\Shield\Support\ShieldCache;

class SocialAuthService
{
    protected $userClass;

    public function __construct()
    {
        $this->userClass = config('shield.models.user', config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Get redirect URL for social provider
     */
    public function redirect(string $provider): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle callback from social provider
     *
     * @throws \Exception
     */
    public function handleCallback(string $provider): array
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            throw new \Exception('Failed to authenticate with '.$provider, 401);
        }

        $user = $this->findOrCreateUser($socialUser, $provider);

        // Generate token using configured auth driver
        $authService = AuthServiceFactory::make();

        // For social login, we create token directly without password check
        $token = $this->generateTokenForUser($user, $authService);

        return [
            'error' => 0,
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'token' => $token['token'],
            'token_type' => 'Bearer',
            'provider' => $provider,
        ];
    }

    /**
     * Find or create user from social provider
     *
     * @throws \Exception
     */
    protected function findOrCreateUser($socialUser, string $provider)
    {
        // Try to find by email first
        $user = $this->userClass::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Update provider info if needed
            $this->updateUserProvider($user, $provider, $socialUser->getId());

            return $user;
        }

        // Create new user if auto-create is enabled
        if (config('shield.social.auto_create_user', true)) {
            return $this->createUserFromSocial($socialUser, $provider);
        }

        throw new \Exception('User not found and auto-create is disabled', 404);
    }

    /**
     * Create new user from social provider data
     */
    protected function createUserFromSocial($socialUser, string $provider)
    {
        $userData = [
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'password' => Hash::make(Str::random(32)), // Random password
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
        ];

        // Auto-verify email if configured
        if (config('shield.social.auto_verify_email', true)) {
            $userData['email_verified_at'] = now();
        }

        // Add avatar if available
        if ($avatar = $socialUser->getAvatar()) {
            $userData['avatar'] = $avatar;
        }

        $user = $this->userClass::create($userData);

        // Assign default role
        $defaultRoleSlug = config('shield.default_user_role_slug', 'user');
        $defaultRole = Role::where('slug', $defaultRoleSlug)->first();

        if ($defaultRole) {
            $user->roles()->attach($defaultRole);
        }

        ShieldCache::forgetUser($user);

        return $user;
    }

    /**
     * Update user's provider information
     */
    protected function updateUserProvider($user, string $provider, string $providerId): void
    {
        if (! $user->provider || ! $user->provider_id) {
            $user->update([
                'provider' => $provider,
                'provider_id' => $providerId,
            ]);
        }
    }

    /**
     * Generate token for user based on auth driver
     *
     * @throws \Exception
     */
    protected function generateTokenForUser($user, $authService): array
    {
        $driver = config('shield.auth_driver', 'sanctum');

        switch ($driver) {
            case 'sanctum':
                $roles = $user->roles->pluck('slug')->all();
                $token = $user->createToken('shield-social-token', $roles)->plainTextToken;

                return ['token' => $token];

            case 'passport':
                $tokenResult = $user->createToken('shield-social-token', $user->roles->pluck('slug')->all());

                return [
                    'token' => $tokenResult->accessToken,
                    'expires_at' => $tokenResult->token->expires_at,
                ];

            case 'jwt':
                return (new JWTAuthService)->login(['skip_password_check' => true, 'user' => $user]);

            default:
                throw new \Exception('Unsupported auth driver', 500);
        }
    }

    /**
     * Validate if provider is enabled
     *
     * @throws \Exception
     */
    protected function validateProvider(string $provider): void
    {
        if (! config('shield.social.enabled', false)) {
            throw new \RuntimeException('Social login is not enabled', 403);
        }

        $providerConfig = config("shield.social.providers.{$provider}");

        if (! $providerConfig || ! ($providerConfig['enabled'] ?? false)) {
            throw new \RuntimeException("Provider {$provider} is not enabled", 403);
        }

        if (empty($providerConfig['client_id']) || empty($providerConfig['client_secret'])) {
            throw new \RuntimeException("Provider {$provider} is not properly configured", 500);
        }
    }

    /**
     * Get list of enabled providers
     */
    public function getEnabledProviders(): array
    {
        if (! config('shield.social.enabled', false)) {
            return [];
        }

        $providers = config('shield.social.providers', []);

        return array_keys(array_filter($providers, function ($config) {
            return $config['enabled'] ?? false;
        }));
    }
}
