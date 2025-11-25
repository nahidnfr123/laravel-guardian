<?php

namespace NahidFerdous\Shield\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShieldLog
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response): void
    {
        if (! config('app.debug', false)) {
            return;
        }

        Log::info(str_repeat('=', 80));
        Log::debug('shield.route', ['route' => optional($request->route())->uri()]);
        Log::debug('shield.headers', $request->headers->all());
        Log::debug('shield.request', $request->all());
        Log::debug('shield.response', ['status' => $response->getStatusCode()]);
        Log::info(str_repeat('=', 80));
    }
}
