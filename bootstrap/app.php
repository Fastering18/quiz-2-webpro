<?php

use App\Http\Middleware\EnsureAdmin;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Render/Cloudflare: trust X-Forwarded-* so https, host and client IP (rate limits) are right.
        $middleware->trustProxies(at: '*');
        $middleware->statefulApi();
        $middleware->throttleApi('api');
        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $isJson = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isJson) {
            if ($isJson($request)) {
                return response()->json(['message' => 'Anda belum masuk atau sesi telah berakhir. Silakan login kembali.'], 401);
            }
        });
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) use ($isJson) {
            if ($isJson($request)) {
                return response()->json(['message' => $e->getMessage() ?: 'Akses ditolak.'], 403);
            }
        });
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) use ($isJson) {
            if ($isJson($request)) {
                return response()->json(['message' => 'Data atau halaman yang diminta tidak ditemukan.'], 404);
            }
        });
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($isJson) {
            if ($isJson($request)) {
                return response()->json(['message' => 'Metode HTTP tidak diizinkan untuk alamat ini.'], 405, $e->getHeaders());
            }
        });
        $exceptions->render(function (TokenMismatchException $e, Request $request) use ($isJson) {
            if ($isJson($request)) {
                return response()->json(['message' => 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'], 419);
            }
        });
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($isJson) {
            if ($isJson($request)) {
                return response()->json(['message' => 'Terlalu banyak permintaan. Coba lagi beberapa saat lagi.'], 429, $e->getHeaders());
            }
        });
        $exceptions->render(function (PostTooLargeException $e, Request $request) use ($isJson) {
            if ($isJson($request)) {
                return response()->json(['message' => 'Ukuran berkas terlalu besar.'], 413);
            }
        });
        $exceptions->render(function (ValidationException $e, Request $request) use ($isJson) {
            if ($isJson($request)) {
                return response()->json([
                    'message' => $e->validator->errors()->first() ?: 'Data yang dikirim tidak valid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isJson) {
            if ($isJson($request) && $e->getStatusCode() >= 500) {
                return response()->json(['message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.'], $e->getStatusCode());
            }
        });
        $exceptions->render(function (Throwable $e, Request $request) use ($isJson) {
            if ($isJson($request) && ! config('app.debug') && ! $e instanceof HttpExceptionInterface) {
                return response()->json(['message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.'], 500);
            }
        });
    })->create();
