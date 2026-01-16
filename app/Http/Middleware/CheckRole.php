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
        // Chuyển đổi mảng roles (string) từ route sang mảng các giá trị int hợp lệ của Enum
        // Ví dụ: ["1", "4"] -> [1, 4]
        $allowedRoleValues = collect($roles)
            ->map(fn($r) => constant("App\Enums\UserRole::" . strtoupper($r))->value ?? null)
            ->filter()
            ->toArray();
        // Kiểm tra nếu Role của User không nằm trong danh sách được phép
        if (!in_array($user->role, $allowedRoleValues)) {
            return $this->sendError(__('common_error.unauthorized'), 403);
        }

        return $next($request);
    }
}
