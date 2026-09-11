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
use App\Core\Helper\CalculatePrice;
use App\Enums\ConfigName;
use App\Models\Category;
use App\Models\CategoryPrice;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestProposal;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\ConfigService;
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

            $province = $data['province'] ?? null;
            $ward = $data['ward'] ?? null;

            if (empty($province) || empty($ward)) {
                $customer = User::with('profile')->find($customerId);
                $province = $province ?: ($customer?->province ?? $customer?->profile?->province);
                $ward = $ward ?: ($customer?->ward ?? $customer?->profile?->ward);
            }

            if ((empty($province) || empty($ward)) && !empty($data['address'])) {
                $addressParts = array_map('trim', explode(',', $data['address']));
                if (count($addressParts) >= 2) {
                    $province = $province ?: end($addressParts);
                    $ward = $ward ?: $addressParts[count($addressParts) - 2];
                }
            }

            $request = ServiceRequest::create([
                'customer_id' => $customerId,
                'service_id' => (string) $data['service_id'],
                'duration' => isset($data['duration']) ? (int) $data['duration'] : 60,
                'preferred_techniques' => $data['preferred_techniques'] ?? [],
                'province_code' => $data['province_code'] ?? null,
                'district_code' => $data['district_code'] ?? null,
                'ward_code' => $data['ward_code'] ?? null,
                'province' => $province,
                'ward' => $ward,
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

            return ServiceReturn::success($request->load(['service', 'category', 'customer']));
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
            $requests = ServiceRequest::with(['service.category', 'category', 'proposals.ktv', 'cskh'])
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
            $proposals = ServiceRequestProposal::with(['serviceRequest.service.category', 'serviceRequest.category', 'serviceRequest.customer', 'cskh'])
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
    public function ktvRespondProposal(int|string $proposalId, string $ktvId, bool $accept): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($proposalId, $ktvId, $accept) {
                $proposal = ServiceRequestProposal::with('serviceRequest')->lockForUpdate()->find($proposalId);

                // Fallback nếu client JavaScript bị làm tròn số dấu phẩy động 64-bit BigInt (Number.MAX_SAFE_INTEGER)
                if (!$proposal && is_numeric($proposalId) && (float) $proposalId > 1e12) {
                    $num = (float) $proposalId;
                    $min = sprintf('%.0f', $num - 256);
                    $max = sprintf('%.0f', $num + 256);

                    $proposal = ServiceRequestProposal::with('serviceRequest')
                        ->where('ktv_id', (string) $ktvId)
                        ->whereBetween('id', [$min, $max])
                        ->lockForUpdate()
                        ->first();
                }

                if (!$proposal || (string) $proposal->ktv_id !== (string) $ktvId) {
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
    public function customerRespondProposal(int|string $proposalId, string $customerId, bool $accept): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($proposalId, $customerId, $accept) {
                $proposal = ServiceRequestProposal::with('serviceRequest')->lockForUpdate()->find($proposalId);

                // Fallback nếu client JavaScript bị làm tròn số dấu phẩy động 64-bit BigInt (Number.MAX_SAFE_INTEGER)
                if (!$proposal && is_numeric($proposalId) && (float) $proposalId > 1e12) {
                    $num = (float) $proposalId;
                    $min = sprintf('%.0f', $num - 256);
                    $max = sprintf('%.0f', $num + 256);

                    $proposal = ServiceRequestProposal::with('serviceRequest')
                        ->whereHas('serviceRequest', fn($q) => $q->where('customer_id', (string) $customerId))
                        ->whereBetween('id', [$min, $max])
                        ->lockForUpdate()
                        ->first();
                }

                if (!$proposal || (string) $proposal->serviceRequest?->customer_id !== (string) $customerId) {
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
            // 1. Xác định thời gian đặt lịch & khung giờ (start_time, end_time)
            $startTime = '09:00';
            if (!empty($request->time_slot)) {
                $parts = explode('-', $request->time_slot);
                $startTime = trim($parts[0]);
            }

            $bookingTime = $request->preferred_date
                ? Carbon::parse($request->preferred_date->format('Y-m-d') . ' ' . $startTime)
                : now()->addHour();

            // 2. Xác định Loại dịch vụ (Category) chuẩn xác từ service_id
            $category = null;
            $categoryId = $request->service_id;

            // 2.1. Tra cứu trực tiếp trong Category nếu service_id là Category ID
            if (!empty($categoryId)) {
                $category = Category::with('prices')->find($categoryId);
            }

            // 2.2. Nếu không có, kiểm tra nếu service_id là ID của bảng services (dịch vụ KTV đăng ký)
            if (!$category && !empty($categoryId)) {
                $service = Service::with('category.prices')->find($categoryId);
                if ($service && $service->category) {
                    $category = $service->category;
                }
            }

            // 2.3. Fallback qua các quan hệ đã nạp trước trên ServiceRequest
            if (!$category) {
                if ($request->relationLoaded('category') && $request->category) {
                    $category = $request->category;
                } elseif ($request->relationLoaded('service') && $request->service?->category) {
                    $category = $request->service->category;
                }
            }

            // 2.4. Nếu vẫn chưa xác định được và có ktvId, tìm dịch vụ KTV này cung cấp
            if (!$category && !empty($ktvId)) {
                $ktvService = Service::with('category.prices')
                    ->where('user_id', (string) $ktvId)
                    ->first();
                if ($ktvService && $ktvService->category) {
                    $category = $ktvService->category;
                }
            }

            if (!$category) {
                return ServiceReturn::error(__('admin.service_request.messages.category_not_found'));
            }

            $finalCategoryId = (string) $category->id;

            // 3. Tra cứu Thời lượng và Giá dịch vụ trực tiếp từ bảng category_prices cấu hình theo loại dịch vụ này
            // Ưu tiên thời lượng khách hàng đã chọn trong ServiceRequest, fallback về 60 phút hoặc gói đầu tiên
            $requestedDuration = (int) ($request->duration ?: 60);

            $categoryPrice = CategoryPrice::where('category_id', $finalCategoryId)
                ->where('duration', $requestedDuration)
                ->first()
                ?? CategoryPrice::where('category_id', $finalCategoryId)->where('duration', 60)->first()
                ?? CategoryPrice::where('category_id', $finalCategoryId)->oldest('duration')->first()
                ?? $category->cheapestPrice
                ?? $category->prices()->first();

            if (!$categoryPrice || (float) $categoryPrice->price <= 0) {
                $locale = app()->getLocale();
                $catName = is_array($category->name)
                    ? ($category->name[$locale] ?? $category->name['vi'] ?? reset($category->name))
                    : ($category->name ?? $finalCategoryId);

                return ServiceReturn::error(__('admin.service_request.messages.category_price_not_configured', [
                    'name' => $catName
                ]));
            }

            $duration = (int) $categoryPrice->duration;
            $basePrice = (float) $categoryPrice->price;

            $startTimeCarbon = $bookingTime->copy();
            $endTimeCarbon = $bookingTime->copy()->addMinutes($duration);

            // 4. Lấy địa chỉ KTV & Tính toán phí di chuyển
            $ktvAddress = UserAddress::where('user_id', $ktvId)->where('is_primary', true)->first()
                ?? UserAddress::where('user_id', $ktvId)->first();

            $priceTransportation = 0;
            $ktvLat = (float) ($ktvAddress?->latitude ?? 0);
            $ktvLng = (float) ($ktvAddress?->longitude ?? 0);
            $custLat = (float) ($request->latitude ?? 0);
            $custLng = (float) ($request->longitude ?? 0);

            if ($ktvLat && $ktvLng && $custLat && $custLng) {
                try {
                    $pricePerKm = (float) app(ConfigService::class)->getConfigValue(ConfigName::PRICE_TRANSPORTATION);
                    $priceData = CalculatePrice::calculateBookingPrice(
                        price: $basePrice,
                        coupon: null,
                        pricePerKm: $pricePerKm,
                        longitude: $custLng,
                        latitude: $custLat,
                        ktvLongitude: $ktvLng,
                        ktvLatitude: $ktvLat,
                    );
                    $priceTransportation = (float) ($priceData['price_distance'] ?? 0);
                } catch (\Throwable) {
                    $priceTransportation = 0;
                }
            }

            // 5. Tạo ServiceBooking với đầy đủ thông tin chuẩn xác
            $booking = ServiceBooking::create([
                'user_id' => $request->customer_id,
                'ktv_user_id' => $ktvId,
                'category_id' => $finalCategoryId,
                'duration' => $duration,
                'booking_time' => $bookingTime,
                'start_time' => null,
                'end_time' => null,
                'address' => $request->address ?? '',
                'latitude' => $custLat,
                'longitude' => $custLng,
                'ktv_address' => $ktvAddress?->address ?? '',
                'ktv_latitude' => $ktvLat,
                'ktv_longitude' => $ktvLng,
                'status' => BookingStatus::CONFIRMED->value,
                'price' => $basePrice,
                'price_discount' => 0,
                'price_transportation' => $priceTransportation,
                'note' => $request->note,
            ]);

            // 6. Cộng thêm performed_count của service cho KTV nếu có
            Service::where('user_id', $ktvId)
                ->where('category_id', $finalCategoryId)
                ->increment('performed_count');

            $request->status = ServiceRequestStatus::BOOKING_CREATED;
            $request->save();

            return ServiceReturn::success($booking);
        } catch (\Throwable $e) {
            return ServiceReturn::error($e->getMessage());
        }
    }
}
