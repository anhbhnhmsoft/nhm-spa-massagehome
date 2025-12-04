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
        $user = $request->user();
        if ($user) {
            $locale = $user->language ?? $locale;
        }else {
            $filter = $request->input('locate');
            if (Helper::checkLanguage($filter)) {
                $locale = $filter;
            }
        }
        app()->setLocale($locale);
        return $next($request);
    }
}
