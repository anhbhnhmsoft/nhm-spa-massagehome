<?php

namespace App\Http\Middleware;

use App\Core\Helper;
use App\Enums\Language;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Language::VIETNAMESE->value;
        $user = auth('sanctum')->user();
        if ($user) {
            $locale = $user->language ?? $locale;
        }else {
            $locate = $request->query('locate');
            if (Helper::checkLanguage($locate)) {
                $locale = $locate;
            }
        }
        app()->setLocale($locale);
        return $next($request);
    }
}
