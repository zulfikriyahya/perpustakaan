<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi sederhana untuk endpoint device ESP32 (bukan HMAC seperti WA
 * Gateway) - firmware mengirim header X-API-KEY statis yang sama untuk
 * seluruh device (lihat kirimLangsung/nvsSyncToServer/dst. di firmware).
 *
 * Perubahan pada key ini WAJIB dikomunikasikan ke seluruh device di lapangan
 * (harus di-reconfigure via provisioning mode) - lihat Aturan poin 17.
 */
class AuthenticateDeviceApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-KEY');
        $expected = (string) config('services.device_gateway.api_key');

        if (! $expected || ! $key || ! hash_equals($expected, $key)) {
            return response()->json(['error' => 'API key tidak valid'], 401);
        }

        return $next($request);
    }
}
