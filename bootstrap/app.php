<?php

use App\Http\Middleware\PreventAgents;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetLocaleFromCookieForWeb;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies([
            'user_locale',
        ]);
        $middleware->api([
            SetLocale::class,
        ]);
        $middleware->web([
            SetLocaleFromCookieForWeb::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('agents:check-monthly-targets')->lastDayOfMonth('23:59');
        // $schedule->command('agents:check-monthly-targets')->everyMinute();
        $schedule->command('chatbot:cleanup-expired')->daily();
    })
    ->create();
