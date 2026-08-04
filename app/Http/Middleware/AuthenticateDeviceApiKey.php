<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi sederhana untuk endpoint device ESP32 - firmware mengirim
 * header X-API-KEY statis yang sama untuk seluruh device.
 *
 * Key SEKARANG dibaca dari Setting('device_gateway_api_key') (grup
 * Kredensial, lihat PengaturanSistem), dengan FALLBACK ke
 * config('services.device_gateway.api_key') / .env jika Setting belum
 * diisi. Perubahan pada key ini WAJIB dikomunikasikan ke seluruh device
 * di lapangan (harus di-reconfigure via provisioning mode) - lihat
 * Aturan poin 17. Mengubah nilai lewat panel Setting TIDAK mendorong
 * key baru ke device manapun secara otomatis.
 */
class AuthenticateDeviceApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-KEY');
        $expected = (string) (Setting::get('device_gateway_api_key') ?: config('services.device_gateway.api_key'));

        if (! $expected || ! $key || ! hash_equals($expected, $key)) {
            return response()->json(['error' => 'API key tidak valid'], 401);
        }

        return $next($request);
    }
}
