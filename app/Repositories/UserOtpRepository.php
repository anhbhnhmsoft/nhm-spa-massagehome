<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\UserOtpType;
use App\Models\UserOtp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserOtpRepository extends BaseRepository
{

    protected function getModel(): string
    {
        return UserOtp::class;
    }


    /**
     * Lấy OTP chưa được xác thực mới nhất cho số điện thoại và loại OTP
     * @param string $phone
     * @param UserOtpType $type
     * @return UserOtp|null
     */
    public function getLastOtpNotVerified(string $phone, UserOtpType $type): ?UserOtp
    {
        return $this->query()
            ->where('phone', $phone)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->where('expired_at', '>', now())
            ->latest()
            ->first();
    }


    /**
     * Tính tổng số lần gửi OTP trong ngày hôm nay
     * @param string $phone
     * @param UserOtpType $type
     * @return int
     */
    public function sumTotalSendOTPToday(string $phone, UserOtpType $type): int
    {
        $totalSentToday = $this->query()
            ->where('phone', $phone)
            ->where('type', $type)
            ->whereDate('created_at', now()->toDateString())
            ->sum('send_count');

        return $totalSentToday ?? 0;
    }

    /**
     * Kiểm tra xem có OTP nào vừa được xác thực thành công không
     * OTP xác thực chỉ có hiệu lực trong 30 phút để hoàn tất đăng ký
     */
    public function getLatestVerifiedOtp(string $phone, UserOtpType $type): ?UserOtp
    {
        return $this->query()
            ->where('phone', $phone)
            ->where('type', $type)
            ->whereNotNull('verified_at')
            // OTP xác thực chỉ có hiệu lực trong 30 phút để hoàn tất đăng ký
            ->where('verified_at', '>', now()->subMinutes(30))
            ->latest()
            ->first();
    }

    /**
     * Tạo mới hoặc cập nhật OTP hiện có
     * @param string $phone
     * @param UserOtpType $type
     * @param string $otp
     * @param string $ip
     * @return Model
     */
    public function createOrUpdateOtp(
        string $phone,
        UserOtpType $type,
        string $otp,
        string $ip
    ) {
        $otpModel = $this->query()
            ->where('phone', $phone)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->first();
        if ($otpModel) {
            $otpModel->update([
                'otp_hash'     => Hash::make($otp),
                'expired_at'   => now()->addMinutes(5),
                'last_sent_at' => now(),
                'ip_address'   => $ip,
                'attempts'     => 0,
            ]);

            $otpModel->increment('send_count');

            return $otpModel;
        }
        return $this->query()->create([
            'phone'        => $phone,
            'type'         => $type,
            'otp_hash'     => Hash::make($otp),
            'expired_at'   => now()->addMinutes(5),
            'last_sent_at' => now(),
            'send_count'   => 1,
            'ip_address'   => $ip,
            'attempts'     => 0,
        ]);
    }
}
