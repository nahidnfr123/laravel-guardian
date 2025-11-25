<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserSuspensionController extends Controller
{
    public function store(Request $request, $user)
    {
        $payload = $request->validate([
            'reason' => 'nullable|string|max:65535',
        ]);

        $user = $this->resolveUser($user);

        if ($this->userIsSuspended($user)) {
            return response([
                'error' => 0,
                'status' => 'suspended',
                'user_id' => $user->id,
                'message' => 'User already suspended.',
                'reason' => $user->suspension_reason,
                'revoked_tokens' => 0,
            ], 200);
        }

        $user->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => $payload['reason'] ?? null,
        ])->save();

        $revoked = $this->revokeTokens($user);

        return response([
            'error' => 0,
            'status' => 'suspended',
            'user_id' => $user->id,
            'reason' => $payload['reason'] ?? null,
            'revoked_tokens' => $revoked,
        ], 200);
    }

    public function destroy($user)
    {
        $user = $this->resolveUser($user);

        if (! $this->userIsSuspended($user)) {
            return response([
                'error' => 0,
                'status' => 'active',
                'user_id' => $user->id,
                'message' => 'User is already active.',
            ], 200);
        }

        $user->forceFill([
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        return response([
            'error' => 0,
            'status' => 'active',
            'user_id' => $user->id,
        ], 200);
    }

    protected function userClass(): string
    {
        return config('shield.models.user', config('auth.providers.users.model', 'App\\Models\\User'));
    }

    protected function resolveUser($user)
    {
        if ($user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            return $user;
        }

        return $this->userClass()::query()->findOrFail($user);
    }

    protected function userIsSuspended($user): bool
    {
        if (method_exists($user, 'isSuspended')) {
            return $user->isSuspended();
        }

        return (bool) ($user->suspended_at ?? false);
    }

    protected function revokeTokens($user): int
    {
        if (! method_exists($user, 'tokens')) {
            return 0;
        }

        return (int) $user->tokens()->delete();
    }
}
