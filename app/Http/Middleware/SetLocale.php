<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App; // Import the App facade
use Illuminate\Support\Facades\Log; // Optional: for logging

class SetLocale
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
        $locale = $request->header('Accept-Language');

        if ($locale) {
            $primaryLocale = strtolower(trim(explode(',', $locale)[0]));
            $normalizedLocale = explode('-', $primaryLocale)[0];

            $supportedLocales = ['en', 'ar'];
            if (in_array($normalizedLocale, $supportedLocales, true)) {
                App::setLocale($normalizedLocale);
            }
        }

        return $next($request);
    }
}
