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
     * OTP xác thực chỉ có hiệu lực trong ? phút để hoàn tất đăng ký
     * @param string $phone - Số điện thoại
     * @param UserOtpType $type - Loại OTP
     * @param int $minutes - Thời gian hiệu lực OTP (mặc định là 30 phút)
     * @return UserOtp|null
     */
    public function getLatestVerifiedOtp(string $phone, UserOtpType $type, int $minutes): ?UserOtp
    {
        return $this->query()
            ->where('phone', $phone)
            ->where('type', $type)
            ->whereNotNull('verified_at')
            // OTP xác thực chỉ có hiệu lực trong $minutes phút để hoàn tất đăng ký
            ->where('verified_at', '>', now()->subMinutes($minutes))
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


    /**
     * Xoá OTP cũ khi đã xác thực thành công
     * @param string $phone
     * @param UserOtpType $type
     * @return void
     */
    public function deleteOtpHadVerified(string $phone, UserOtpType $type)
    {
        $this->query()
            ->where('phone', $phone)
            ->whereNotNull('verified_at')
            ->where('type', $type)
            ->delete();
    }


    public function deleteExpiredOtp(int $minutes)
    {
        $expirationTime = now()->subMinutes($minutes);

        // 1. Xóa các OTP chưa được xác thực và đã quá thời gian TTL
        $this->query()
            ->whereNull('verified_at')
            ->where('created_at', '<', $expirationTime)
            ->delete();

        // Xóa các OTP đã xác thực nhưng đã cũ hơn 24h
        $this->query()
            ->whereNotNull('verified_at')
            ->where('verified_at', '<', now()->subDay())
            ->delete();
    }
}
