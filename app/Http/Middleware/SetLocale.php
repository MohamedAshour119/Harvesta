<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language') 
            ?: $request->query('lang') 
            ?: $request->cookie('user_locale');

        if ($locale) {
            $primaryLocale = strtolower(trim(explode(',', $locale)[0]));
            $normalizedLocale = explode('-', $primaryLocale)[0];

            $supportedLocales = ['en', 'ar'];
            if (in_array($normalizedLocale, $supportedLocales, true)) {
                App::setLocale($normalizedLocale);
            }
        }

        $response = $next($request);

        // Ensure all API JSON responses output clean UTF-8 characters without \uXXXX escaping
        if ($response instanceof JsonResponse) {
            $response->setEncodingOptions(
                $response->getEncodingOptions() | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        return $response;
    }
}
