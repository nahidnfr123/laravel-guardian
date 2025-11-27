<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; // ADD THIS

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return failureResponse('Resource not found', 404);
            }

            return response()->view('errors.error', ['error' => $e], 404);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return failureResponse('Resource not found', 404);
            }

            return response()->view('errors.error', ['error' => $e], 404);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return failureResponse('Unauthenticated', 401);
            }

            return response()->view('errors.error', ['error' => $e], 401);
        });

        // Now this should work!
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return failureResponse('Unauthorized action. You need permission to view.', 403);
            }

            return response()->view('errors.error', ['error' => $e], 403);
        });

        // Optionally handle UnauthorizedException separately if needed
        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->is('api/*')) {
                return failureResponse('Unauthorized action.', 403);
            }

            return response()->view('errors.error', ['error' => $e], 403);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'message' => $e->getMessage(),
                    'status' => 422,
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors());
        });

        // Report all exceptions with a full stack trace to our logs.
        $exceptions->reportable(function (Throwable $e) {
            $request = request();
            Log::error("Uncaught exception: {$e->getMessage()}", [
                'exception' => $e,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
            ]);
        });
    })->create();

function failureResponse($message, $status = 400): \Illuminate\Http\JsonResponse
{
    return response()->json(['success' => false, 'message' => $message, 'status' => $status], $status);
}
