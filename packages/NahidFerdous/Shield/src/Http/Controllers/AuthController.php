<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NahidFerdous\Shield\Http\Requests\ShieldLoginRequest;
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

    public function verifyEmail() {}

    public function changePassword() {}

    public function forgotPassword() {}

    public function resetPassword() {}
}
