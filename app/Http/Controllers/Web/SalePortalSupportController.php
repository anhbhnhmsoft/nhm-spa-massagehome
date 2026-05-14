<?php

namespace App\Http\Controllers\Web;

use App\Core\Controller\ListRequest;
use App\Http\Resources\Support\SupportMessageResource;
use App\Http\Resources\Support\SupportTicketResource;
use App\Services\AuthService;
use App\Services\SupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalePortalSupportController
{
    public function __construct(
        protected SupportService $supportService,
        protected AuthService $authService,
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $result = $this->authService->loginAdmin(
            username: $data['username'],
            password: $data['password'],
            remember: (bool) ($data['remember'] ?? false),
        );

        if ($result->isError()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 422);
        }

        $request->session()->regenerate();
        $admin = $result->getData()['user'];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => (string) $admin->id,
                    'username' => $admin->username,
                    'name' => $admin->name,
                    'role' => $admin->role->value ?? $admin->role,
                ],
            ],
            'message' => __('auth.success.login'),
        ]);
    }

    public function me(): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => (string) $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'role' => $user->role->value ?? $user->role,
                    'last_seen_at' => $user->last_seen_at?->toISOString(),
                ],
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return response()->json(['success' => true, 'message' => __('auth.success.logout')]);
    }

    public function socketToken(): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $this->supportService->issueAdminSocketToken($user),
            ],
        ]);
    }

    public function heartbeat(): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 401);
        }

        $result = $this->supportService->heartbeatAdmin((int) $user->id);
        if ($result->isError()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function inbox(ListRequest $request): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 401);
        }

        $scope = (string) $request->query('scope', 'all');
        $dto = $request->getFilterOptions();
        $result = $this->supportService->listStaffTickets(
            staffId: (int) $user->id,
            scope: $scope,
            page: $dto->page,
            perPage: $dto->perPage,
        );

        if ($result->isError()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => SupportTicketResource::collection($result->getData())->response()->getData(true),
        ]);
    }

    public function detail(int $id): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 401);
        }

        $result = $this->supportService->detailTicket($id);
        if ($result->isError()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }

        $ticket = $result->getData();
        if ($ticket->assigned_staff_id && (string) $ticket->assigned_staff_id !== (string) $user->id) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new SupportTicketResource($ticket),
        ]);
    }

    public function messages(int $id, ListRequest $request): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 401);
        }

        $ticket = $this->supportService->detailTicket($id);
        if ($ticket->isError()) {
            return response()->json(['success' => false, 'message' => $ticket->getMessage()], 422);
        }
        if ($ticket->getData()->assigned_staff_id && (string) $ticket->getData()->assigned_staff_id !== (string) $user->id) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 403);
        }

        $dto = $request->getFilterOptions();
        $result = $this->supportService->listMessages(
            ticketId: $id,
            page: $dto->page,
            perPage: $dto->perPage,
        );
        if ($result->isError()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => SupportMessageResource::collection($result->getData())->response()->getData(true),
        ]);
    }

    public function claim(int $id): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 401);
        }

        $result = $this->supportService->claimTicket((int) $id, (int) $user->id);
        if ($result->isError()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => new SupportTicketResource($result->getData()),
        ]);
    }

    public function close(int $id): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 401);
        }

        $result = $this->supportService->closeTicket((int) $id, (int) $user->id);
        if ($result->isError()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => new SupportTicketResource($result->getData()),
        ]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 401);
        }

        $data = $request->validate([
            'support_ticket_id' => ['required', 'numeric', 'exists:support_tickets,id'],
            'content' => ['required', 'string', 'max:2000'],
            'temp_id' => ['nullable', 'string'],
        ]);

        $ticket = $this->supportService->detailTicket((int) $data['support_ticket_id']);
        if ($ticket->isError()) {
            return response()->json(['success' => false, 'message' => $ticket->getMessage()], 422);
        }
        if ((string) $ticket->getData()->assigned_staff_id !== (string) $user->id) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 403);
        }

        $result = $this->supportService->sendMessage(
            ticketId: (int) $data['support_ticket_id'],
            content: $data['content'],
            tempId: $data['temp_id'] ?? null,
        );
        if ($result->isError()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => new SupportMessageResource($result->getData()),
        ]);
    }

    public function seen(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 401);
        }

        $data = $request->validate([
            'support_ticket_id' => ['required', 'numeric', 'exists:support_tickets,id'],
        ]);

        $ticket = $this->supportService->detailTicket((int) $data['support_ticket_id']);
        if ($ticket->isError()) {
            return response()->json(['success' => false, 'message' => $ticket->getMessage()], 422);
        }
        if ((string) $ticket->getData()->assigned_staff_id !== (string) $user->id) {
            return response()->json(['success' => false, 'message' => __('common_error.unauthorized')], 403);
        }

        $result = $this->supportService->seenMessages((int) $data['support_ticket_id']);
        if ($result->isError()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }
}
