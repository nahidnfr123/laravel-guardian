<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Routing\Controller;
use NahidFerdous\Shield\Services\SocialAuthService;
use NahidFerdous\Shield\Traits\ApiResponseTrait;

class SocialAuthController extends Controller
{
    use ApiResponseTrait;

    protected $socialAuthService;

    public function __construct(SocialAuthService $socialAuthService)
    {
        $this->socialAuthService = $socialAuthService;
    }

    /**
     * Get a list of enabled social providers
     */
    public function providers()
    {
        $providers = $this->socialAuthService->getEnabledProviders();

        return response([
            'error' => 0,
            'providers' => $providers,
        ], 200);
    }

    /**
     * Redirect to social provider
     */
    public function redirect(string $provider)
    {
        try {
            return $this->socialAuthService->redirect($provider);
        } catch (\Exception $e) {
            return response([
                'error' => 1,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }

    /**
     * Handle callback from social provider
     */
    public function callback(string $provider)
    {
        try {
            $result = $this->socialAuthService->handleCallback($provider);

            return response($result, 200);
        } catch (\Exception $e) {
            return response([
                'error' => 1,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 401);
        }
    }
}
