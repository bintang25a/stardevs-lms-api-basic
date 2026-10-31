<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  mixed  ...$roles  (Menerima parameter role yang diizinkan)
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Cek apakah user sudah login dan apakah rolenya sesuai dengan yang diizinkan
        if (!$request->user() || !in_array($request->user()->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Access denied for your role.',
            ], 403);
        }

        return $next($request);
    }
}
