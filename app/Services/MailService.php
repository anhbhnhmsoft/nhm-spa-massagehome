<?php

namespace App\Services;


use App\Core\Service\BaseService;
use App\Enums\ConfigName;
use App\Enums\Language;
use App\Mail\OtpEmail;
use Illuminate\Support\Facades\Mail;

class MailService extends BaseService
{
    public function __construct(
        protected ConfigService $configService,
    )
    {
        parent::__construct();
    }

    /**
     * Tạo một instance của Mailer với cấu hình
     * @return \Illuminate\Mail\Mailer
     * @throws \App\Core\Service\ServiceException
     */
    protected function bootMailer()
    {
        $host = $this->configService->getConfigValue(ConfigName::MAIL_HOST);
        $port = $this->configService->getConfigValue(ConfigName::MAIL_PORT);
        $username = $this->configService->getConfigValue(ConfigName::MAIL_USERNAME);
        $password = $this->configService->getConfigValue(ConfigName::MAIL_PASSWORD);
        $encryption = $this->configService->getConfigValue(ConfigName::MAIL_ENCRYPTION);
        $fromAddress = $this->configService->getConfigValue(ConfigName::MAIL_FROM_ADDRESS);
        $fromName = $this->configService->getConfigValue(ConfigName::MAIL_FROM_NAME);
        $fromAddress = $fromAddress ?: $username;
        $fromName = $fromName ?: config('app.name');
        $mailer = Mail::build([
            'transport' => 'smtp',
            'host' => $host,
            'port' => (int)$port,
            'encryption' => $encryption ?: null,
            'username' => $username,
            'password' => $password,
            'timeout' => 30,
        ]);

        // 2. Ép Mailer này luôn sử dụng địa chỉ người gửi đã cấu hình
        $mailer->alwaysFrom($fromAddress, $fromName);
        return $mailer;
    }

    /**
     * Gửi email OTP
     * @param string $email
     * @param string $otp
     * @return \App\Core\Service\ServiceReturn
     * @throws \Throwable
     */
    public function sendOTP(string $email, string $otp)
    {
        return $this->execute(
            callback: function () use ($email, $otp) {
                $lang = app()->getLocale();
                $lang = Language::tryFrom($lang) ?? Language::VIETNAMESE;
                $this->bootMailer()
                    ->to($email)
                    ->send(new OtpEmail($otp, $lang));
            },
            catchCallback: function ($e) {
                dd($e);
            }
        );
    }

}
