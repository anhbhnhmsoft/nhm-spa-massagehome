<?php

namespace App\Services;

use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\ProposalStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UrgencyLevel;
use App\Events\ProposalRespondedEvent;
use App\Events\ServiceRequestCreatedEvent;
use App\Events\ServiceRequestProposedEvent;
use App\Models\ServiceBooking;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestProposal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ServiceRequestService extends BaseService
{
    /**
     * Khách hàng tạo Yêu cầu dịch vụ mới
     */
    public function createRequest(array $data, string $customerId): ServiceReturn
    {
        try {
            $urgencyValue = isset($data['urgency_level']) ? (int)$data['urgency_level'] : UrgencyLevel::NEED_NOW->value;
            $urgency = UrgencyLevel::tryFrom($urgencyValue) ?? UrgencyLevel::NEED_NOW;

            $expiresAt = match ($urgency) {
                UrgencyLevel::NEED_NOW => now()->addHours(2),
                UrgencyLevel::TODAY => now()->endOfDay(),
                UrgencyLevel::SCHEDULED => isset($data['preferred_date']) ? Carbon::parse($data['preferred_date'])->endOfDay() : now()->addDays(2),
            };

            $request = ServiceRequest::create([
                'customer_id' => $customerId,
                'service_id' => $data['service_id'],
                'preferred_techniques' => $data['preferred_techniques'] ?? [],
                'province_code' => $data['province_code'] ?? null,
                'district_code' => $data['district_code'] ?? null,
                'ward_code' => $data['ward_code'] ?? null,
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'preferred_date' => $data['preferred_date'] ?? null,
                'time_slot' => $data['time_slot'] ?? null,
                'urgency_level' => $urgency,
                'preferred_ktv_ids' => $data['preferred_ktv_ids'] ?? [],
                'note' => $data['note'] ?? null,
                'status' => ServiceRequestStatus::NEW,
                'expires_at' => $expiresAt,
            ]);

            ServiceRequestCreatedEvent::dispatch($request);

            return ServiceReturn::success($request->load(['service', 'customer']));
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Lấy danh sách Yêu cầu dịch vụ của khách hàng
     */
    public function getCustomerRequests(string $customerId): ServiceReturn
    {
        try {
            $requests = ServiceRequest::with(['service', 'proposals.ktv', 'cskh'])
                ->where('customer_id', $customerId)
                ->orderBy('created_at', 'desc')
                ->get();

            return ServiceReturn::success($requests);
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Lấy danh sách Lượt đề xuất dành cho KTV
     */
    public function getKtvProposals(string $ktvId): ServiceReturn
    {
        try {
            $proposals = ServiceRequestProposal::with(['serviceRequest.service', 'serviceRequest.customer', 'cskh'])
                ->where('ktv_id', $ktvId)
                ->orderBy('created_at', 'desc')
                ->get();

            return ServiceReturn::success($proposals);
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * CSKH gửi đề xuất KTV cho Yêu cầu dịch vụ
     */
    public function proposeKtvForRequest(int $requestId, string $ktvId, string $cskhId): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($requestId, $ktvId, $cskhId) {
                $serviceRequest = ServiceRequest::lockForUpdate()->find($requestId);
                if (!$serviceRequest) {
                    return ServiceReturn::error(__('admin.service_request.messages.not_found'));
                }

                if (in_array($serviceRequest->status, [ServiceRequestStatus::MATCHED, ServiceRequestStatus::BOOKING_CREATED, ServiceRequestStatus::CLOSED])) {
                    return ServiceReturn::error(__('admin.service_request.messages.already_matched'));
                }

                // Cập nhật thông tin CSKH & trạng thái
                $serviceRequest->cskh_id = $cskhId;
                $serviceRequest->status = ServiceRequestStatus::PROPOSAL_SENT;
                $serviceRequest->save();

                // Tạo lượt đề xuất KTV
                $proposal = ServiceRequestProposal::create([
                    'request_id' => $requestId,
                    'ktv_id' => $ktvId,
                    'cskh_id' => $cskhId,
                    'status' => ProposalStatus::PROPOSED,
                    'expires_at' => now()->addMinutes(30),
                ]);

                ServiceRequestProposedEvent::dispatch($proposal);

                return ServiceReturn::success($proposal->load(['serviceRequest', 'ktv']));
            });
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * KTV Phản hồi Lời mời đề xuất (Chấp nhận / Từ chối)
     */
    public function ktvRespondProposal(int $proposalId, string $ktvId, bool $accept): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($proposalId, $ktvId, $accept) {
                $proposal = ServiceRequestProposal::with('serviceRequest')->lockForUpdate()->find($proposalId);
                if (!$proposal || $proposal->ktv_id !== $ktvId) {
                    return ServiceReturn::error(__('admin.service_request.messages.proposal_not_found'));
                }

                if ($accept) {
                    $proposal->status = ProposalStatus::KTV_ACCEPTED;
                    $proposal->serviceRequest->status = ServiceRequestStatus::WAITING_CUSTOMER_CONFIRM;
                } else {
                    $proposal->status = ProposalStatus::KTV_DECLINED;
                    $proposal->serviceRequest->status = ServiceRequestStatus::SEARCHING_KTV;
                }

                $proposal->save();
                $proposal->serviceRequest->save();

                ProposalRespondedEvent::dispatch($proposal, 'ktv', $accept);

                return ServiceReturn::success($proposal);
            });
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Khách hàng Phản hồi Đề xuất KTV (Chấp nhận / Từ chối)
     */
    public function customerRespondProposal(int $proposalId, string $customerId, bool $accept): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($proposalId, $customerId, $accept) {
                $proposal = ServiceRequestProposal::with('serviceRequest')->lockForUpdate()->find($proposalId);
                if (!$proposal || $proposal->serviceRequest->customer_id !== $customerId) {
                    return ServiceReturn::error(__('admin.service_request.messages.proposal_not_found'));
                }

                $request = $proposal->serviceRequest;

                if ($accept) {
                    $proposal->status = ProposalStatus::CUSTOMER_ACCEPTED;
                    $request->status = ServiceRequestStatus::MATCHED;

                    // Đóng tất cả đề xuất khác nếu có
                    ServiceRequestProposal::where('request_id', $request->id)
                        ->where('id', '!=', $proposal->id)
                        ->update(['status' => ProposalStatus::EXPIRED->value]);

                    $proposal->save();
                    $request->save();

                    // Tự động tạo ServiceBooking 1-Click
                    $bookingResult = $this->createBookingFromRequest($request, $proposal->ktv_id);

                    ProposalRespondedEvent::dispatch($proposal, 'customer', true);

                    return ServiceReturn::success([
                        'proposal' => $proposal,
                        'booking' => $bookingResult->getData(),
                    ]);
                } else {
                    $proposal->status = ProposalStatus::CUSTOMER_DECLINED;
                    $request->status = ServiceRequestStatus::SEARCHING_KTV;

                    $proposal->save();
                    $request->save();

                    ProposalRespondedEvent::dispatch($proposal, 'customer', false);

                    return ServiceReturn::success(['proposal' => $proposal]);
                }
            });
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Tự động tạo ServiceBooking từ Yêu cầu dịch vụ đã Matching
     */
    public function createBookingFromRequest(ServiceRequest $request, string $ktvId): ServiceReturn
    {
        try {
            $startTime = '09:00';
            if (!empty($request->time_slot)) {
                $parts = explode('-', $request->time_slot);
                $startTime = trim($parts[0]);
            }

            $bookingTime = $request->preferred_date
                ? Carbon::parse($request->preferred_date->format('Y-m-d') . ' ' . $startTime)
                : now()->addHour();

            $categoryId = $request->service?->category_id ?? $request->service_id;
            if (!\App\Models\Category::where('id', $categoryId)->exists()) {
                $categoryId = \App\Models\Category::first()?->id;
            }

            $booking = ServiceBooking::create([
                'user_id' => $request->customer_id,
                'ktv_user_id' => $ktvId,
                'category_id' => $categoryId,
                'duration' => $request->service?->duration ?? 60,
                'booking_time' => $bookingTime,
                'address' => $request->address ?? '',
                'latitude' => $request->latitude ?? 0,
                'longitude' => $request->longitude ?? 0,
                'status' => BookingStatus::CONFIRMED->value,
                'price' => $request->service?->price ?? 0,
                'price_discount' => 0,
                'price_transportation' => 0,
                'note' => $request->note,
            ]);

            $request->status = ServiceRequestStatus::BOOKING_CREATED;
            $request->save();

            return ServiceReturn::success($booking);
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }
}
