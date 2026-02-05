<?php

use App\Http\Middleware\KycApprovedMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'kyc.approved' => KycApprovedMiddleware::class,
        ]);

        $middleware->throttleWithRedis();

        $middleware->validateCsrfTokens(except: [
            'webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Unified JSON error responses for API requests
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'unauthenticated',
                    'message' => 'Authentication required.',
                ], 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'validation_failed',
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'error' => 'not_found',
                    'message' => "{$model} not found.",
                ], 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'not_found',
                    'message' => 'The requested endpoint does not exist.',
                ], 404);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();
                $messages = [
                    403 => 'You do not have permission to perform this action.',
                    429 => 'Too many requests. Please try again later.',
                    500 => 'An internal server error occurred.',
                ];
                return response()->json([
                    'error' => match ($status) {
                        403 => 'forbidden',
                        429 => 'rate_limited',
                        default => 'error',
                    },
                    'message' => $messages[$status] ?? $e->getMessage(),
                ], $status);
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') && !app()->hasDebugModeEnabled()) {
                return response()->json([
                    'error' => 'server_error',
                    'message' => 'An unexpected error occurred.',
                ], 500);
            }
        });
    })->create();
