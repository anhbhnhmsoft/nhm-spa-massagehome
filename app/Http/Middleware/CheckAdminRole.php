<?php

namespace App\Http\Middleware;

use App\Enums\Admin\AdminRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth('web')->user();
        if (!$user || !$user->is_active) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 401);
        }

        $allowedRoles = collect($roles)
            ->map(fn (string $role) => AdminRole::tryFrom((int) $role) ?? collect(AdminRole::cases())
                ->first(fn (AdminRole $case) => strtolower($case->name) === strtolower($role)))
            ->filter()
            ->all();

        if (!in_array($user->role, $allowedRoles, true)) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 403);
        }

        return $next($request);
    }
}
