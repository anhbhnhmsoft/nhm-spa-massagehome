<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActive
{
    public function __construct(
        protected AuthService $authService
    )
    {
    }
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $this->authService->heartbeat();
        }
        return $next($request);
    }
}
