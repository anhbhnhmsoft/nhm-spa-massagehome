<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

class SetWebLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('locale')) {
            $locale = session('locale');
            App::setLocale($locale);

            $numberLocale = match ($locale) {
                'cn' => 'zh_CN',
                'vi' => 'vi_VN',
                'en' => 'en_US',
                default => $locale,
            };
            Number::useLocale($numberLocale);
        }

        return $next($request);
    }
}
