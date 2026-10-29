<?php

namespace App\Http\Middleware;

use App\Models\RegisteredDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DeviceFingerprint
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = 'flow_device_token';
        $token = $request->cookie($cookieName);
        $isNewToken = false;

        if (! $token || ! is_string($token) || strlen($token) > 64) {
            $token = (string) Str::uuid();
            $isNewToken = true;
        }

        // Upsert device record
        $device = RegisteredDevice::firstOrCreate(
            ['device_token' => $token],
            [
                'last_ip_address' => $request->ip(),
                'last_user_agent' => substr((string) $request->userAgent(), 0, 500),
                'last_seen_at' => now(),
            ]
        );

        if (! $device->wasRecentlyCreated) {
            $device->update([
                'last_ip_address' => $request->ip(),
                'last_user_agent' => substr((string) $request->userAgent(), 0, 500),
                'last_seen_at' => now(),
            ]);
        }

        // Attach device to request
        $request->attributes->set('registered_device', $device);

        $response = $next($request);

        if ($isNewToken) {
            // Permanent cookie: 5 years (2628000 minutes)
            $response->headers->setCookie(
                cookie()->forever($cookieName, $token, null, null, false, false)
            );
        }

        return $response;
    }
}
