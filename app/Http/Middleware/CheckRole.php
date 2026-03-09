<?php

namespace App\Http\Middleware;

use App\Core\Controller\HandleApi;
use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    use HandleApi;
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return $this->sendError('Unauthorized', 401);
        }
        // Chuyển đổi ["customer", "ktv"] thành [1, 2] dựa trên Enum
        $allowedRoleValues = collect($roles)
            ->map(function ($roleName) {
                // Tìm Case trong Enum có tên trùng với string truyền vào (không phân biệt hoa thường)
                foreach (UserRole::cases() as $case) {
                    if (strtolower($case->name) === strtolower($roleName)) {
                        return $case->value;
                    }
                }
                return null;
            })
            ->filter()
            ->toArray();

        if (!in_array($user->role, $allowedRoleValues)) {
            return $this->sendError(__('common_error.unauthorized'), 403);
        }

        return $next($request);
    }
}
