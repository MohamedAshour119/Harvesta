<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use App\Models\User;

Route::middleware(['auth', 'can:superAdminAccess'])->get("/phpinfo", function () {
    abort_unless(app()->environment('local'), 404);
    return phpinfo();
});

// Admin: open-in-new-tab impersonation entrypoint
Route::middleware(['auth'])
    ->get('/admin/agents/{user}/impersonate', function (User $user) {
        abort_unless(Gate::allows('superAdminAccess'), 403);

        $response = Http::withToken(auth()->user()->createToken('filament-impersonate', [], now()->addSeconds(15))->plainTextToken)
            ->post(route('api.agents.impersonate', ['user' => $user->id]));

        if ($response->successful()) {
            $payload = $response->json('payload');
            $frontendUrl = rtrim(config('app.app_url'), '/') . '/dashboard?payload=' . urlencode($payload) . '&impersonate=true';
            return redirect()->away($frontendUrl);
        }

        abort(500, 'Impersonation failed.');
    })
    ->name('agents.impersonate.web');

Route::get('/payment/success/{order}', function($orderId) {
    return view('payment.success', compact('orderId'));
})->name('payment.success');

Route::get('/payment/failed', function() {
    return view('payment.failed');
})->name('payment.failed');

Route::get('/reset-password', function () {
    return view('welcome');
})->name('password.reset');

Route::middleware(['auth', 'can:superAdminAccess'])
    ->prefix('admin/chatbot')
    ->group(function () {
        Route::post('session', [\App\Http\Controllers\AdminChatbotController::class, 'initSession']);
        Route::post('send', [\App\Http\Controllers\AdminChatbotController::class, 'sendMessage']);
        Route::get('history', [\App\Http\Controllers\AdminChatbotController::class, 'getHistory']);
    });

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
