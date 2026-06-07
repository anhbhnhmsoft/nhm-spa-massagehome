<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\Helper;
use App\Core\Helper\CalculatePrice;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingApplicationStatus;
use App\Enums\BookingStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\ExpireKtvConfirmationJob;
use App\Jobs\SendNotificationJob;
use App\Models\BookingApplication;
use App\Models\ServiceBooking;
use App\Repositories\BookingApplicationRepository;
use App\Repositories\BookingRepository;
use App\Repositories\UserRepository;
use App\Repositories\CouponRepository;
use App\Services\ConfigService;
use App\Services\Validator\WalletValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingApplicationService extends BaseService
{
    private const CONFIRM_MINUTES = 3;
    private const APPLICATION_RADIUS_METERS = 30000;

    public function __construct(
        protected BookingRepository $bookingRepository,
        protected BookingApplicationRepository $bookingApplicationRepository,
        protected UserRepository $userRepository,
        protected CouponRepository $couponRepository,
        protected ConfigService $configService,
        protected WalletValidator $walletValidator,
    ) {
        parent::__construct();
    }

    public function markWaitingKtvConfirm(ServiceBooking $booking): ServiceReturn
    {
        $deadline = now()->addMinutes(self::CONFIRM_MINUTES);
        $booking->status = BookingStatus::WAITING_KTV_CONFIRM->value;
        $booking->original_ktv_user_id = $booking->original_ktv_user_id ?: $booking->ktv_user_id;
        $booking->ktv_confirm_deadline_at = $deadline;
        $booking->application_opened_at = null;
        $booking->application_open_reason = null;
        $booking->save();

        ExpireKtvConfirmationJob::dispatch($booking->id)->delay($deadline)->afterCommit();

        SendNotificationJob::dispatch(
            userId: $booking->ktv_user_id,
            type: NotificationType::NEW_BOOKING_REQUEST,
            data: [
                'booking_id' => $booking->id,
                'customer_name' => $booking->user->name ?? '',
                'booking_time' => $booking->booking_time?->format('Y-m-d H:i:s'),
                'confirm_deadline_at' => $deadline->format('Y-m-d H:i:s'),
            ]
        )->afterCommit();

        return ServiceReturn::success(data: $booking);
    }

    public function confirmBookingByKtv(string $bookingId): ServiceReturn
    {
        return $this->execute(function () use ($bookingId) {
            $user = Auth::user();
            if (!$user || $user->role !== UserRole::KTV->value) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $booking = $this->bookingRepository->query()
                ->lockForUpdate()
                ->where('id', $bookingId)
                ->where('status', BookingStatus::WAITING_KTV_CONFIRM->value)
                ->first();

            if (!$booking) {
                throw new ServiceException(__('booking.not_found'));
            }

            if ((string) $booking->ktv_user_id !== (string) $user->id) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            if (!$booking->ktv_confirm_deadline_at || now()->greaterThan($booking->ktv_confirm_deadline_at)) {
                $this->openBookingForApplicationInternal($booking, 'timeout');
                throw new ServiceException(__('booking.ktv_confirm_expired'));
            }

            $booking->status = BookingStatus::CONFIRMED->value;
            $booking->ktv_confirm_deadline_at = null;
            $booking->application_opened_at = null;
            $booking->application_open_reason = null;
            $booking->save();

            return ServiceReturn::success(data: $booking, message: __('booking.confirmed_successfully'));
        }, useTransaction: true);
    }

    public function expireKtvConfirmation(string $bookingId): ServiceReturn
    {
        return $this->execute(function () use ($bookingId) {
            $booking = $this->bookingRepository->query()
                ->lockForUpdate()
                ->where('id', $bookingId)
                ->where('status', BookingStatus::WAITING_KTV_CONFIRM->value)
                ->first();

            if (!$booking) {
                return ServiceReturn::success();
            }

            if ($booking->ktv_confirm_deadline_at && now()->lessThan($booking->ktv_confirm_deadline_at)) {
                return ServiceReturn::success();
            }

            $this->openBookingForApplicationInternal($booking, 'timeout');

            return ServiceReturn::success(data: $booking);
        }, useTransaction: true);
    }

    public function releaseBookingByKtv(string $bookingId, ?string $reason = null): ServiceReturn
    {
        return $this->execute(function () use ($bookingId, $reason) {
            $user = Auth::user();
            if (!$user || $user->role !== UserRole::KTV->value) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $booking = $this->bookingRepository->query()
                ->lockForUpdate()
                ->where('id', $bookingId)
                ->whereIn('status', [
                    BookingStatus::WAITING_KTV_CONFIRM->value,
                    BookingStatus::CONFIRMED->value,
                ])
                ->first();

            if (!$booking) {
                throw new ServiceException(__('booking.not_found'));
            }

            if ((string) $booking->ktv_user_id !== (string) $user->id) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $booking->reason_cancel = $reason;
            $booking->cancel_by = UserRole::KTV->value;
            $this->openBookingForApplicationInternal($booking, 'ktv_released');

            return ServiceReturn::success(data: $booking, message: __('booking.opened_for_application'));
        }, useTransaction: true);
    }

    public function availableBookingsForKtv(FilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = Auth::user();
            if (!$user || $user->role !== UserRole::KTV->value) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $address = $user->primaryAddress;
            if (!$address || !$address->latitude || !$address->longitude) {
                return new LengthAwarePaginator([], 0, $dto->perPage, $dto->page);
            }

            $query = $this->availableBookingQueryForKtv(
                ktvId: (int) $user->id,
                lat: (float) $address->latitude,
                lng: (float) $address->longitude
            );

            $query->orderBy('application_opened_at', 'desc');

            return $query->paginate(perPage: $dto->perPage, page: $dto->page);
        });
    }

    public function detailAvailableBookingForKtv(string $bookingId): ServiceReturn
    {
        return $this->execute(function () use ($bookingId) {
            $user = Auth::user();
            if (!$user || $user->role !== UserRole::KTV->value) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $address = $user->primaryAddress;
            if (!$address || !$address->latitude || !$address->longitude) {
                throw new ServiceException(__('booking.service.not_found_location'));
            }

            $booking = $this->availableBookingQueryForKtv(
                ktvId: (int) $user->id,
                lat: (float) $address->latitude,
                lng: (float) $address->longitude
            )
                ->where('service_bookings.id', $bookingId)
                ->first();

            if (!$booking) {
                throw new ServiceException(__('booking.not_found'));
            }

            return $booking;
        });
    }

    public function applyBooking(string $bookingId): ServiceReturn
    {
        return $this->execute(function () use ($bookingId) {
            $user = Auth::user();
            if (!$user || $user->role !== UserRole::KTV->value) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $address = $user->primaryAddress;
            if (!$address || !$address->latitude || !$address->longitude) {
                throw new ServiceException(__('booking.service.not_found_location'));
            }

            $booking = $this->availableBookingQueryForKtv(
                ktvId: (int) $user->id,
                lat: (float) $address->latitude,
                lng: (float) $address->longitude
            )
                ->lockForUpdate()
                ->where('service_bookings.id', $bookingId)
                ->first();

            if (!$booking) {
                throw new ServiceException(__('booking.not_found'));
            }

            $application = $this->bookingApplicationRepository->query()
                ->where('booking_id', $booking->id)
                ->where('ktv_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$application) {
                $application = $this->bookingApplicationRepository->create([
                    'booking_id' => $booking->id,
                    'ktv_id' => $user->id,
                    'status' => BookingApplicationStatus::APPLIED->value,
                    'applied_at' => now(),
                ]);
            } elseif ($application->status !== BookingApplicationStatus::APPLIED->value) {
                $application->status = BookingApplicationStatus::APPLIED->value;
                $application->applied_at = $application->applied_at ?? now();
                $application->selected_at = null;
                $application->removed_reason = null;
                $application->save();
            }

            SendNotificationJob::dispatch(
                userId: $booking->user_id,
                type: NotificationType::BOOKING_APPLICATION_RECEIVED,
                data: [
                    'booking_id' => $booking->id,
                    'ktv_id' => $user->id,
                    'ktv_name' => $user->reviewApplication?->nickname ?? $user->name,
                ]
            );

            return ServiceReturn::success(data: $application, message: __('booking.application_success'));
        }, useTransaction: true);
    }

    public function listBookingApplicationsForCustomer(string $bookingId, FilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($bookingId, $dto) {
            $user = Auth::user();
            if (!$user || $user->role !== UserRole::CUSTOMER->value) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $booking = $this->bookingRepository->query()
                ->where('id', $bookingId)
                ->where('user_id', $user->id)
                ->first();

            if (!$booking) {
                throw new ServiceException(__('booking.not_found'));
            }

            return $this->bookingApplicationRepository->queryWithRelations()
                ->where('booking_id', $booking->id)
                ->whereIn('status', [
                    BookingApplicationStatus::APPLIED->value,
                    BookingApplicationStatus::SELECTED->value,
                ])
                ->orderBy('applied_at', 'desc')
                ->paginate(perPage: $dto->perPage, page: $dto->page);
        });
    }

    public function selectApplicationByCustomer(string $bookingId, string $applicationId): ServiceReturn
    {
        return $this->execute(function () use ($bookingId, $applicationId) {
            $user = Auth::user();
            if (!$user || $user->role !== UserRole::CUSTOMER->value) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $booking = $this->bookingRepository->query()
                ->lockForUpdate()
                ->where('id', $bookingId)
                ->where('user_id', $user->id)
                ->where('status', BookingStatus::OPEN_FOR_APPLICATION->value)
                ->first();

            if (!$booking) {
                throw new ServiceException(__('booking.not_found'));
            }

            $application = $this->bookingApplicationRepository->query()
                ->lockForUpdate()
                ->where('id', $applicationId)
                ->where('booking_id', $booking->id)
                ->where('status', BookingApplicationStatus::APPLIED->value)
                ->first();

            if (!$application) {
                throw new ServiceException(__('booking.application_not_found'));
            }

            $previewData = $this->buildApplicationSelectionPreview($booking, $application);
            $this->applyTransportationDeltaForCustomer($booking, (float) $previewData['price_transportation']);

            $booking->ktv_user_id = $application->ktv_id;
            $booking->ktv_address = $previewData['ktv_address'];
            $booking->ktv_latitude = $previewData['ktv_latitude'];
            $booking->ktv_longitude = $previewData['ktv_longitude'];
            $booking->price_transportation = $previewData['price_transportation'];
            $booking->status = BookingStatus::CONFIRMED->value;
            $booking->ktv_confirm_deadline_at = null;
            $booking->application_opened_at = null;
            $booking->application_open_reason = null;
            $booking->reason_cancel = null;
            $booking->cancel_by = null;
            $booking->save();

            $application->status = BookingApplicationStatus::SELECTED->value;
            $application->selected_at = now();
            $application->removed_reason = null;
            $application->save();

            $this->bookingApplicationRepository->query()
                ->where('booking_id', $booking->id)
                ->where('id', '!=', $application->id)
                ->where('status', BookingApplicationStatus::APPLIED->value)
                ->update([
                    'status' => BookingApplicationStatus::REJECTED->value,
                    'removed_reason' => 'customer_selected_other_ktv',
                    'updated_at' => now(),
                ]);

            $this->removeConflictingApplicationsForKtv($booking, (int) $application->ktv_id);

            SendNotificationJob::dispatch(
                userId: $application->ktv_id,
                type: NotificationType::BOOKING_APPLICATION_SELECTED,
                data: [
                    'booking_id' => $booking->id,
                    'customer_name' => $booking->user->name ?? '',
                    'booking_time' => $booking->booking_time?->format('Y-m-d H:i:s'),
                ]
            );

            return ServiceReturn::success(data: $booking, message: __('booking.confirmed_successfully'));
        }, useTransaction: true);
    }

    public function previewApplicationSelectionByCustomer(string $bookingId, string $applicationId): ServiceReturn
    {
        return $this->execute(function () use ($bookingId, $applicationId) {
            $user = Auth::user();
            if (!$user || $user->role !== UserRole::CUSTOMER->value) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            $booking = $this->bookingRepository->query()
                ->where('id', $bookingId)
                ->where('user_id', $user->id)
                ->where('status', BookingStatus::OPEN_FOR_APPLICATION->value)
                ->first();

            if (!$booking) {
                throw new ServiceException(__('booking.not_found'));
            }

            $application = $this->bookingApplicationRepository->queryWithRelations()
                ->where('id', $applicationId)
                ->where('booking_id', $booking->id)
                ->where('status', BookingApplicationStatus::APPLIED->value)
                ->first();

            if (!$application) {
                throw new ServiceException(__('booking.application_not_found'));
            }

            $previewData = $this->buildApplicationSelectionPreview($booking, $application);

            return [
                'booking_id' => $booking->id,
                'ktv_id' => $application->ktv_id,
                'technician_name' => $application->ktv?->reviewApplication?->nickname ?? $application->ktv?->name,
                'price' => (float) $booking->price,
                'price_discount' => (float) $booking->price_discount,
                'price_transportation' => (float) $previewData['price_transportation'],
                'total_price' => (float) CalculatePrice::totalBookingPrice(
                    price: (float) $booking->price,
                    priceDiscount: (float) $booking->price_discount,
                    priceTransportation: (float) $previewData['price_transportation'],
                ),
                'distance' => $previewData['distance'],
            ];
        });
    }

    protected function openBookingForApplicationInternal(ServiceBooking $booking, string $reason): void
    {
        $booking->status = BookingStatus::OPEN_FOR_APPLICATION->value;
        $booking->ktv_confirm_deadline_at = null;
        $booking->application_opened_at = now();
        $booking->application_open_reason = $reason;
        $booking->save();

        // Khi booking được mở lại cho ứng đơn, application cũ của KTV từng được chọn
        // không còn được xem là "đang apply" để KTV có thể chủ động ứng lại.
        $this->bookingApplicationRepository->query()
            ->where('booking_id', $booking->id)
            ->where('ktv_id', $booking->ktv_user_id)
            ->where('status', BookingApplicationStatus::SELECTED->value)
            ->update([
                'status' => BookingApplicationStatus::REMOVED->value,
                'removed_reason' => 'ktv_released_booking',
                'updated_at' => now(),
            ]);

        $customerNotificationType = $reason === 'timeout'
            ? NotificationType::BOOKING_KTV_CONFIRM_TIMEOUT
            : NotificationType::BOOKING_KTV_RELEASED;

        SendNotificationJob::dispatch(
            userId: $booking->user_id,
            type: $customerNotificationType,
            data: [
                'booking_id' => $booking->id,
                'reason' => $booking->reason_cancel,
            ]
        );

        $this->notifyNearbyKtvs($booking);
    }

    protected function notifyNearbyKtvs(ServiceBooking $booking): void
    {
        $query = $this->nearbyKtvQuery($booking)
            ->where('users.id', '!=', $booking->ktv_user_id);

        $query->chunk(100, function ($ktvs) use ($booking) {
            foreach ($ktvs as $ktv) {
                SendNotificationJob::dispatch(
                    userId: $ktv->id,
                    type: NotificationType::APPLICATION_BOOKING_AVAILABLE,
                    data: [
                        'booking_id' => $booking->id,
                        'booking_time' => $booking->booking_time?->format('Y-m-d H:i:s'),
                        'category_id' => $booking->category_id,
                    ]
                );
            }
        });
    }

    protected function availableBookingQueryForKtv(int $ktvId, float $lat, float $lng): Builder
    {
        $distanceFormula = $this->distanceSql('service_bookings.longitude', 'service_bookings.latitude', $lng, $lat);

        return $this->bookingRepository->queryBooking()
            ->where('service_bookings.status', BookingStatus::OPEN_FOR_APPLICATION->value)
            ->whereNotNull('service_bookings.application_opened_at')
            ->whereHas('service', function ($query) use ($ktvId) {
                $query->whereHas('users', function ($query) use ($ktvId) {
                    $query->where('users.id', $ktvId);
                });
            })
            ->whereRaw("$distanceFormula <= ?", [self::APPLICATION_RADIUS_METERS])
            ->select('service_bookings.*')
            ->selectRaw("$distanceFormula AS distance_in_meters")
            ->withExists(['applications as has_applied' => function ($query) use ($ktvId) {
                $query->where('ktv_id', $ktvId)
                    ->whereIn('status', $this->bookingApplicationRepository->activeApplicationStatuses());
            }])
            ->with(['applications' => function ($query) use ($ktvId) {
                $query->where('ktv_id', $ktvId);
            }]);
    }

    protected function nearbyKtvQuery(ServiceBooking $booking): Builder
    {
        $distanceFormula = $this->distanceSql('user_address.longitude', 'user_address.latitude', (float) $booking->longitude, (float) $booking->latitude);

        return $this->userRepository->query()
            ->join('user_address', function ($join) {
                $join->on('users.id', '=', 'user_address.user_id')
                    ->where('user_address.is_primary', true);
            })
            ->where('users.role', UserRole::KTV->value)
            ->where('users.is_active', true)
            ->whereHas('categories', function ($query) use ($booking) {
                $query->where('categories.id', $booking->category_id);
            })
            ->whereRaw("$distanceFormula <= ?", [self::APPLICATION_RADIUS_METERS])
            ->select('users.*')
            ->selectRaw("$distanceFormula AS distance_in_meters")
            ->orderBy('distance_in_meters');
    }

    protected function removeConflictingApplicationsForKtv(ServiceBooking $selectedBooking, int $ktvId): void
    {
        $selectedStart = Carbon::make($selectedBooking->booking_time);
        $selectedEnd = $selectedStart->copy()->addMinutes((int) $selectedBooking->duration);

        $conflictingBookingIds = $this->bookingRepository->query()
            ->where('id', '!=', $selectedBooking->id)
            ->where('status', BookingStatus::OPEN_FOR_APPLICATION->value)
            ->where(function ($query) use ($selectedStart, $selectedEnd) {
                $query->whereRaw(
                    "booking_time < ? AND booking_time + (duration * interval '1 minute') > ?",
                    [$selectedEnd, $selectedStart]
                );
            })
            ->pluck('id');

        if ($conflictingBookingIds->isEmpty()) {
            return;
        }

        $this->bookingApplicationRepository->query()
            ->where('ktv_id', $ktvId)
            ->whereIn('booking_id', $conflictingBookingIds)
            ->where('status', BookingApplicationStatus::APPLIED->value)
            ->update([
                'status' => BookingApplicationStatus::REMOVED->value,
                'removed_reason' => 'selected_other_booking_same_time',
                'updated_at' => now(),
            ]);
    }

    protected function distanceSql(string $lngColumn, string $latColumn, float $targetLng, float $targetLat): string
    {
        return sprintf(
            "ST_DistanceSphere(ST_MakePoint(%s::float, %s::float), ST_MakePoint(%F, %F))",
            $lngColumn,
            $latColumn,
            $targetLng,
            $targetLat
        );
    }

    protected function buildApplicationSelectionPreview(ServiceBooking $booking, BookingApplication $application): array
    {
        $ktv = $application->ktv;
        $ktvAddress = $ktv?->primaryAddress;
        if (!$ktvAddress || !$ktvAddress->latitude || !$ktvAddress->longitude) {
            throw new ServiceException(__('booking.service.not_found_location'));
        }

        $pricePerKm = (float) $this->configService->getConfigValue(\App\Enums\ConfigName::PRICE_TRANSPORTATION);
        $coupon = !empty($booking->coupon_id)
            ? $this->couponRepository->query()->find($booking->coupon_id)
            : null;

        $priceData = CalculatePrice::calculateBookingPrice(
            price: (float) $booking->price,
            coupon: $coupon,
            pricePerKm: $pricePerKm,
            longitude: (float) $booking->longitude,
            latitude: (float) $booking->latitude,
            ktvLongitude: (float) $ktvAddress->longitude,
            ktvLatitude: (float) $ktvAddress->latitude,
        );

        $walletCustomer = $booking->user?->wallet;
        if ($walletCustomer) {
            $transportDelta = (float) $priceData['price_distance'] - (float) $booking->price_transportation;
            if ($transportDelta > 0 && $transportDelta > (float) $walletCustomer->balance) {
                throw new ServiceException(
                    message: __("booking.error.user_not_enough_money", [
                        'balance' => Helper::formatPrice($walletCustomer->balance),
                        'price' => Helper::formatPrice($booking->price),
                        'coupon_discount' => Helper::formatPrice($booking->price_discount),
                        'price_move' => Helper::formatPrice($priceData['price_distance']),
                    ])
                );
            }
        }

        return [
            'ktv_address' => $ktvAddress->address ?? '',
            'ktv_latitude' => $ktvAddress->latitude ?? 0,
            'ktv_longitude' => $ktvAddress->longitude ?? 0,
            'price_transportation' => (float) $priceData['price_distance'],
            'distance' => (float) $priceData['distance'],
        ];
    }

    protected function applyTransportationDeltaForCustomer(ServiceBooking $booking, float $newTransportationPrice): void
    {
        $oldTransportationPrice = (float) $booking->price_transportation;
        $transportDelta = $newTransportationPrice - $oldTransportationPrice;

        if (abs($transportDelta) < 0.0001) {
            return;
        }

        $walletCustomer = $booking->user?->wallet()->lockForUpdate()->first();
        if (!$walletCustomer) {
            throw new ServiceException(__('booking.payment.wallet_customer_not_found'));
        }

        $transportTransaction = $this->bookingRepository->query()
            ->getModel()
            ->newQuery()
            ->from('wallet_transactions')
            ->where('foreign_key', $booking->id)
            ->where('type', WalletTransactionType::PAYMENT_FEE_TRANSPORT->value)
            ->where('status', WalletTransactionStatus::COMPLETED->value)
            ->lockForUpdate()
            ->first();

        if (!$transportTransaction) {
            throw new ServiceException(__('error.transaction_not_found'));
        }

        $exchangeRate = (float) ($transportTransaction->exchange_rate_point ?? 1);

        if ($transportDelta > 0) {
            if ($walletCustomer->balance < $transportDelta) {
                throw new ServiceException(__('booking.payment.wallet_customer_not_enough'));
            }

            DB::table('wallet_transactions')->insert([
                'wallet_id' => $walletCustomer->id,
                'foreign_key' => $booking->id,
                'money_amount' => $transportDelta * $exchangeRate,
                'exchange_rate_point' => $exchangeRate,
                'point_amount' => $transportDelta,
                'type' => WalletTransactionType::PAYMENT_FEE_TRANSPORT->value,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'description' => __('booking.payment.wallet_customer'),
                'expired_at' => now(),
                'metadata' => null,
                'transaction_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $walletCustomer->balance -= $transportDelta;
            $walletCustomer->save();
            return;
        }

        $refundAmount = abs($transportDelta);

        DB::table('wallet_transactions')->insert([
            'wallet_id' => $walletCustomer->id,
            'foreign_key' => $booking->id,
            'money_amount' => $refundAmount * $exchangeRate,
            'exchange_rate_point' => $exchangeRate,
            'point_amount' => $refundAmount,
            'type' => WalletTransactionType::REFUND_CUSTOMER_TRANSPORT->value,
            'status' => WalletTransactionStatus::COMPLETED->value,
            'transaction_code' => Helper::createDescPayment(PaymentType::REFUND),
            'description' => __('booking.payment.wallet_customer'),
            'expired_at' => null,
            'metadata' => null,
            'transaction_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $walletCustomer->balance += $refundAmount;
        $walletCustomer->save();
    }
}
