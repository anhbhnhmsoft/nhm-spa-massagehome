<?php

namespace App\Http\Middleware;

use App\Support\AppVersion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectAppVersion
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $platform = strtolower((string) $request->header('X-App-Platform'));
        $platform = in_array($platform, ['ios', 'android'], true) ? $platform : null;
        $version = AppVersion::normalize($request->header('X-App-Version'));

        $request->attributes->set('app_platform', $platform);
        $request->attributes->set('app_version', $version);
        $request->attributes->set('is_known_app_version', $platform !== null && $version !== null);

        return $next($request);
    }
}
