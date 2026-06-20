<?php

namespace App\Services;

use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioVerifyService extends BaseService
{
    private const CHANNEL_SMS = 'sms';

    protected bool $isBooted = false;
    protected ?Client $client = null;

    /**
     * @var array{
     *     account_sid: string|null,
     *     auth_token: string|null,
     *     service_sid: string|null,
     *     channel: string|null,
     * }
     */
    protected array $configs = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function sendOtp(string $phoneNumber): ServiceReturn
    {
        return $this->execute(function () use ($phoneNumber) {
            $this->boot();

            $formattedPhone = $this->formatToE164($phoneNumber);
            if (!$formattedPhone) {
                throw new ServiceException(__('error.invalid_phone_number'));
            }

            $payload = [
                'to' => $formattedPhone,
                'channel' => $this->configs['channel'] ?: self::CHANNEL_SMS,
            ];

            LogHelper::debug('TwilioVerifyService::sendOtp request', [
                'service_sid' => $this->configs['service_sid'],
                'payload' => $payload,
            ]);

            try {
                $verification = $this->client
                    ->verify
                    ->v2
                    ->services($this->configs['service_sid'])
                    ->verifications
                    ->create($payload['to'], $payload['channel']);
            } catch (TwilioException $exception) {
                LogHelper::error('TwilioVerifyService::sendOtp failed', $exception, [
                    'service_sid' => $this->configs['service_sid'],
                    'payload' => $payload,
                ]);
                throw new ServiceException($exception->getMessage() ?: __('error.could_not_send_otp'));
            }

            $data = [
                'sid' => $verification->sid ?? null,
                'status' => $verification->status ?? null,
                'to' => $verification->to ?? null,
                'channel' => $verification->channel ?? null,
                'send_code_attempts' => $verification->sendCodeAttempts ?? null,
                'date_created' => isset($verification->dateCreated) ? $verification->dateCreated->format(DATE_ATOM) : null,
                'date_updated' => isset($verification->dateUpdated) ? $verification->dateUpdated->format(DATE_ATOM) : null,
            ];

            if (!$this->isSuccessfulResponse($data)) {
                LogHelper::error('TwilioVerifyService::sendOtp unexpected response', null, [
                    'service_sid' => $this->configs['service_sid'],
                    'payload' => $payload,
                    'response' => $data,
                ]);
                throw new ServiceException(__('error.could_not_send_otp'));
            }

            LogHelper::debug('TwilioVerifyService::sendOtp success', [
                'service_sid' => $this->configs['service_sid'],
                'payload' => $payload,
                'response' => $data,
            ]);

            return $data;
        }, logServiceError: true);
    }

    public function verifyOtp(string $phoneNumber, string $otpCode): ServiceReturn
    {
        return $this->execute(function () use ($phoneNumber, $otpCode) {
            $this->boot();

            $formattedPhone = $this->formatToE164($phoneNumber);
            if (!$formattedPhone) {
                throw new ServiceException(__('error.invalid_phone_number'));
            }

            $payload = [
                'to' => $formattedPhone,
                'code' => $otpCode,
            ];

            LogHelper::debug('TwilioVerifyService::verifyOtp request', [
                'service_sid' => $this->configs['service_sid'],
                'payload' => $payload,
            ]);

            try {
                $verificationCheck = $this->client
                    ->verify
                    ->v2
                    ->services($this->configs['service_sid'])
                    ->verificationChecks
                    ->create($payload);
            } catch (TwilioException $exception) {
                LogHelper::error('TwilioVerifyService::verifyOtp failed', $exception, [
                    'service_sid' => $this->configs['service_sid'],
                    'payload' => $payload,
                ]);
                throw new ServiceException($exception->getMessage() ?: __('error.could_not_send_otp'));
            }

            $data = [
                'sid' => $verificationCheck->sid ?? null,
                'status' => $verificationCheck->status ?? null,
                'to' => $verificationCheck->to ?? null,
                'channel' => $verificationCheck->channel ?? null,
                'date_created' => isset($verificationCheck->dateCreated) ? $verificationCheck->dateCreated->format(DATE_ATOM) : null,
                'date_updated' => isset($verificationCheck->dateUpdated) ? $verificationCheck->dateUpdated->format(DATE_ATOM) : null,
            ];

            if (!$this->isApprovedResponse($data)) {
                LogHelper::error('TwilioVerifyService::verifyOtp unexpected response', null, [
                    'service_sid' => $this->configs['service_sid'],
                    'payload' => $payload,
                    'response' => $data,
                ]);
                throw new ServiceException(__('auth.error.otp_invalid_or_expired'));
            }

            LogHelper::debug('TwilioVerifyService::verifyOtp success', [
                'service_sid' => $this->configs['service_sid'],
                'payload' => $payload,
                'response' => $data,
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
            'account_sid' => config('services.twilio_verify.account_sid'),
            'auth_token' => config('services.twilio_verify.auth_token'),
            'service_sid' => config('services.twilio_verify.service_sid'),
            'channel' => config('services.twilio_verify.channel', self::CHANNEL_SMS),
        ];

        if (
            empty($this->configs['account_sid'])
            || empty($this->configs['auth_token'])
            || empty($this->configs['service_sid'])
        ) {
            LogHelper::error('TwilioVerifyService::boot missing configuration', null, [
                'has_account_sid' => !empty($this->configs['account_sid']),
                'has_auth_token' => !empty($this->configs['auth_token']),
                'has_service_sid' => !empty($this->configs['service_sid']),
            ]);
            throw new ServiceException(__('error.could_not_send_otp'));
        }

        LogHelper::debug('TwilioVerifyService::boot success', [
            'service_sid' => $this->configs['service_sid'],
            'channel' => $this->configs['channel'],
        ]);

        $this->client = new Client(
            $this->configs['account_sid'],
            $this->configs['auth_token']
        );

        $this->isBooted = true;
    }

    protected function formatToE164(string $phoneNumber): ?string
    {
        $formatted = Helper::formatPhone($phoneNumber);
        if (!Helper::isValidPhone($formatted)) {
            return null;
        }

        return '+' . $formatted;
    }

    protected function isSuccessfulResponse(mixed $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        return ($data['status'] ?? null) === 'pending';
    }

    protected function isApprovedResponse(mixed $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        return ($data['status'] ?? null) === 'approved';
    }
}
