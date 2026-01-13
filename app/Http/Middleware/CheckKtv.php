<?php

namespace App\Http\Middleware;

use App\Core\Controller\HandleApi;
use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckKtv
{
    use HandleApi;
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return $this->sendError('Unauthorized', 401);
        }
        if ($user->role !== UserRole::KTV->value) {
            return $this->sendError('Forbidden', 403);
        }
        return $next($request);
    }
}
