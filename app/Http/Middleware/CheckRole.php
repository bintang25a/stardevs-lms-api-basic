<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
  public function handle(Request $request, Closure $next, ...$roles): Response
  {
    $user = $request->user();

    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.',
      ], 401);
    }

    $token = $user->currentAccessToken();

    $hasAccess = false;
    foreach ($roles as $role) {
      if ($token && $token->can('role:' . $role)) {
        $hasAccess = true;
        break;
      }
    }

    if ($user->role === 'admin' && in_array('admin', $roles)) {
      $hasAccess = true;
    }

    if (!$hasAccess) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthorized. Access denied for your active role.',
      ], 403);
    }

    return $next($request);
  }
}
