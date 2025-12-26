<?php

namespace App\Services;

use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Enums\BookingStatus;
use App\Enums\ConfigName;
use App\Enums\PaymentType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Repositories\BookingRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\ConfigService;
use Illuminate\Support\Facades\DB;

class WalletService extends BaseService
{
    public function __construct(
        protected WalletRepository $walletRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
        protected BookingRepository $bookingRepository,
        protected ConfigService $configService,
    ) {
        parent::__construct();
    }

    public function paymentInitBooking($bookingId)
    {
        DB::beginTransaction();
        try {
            $booking = $this->bookingRepository->query()->find($bookingId);
            if (!$booking) {
                return ServiceReturn::error(
                    message: __("booking.payment.booking_not_found")
                );
            }

            $walletCustomer = $this->walletRepository->query()->where('user_id', $booking->user_id)->first();
            if (!$walletCustomer || $walletCustomer->is_active == false) {
                return ServiceReturn::error(
                    message: __("booking.payment.wallet_customer_not_found")
                );
            }

            if ($walletCustomer->balance < $booking->price) {
                // thông báo người dùng không đủ số dư, không tiến hành trừ
                // tiến hành hủy booking
                $booking->status = BookingStatus::CANCELED->value;
                $booking->save();
                return ServiceReturn::error(
                    message: __("booking.payment.wallet_customer_not_enough")
                );
            }
            // tiến hành trừ và lưu số dư
            $walletCustomer->balance -= $booking->price;
            $walletCustomer->save();

            $exchangeRate = $this->configService->getConfig(ConfigName::CURRENCY_EXCHANGE_RATE);

            $exchangeRate = $exchangeRate->getData()['config_value'];
            // tiến hành lưu giao dịch
            $this->walletTransactionRepository->create([
                'wallet_id' => $walletCustomer->id,
                'foreign_key' => $bookingId,
                'money_amount' => $booking->price * $exchangeRate,
                'exchange_rate_point' => $exchangeRate,
                'point_amount' => $booking->price,
                'balance_after' => $walletCustomer->balance,
                'type' => WalletTransactionType::PAYMENT->value,
                'status' => WalletTransactionStatus::COMPLETED->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'description' => __('booking.payment.wallet_customer'),
                'expired_at' => now()
            ]);

            // cần bổ sung thông báo thay đổi số dư ví

            $walletTechnician = $this->walletRepository->query()->where('user_id', $booking->service->user_id)->first();
            // tạo transaction tạm để trả tiền cho kỹ thuật viên
            // sau khi kết thúc booking và thanh toán thành công, duyệt transaction này và tiến hành cộng số dư cho kỹ thuật viên
            $this->walletTransactionRepository->create([
                'wallet_id' => $walletTechnician->id,
                'foreign_key' => $bookingId,
                'money_amount' => $booking->price * $exchangeRate,
                'exchange_rate_point' => $exchangeRate,
                'point_amount' => $booking->price,
                'balance_after' => $walletTechnician->balance,
                'type' => WalletTransactionType::PAYMENT_FOR_KTV->value,
                'status' => WalletTransactionStatus::PENDING->value,
                'transaction_code' => Helper::createDescPayment(PaymentType::BY_POINTS),
                'description' => __('booking.payment.wallet_technician'),
                'expired_at' => now()->addMinutes($booking->duration + 120)
            ]);

            $booking->status = BookingStatus::CONFIRMED->value;
            $booking->save();
            DB::commit();
            return ServiceReturn::success(
                message: __("booking.payment.success")
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi WalletService@paymentBooking",
                ex: $exception
            );
            throw $exception;
        }
    }

    /**
     * Khởi tạo ví cho nhân viên
     * @param int $staffId
     * @return ServiceReturn
     */
    public function initWalletForStaff(int $staffId): ServiceReturn
    {
        try {
            $wallet = $this->walletRepository->query()->where('user_id', $staffId)->first();
            if (!$wallet) {
                $this->walletRepository->create([
                    'user_id' => $staffId,
                    'is_active' => true,
                    'balance' => 0,
                ]);
            }
            return ServiceReturn::success(
                message: __("wallet.init_success")
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi WalletService@initWalletForStaff",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("wallet.init_failed")
            );
        }

    }
}
