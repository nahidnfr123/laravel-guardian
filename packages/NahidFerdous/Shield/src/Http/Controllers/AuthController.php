<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use NahidFerdous\Shield\Http\Requests\ShieldLoginRequest;
use NahidFerdous\Shield\Mail\VerifyEmailMail;
use NahidFerdous\Shield\Models\EmailVerificationToken;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;

class AuthController extends Controller
{
    /**
     * Handle user login
     */
    public function login(ShieldLoginRequest $request)
    {
        try {
            $authService = AuthServiceFactory::make();
            $result = $authService->login($request->validated());

            return response($result, 200);
        } catch (\Exception $e) {
            $exceptionCode = $e->getCode();
            $statusCode = ($exceptionCode >= 100 && $exceptionCode < 600) ? $exceptionCode : 401;

            return response([
                'error' => 1,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $authService = AuthServiceFactory::make();
            $authService->logout($user);

            return response(['error' => 0, 'message' => 'Logged out successfully'], 200);
        } catch (\Exception $e) {
            return response(['error' => 1, 'message' => 'Logout failed'], 500);
        }
    }

    /**
     * Refresh authentication token
     */
    public function refresh(Request $request)
    {
        try {
            $user = $request->user();
            $authService = AuthServiceFactory::make();
            $result = $authService->refresh($user);

            return response($result, 200);
        } catch (\Exception $e) {
            return response(['error' => 1, 'message' => 'Token refresh failed'], 401);
        }
    }

    /**
     * Get authenticated user information
     */
    public function me(Request $request)
    {
        return response($request->user(), 200);
    }

    /**
     * Verify user email with token
     */
    public function verifyEmail(string $token)
    {
        $verification = EmailVerificationToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            return response([
                'error' => 1,
                'message' => 'Invalid or expired verification token'
            ], 400);
        }

        $userClass = config('shield.models.user');
        $user = $userClass::find($verification->user_id);

        if (!$user) {
            return response([
                'error' => 1,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->email_verified_at) {
            $verification->delete();
            return response([
                'error' => 0,
                'message' => 'Email already verified'
            ], 200);
        }

        $user->email_verified_at = now();
        $user->save();
        $verification->delete();

        return response([
            'error' => 0,
            'message' => 'Email verified successfully'
        ], 200);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response([
                'error' => 0,
                'message' => 'Password reset link sent to your email'
            ], 200);
        }

        return response([
            'error' => 1,
            'message' => 'Unable to send reset link'
        ], 400);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response([
                'error' => 0,
                'message' => 'Password reset successfully'
            ], 200);
        }

        return response([
            'error' => 1,
            'message' => __($status)
        ], 400);
    }

    /**
     * Change password for authenticated user
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response([
                'error' => 1,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response([
            'error' => 0,
            'message' => 'Password changed successfully'
        ], 200);
    }
}
