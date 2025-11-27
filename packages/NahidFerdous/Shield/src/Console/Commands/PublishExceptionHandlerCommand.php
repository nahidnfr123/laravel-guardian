<?php

namespace NahidFerdous\Shield\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishExceptionHandlerCommand extends Command
{
    protected $signature = 'shield:publish-exceptions {--force : Overwrite existing files}';

    protected $description = 'Publish Shield exception handlers and update bootstrap/app.php';

    public function handle(): int
    {
        $this->info('Publishing Shield exception handlers...');

        // Create the Exceptions directory if it doesn't exist
        $exceptionsDir = app_path('Exceptions');
        if (! File::isDirectory($exceptionsDir)) {
            File::makeDirectory($exceptionsDir, 0755, true);
            $this->info('Created Exceptions directory.');
        }

        // Publish the ShieldExceptionHandler file
        $handlerPath = app_path('Exceptions/ShieldExceptionHandler.php');

        if (File::exists($handlerPath) && ! $this->option('force')) {
            if (! $this->confirm('ShieldExceptionHandler.php already exists. Overwrite?')) {
                $this->warn('Skipped publishing exception handler.');

                return self::SUCCESS;
            }
        }

        File::put($handlerPath, $this->getExceptionHandlerStub());
        $this->info('Published: app/Exceptions/ShieldExceptionHandler.php');

        // Update bootstrap/app.php
        $this->updateBootstrapApp();

        $this->newLine();
        $this->info('✅ Exception handlers published successfully!');
        $this->newLine();
        $this->line('Your API will now return custom error responses for:');
        $this->line('  • 401 - Unauthenticated');
        $this->line('  • 403 - Unauthorized/Missing Permissions');
        $this->line('  • 404 - Resource Not Found');
        $this->line('  • 422 - Validation Errors');

        return self::SUCCESS;
    }

    protected function updateBootstrapApp(): void
    {
        $appPath = base_path('bootstrap/app.php');

        if (! File::exists($appPath)) {
            $this->error('bootstrap/app.php not found!');

            return;
        }

        $content = File::get($appPath);

        // Check if already updated
        if (str_contains($content, 'ShieldExceptionHandler::handle')) {
            $this->warn('bootstrap/app.php already configured for Shield exceptions.');

            return;
        }

        // Add use statement if not present
        if (! str_contains($content, 'use App\Exceptions\ShieldExceptionHandler;')) {
            // Find the last use statement and add after it
            $usePattern = '/(use Illuminate[^;]+;)/';
            preg_match_all($usePattern, $content, $matches);

            if (! empty($matches[0])) {
                $lastUseStatement = end($matches[0]);
                $content = str_replace(
                    $lastUseStatement,
                    $lastUseStatement."\n".'use App\Exceptions\ShieldExceptionHandler;',
                    $content
                );
            }
        }

        // Pattern to match the ->withExceptions() section and replace its content
        $pattern = '/(->withExceptions\(function\s*\(Exceptions\s+\$exceptions\)\s*:\s*void\s*\{\s*)((?:\/\/[^\n]*\n\s*)*)\s*(\}\))/s';

        $replacement = '$1'."\n        ".'ShieldExceptionHandler::handle($exceptions);'."\n    ".'$3';

        $content = preg_replace($pattern, $replacement, $content);

        if ($content === null) {
            $this->error('Failed to update bootstrap/app.php. Please add manually.');

            return;
        }

        File::put($appPath, $content);
        $this->info('Updated: bootstrap/app.php');
    }

    protected function getExceptionHandlerStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ShieldExceptionHandler
{
    /**
     * Register Shield exception handlers
     */
    public static function handle(Exceptions $exceptions): void
    {
        // 404 - Not Found (Route)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return self::failureResponse('Resource not found', 404);
            }

            return response()->view('errors.error', ['error' => $e], 404);
        });

        // 404 - Model Not Found
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return self::failureResponse('Resource not found', 404);
            }

            return response()->view('errors.error', ['error' => $e], 404);
        });

        // 401 - Unauthenticated
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return self::failureResponse('Unauthenticated', 401);
            }

            return response()->view('errors.error', ['error' => $e], 401);
        });

        // 403 - Access Denied (Abilities/Scopes)
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return self::failureResponse(
                    'Unauthorized action. You do not have the required permissions.',
                    403
                );
            }

            return response()->view('errors.error', ['error' => $e], 403);
        });

        // 403 - Unauthorized
        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->is('api/*')) {
                return self::failureResponse('Unauthorized action.', 403);
            }

            return response()->view('errors.error', ['error' => $e], 403);
        });

        // 422 - Validation Errors
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

        // Log all exceptions with full context
        $exceptions->reportable(function (Throwable $e) {
            $request = request();
            Log::error("Uncaught exception: {$e->getMessage()}", [
                'exception' => $e,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
            ]);
        });
    }

    /**
     * Helper method for consistent failure responses
     */
    protected static function failureResponse(string $message, int $status = 400): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'status' => $status,
        ], $status);
    }
}

PHP;
    }
}
