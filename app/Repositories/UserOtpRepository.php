<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\TypeAuthenticate;
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
     * @param string $identifier
     * @param UserOtpType $type
     * @return UserOtp|null
     */
    public function getLastOtpNotVerified(string $identifier, UserOtpType $type, TypeAuthenticate $typeAuthenticate): ?UserOtp
    {
        return $this->query()
            ->when($typeAuthenticate === TypeAuthenticate::PHONE, function ($query) use ($identifier) {
                $query->where('phone', $identifier);
            })
            ->when($typeAuthenticate === TypeAuthenticate::EMAIL, function ($query) use ($identifier) {
                $query->where('email', $identifier);
            })
            ->where('type', $type)
            ->whereNull('verified_at')
            ->where('expired_at', '>', now())
            ->latest()
            ->first();
    }


    /**
     * Tính tổng số lần gửi OTP trong ngày hôm nay
     * @param string $identifier
     * @param UserOtpType $type
     * @param TypeAuthenticate $typeAuthenticate
     * @return int
     */
    public function sumTotalSendOTPToday(string $identifier, UserOtpType $type, TypeAuthenticate $typeAuthenticate): int
    {
        $totalSentToday = $this->query()
            ->when($typeAuthenticate === TypeAuthenticate::PHONE, function ($query) use ($identifier) {
                $query->where('phone', $identifier);
            })
            ->when($typeAuthenticate === TypeAuthenticate::EMAIL, function ($query) use ($identifier) {
                $query->where('email', $identifier);
            })
            ->where('type', $type)
            ->whereDate('created_at', now()->toDateString())
            ->sum('send_count');

        return $totalSentToday ?? 0;
    }

    /**
     * Kiểm tra xem có OTP nào vừa được xác thực thành công không
     * OTP xác thực chỉ có hiệu lực trong ? phút để hoàn tất đăng ký
     * @param string $identifier - Số điện thoại hoặc email
     * @param UserOtpType $type - Loại OTP
     * @param int $minutes - Thời gian hiệu lực OTP (mặc định là 30 phút)
     * @param TypeAuthenticate $typeAuthenticate - Loại xác thực (số điện thoại hoặc email)
     * @return UserOtp|null
     */
    public function getLatestVerifiedOtp(string $identifier, UserOtpType $type, int $minutes, TypeAuthenticate $typeAuthenticate): ?UserOtp
    {
        return $this->query()
            ->when($typeAuthenticate === TypeAuthenticate::PHONE, function ($query) use ($identifier) {
                $query->where('phone', $identifier);
            })
            ->when($typeAuthenticate === TypeAuthenticate::EMAIL, function ($query) use ($identifier) {
                $query->where('email', $identifier);
            })
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
        string $identifier,
        UserOtpType $type,
        string $otp,
        string $ip,
        TypeAuthenticate $typeAuthenticate
    ) {
        $otpModel = $this->query()
            ->when($typeAuthenticate === TypeAuthenticate::PHONE, function ($query) use ($identifier) {
                $query->where('phone', $identifier);
            })
            ->when($typeAuthenticate === TypeAuthenticate::EMAIL, function ($query) use ($identifier) {
                $query->where('email', $identifier);
            })
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
        // Tạo mới OTP
        $column = match ($typeAuthenticate) {
            TypeAuthenticate::PHONE => 'phone',
            TypeAuthenticate::EMAIL => 'email',
        };
        return $this->query()->create([
            $column        => $identifier,
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
    public function deleteOtpHadVerified(string $identifier, UserOtpType $type, TypeAuthenticate $typeAuthenticate)
    {
        $column = match ($typeAuthenticate) {
            TypeAuthenticate::PHONE => 'phone',
            TypeAuthenticate::EMAIL => 'email',
        };
        $otp = $this->query()
            ->where($column, $identifier)
            ->whereNotNull('verified_at')
            ->where('type', $type)
            ->first();
        if ($otp) {
            $otp->delete();
        }else{
            throw new \Exception('OTP không tồn tại hoặc đã được xác thực');
        }
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
