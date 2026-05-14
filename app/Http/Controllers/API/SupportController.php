<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Http\Resources\Support\SupportCategoryResource;
use App\Http\Resources\Support\SupportMessageResource;
use App\Http\Resources\Support\SupportTicketResource;
use App\Services\SupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends BaseController
{
    public function __construct(
        protected SupportService $supportService,
    ) {
    }

    public function categories(): JsonResponse
    {
        $result = $this->supportService->listCategories();
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: SupportCategoryResource::collection($result->getData())->resolve(),
        );
    }

    public function createTicket(Request $request): JsonResponse
    {
        $data = $request->validate([
            'support_category_id' => ['required', 'numeric', 'exists:support_categories,id'],
            'content' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->supportService->createTicketForCustomer(
            customerId: $request->user()->id,
            categoryId: (int) $data['support_category_id'],
            content: $data['content'] ?? null,
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: new SupportTicketResource($result->getData()['ticket']),
            message: __('common.success.data_created'),
        );
    }

    public function tickets(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $result = $this->supportService->listCustomerTickets(
            customerId: $request->user()->id,
            page: $dto->page,
            perPage: $dto->perPage,
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: SupportTicketResource::collection($result->getData())->response()->getData(true),
        );
    }

    public function detail(int $id): JsonResponse
    {
        $result = $this->supportService->detailTicket($id);
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        $ticket = $result->getData();
        if ((string) $ticket->customer_id !== (string) request()->user()->id) {
            return $this->sendError(__('common_error.unauthorized'), 403);
        }

        return $this->sendSuccess(
            data: new SupportTicketResource($ticket),
        );
    }

    public function messages(int $id, ListRequest $request): JsonResponse
    {
        $ticket = $this->supportService->detailTicket($id);
        if ($ticket->isError()) {
            return $this->sendError($ticket->getMessage());
        }
        if ((string) $ticket->getData()->customer_id !== (string) $request->user()->id) {
            return $this->sendError(__('common_error.unauthorized'), 403);
        }

        $dto = $request->getFilterOptions();
        $result = $this->supportService->listMessages(
            ticketId: $id,
            page: $dto->page,
            perPage: $dto->perPage,
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: SupportMessageResource::collection($result->getData())->response()->getData(true),
        );
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'support_ticket_id' => ['required', 'numeric', 'exists:support_tickets,id'],
            'content' => ['required', 'string', 'max:2000'],
            'temp_id' => ['nullable', 'string'],
        ]);

        $ticket = $this->supportService->detailTicket((int) $data['support_ticket_id']);
        if ($ticket->isError()) {
            return $this->sendError($ticket->getMessage());
        }
        if ((string) $ticket->getData()->customer_id !== (string) $request->user()->id) {
            return $this->sendError(__('common_error.unauthorized'), 403);
        }

        $result = $this->supportService->sendMessage(
            ticketId: (int) $data['support_ticket_id'],
            content: $data['content'],
            tempId: $data['temp_id'] ?? null,
        );

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(
            data: new SupportMessageResource($result->getData()),
            message: __('common.success.data_created'),
        );
    }

    public function seen(Request $request): JsonResponse
    {
        $data = $request->validate([
            'support_ticket_id' => ['required', 'numeric', 'exists:support_tickets,id'],
        ]);

        $ticket = $this->supportService->detailTicket((int) $data['support_ticket_id']);
        if ($ticket->isError()) {
            return $this->sendError($ticket->getMessage());
        }
        if ((string) $ticket->getData()->customer_id !== (string) $request->user()->id) {
            return $this->sendError(__('common_error.unauthorized'), 403);
        }

        $result = $this->supportService->seenMessages((int) $data['support_ticket_id']);
        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }

        return $this->sendSuccess(message: __('common.success.data_updated'));
    }
}
