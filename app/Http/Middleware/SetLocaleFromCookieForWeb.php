<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie; // Import Cookie facade

class SetLocaleFromCookieForWeb
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $localeCookie = $request->cookie('user_locale'); // Read the cookie
        $initialLocale = App::getLocale();   
        
        // info('initialLocale: ' . $initialLocale);
        // info('localeCookie: ' . $localeCookie);


        if ($localeCookie) {
            $supportedLocales = ['en', 'ar']; // Your supported locales
            if (in_array($localeCookie, $supportedLocales)) {
                App::setLocale($localeCookie);
            }
        }
        $finalLocale = App::getLocale();

        // dd(
        //     'Middleware Executed: SetLocaleFromCookieForWeb',
        //     'Cookie (user_locale): ' . $localeCookie,
        //     'Initial App Locale: ' . $initialLocale,
        //     'Final App Locale (after potential set): ' . $finalLocale
        // );

        // If no cookie or unsupported locale, Laravel will use the default from config/app.php

        return $next($request);
    }
}