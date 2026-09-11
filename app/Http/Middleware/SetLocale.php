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
        $locate = $request->query('locate') ?? $request->header('X-Locale');

        if (Helper::checkLanguage($locate)) {
            $locale = $locate;
        } else {
            $user = auth('sanctum')->user();
            if ($user && !empty($user->language) && Helper::checkLanguage($user->language)) {
                $locale = $user->language;
            }
        }

        app()->setLocale($locale);
        return $next($request);
    }
}
