<?php

namespace NahidFerdous\Shield\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;

class JWTAuthService extends AuthService
{
    protected $secret;

    protected $algo;

    protected $ttl;

    protected $refreshTtl;

    public function __construct()
    {
        parent::__construct();

        $this->secret = config('shield.jwt.secret') ?: config('app.key');
        $this->algo = config('shield.jwt.algo', 'HS256');
        $this->ttl = config('shield.jwt.ttl', 60);
        $this->refreshTtl = config('shield.jwt.refresh_ttl', 20160);
    }

    public function login(array $credentials): array
    {
        $user = $this->findUserByCredentials($credentials);

        if (! $this->validateCredentials($user, $credentials['password'])) {
            throw new \Exception('Invalid credentials', 401);
        }

        if ($this->userIsSuspended($user)) {
            throw new \Exception('User is suspended', 423);
        }

        if (! $this->userIsVerified($user)) {
            throw new \Exception('Account not verified', 403);
        }

        $token = $this->generateToken($user);
        $refreshToken = $this->generateRefreshToken($user);

        return $this->successResponse($user, $token, [
            'refresh_token' => $refreshToken,
            'expires_in' => $this->ttl * 60, // in seconds
        ]);
    }

    public function logout($user): bool
    {
        // Blacklist the current token
        $jti = request()->attributes->get('jwt_id');
        if ($jti && config('shield.jwt.blacklist_enabled', true)) {
            $this->blacklistToken($jti);
        }

        return true;
    }

    public function refresh($user): array
    {
        $token = $this->generateToken($user);
        $refreshToken = $this->generateRefreshToken($user);

        return $this->successResponse($user, $token, [
            'refresh_token' => $refreshToken,
            'expires_in' => $this->ttl * 60,
        ]);
    }

    public function validate(string $token): bool
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));

            // Check if token is blacklisted
            if (config('shield.jwt.blacklist_enabled', true)) {
                if ($this->isBlacklisted($decoded->jti)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate JWT token
     */
    protected function generateToken($user): string
    {
        $now = time();
        $payload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $now + ($this->ttl * 60),
            'nbf' => $now,
            'sub' => $user->id,
            'jti' => $this->generateJti(),
            'roles' => $this->getUserRoles($user),
            'email' => $user->email,
            'name' => $user->name,
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Generate refresh token
     */
    protected function generateRefreshToken($user): string
    {
        $now = time();
        $payload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $now + ($this->refreshTtl * 60),
            'nbf' => $now,
            'sub' => $user->id,
            'jti' => $this->generateJti(),
            'type' => 'refresh',
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Generate unique token ID
     */
    protected function generateJti(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Blacklist a token
     */
    protected function blacklistToken(string $jti): void
    {
        $gracePeriod = config('shield.jwt.blacklist_grace_period', 0);
        $ttl = $this->ttl + $gracePeriod;
        Cache::put("jwt_blacklist:{$jti}", true, $ttl * 60);
    }

    /**
     * Check if token is blacklisted
     */
    protected function isBlacklisted(string $jti): bool
    {
        return Cache::has("jwt_blacklist:{$jti}");
    }

    /**
     * Decode token without validation
     */
    public function decodeToken(string $token)
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algo));
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getTokenType(): string
    {
        return 'Bearer';
    }
}
