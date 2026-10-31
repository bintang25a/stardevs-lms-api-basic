<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware): void {
    $middleware->redirectGuestsTo(fn(Request $request) => route('api.unauthenticated'));

    $middleware->alias([
      'role' => \App\Http\Middleware\CheckRole::class,
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions): void {
    // Paksa render JSON untuk semua error pada request API atau yang mengharapkan JSON
    $exceptions->shouldRenderJsonWhen(
      fn(Request $request) => true
    );

    // Cegah total error Route [login] not defined dengan langsung me-return JSON
    $exceptions->render(function (AuthenticationException $e, Request $request) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated or Invalid Token.',
      ], 401);
    });
  })->create();
