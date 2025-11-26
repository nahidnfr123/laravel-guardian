<?php

namespace NahidFerdous\Shield\Services\Auth;

use Laravel\Passport\Token;

class PassportAuthService extends AuthService
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

        // Create personal access token
        $tokenResult = $user->createToken('shield-api-token', $this->getUserRoles($user));
        $token = $tokenResult->accessToken;

        return $this->successResponse($user, $token, [
            'expires_at' => $tokenResult->token->expires_at,
        ]);
    }

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

        // Create new token
        $tokenResult = $user->createToken('shield-api-token', $this->getUserRoles($user));
        $token = $tokenResult->accessToken;

        return $this->successResponse($user, $token, [
            'expires_at' => $tokenResult->token->expires_at,
        ]);
    }

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

    protected function deletePreviousTokens($user): void
    {
        if (config('shield.delete_previous_access_tokens_on_login', false)) {
            $user->tokens()->delete();
        }
    }
}
