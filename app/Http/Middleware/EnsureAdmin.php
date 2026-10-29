<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีสิทธิ์เข้าถึง: สำหรับผู้ดูแลระบบ (Admin) เท่านั้น',
                ], 403);
            }

            abort(403, 'ไม่มีสิทธิ์เข้าถึง: สำหรับผู้ดูแลระบบ (Admin) เท่านั้น');
        }

        return $next($request);
    }
}
