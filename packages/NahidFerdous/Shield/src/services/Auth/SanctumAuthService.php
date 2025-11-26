<?php

namespace NahidFerdous\Shield\Services\Auth;

class SanctumAuthService extends AuthService
{
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

        $this->deletePreviousTokens($user);

        $roles = $this->getUserRoles($user);
        $token = $user->createToken('shield-api-token', $roles)->plainTextToken;

        return $this->successResponse($user, $token);
    }

    public function logout($user): bool
    {
        $user->currentAccessToken()->delete();

        return true;
    }

    public function refresh($user): array
    {
        // Sanctum doesn't need explicit refresh
        // Just create a new token
        $this->deletePreviousTokens($user);
        $roles = $this->getUserRoles($user);
        $token = $user->createToken('shield-api-token', $roles)->plainTextToken;

        return $this->successResponse($user, $token);
    }

    public function validate(string $token): bool
    {
        return ! empty($token);
    }

    protected function getTokenType(): string
    {
        return 'Bearer';
    }
}
