<?php

namespace App\Http\Resources\Booking;

use App\Core\Helper;
use App\Core\Helper\CalculatePrice;
use App\Enums\ConfigName;
use App\Enums\Gender;
use App\Enums\UserRole;
use App\Services\ConfigService;
use App\Support\MobileVersionFlow;
use App\Support\BookingContactPrivacy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = $this->service;
        $ktvUser = $this->ktvUser;
        $ktvUserProfile = $ktvUser?->profile;
        $ktvUserReviewApplication = $ktvUser?->reviewApplication;
        $user = $this->user;
        $userProfile = $this->user?->profile;
        $coupon = $this->coupon ?? null;
        $viewer = $request->user();
        $discountRate = (float) app(ConfigService::class)->getConfigValue(ConfigName::DISCOUNT_RATE);
        $platform = $request->attributes->get('app_platform');
        $version = $request->attributes->get('app_version');
        $useModernContract = MobileVersionFlow::shouldUseModernMobileContract(
            platform: $platform,
            version: $version,
        );
        $isKtvSelected = in_array((int) $this->status, [
            \App\Enums\BookingStatus::WAITING_KTV_CONFIRM->value,
            \App\Enums\BookingStatus::CONFIRMED->value,
            \App\Enums\BookingStatus::ONGOING->value,
            \App\Enums\BookingStatus::COMPLETED->value,
        ], true);
        $selectedKtvUser = $isKtvSelected ? $ktvUser : null;
        $selectedKtvProfile = $selectedKtvUser?->profile;
        $selectedKtvReviewApplication = $selectedKtvUser?->reviewApplication;
        $bookingPhase = match ((int) $this->status) {
            \App\Enums\BookingStatus::WAITING_KTV_CONFIRM->value => 'waiting_ktv_confirm',
            \App\Enums\BookingStatus::OPEN_FOR_APPLICATION->value => 'open_for_application',
            \App\Enums\BookingStatus::CONFIRMED->value => 'confirmed',
            \App\Enums\BookingStatus::ONGOING->value => 'ongoing',
            \App\Enums\BookingStatus::COMPLETED->value => 'completed',
            \App\Enums\BookingStatus::WAITING_CANCEL->value,
            \App\Enums\BookingStatus::CANCELED->value => 'canceled',
            \App\Enums\BookingStatus::PAYMENT_FAILED->value => 'failed',
            default => 'waiting',
        };

        $price = (float)($this->price ?? 0);
        $priceDiscount = (float)($this->price_discount ?? 0);
        $priceTransportation = (float)($this->price_transportation ?? 0);
        $totalPrice = CalculatePrice::totalBookingPrice(
            price: $price,
            priceDiscount: $priceDiscount,
            priceTransportation: $priceTransportation,
        );
        $ktvServiceIncome = CalculatePrice::calculatePriceDiscountForKTV($price, $discountRate);
        $ktvIncomeTotal = $ktvServiceIncome + $priceTransportation;
        $customerGender = $userProfile?->gender ? Gender::getLabel((int) $userProfile->gender) : null;
        $canViewCustomerContact = $useModernContract
            ? BookingContactPrivacy::canViewCustomerContact($this->resource, $viewer)
            : true;
        $customerName = $canViewCustomerContact ? $user->name : BookingContactPrivacy::maskCustomerName($user->name);
        $customerAddress = $canViewCustomerContact ? $this->address : BookingContactPrivacy::maskAddress($this->address);
        $canChat = $useModernContract
            ? $canViewCustomerContact && in_array((int) $this->status, [
                \App\Enums\BookingStatus::CONFIRMED->value,
                \App\Enums\BookingStatus::ONGOING->value,
            ], true)
            : true;
        $canCall = $useModernContract
            ? $canViewCustomerContact && in_array((int) $this->status, [
                \App\Enums\BookingStatus::CONFIRMED->value,
                \App\Enums\BookingStatus::ONGOING->value,
            ], true)
            : true;
        $canOpenMap = $useModernContract
            ? $canViewCustomerContact && in_array((int) $this->status, [
                \App\Enums\BookingStatus::CONFIRMED->value,
                \App\Enums\BookingStatus::ONGOING->value,
            ], true)
            : true;

        return [
            'id' => $this->id,
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'image' => $service->image_url ? Helper::getPublicUrl($service->image_url) : null,
            ],
            'service_category_name' => $service->name,
            'ktv_user' => [
                'id' => $ktvUser?->id,
                'name' => $ktvUserReviewApplication->nickname ?? "",
                'avatar_url' => $ktvUserProfile?->avatar_url ? Helper::getPublicUrl($ktvUserProfile->avatar_url) : null,
            ],
            'selected_ktv_user' => [
                'id' => $selectedKtvUser?->id,
                'name' => $selectedKtvReviewApplication?->nickname ?? "",
                'avatar_url' => $selectedKtvProfile?->avatar_url ? Helper::getPublicUrl($selectedKtvProfile->avatar_url) : null,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $customerName,
                'avatar_url' => $userProfile->avatar_url ? Helper::getPublicUrl($userProfile->avatar_url) : null,
                'phone' => $canViewCustomerContact ? ($user->phone ?? null) : null,
            ],
            'customer_gender' => $customerGender,
            'address' => $customerAddress,
            'lat' => $canViewCustomerContact ? (string)$this->latitude : null,
            'lng' => $canViewCustomerContact ? (string)$this->longitude : null,
            'booking_time' => $this->booking_time,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'note' => $this->note,
            'duration' => $this->duration,
            'service_duration_total' => (int) $this->duration,
            'status' => $this->status,
            'booking_phase' => $bookingPhase,
            'is_ktv_selected' => $isKtvSelected,
            'ktv_confirm_deadline_at' => $this->ktv_confirm_deadline_at,
            'application_opened_at' => $this->application_opened_at,
            'application_open_reason' => $this->application_open_reason,
            'has_applied' => (bool) ($this->has_applied ?? false),
            'is_original_ktv' => $request->user()?->role === UserRole::KTV->value
                && (string) ($this->original_ktv_user_id ?: $this->ktv_user_id) === (string) $request->user()?->id,
            'application_status' => $this->applications?->first()?->status ?? null,
            'distance' => isset($this->distance_in_meters) ? (float) $this->distance_in_meters : null,
            'price' => $price,
            'price_discount' => $priceDiscount,
            'price_transportation' => $priceTransportation,
            'total_price' => $totalPrice,
            'ktv_service_income' => $ktvServiceIncome,
            'ktv_income_total' => $ktvIncomeTotal,
            'is_customer_contact_visible' => $canViewCustomerContact,
            'can_chat' => $canChat,
            'can_call' => $canCall,
            'can_open_map' => $canOpenMap,
            'coupon' => $coupon ? [
                'id' => $coupon->id,
                'label' => $coupon->label,
            ] : null,
            // Số lượng đánh giá
            'has_reviews' => $this->reviews_count > 0,
            'reason_cancel' => (string)($this->reason_cancel ?? null),
        ];
    }

}
