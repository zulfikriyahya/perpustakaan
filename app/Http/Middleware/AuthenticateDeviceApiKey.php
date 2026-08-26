<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
