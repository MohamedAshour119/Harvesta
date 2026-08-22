<?php

namespace App\Providers;

use App\Models\InventoryProduct;
use Illuminate\Support\ServiceProvider;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Observers\InventoryProductObserver;
use App\Observers\ProductObserver;
use App\Observers\ProductVariantObserver;
use App\Observers\MediaObserver;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only log DB queries in local environment
        if (app()->environment('local')) {
            DB::listen(function ($query) {
                Log::info('DB Query', [
                    'sql' => $query->sql,
                    'time_ms' => $query->time,
                ]);
            });
        }

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
            ->locales(['ar','en']);
        });

        Gate::define('superAdminAccess', function (User $user) {
            return $user->hasRole(['Super Admin']);
        });

        InventoryProduct::observe(InventoryProductObserver::class);
        Product::observe(ProductObserver::class);
        ProductVariant::observe(ProductVariantObserver::class);
        Media::observe(MediaObserver::class);

        Route::pattern('productId', '[0-9]+');

        // Chatbot rate limiters
        RateLimiter::for('chatbot', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->ip()),       // 30 messages/min
                Limit::perDay(400)->by($request->ip()),         // 400 messages/day max
            ];
        });

        RateLimiter::for('chatbot-session', function (Request $request) {
            return [
                Limit::perMinute(6)->by($request->ip()),         // 6 sessions/min
                Limit::perDay(40)->by($request->ip()),           // 40 sessions/day max
            ];
        });
    }
}
