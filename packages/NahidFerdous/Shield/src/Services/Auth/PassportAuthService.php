<?php

namespace NahidFerdous\Shield\Services\Auth;

use Laravel\Passport\Token;

class PassportAuthService extends AuthService
{
    /**
     * @throws \Exception
     *                    The error "The requested scope is invalid, unknown, or malformed" occurs because you're passing role names as scopes to Passport, but Passport expects predefined OAuth scopes, not dynamic role names.
     *                    Here's the problematic line $tokenResult = $user->createToken('shield-api-token', $this->getUserRoles($user));
     *
     * If you want to use OAuth scopes with Passport, you need to define them in your AuthServiceProvider:
     *
     *public function boot()
     * {
     * $this->registerPolicies();
     *
     * // Define valid Passport scopes
     * Passport::tokensCan([
     * 'admin' => 'Admin access',
     * 'user' => 'User access',
     * 'moderator' => 'Moderator access',
     * // Add all your role names here
     * ]);
     *
     * Passport::setDefaultScope([
     * 'user',
     * ]);
     * }
     */
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

        $this->deletePreviousTokens($user);

        // Create personal access token WITHOUT scopes
        $tokenResult = $user->createToken('shield-api-token');
        $token = $tokenResult->accessToken;

        return $this->successResponse($user, $token, [
            'expires_at' => $tokenResult->token->expires_at,
        ]);
    }

    /*
     * with scopes
     * */
    //    public function login(array $credentials): array
    //    {
    //        $user = $this->findUserByCredentials($credentials);
    //
    //        if (! $this->validateCredentials($user, $credentials['password'])) {
    //            throw new \RuntimeException('Invalid credentials', 401);
    //        }
    //
    //        if ($this->userIsSuspended($user)) {
    //            throw new \RuntimeException('User is suspended', 423);
    //        }
    //
    //        if (! $this->userIsVerified($user)) {
    //            throw new \RuntimeException('Account not verified', 403);
    //        }
    //
    //        $this->deletePreviousTokens($user);
    //
    //        // Create personal access token
    //        $tokenResult = $user->createToken('shield-api-token', $this->getUserRoles($user));
    //        $token = $tokenResult->accessToken;
    //
    //        return $this->successResponse($user, $token, [
    //            'expires_at' => $tokenResult->token->expires_at,
    //        ]);
    //    }

    public function logout($user): bool
    {
        $token = $user->token();
        if ($token) {
            $token->revoke();

            return true;
        }

        return false;
    }

    public function refresh($user): array
    {
        // Revoke old tokens
        $user->tokens()->delete();

        // Create new token WITHOUT scopes
        $tokenResult = $user->createToken('shield-api-token');
        $token = $tokenResult->accessToken;

        return $this->successResponse($user, $token, [
            'expires_at' => $tokenResult->token->expires_at,
        ]);
    }
    /*
     * with scopes
     * */
    //    public function refresh($user): array
    //    {
    //        // Revoke old tokens
    //        $user->tokens()->delete();
    //
    //        // Create new token
    //        $tokenResult = $user->createToken('shield-api-token', $this->getUserRoles($user));
    //        $token = $tokenResult->accessToken;
    //
    //        return $this->successResponse($user, $token, [
    //            'expires_at' => $tokenResult->token->expires_at,
    //        ]);
    //    }

    public function validate(string $token): bool
    {
        $tokenModel = Token::where('id', $token)->first();

        if (! $tokenModel) {
            return false;
        }

        return ! $tokenModel->revoked && $tokenModel->expires_at->isFuture();
    }

    protected function getTokenType(): string
    {
        return 'Bearer';
    }
}
