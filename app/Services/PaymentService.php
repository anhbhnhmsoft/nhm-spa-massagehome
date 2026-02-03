<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\BankBin;
use App\Enums\ConfigName;
use App\Enums\NotificationAdminType;
use App\Enums\PaymentType;
use App\Enums\NotificationType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\SendNotificationJob;
use App\Models\WalletTransaction;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService extends BaseService
{
    public function __construct(
        protected WalletRepository            $walletRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
        protected ConfigService               $configService,
        protected PayOsService                $payOsService,
        protected ZaloService                 $zaloService,
        protected NotificationService        $notificationService,
    )
    {
        parent::__construct();
    }

    /**
     * Lấy ví của người dùng
     * @param int $userId
     * @param bool $withTotal
     * @return ServiceReturn
     */
    public function getUserWallet(int $userId, bool $withTotal = false): ServiceReturn
    {
        try {
            $wallet = $this->walletRepository->queryWallet()
                ->where('user_id', $userId)
                ->first();
            if (!$wallet) {
                throw new ServiceException(
                    message: __("error.wallet_not_found")
                );
            }
            // Nếu không cần lấy tổng số điểm đã nạp vào ví và rút ra khỏi ví
            if (!$withTotal) {
                return ServiceReturn::success(
                    data: [
                        'wallet' => $wallet,
                    ]
                );
            }
            // Lấy tổng số điểm đã nạp vào ví
            $totalDeposit = $this->walletTransactionRepository->queryTransaction()
                ->where('wallet_id', $wallet->id)
                ->whereIn('type', WalletTransactionType::statusIn())
                ->where('status', WalletTransactionStatus::COMPLETED)
                ->sum('point_amount');

            // Lấy tổng số điểm đã rút ra khỏi ví
            $totalWithdrawal = $this->walletTransactionRepository->queryTransaction()
                ->where('wallet_id', $wallet->id)
                ->whereIn('type', WalletTransactionType::statusOut())
                ->where('status', WalletTransactionStatus::COMPLETED)
                ->sum('point_amount');

            return ServiceReturn::success(
                data: [
                    'wallet' => $wallet,
                    'total_deposit' => $totalDeposit,
                    'total_withdrawal' => $totalWithdrawal,
                ]
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi PaymentService@getUserWallet",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }

    }

    /**
     * Lấy danh sách giao dịch của người dùng
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function transactionPagination(FilterDTO $dto): ServiceReturn
    {
        try {
            $query = $this->walletTransactionRepository->queryTransaction();
            $query = $this->walletTransactionRepository->filterQuery(
                query: $query,
                filters: $dto->filters
            );
            $query = $this->walletTransactionRepository->sortQuery(
                query: $query,
                sortBy: $dto->sortBy,
                direction: $dto->direction
            );
            $paginate = $query->paginate(
                perPage: $dto->perPage,
                page: $dto->page
            );
            return ServiceReturn::success(
                data: $paginate
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi PaymentService@transactionPagination",
                ex: $exception
            );
            return ServiceReturn::success(
                data: new LengthAwarePaginator(
                    items: [],
                    total: 0,
                    perPage: $dto->perPage,
                    currentPage: $dto->page
                )
            );
        }
    }


    /**
     * Lấy cấu hình thanh toán
     * @return ServiceReturn
     */
    public function getConfigPayment(): ServiceReturn
    {
        try {
            /**
             * Lấy tỉ giá giữa point và tiền thực
             */
            $currencyExchangeRate = $this->configService->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);
            /**
             * Lấy tỉ giá giữa tiền tệ và đồng
             */
            $exchangeRateVndCny = $this->configService->getConfigValue(ConfigName::EXCHANGE_RATE_VND_CNY);
            return ServiceReturn::success(
                data: [
                    'currency_exchange_rate' => $currencyExchangeRate,
                    'exchange_rate_vnd_cny' => $exchangeRateVndCny,
                    'allow_payment' => [
                        'qrcode' => (bool)config('services.payment.qrcode'),
                        'zalopay' => (bool)config('services.payment.zalopay'),
                        'momo' => (bool)config('services.payment.momo'),
                        'wechatpay' => (bool)config('services.payment.wechatpay'),
                    ]
                ]
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi PaymentService@getConfigPayment",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Nạp tiền vào ví của người dùng
     * @param string $amount Số tiền cần nạp - (string phải là số)
     * @param PaymentType $paymentType Loại hình thanh toán
     * @return ServiceReturn
     */
    public function deposit(
        string      $amount,
        PaymentType $paymentType,
    ): ServiceReturn
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            // Lấy config
            $exchangeRate = $this->configService->getConfigValue(ConfigName::CURRENCY_EXCHANGE_RATE);
            // Kiểm tra ví của người dùng
            $wallet = $this->walletRepository->queryWallet()
                ->where('user_id', $user->id)
                ->first();
            if (!$wallet) {
                throw new ServiceException(
                    message: __("error.wallet_not_found")
                );
            }
            $pointAmount = $this->calculatePointAmount($amount, $exchangeRate);
            $orderCode = (int)(microtime(true) * 1000);

            // Thời gian hết hạn 30 phút
            $expireTime = now()->addMinutes(30);
            switch ($paymentType) {
                case PaymentType::QR_BANKING:
                    // Tính toán số lượng point cần cộng dồn
                    // Tạo mã đơn hàng duy nhất
                    // Tạo Transaction
                    $transaction = $this->walletTransactionRepository->create(
                        data: [
                            'wallet_id' => $wallet->id,
                            'money_amount' => $amount, // Số tiền nạp vào
                            'point_amount' => $pointAmount, // Số lượng point nạp vào
                            'type' => WalletTransactionType::DEPOSIT_QR_CODE->value,
                            'exchange_rate_point' => $exchangeRate, // Tỉ giá chuyển đổi point
                            'payment_type' => $paymentType,
                            'transaction_id' => $orderCode,
                            'transaction_code' => Helper::createDescPayment(PaymentType::QR_BANKING),
                            'status' => WalletTransactionStatus::PENDING->value, // Trạng thái giao dịch chờ xử lý
                            'expire_at' => $expireTime, // Thời gian hết hạn
                        ]
                    );
                    // Tạo payos để xử lý thanh toán QR Banking
                    $payosResult = $this->payOsService->createPayment(
                        amount: $amount,
                        cancelUrl: route('home'),
                        description: $transaction->transaction_code,
                        orderCode: $transaction->transaction_id,
                        returnUrl: route('home'),
                        expiredAt: $expireTime
                    );
                    if ($payosResult->isError()) {
                        throw new ServiceException(
                            message: $payosResult->getMessage()
                        );
                    }
                    $payosResponse = $payosResult->getData();
                    // Cập nhật metadata của transaction
                    $transaction->update([
                        'metadata' => json_encode($payosResponse),
                    ]);

                    // Lấy dữ liệu QR Banking từ PayOS
                    $payosData = $payosResponse['data'];
                    DB::commit();
                    return ServiceReturn::success(
                        data: [
                            'transaction_id' => $transaction->id,
                            'payment_type' => $paymentType->value,
                            'data_payment' => [
                                'bin' => $payosData['bin'],
                                'bank_name' => BankBin::getBankByBin($payosData['bin'])['short_name'] ?? __("error.bank_not_found"),
                                'account_number' => $payosData['accountNumber'],
                                'account_name' => $payosData['accountName'],
                                'amount' => $payosData['amount'],
                                'description' => $payosData['description'],
                                'qr_code' => $payosData['qrCode'],
                            ]
                        ]
                    );
                case PaymentType::ZALO_PAY:
                    // Hiện tại không hỗ trợ nạp tiền qua ZaloPay và MoMoPay
                    throw new ServiceException(
                        message: __("error.payment_type_not_supported")
                    );
                    $transaction = $this->walletTransactionRepository->create([
                        'wallet_id' => $wallet->id,
                        'money_amount' => $amount,
                        'point_amount' => $pointAmount,
                        'type' => WalletTransactionType::DEPOSIT_ZALO_PAY->value,
                        'exchange_rate_point' => $exchangeRate,
                        'payment_type' => $paymentType,
                        'transaction_id' => $orderCode,
                        'transaction_code' => Helper::createDescPayment(PaymentType::ZALO_PAY),
                        'status' => WalletTransactionStatus::PENDING->value,
                        'expire_at' => $expireTime,
                    ]);

                    $zalopayResult = $this->zaloService->createOrder(
                        amount: $amount,
                        orderCode: $orderCode,
                        description: $transaction->transaction_code,
                        userId: $user->id
                    );

                    if ($zalopayResult->isError()) {
                        throw new ServiceException($zalopayResult->getMessage());
                    }

                    $zpData = $zalopayResult->getData();

                    $transaction->update([
                        'metadata' => json_encode($zpData),
                    ]);

                    DB::commit();

                    return ServiceReturn::success([
                        'transaction_id' => $transaction->id,
                        'payment_type' => $paymentType->value,
                        'data_payment' => [
                            'order_url' => $zpData['order_url'] ?? null,
                            'qr_code' => $zpData['qr_code'] ?? null,
                        ]
                    ]);
                case PaymentType::MOMO_PAY:
                    throw new ServiceException(
                        message: __("error.payment_type_not_supported")
                    );
                case PaymentType::WECHAT:
                    $wechatQrImage = $this->configService->getConfigValue(ConfigName::SP_WECHAT_QR_IMAGE);
                    if (empty($wechatQrImage)) {
                        throw new ServiceException(
                            message: __("error.config_wallet_error")
                        );
                    }
                    $wechatQrUrl = Helper::getPublicUrl($wechatQrImage);

                    $transaction = $this->walletTransactionRepository->create(
                        data: [
                            'wallet_id' => $wallet->id,
                            'money_amount' => $amount,
                            'point_amount' => $pointAmount,
                            'type' => WalletTransactionType::DEPOSIT_WECHAT_PAY->value,
                            'exchange_rate_point' => $exchangeRate,
                            'payment_type' => $paymentType,
                            'transaction_id' => $orderCode,
                            'transaction_code' => Helper::createDescPayment(PaymentType::WECHAT),
                            'status' => WalletTransactionStatus::PENDING->value,
                            'expire_at' => $expireTime,
                        ]
                    );
                    $exchangeRate = $this->configService->getConfigValue(ConfigName::EXCHANGE_RATE_VND_CNY);
                    $amountCNY = $amount / $exchangeRate;

                    // Thông báo tới admin
                    $this->notificationService->sendAdminNotification(
                        type: NotificationAdminType::CONFIRM_WECHAT_PAYMENT,
                        data: [
                            'transaction_id' => $transaction->id,
                        ]
                    );

                    DB::commit();

                    return ServiceReturn::success([
                        'transaction_id' => $transaction->id,
                        'payment_type' => $paymentType->value,
                        'data_payment' => [
                            'qr_image' => $wechatQrUrl,
                            'amount' => $amount,
                            'description' => $transaction->transaction_code,
                            'amount_cny' => $amountCNY,
                            'exchange_rate' => $exchangeRate,
                        ]
                    ]);
                default:
                    // Hiện tại không hỗ trợ nạp tiền qua ZaloPay và MoMoPay
                    throw new ServiceException(
                        message: __("error.payment_type_not_supported")
                    );
            }

        } catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi PaymentService@deposit",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Kiểm tra trạng thái giao dịch.
     * @param int $transactionId
     * @return ServiceReturn
     */
    public function checkTransaction(int $transactionId): ServiceReturn
    {
        try {
            $user = Auth::user();
            $wallet = $this->walletRepository->queryWallet()
                ->where('user_id', $user->id)
                ->first(['id']);
            if (!$wallet) {
                throw new ServiceException(
                    message: __("error.wallet_not_found")
                );
            }
            $transaction = $this->walletTransactionRepository
                ->query()
                // Kiểm tra xem transaction có phải của user hay không
                ->where('wallet_id', $wallet->id)
                ->find($transactionId);
            if (!$transaction) {
                throw new ServiceException(
                    message: __("error.transaction_not_found")
                );
            }

            return ServiceReturn::success(
                data: $transaction->status == WalletTransactionStatus::COMPLETED->value
            );
        } catch (ServiceException $exception) {
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi PaymentService@checkTransaction",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * Kiểm tra webhook PayOS.
     *  data có dạng như sau
     *  $webhookData = array(
     *  "code" => "00",
     *  "desc" => "success",
     *  "success" => true,
     *  "data" => array(
     *  "orderCode"           => 123,
     *  "amount"              => 3000,
     *  "description"         => "VQRIO123",
     *  "accountNumber"       => "12345678",
     *  "reference"           => "TF230204212323",
     *  "transactionDateTime" => "2023-02-04 18:25:00",
     *  "currency"            => "VND",
     *  "paymentLinkId"       => "124c33293c43417ab7879e14c8d9eb18",
     *  "code"                => "00",
     *  "desc"                => "Thành công",
     *  "counterAccountBankId"    => "",
     *  "counterAccountBankName"  => "",
     *  "counterAccountName"      => "",
     *  "counterAccountNumber"    => "",
     *  "virtualAccountName"      => "",
     *  "virtualAccountNumber"    => ""
     *  ),
     *  "signature"             => "412e915d2871504ed31be63c8f62a149a4410d34c4c42affc9006ef9917eaa03"
     *  );
     * @param array $data
     * @return ServiceReturn
     */
    public function checkWebhookPayOs(array $data): ServiceReturn
    {
        DB::beginTransaction();
        try {
            if (config('app.debug')) {
                // Trong môi trường dev, không kiểm tra signature
                $valid = true;
            } else {
                $valid = $this->payOsService->isValidOsPayData($data['data'], $data['signature']);
            }
            if (!$valid) {
                throw new ServiceException(
                    message: "Dữ liệu từ PayOS không hợp lệ"
                );
            }
            $dataPayOs = $data['data'];
            // Kiểm tra xem transaction có tồn tại hay không
            $transaction = $this->walletTransactionRepository
                ->query()
                // Cần kiểm tra xem transaction có phải là nạp tiền hay không
                ->where('type', WalletTransactionType::DEPOSIT_QR_CODE->value)
                ->where('status', WalletTransactionStatus::PENDING->value)
                ->where('transaction_id', $dataPayOs['orderCode'])
                ->first();
            if (!$transaction) {
                throw new ServiceException(message: "Giao dịch không tồn tại");
            }
            // Lấy ví của user
            $wallet = $this->walletRepository->queryWallet()
                ->where('id', $transaction->wallet_id)
                ->first();
            if (!$wallet) {
                throw new ServiceException(message: "Ví không tồn tại");
            }
            // Tính toán số lượng point cần cộng dồn
            $pointEarned = $this->calculatePointAmount(
                amount: $dataPayOs['amount'],
                exchangeRate: $transaction->exchange_rate_point // Lấy tỉ giá từ lúc tạo transaction
            );

            // Cập nhật trạng thái giao dịch thành công
            $transaction->update([
                'status' => WalletTransactionStatus::COMPLETED->value,
                'balance_after' => $wallet->balance + $pointEarned,
                'metadata' => json_encode($data), // Lưu toàn bộ dữ liệu từ PayOS cập nhật mới nhất
            ]);

            // cộng dồn số dư ví
            $wallet->update([
                'balance' => $wallet->balance + $pointEarned,
            ]);

            // Bắn notif cho người dùng khi thanh toán thành công
            SendNotificationJob::dispatch(
                userId: $wallet->user_id,
                type: NotificationType::WALLET_DEPOSIT,
                data: [
                    'transaction_id' => $transaction->id,
                    'amount' => $dataPayOs['amount'],
                    'point_amount' => $pointEarned,
                    'balance_after' => $wallet->balance,
                ]
            );

            DB::commit();
            return ServiceReturn::success();
        } catch (ServiceException $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi PaymentService@checkWebhookPayOs",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi PaymentService@checkWebhookPayOs",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    public function handleAdminConfirmTransaction(WalletTransaction $record): void
    {
        try {
            $record->update(['status' => WalletTransactionStatus::COMPLETED]);
            // Kiểm tra xem transaction có phải là nạp tiền hay không
            if (in_array($record->type, WalletTransactionType::incomeStatus())) {
                $record->wallet->increment('balance', (float)$record->point_amount);
                // Thông báo tới người dùng khi nạp tiền thành công
                SendNotificationJob::dispatch(
                    userId: $record->wallet->user_id,
                    type: NotificationType::DEPOSIT_SUCCESS,
                    data: [
                        'transaction_id' => $record->id,
                        'amount' => $record->point_amount,
                        'deposit_time' => $record->created_at->format('Y-m-d H:i:s'),
                    ]
                );
            }

        }catch (\Exception $exception){
            LogHelper::error(
                message: "Lỗi WalletService@handleAdminConfirmTransaction",
                ex: $exception
            );
            throw $exception;
        }
    }


    /**
     * Xử lý giao dịch ZaloPay.
     * @param string $orderCode
     * @param array $data
     * @return ServiceReturn
     */
    public function handleZaloPayTransaction(string $orderCode, array $data): ServiceReturn
    {
        DB::beginTransaction();
        try {
            // Kiểm tra xem transaction có tồn tại hay không
            $transaction = $this->walletTransactionRepository
                ->query()
                // Cần kiểm tra xem transaction có phải là nạp tiền hay không
                ->where('type', WalletTransactionType::DEPOSIT_ZALO_PAY->value)
                ->where('status', WalletTransactionStatus::PENDING->value)
                ->where('transaction_id', $orderCode)
                ->first();
            if (!$transaction) {
                throw new ServiceException(message: __('error.transaction_not_found'));
            }
            // Lấy ví của user
            $wallet = $this->walletRepository->queryWallet()
                ->where('id', $transaction->wallet_id)
                ->first();
            if (!$wallet) {
                throw new ServiceException(message: __('error.wallet_not_found'));
            }
            // Tính toán số lượng point cần cộng dồn
            $pointEarned = $this->calculatePointAmount(
                amount: $transaction->money_amount,
                exchangeRate: $transaction->exchange_rate_point // Lấy tỉ giá từ lúc tạo transaction
            );

            // Cập nhật trạng thái giao dịch thành công
            $transaction->update([
                'status' => WalletTransactionStatus::COMPLETED->value,
                'balance_after' => $wallet->balance + $pointEarned,
                'metadata' => json_encode($data), // Lưu toàn bộ dữ liệu từ ZaloPay cập nhật mới nhất
            ]);

            // cộng dồn số dư ví
            $wallet->update([
                'balance' => $wallet->balance + $pointEarned,
            ]);

            // Bắn notif cho người dùng khi thanh toán thành công
            SendNotificationJob::dispatch(
                userId: $wallet->user_id,
                type: NotificationType::WALLET_DEPOSIT,
                data: [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->money_amount,
                    'point_amount' => $pointEarned,
                    'balance_after' => $wallet->balance,
                ]
            );

            DB::commit();
            return ServiceReturn::success();
        } catch (ServiceException $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi PaymentService@handleZaloPayTransaction",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi PaymentService@handleZaloPayTransaction",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

    /**
     * --------- Protected Methods ---------
     */
    /**
     * Tính toán số lượng point cần cộng dồn
     * @param string $amount Số tiền cần nạp - (string phải là số)
     * @param float $exchangeRate Tỉ giá giữa tiền tệ và đồng
     * @return float Số lượng point cần cộng dồn
     */

    protected function calculatePointAmount(string $amount, float $exchangeRate): float
    {
        return $amount / $exchangeRate;
    }


}
