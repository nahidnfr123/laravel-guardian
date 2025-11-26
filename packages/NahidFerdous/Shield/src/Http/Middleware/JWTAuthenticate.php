<?php

namespace NahidFerdous\Shield\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NahidFerdous\Shield\Services\Auth\JWTAuthService;

class JWTAuthenticate
{
    protected $jwtService;

    public function __construct(JWTAuthService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $this->getTokenFromRequest($request);

        if (! $token) {
            return response()->json(['error' => 1, 'message' => 'Token not provided'], 401);
        }

        if (! $this->jwtService->validate($token)) {
            return response()->json(['error' => 1, 'message' => 'Invalid or expired token'], 401);
        }

        try {
            $payload = $this->jwtService->decodeToken($token);

            // Get user
            $userClass = config('shield.models.user', config('auth.providers.users.model'));
            $user = $userClass::find($payload->sub);

            if (! $user) {
                return response()->json(['error' => 1, 'message' => 'User not found'], 401);
            }

            // Set user on request
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            // Store JWT ID for potential blacklisting
            $request->attributes->set('jwt_id', $payload->jti);
            $request->attributes->set('jwt_roles', $payload->roles ?? []);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 1, 'message' => 'Authentication failed'], 401);
        }
    }

    protected function getTokenFromRequest(Request $request): ?string
    {
        $token = $request->bearerToken();

        if (! $token) {
            $token = $request->input('token');
        }

        return $token;
    }
}
