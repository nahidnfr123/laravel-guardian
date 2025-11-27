<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use NahidFerdous\Shield\Http\Requests\ShieldLoginRequest;
use NahidFerdous\Shield\Models\EmailVerificationToken;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;
use NahidFerdous\Shield\Traits\ApiResponseTrait;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Handle user login
     */
    public function login(ShieldLoginRequest $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            $authService = AuthServiceFactory::make();
            $result = $authService->login($request->validated());

            return $this->success('Login successful', $result);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $authService = AuthServiceFactory::make();
            $authService->logout($user);

            return $this->success('Logout successful');
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Refresh authentication token
     */
    public function refresh(Request $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $authService = AuthServiceFactory::make();
            $result = $authService->refresh($user);

            return $this->success('Token refreshed successfully', $result);
        } catch (\Exception $e) {
            return $this->failure('Token refresh failed' || $e->getMessage(), 401 || $e->getCode());
        }
    }

    /**
     * Get authenticated user information
     */
    public function me(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->success('me', $request->user());
    }

    /**
     * Verify user email with token
     */
    public function verifyEmail(string $token): \Illuminate\Http\JsonResponse
    {
        $verification = EmailVerificationToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (! $verification) {
            return $this->failure('Invalid or expired verification token', 400);
        }

        $userClass = config('shield.models.user', 'App\\Models\\User');
        $user = $userClass::find($verification->user_id);

        if (! $user) {
            return $this->failure('User not found', 404);
        }

        if ($user->email_verified_at) {
            $verification->delete();

            return $this->success('Email already verified');
        }
        $user->email_verified_at = now();
        $user->save();
        $verification->delete();

        return $this->success('Email verified successfully');
    }

    /**
     * Verify user email with token
     */
    public function resendEmailVerificationLink(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $userClass = config('shield.models.user', 'App\\Models\\User');
        $user = $userClass::where('email', $request->email)->firstOrFail();

        if ($user->email_verified_at) {
            return $this->failure('Email already verified', 400);
        }

        $authService = AuthServiceFactory::make();
        $authService->sendVerificationEmail($user);

        return $this->success('Verification link sent to your email');
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return $this->success('Password reset link sent to your email');
            }

            return $this->failure('Unable to send reset link', 400);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage() ?? 'Unable to send reset link', 400);
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success('Password reset successfully');
        }

        return $this->failure(__($status), 400);
    }

    /**
     * Change password for an authenticated user
     */
    public function changePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'current_password' => ['Current password is incorrect'],
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return $this->success('Password updated successfully');
    }
}
