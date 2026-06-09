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
use App\Enums\ConfigName;
use App\Enums\Jobs\WalletTransCase;
use App\Enums\NotificationType;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Jobs\ExpireOpenApplicationBookingJob;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\ExpireKtvConfirmationJob;
use App\Jobs\RemindKtvBookingConfirmationJob;
use App\Jobs\SendNotificationJob;
use App\Jobs\WalletTransactionBookingJob;
use App\Models\BookingApplication;
use App\Models\ServiceBooking;
use App\Repositories\BookingApplicationRepository;
use App\Repositories\BookingRepository;
use App\Repositories\UserRepository;
use App\Repositories\CouponRepository;
use App\Services\Validator\WalletValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingApplicationService extends BaseService
{
    private const CONFIRM_MINUTES = 3;
    private const CONFIRM_REMINDER_ATTEMPTS = 3;
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

        $this->dispatchKtvConfirmationNotification($booking, NotificationType::NEW_BOOKING_REQUEST, 0);

        foreach (range(1, self::CONFIRM_REMINDER_ATTEMPTS) as $attemptNumber) {
            $delayAt = now()->addMinutes($attemptNumber);

            if ($delayAt->greaterThanOrEqualTo($deadline)) {
                break;
            }

            RemindKtvBookingConfirmationJob::dispatch($booking->id, $attemptNumber)
                ->delay($delayAt)
                ->afterCommit();
        }

        return ServiceReturn::success(data: $booking);
    }

    public function openBookingForAssignment(ServiceBooking $booking): ServiceReturn
    {
        $deadline = $this->newApplicationDeadline();
        $now = now();

        $booking->status = BookingStatus::OPEN_FOR_APPLICATION->value;
        $booking->original_ktv_user_id = $booking->original_ktv_user_id ?: $booking->ktv_user_id;
        $booking->ktv_confirm_deadline_at = $deadline;
        $booking->application_opened_at = $now;
        $booking->application_open_reason = 'booking_created';
        $booking->reason_cancel = null;
        $booking->cancel_by = null;
        $booking->save();

        ExpireOpenApplicationBookingJob::dispatch($booking->id)->delay($deadline)->afterCommit();

        $this->dispatchKtvConfirmationNotification($booking, NotificationType::NEW_BOOKING_REQUEST, 0);
        $this->notifyNearbyKtvs($booking);

        return ServiceReturn::success(data: $booking, message: __('booking.opened_for_application'));
    }

    public function sendKtvConfirmationReminder(string $bookingId, int $attemptNumber): ServiceReturn
    {
        return $this->execute(function () use ($bookingId, $attemptNumber) {
            $booking = $this->bookingRepository->query()
                ->where('id', $bookingId)
                ->where('status', BookingStatus::WAITING_KTV_CONFIRM->value)
                ->first();

            if (!$booking || !$booking->ktv_confirm_deadline_at || now()->greaterThanOrEqualTo($booking->ktv_confirm_deadline_at)) {
                return ServiceReturn::success();
            }

            $this->dispatchKtvConfirmationNotification(
                booking: $booking,
                type: NotificationType::NEW_BOOKING_REQUEST,
                reminderAttempt: $attemptNumber,
            );

            return ServiceReturn::success();
        });
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
                ->whereIn('status', [
                    BookingStatus::WAITING_KTV_CONFIRM->value,
                    BookingStatus::OPEN_FOR_APPLICATION->value,
                ])
                ->first();

            if (!$booking) {
                throw new ServiceException(__('booking.not_found'));
            }

            $originalKtvId = $booking->original_ktv_user_id ?: $booking->ktv_user_id;
            if ((string) $originalKtvId !== (string) $user->id) {
                throw new ServiceException(__('common_error.unauthorized'));
            }

            if (!$booking->ktv_confirm_deadline_at || now()->greaterThan($booking->ktv_confirm_deadline_at)) {
                if ((int) $booking->status === BookingStatus::WAITING_KTV_CONFIRM->value) {
                    $this->openBookingForApplicationInternal($booking, 'timeout');
                } else {
                    ExpireOpenApplicationBookingJob::dispatch($booking->id)->afterCommit();
                }

                return ServiceReturn::error(__('booking.ktv_confirm_expired'));
            }

            $this->userRepository->query()->lockForUpdate()->find($user->id);

            $this->ensureKtvDoesNotHaveConflictingBooking(
                ktvId: (int) $user->id,
                bookingTime: Carbon::make($booking->booking_time),
                duration: (int) $booking->duration,
                exceptBookingId: (int) $booking->id,
            );

            $this->assignBookingToKtv($booking, (int) $user->id, null, 'original_ktv_accepted');

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

    public function expireOpenApplicationBooking(string $bookingId): ServiceReturn
    {
        return $this->execute(function () use ($bookingId) {
            $booking = $this->bookingRepository->query()
                ->lockForUpdate()
                ->where('id', $bookingId)
                ->where('status', BookingStatus::OPEN_FOR_APPLICATION->value)
                ->first();

            if (!$booking || !$this->hasApplicationDeadlineExpired($booking)) {
                return ServiceReturn::success();
            }

            $appliedKtvIds = $this->bookingApplicationRepository->query()
                ->where('booking_id', $booking->id)
                ->where('status', BookingApplicationStatus::APPLIED->value)
                ->pluck('ktv_id');

            $booking->status = BookingStatus::CANCELED->value;
            $booking->reason_cancel = $booking->reason_cancel ?: __('booking.assignment_auto_cancel_reason');
            $booking->cancel_by = null;
            $booking->ktv_confirm_deadline_at = null;
            $booking->save();

            $this->bookingApplicationRepository->query()
                ->where('booking_id', $booking->id)
                ->where('status', BookingApplicationStatus::APPLIED->value)
                ->update([
                    'status' => BookingApplicationStatus::EXPIRED->value,
                    'removed_reason' => 'booking_assignment_timeout',
                    'updated_at' => now(),
                ]);

            foreach ($appliedKtvIds as $ktvId) {
                if ((string) $ktvId === (string) $booking->ktv_user_id) {
                    continue;
                }

                SendNotificationJob::dispatch(
                    userId: $ktvId,
                    type: NotificationType::BOOKING_CANCELLED,
                    data: [
                        'booking_id' => $booking->id,
                        'reason' => $booking->reason_cancel,
                    ]
                )->afterCommit();
            }

            WalletTransactionBookingJob::dispatch(
                bookingId: $booking->id,
                case: WalletTransCase::AUTO_CANCEL_BOOKING,
            )->afterCommit();

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

            if ($this->hasApplicationDeadlineExpired($booking)) {
                ExpireOpenApplicationBookingJob::dispatch($booking->id)->afterCommit();

                return ServiceReturn::error(__('booking.application_expired'));
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

            if ($this->hasApplicationDeadlineExpired($booking)) {
                ExpireOpenApplicationBookingJob::dispatch($booking->id)->afterCommit();

                return ServiceReturn::error(__('booking.application_expired'));
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

            $this->userRepository->query()->lockForUpdate()->find($application->ktv_id);

            $this->ensureKtvDoesNotHaveConflictingBooking(
                ktvId: (int) $application->ktv_id,
                bookingTime: Carbon::make($booking->booking_time),
                duration: (int) $booking->duration,
                exceptBookingId: (int) $booking->id,
            );

            $this->assignBookingToKtv(
                booking: $booking,
                ktvId: (int) $application->ktv_id,
                selectedApplication: $application,
                rejectedReason: 'customer_selected_other_ktv',
            );

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

            if ($this->hasApplicationDeadlineExpired($booking)) {
                ExpireOpenApplicationBookingJob::dispatch($booking->id)->afterCommit();

                return ServiceReturn::error(__('booking.application_expired'));
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
        $deadline = $this->newApplicationDeadline();

        $booking->status = BookingStatus::OPEN_FOR_APPLICATION->value;
        $booking->original_ktv_user_id = $booking->original_ktv_user_id ?: $booking->ktv_user_id;
        $booking->ktv_confirm_deadline_at = $deadline;
        $booking->application_opened_at = now();
        $booking->application_open_reason = $reason;
        $booking->save();

        ExpireOpenApplicationBookingJob::dispatch($booking->id)->delay($deadline)->afterCommit();

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
            ->whereNotIn('users.id', array_filter([
                $booking->ktv_user_id,
                $booking->original_ktv_user_id,
            ]));

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
            ->where(function ($query) {
                $query->whereNull('service_bookings.ktv_confirm_deadline_at')
                    ->orWhere('service_bookings.ktv_confirm_deadline_at', '>', now());
            })
            ->whereHas('service', function ($query) use ($ktvId) {
                $query->whereHas('users', function ($query) use ($ktvId) {
                    $query->where('users.id', $ktvId);
                });
            })
            ->where(function ($query) use ($ktvId, $distanceFormula) {
                $query->where('service_bookings.original_ktv_user_id', $ktvId)
                    ->orWhereRaw("$distanceFormula <= ?", [self::APPLICATION_RADIUS_METERS]);
            })
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

    protected function assignBookingToKtv(
        ServiceBooking $booking,
        int $ktvId,
        ?BookingApplication $selectedApplication,
        string $rejectedReason,
    ): void {
        if ($selectedApplication) {
            $previewData = $this->buildApplicationSelectionPreview($booking, $selectedApplication);
            $this->applyTransportationDeltaForCustomer($booking, (float) $previewData['price_transportation']);

            $booking->ktv_address = $previewData['ktv_address'];
            $booking->ktv_latitude = $previewData['ktv_latitude'];
            $booking->ktv_longitude = $previewData['ktv_longitude'];
            $booking->price_transportation = $previewData['price_transportation'];
        } else {
            $ktv = $this->userRepository->query()
                ->with('primaryAddress')
                ->lockForUpdate()
                ->find($ktvId);
            $ktvAddress = $ktv?->primaryAddress;

            if ($ktvAddress) {
                $booking->ktv_address = $ktvAddress->address ?? $booking->ktv_address;
                $booking->ktv_latitude = $ktvAddress->latitude ?? $booking->ktv_latitude;
                $booking->ktv_longitude = $ktvAddress->longitude ?? $booking->ktv_longitude;
            }

            $selectedApplication = $this->bookingApplicationRepository->query()
                ->where('booking_id', $booking->id)
                ->where('ktv_id', $ktvId)
                ->where('status', BookingApplicationStatus::APPLIED->value)
                ->lockForUpdate()
                ->first();
        }

        $booking->ktv_user_id = $ktvId;
        $booking->status = BookingStatus::CONFIRMED->value;
        $booking->ktv_confirm_deadline_at = null;
        $booking->application_opened_at = null;
        $booking->application_open_reason = null;
        $booking->reason_cancel = null;
        $booking->cancel_by = null;
        $booking->save();

        if ($selectedApplication) {
            $selectedApplication->status = BookingApplicationStatus::SELECTED->value;
            $selectedApplication->selected_at = now();
            $selectedApplication->removed_reason = null;
            $selectedApplication->save();
        }

        $this->bookingApplicationRepository->query()
            ->where('booking_id', $booking->id)
            ->when($selectedApplication, function ($query) use ($selectedApplication) {
                $query->where('id', '!=', $selectedApplication->id);
            })
            ->where('status', BookingApplicationStatus::APPLIED->value)
            ->update([
                'status' => BookingApplicationStatus::REJECTED->value,
                'removed_reason' => $rejectedReason,
                'updated_at' => now(),
            ]);

        $this->removeConflictingApplicationsForKtv($booking, $ktvId);
    }

    protected function ensureKtvDoesNotHaveConflictingBooking(
        int $ktvId,
        Carbon $bookingTime,
        int $duration,
        int $exceptBookingId,
    ): void {
        $bookingEnd = $bookingTime->copy()->addMinutes($duration);

        $conflictingBookingExists = $this->bookingRepository->query()
            ->lockForUpdate()
            ->where('ktv_user_id', $ktvId)
            ->where('id', '!=', $exceptBookingId)
            ->whereIn('status', [
                BookingStatus::WAITING_KTV_CONFIRM->value,
                BookingStatus::CONFIRMED->value,
                BookingStatus::ONGOING->value,
            ])
            ->where(function ($query) use ($bookingTime, $bookingEnd) {
                $query->whereRaw(
                    "booking_time < ? AND booking_time + (duration * interval '1 minute') > ?",
                    [$bookingEnd, $bookingTime]
                );
            })
            ->exists();

        if ($conflictingBookingExists) {
            throw new ServiceException(__('booking.ktv.not_available_for_selected_booking'));
        }
    }

    protected function dispatchKtvConfirmationNotification(
        ServiceBooking $booking,
        NotificationType $type,
        int $reminderAttempt = 0,
    ): void {
        SendNotificationJob::dispatch(
            userId: $booking->original_ktv_user_id ?: $booking->ktv_user_id,
            type: $type,
            data: [
                'booking_id' => $booking->id,
                'customer_name' => $booking->user->name ?? '',
                'booking_time' => $booking->booking_time?->format('Y-m-d H:i:s'),
                'confirm_deadline_at' => $booking->ktv_confirm_deadline_at?->format('Y-m-d H:i:s'),
                'reminder_attempt' => $reminderAttempt,
            ]
        )->afterCommit();
    }

    protected function newApplicationDeadline(): Carbon
    {
        return now()->addMinutes($this->applicationTimeoutMinutes());
    }

    protected function applicationTimeoutMinutes(): int
    {
        try {
            $minutes = (int) $this->configService->getConfigValue(ConfigName::BOOKING_APPLICATION_TIMEOUT_MINUTES);

            return $minutes > 0 ? $minutes : 15;
        } catch (\Throwable) {
            return 15;
        }
    }

    protected function hasApplicationDeadlineExpired(ServiceBooking $booking): bool
    {
        return $booking->ktv_confirm_deadline_at
            && now()->greaterThanOrEqualTo($booking->ktv_confirm_deadline_at);
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
