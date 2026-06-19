<?php

namespace App\Services;

use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\UserOtpType;
use Illuminate\Support\Facades\Http;

class SpeedSmsService extends BaseService
{
    private const ROOT_URL = 'https://api.speedsms.vn/index.php';
    private const SMS_TYPE_CSKH = 2;
    private const SMS_TYPE_BRANDNAME = 3;
    private const SMS_TYPE_NOTIFY = 4;
    private const SMS_TYPE_GATEWAY = 5;
    private const RESPONSE_STATUS_SUCCESS = 'success';

    protected bool $isBooted = false;

    /**
     * @var array{
     *     token: string|null,
     *     sender: string|null,
     *     sms_type: int,
     *     app_id: string|null,
     * }
     */
    protected array $configs = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function getUserInfo(): ServiceReturn
    {
        return $this->execute(function () {
            $this->boot();

            LogHelper::debug('SpeedSmsService::getUserInfo request', [
                'endpoint' => self::ROOT_URL . '/user/info',
            ]);

            $response = Http::withBasicAuth($this->configs['token'], 'x')
                ->acceptJson()
                ->get(self::ROOT_URL . '/user/info');

            $data = $response->json();

            if (!$this->isSuccessfulResponse($response->successful(), $data)) {
                LogHelper::error('SpeedSmsService::getUserInfo failed', null, [
                    'status' => $response->status(),
                    'response' => $data,
                ]);
                throw new ServiceException($data['message'] ?? __('error.could_not_send_otp'));
            }

            LogHelper::debug('SpeedSmsService::getUserInfo success', [
                'status' => $response->status(),
                'response' => $data,
            ]);

            return $data;
        }, logServiceError: true);
    }

    public function sendOtp(string $phoneNumber, string $otp, ?UserOtpType $type = null): ServiceReturn
    {
        $formattedPhone = Helper::formatPhone($phoneNumber);

        if (!Helper::isValidPhone($formattedPhone)) {
            return ServiceReturn::error(__('error.invalid_phone_number'));
        }

        $content = $this->otpMessage($otp, $type);

        return $this->sendSms(
            to: [$formattedPhone],
            content: $content,
            smsType: $this->configs['sms_type'] ?? self::SMS_TYPE_NOTIFY,
            sender: null,
        );
    }

    public function sendSms(array $to, string $content, ?int $smsType = null, ?string $sender = null): ServiceReturn
    {
        return $this->execute(function () use ($to, $content, $smsType, $sender) {
            $this->boot();

            if (empty($to) || trim($content) === '') {
                throw new ServiceException(__('error.could_not_send_otp'));
            }

            // Tự động fallback về config nếu tham số truyền vào hàm bị rỗng
            $finalSender = 'Verify' ?: ($this->configs['sender'] ?? null);

            $payload = [
                'to' => array_values($to),
                'content' => $content,
                'sms_type' => $smsType ?: self::SMS_TYPE_NOTIFY,
                'sender' => $finalSender,
            ];

            if ($this->requiresSender($payload['sms_type'])) {
                if (empty($finalSender)) {
                    LogHelper::error('SpeedSmsService::sendSms missing sender for sms type', null, [
                        'sms_type' => $payload['sms_type'],
                        'to' => $payload['to'],
                    ]);
                    throw new ServiceException(__('error.speedsms_sender_required'));
                }

                $payload['sender'] = $finalSender;
            }

            LogHelper::debug('SpeedSmsService::sendSms request', [
                'endpoint' => self::ROOT_URL . '/sms/send',
                'to' => $payload['to'],
                'content' => $payload['content'],
                'sms_type' => $payload['sms_type'],
                'sender' => $payload['sender'] ?? null,
            ]);

            $this->logUserInfoSnapshot();

            $response = Http::withBasicAuth($this->configs['token'], 'x')
                ->acceptJson()
                ->post(self::ROOT_URL . '/sms/send', $payload);

            $data = $response->json();

            if (!$this->isSuccessfulResponse($response->successful(), $data)) {
                LogHelper::error('SpeedSmsService::sendSms failed', null, [
                    'payload' => $payload,
                    'response' => $data,
                    'status' => $response->status(),
                ]);
                throw new ServiceException($data['message'] ?? __('error.could_not_send_otp'));
            }

            LogHelper::debug('SpeedSmsService::sendSms success', [
                'payload' => $payload,
                'response' => $data,
                'status' => $response->status(),
            ]);

            return $data;
        }, logServiceError: true);
    }

    public function createPin(string $phoneNumber, string $content): ServiceReturn
    {
        return $this->execute(function () use ($phoneNumber, $content) {
            $this->boot();

            if (empty($this->configs['app_id'])) {
                throw new ServiceException(__('error.could_not_send_otp'));
            }

            $payload = [
                'to' => Helper::formatPhone($phoneNumber),
                'content' => $content,
                'app_id' => $this->configs['app_id'],
            ];

            LogHelper::debug('SpeedSmsService::createPin request', [
                'endpoint' => self::ROOT_URL . '/pin/create',
                'payload' => $payload,
            ]);

            $response = Http::withBasicAuth($this->configs['token'], 'x')
                ->acceptJson()
                ->post(self::ROOT_URL . '/pin/create', $payload);

            $data = $response->json();

            if (!$this->isSuccessfulResponse($response->successful(), $data)) {
                LogHelper::error('SpeedSmsService::createPin failed', null, [
                    'payload' => $payload,
                    'response' => $data,
                    'status' => $response->status(),
                ]);
                throw new ServiceException($data['message'] ?? __('error.could_not_send_otp'));
            }

            LogHelper::debug('SpeedSmsService::createPin success', [
                'payload' => $payload,
                'response' => $data,
                'status' => $response->status(),
            ]);

            return $data;
        }, logServiceError: true);
    }

    public function verifyPin(string $phoneNumber, string $pinCode): ServiceReturn
    {
        return $this->execute(function () use ($phoneNumber, $pinCode) {
            $this->boot();

            if (empty($this->configs['app_id'])) {
                throw new ServiceException(__('error.could_not_send_otp'));
            }

            $payload = [
                'phone' => Helper::formatPhone($phoneNumber),
                'pin_code' => $pinCode,
                'app_id' => $this->configs['app_id'],
            ];

            LogHelper::debug('SpeedSmsService::verifyPin request', [
                'endpoint' => self::ROOT_URL . '/pin/verify',
                'payload' => $payload,
            ]);

            $response = Http::withBasicAuth($this->configs['token'], 'x')
                ->acceptJson()
                ->post(self::ROOT_URL . '/pin/verify', $payload);

            $data = $response->json();

            if (!$this->isSuccessfulResponse($response->successful(), $data)) {
                LogHelper::error('SpeedSmsService::verifyPin failed', null, [
                    'payload' => $payload,
                    'response' => $data,
                    'status' => $response->status(),
                ]);
                throw new ServiceException($data['message'] ?? __('error.could_not_send_otp'));
            }

            LogHelper::debug('SpeedSmsService::verifyPin success', [
                'payload' => $payload,
                'response' => $data,
                'status' => $response->status(),
            ]);

            return $data;
        }, logServiceError: true);
    }

    protected function boot(): void
    {
        if ($this->isBooted) {
            return;
        }

        $this->configs = [
            'token' => config('services.speedsms.token'),
            'sender' => config('services.speedsms.sender'),
            'sms_type' => (int) config('services.speedsms.sms_type', self::SMS_TYPE_GATEWAY),
            'app_id' => config('services.speedsms.app_id'),
        ];

        if (empty($this->configs['token'])) {
            LogHelper::error('SpeedSmsService::boot missing token');
            throw new ServiceException(__('error.could_not_send_otp'));
        }

        LogHelper::debug('SpeedSmsService::boot success', [
            'sender' => $this->configs['sender'],
            'sms_type' => $this->configs['sms_type'],
            'has_app_id' => !empty($this->configs['app_id']),
        ]);

        $this->isBooted = true;
    }

    protected function otpMessage(string $otp, ?UserOtpType $type = null): string
    {
        return match ($type) {
            UserOtpType::FORGOT_PASSWORD => __('auth.sms.forgot_password_otp_message', ['otp' => $otp]),
            default => __('auth.sms.otp_message', ['otp' => $otp]),
        };
    }

    protected function requiresSender(int $smsType): bool
    {
        return in_array($smsType, [
            self::SMS_TYPE_BRANDNAME,
            self::SMS_TYPE_NOTIFY,
            self::SMS_TYPE_GATEWAY,
        ], true);
    }

    protected function isSuccessfulResponse(bool $httpSuccessful, mixed $data): bool
    {
        if (!$httpSuccessful || !is_array($data)) {
            return false;
        }

        return strtolower((string) ($data['status'] ?? '')) === self::RESPONSE_STATUS_SUCCESS;
    }

    protected function logUserInfoSnapshot(): void
    {
        try {
            $response = Http::withBasicAuth($this->configs['token'], 'x')
                ->acceptJson()
                ->get(self::ROOT_URL . '/user/info');

            $data = $response->json();

            if ($this->isSuccessfulResponse($response->successful(), $data)) {
                LogHelper::debug('SpeedSmsService::userInfo success', [
                    'status' => $response->status(),
                    'response' => $data,
                ]);
                return;
            }

            LogHelper::error('SpeedSmsService::userInfo failed', null, [
                'status' => $response->status(),
                'response' => $data,
            ]);
        } catch (\Throwable $exception) {
            LogHelper::error('SpeedSmsService::userInfo exception', $exception);
        }
    }
}
