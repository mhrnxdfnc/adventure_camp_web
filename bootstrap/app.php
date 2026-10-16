<?php

use App\Http\Middleware\EnsurePemilikReadOnly;
use App\Http\Middleware\EnsureRole;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'pemilik.readonly' => EnsurePemilikReadOnly::class,
        ]);
        $middleware->appendToGroup('web', [
            EnsurePemilikReadOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Tangani kegagalan koneksi database secara ramah tanpa membocorkan kredensial atau detail internal
        $exceptions->render(function (QueryException $e, Request $request) {
            $msg = $e->getMessage();
            $code = (int) $e->getCode();
            $isConnError = in_array($code, [2002, 1045], true)
                || str_contains($msg, 'Connection refused')
                || str_contains($msg, 'Access denied')
                || str_contains($msg, 'getaddrinfo failed')
                || str_contains($msg, 'No connection could be made')
                || str_contains($msg, 'Failed to connect to MySQL');

            if ($isConnError) {
                Log::error('Database Connection Error [ERR_DB_CONN]: '.$msg);

                if ($request->is('api/*') || $request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Layanan database sedang tidak dapat dijangkau. Silakan hubungi administrator.',
                        'code' => 'ERR_DB_CONN',
                    ], 503);
                }

                return response()->view('errors.db', [], 503);
            }
        });

        $exceptions->render(function (PDOException $e, Request $request) {
            $msg = $e->getMessage();
            $code = (int) $e->getCode();
            $isConnError = in_array($code, [2002, 1045], true)
                || str_contains($msg, 'Connection refused')
                || str_contains($msg, 'Access denied')
                || str_contains($msg, 'getaddrinfo failed')
                || str_contains($msg, 'No connection could be made')
                || str_contains($msg, 'Failed to connect to MySQL');

            if ($isConnError) {
                Log::error('PDO Connection Error [ERR_DB_CONN]: '.$msg);

                if ($request->is('api/*') || $request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Layanan database sedang tidak dapat dijangkau. Silakan hubungi administrator.',
                        'code' => 'ERR_DB_CONN',
                    ], 503);
                }

                return response()->view('errors.db', [], 503);
            }
        });
    })->create();
