<?php

namespace NahidFerdous\Shield\Services\Auth;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTAuthService extends AuthService
{
    protected $ttl;

    protected $refreshTtl;

    public function __construct()
    {
        parent::__construct();

        $this->ttl = (int) config('shield.jwt.ttl', 60);
        $this->refreshTtl = (int) config('shield.jwt.refresh_ttl', 20160);
    }

    public function login(array $credentials): array
    {
        $user = $this->findUserByCredentials($credentials);

        if (! $this->validateCredentials($user, $credentials['password'])) {
            throw new \RuntimeException('Invalid credentials', 401);
        }

        if ($this->userIsSuspended($user)) {
            throw new \RuntimeException('User is suspended', 423);
        }

        if (! $this->userIsVerified($user)) {
            throw new \RuntimeException('Account not verified', 403);
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
        try {
            // Invalidate the token (adds to blacklist)
            JWTAuth::invalidate(JWTAuth::getToken());

            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    public function refresh($user): array
    {
        try {
            // Refresh the token
            $token = JWTAuth::refresh(JWTAuth::getToken());
            $refreshToken = $this->generateRefreshToken($user);

            return $this->successResponse($user, $token, [
                'refresh_token' => $refreshToken,
                'expires_in' => $this->ttl * 60,
            ]);
        } catch (TokenExpiredException $e) {
            throw new \RuntimeException('Token has expired and cannot be refreshed', 401);
        } catch (JWTException $e) {
            throw new \RuntimeException('Could not refresh token', 500);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), 500);
        }
    }

    public function validate(string $token): bool
    {
        try {
            JWTAuth::setToken($token)->authenticate();

            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * Generate JWT token
     */
    protected function generateToken($user): string
    {
        // Add custom claims
        $customClaims = [
            'roles' => $this->getUserRoles($user),
            'email' => $user->email,
            'name' => $user->name,
        ];

        // Ensure user implements JWTSubject
        if (! $user instanceof \Tymon\JWTAuth\Contracts\JWTSubject) {
            throw new \RuntimeException('User model must implement JWTSubject interface');
        }

        return JWTAuth::customClaims($customClaims)->fromUser($user);
    }

    /**
     * Generate refresh token
     */
    protected function generateRefreshToken($user): string
    {
        // Ensure user implements JWTSubject
        if (! $user instanceof \Tymon\JWTAuth\Contracts\JWTSubject) {
            throw new \RuntimeException('User model must implement JWTSubject interface');
        }

        // Temporarily set longer TTL for refresh token (ensure integer)
        // $originalTtl = config('jwt.ttl', 60);
        // config(['jwt.ttl' => (int)$this->refreshTtl]);

        $customClaims = [
            'type' => 'refresh',
        ];

        $refreshToken = JWTAuth::customClaims($customClaims)->fromUser($user);

        // Restore original TTL
        // config(['jwt.ttl' => (int)$originalTtl]);

        return $refreshToken;
    }

    /**
     * Decode token without validation
     */
    public function decodeToken(string $token)
    {
        try {
            return JWTAuth::setToken($token)->getPayload();
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Get the authenticated user from a token
     */
    public function getUserFromToken(string $token)
    {
        try {
            return JWTAuth::setToken($token)->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Check if the token is blacklisted
     */
    public function isBlacklisted(string $token): bool
    {
        try {
            JWTAuth::setToken($token)->checkOrFail();

            return false;
        } catch (TokenInvalidException $e) {
            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    protected function getTokenType(): string
    {
        return 'Bearer';
    }
}
