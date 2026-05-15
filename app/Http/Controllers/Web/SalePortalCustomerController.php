<?php

namespace App\Http\Controllers\Web;

use App\Http\Resources\Auth\UserResource;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalePortalCustomerController
{
    public function __construct(
        protected UserRepository $userRepository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $keyword = $request->query('keyword');
        $perPage = $request->query('per_page', 15);

        $query = $this->userRepository->query()
            ->where('role', \App\Enums\UserRole::CUSTOMER->value)
            ->with(['profile']);

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'ILIKE', "%$keyword%")
                  ->orWhere('phone', 'ILIKE', "%$keyword%");
            });
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($paginator)->response()->getData(true),
        ]);
    }
}
