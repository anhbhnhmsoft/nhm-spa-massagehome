<?php

namespace App\Http\Middleware;

use App\Core\Controller\HandleApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalApiSecret
{
    use HandleApi;

    public function handle(Request $request, Closure $next): Response
    {
        $configuredSecret = (string) config('services.internal_api.secret', '');
        $providedSecret = (string) ($request->header('X-Internal-Secret') ?? $request->query('secret', ''));

        if ($configuredSecret === '' || $providedSecret === '' || ! hash_equals($configuredSecret, $providedSecret)) {
            return $this->sendError('Unauthorized', 401);
        }

        return $next($request);
    }
}
