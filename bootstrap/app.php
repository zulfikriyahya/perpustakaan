<?php

use App\Http\Middleware\AuthenticateDeviceApiKey;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'device.api.key' => AuthenticateDeviceApiKey::class,
        ]);

        // GAP-SPEC: nginx (127.0.0.1) meneruskan header X-Forwarded-Proto
        // dari cloudflared (192.168.1.200) - TLS di-terminate di edge
        // Cloudflare, koneksi cloudflared->nginx->Octane murni HTTP.
        // 127.0.0.1 dipercaya karena Octane/FrankenPHP sendiri berjalan
        // di balik nginx di host yang sama (loopback).
        // TODO: verifikasi apakah 192.168.1.200 (cloudflared) perlu
        // ditambahkan eksplisit jika suatu saat nginx pindah host
        // terpisah dari Octane.
        $middleware->trustProxies(
            at: ['127.0.0.1'],
            headers: SymfonyRequest::HEADER_X_FORWARDED_FOR
                | SymfonyRequest::HEADER_X_FORWARDED_HOST
                | SymfonyRequest::HEADER_X_FORWARDED_PORT
                | SymfonyRequest::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    }
    )->create();
