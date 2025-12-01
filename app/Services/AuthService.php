<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class AuthService extends BaseService
{
    protected int $otpTtl = 5;     // OTP tồn tại 5 phút
    protected int $blockTime = 5;   // Khóa 5 phút (tránh gửi OTP quá nhiều)
    protected int $maxAttempts = 5;  // Tối đa 5 lần thử sai
    protected int $registerTimeout = 15;  // Thời gian chờ sau khi đăng ký

    public function __construct(
        protected UserRepository        $userRepository,
        protected UserProfileRepository $userProfileRepository,
        protected WalletRepository      $walletRepository,
    )
    {
        parent::__construct();
    }


    /**
     * Gửi OTP xác thực cho số điện thoại để đăng ký tài khoản.
     * @param string $phone
     * @return ServiceReturn
     */
    public function sendOtpRegister(string $phone): ServiceReturn
    {
        try {
            // Kiểm tra xem số điện thoại đã được đăng ký chưa
            if ($this->userRepository->isPhoneVerified($phone)) {
                return ServiceReturn::error(message: __('auth.error.phone_verified'));
            }

            // Kiểm tra xem số điện thoại có bị khóa không
            if (Caching::hasCache(key: CacheKey::CACHE_KEY_OTP_BLOCK, uniqueKey: $phone)) {
                return ServiceReturn::error(message: __('auth.error.blocked', ['minutes' => $this->blockTime]));
            }

            // Kiểm tra xem số điện thoại có đang có OTP đang chờ xác thực không
            if (Caching::hasCache(key: CacheKey::CACHE_KEY_OTP_AUTH, uniqueKey: $phone)) {
                return ServiceReturn::error(message: __('auth.error.already_sent'));
            }

            // Tạo OTP và lưu vào cache
            $otp = rand(100000, 999999);
            Caching::setCache(
                key: CacheKey::CACHE_KEY_OTP_AUTH,
                value: [
                    'otp' => $otp,
                    'phone' => $phone,
                ],
                uniqueKey: $phone,
                expire: $this->otpTtl
            );

            // Block request để tránh spam
            Caching::setCache(
                key: CacheKey::CACHE_KEY_OTP_BLOCK,
                value: $phone,
                uniqueKey: request()->ip() || request()->userAgent() || $phone,
                expire: $this->blockTime
            );
            // Gửi OTP đến số điện thoại (thay thế bằng logic gửi OTP thực tế)
            // $this->sendOtpToPhone($phone, $otp);

            return ServiceReturn::success(message: __('auth.success.otp_sent'));
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@sendOtpRegister",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Xác thực OTP đăng ký tài khoản.
     * @param string $phone
     * @param string $otp
     * @return ServiceReturn
     */
    public function verifyOtpRegister(string $phone, string $otp): ServiceReturn
    {
        try {
            // Set OTP limit
            if (!Caching::hasCache(key: CacheKey::CACHE_KEY_OTP_ATTEMPTS, uniqueKey: $phone)) {
                Caching::setCache(
                    key: CacheKey::CACHE_KEY_OTP_ATTEMPTS,
                    value: 0,
                    uniqueKey: $phone,
                    expire: $this->otpTtl
                );
            }
            $attempts = Caching::incrementCache(key: CacheKey::CACHE_KEY_OTP_ATTEMPTS, uniqueKey: $phone);
            if ($attempts >= $this->maxAttempts) {
                return ServiceReturn::error(message: __('auth.error.attempts_left', ['minutes' => $this->blockTime]));
            }

            // Lấy OTP từ cache
            $cacheData = Caching::getCache(key: CacheKey::CACHE_KEY_OTP_AUTH, uniqueKey: $phone);
            if (!$cacheData) {
                return ServiceReturn::error(message: __('auth.error.not_sent'));
            }

            if ($cacheData['otp'] != $otp) {
                return ServiceReturn::error(message: __('auth.error.invalid_otp'));
            }

            // Xác thực thành công, xóa OTP khỏi cache
            Caching::deleteCache(key: CacheKey::CACHE_KEY_OTP_AUTH, uniqueKey: $phone);
            Caching::deleteCache(key: CacheKey::CACHE_KEY_OTP_ATTEMPTS, uniqueKey: $phone);
            Caching::deleteCache(key: CacheKey::CACHE_KEY_OTP_BLOCK, uniqueKey: request()->ip() || request()->userAgent() || $phone);

            // Tạo 1 token dùng để đăng ký tài khoản
            $token = Helper::generateTokenRandom();
            Caching::setCache(
                key: CacheKey::CACHE_KEY_REGISTER_TOKEN,
                value: [
                    'phone' => $phone,
                ],
                uniqueKey: $token,
                expire: $this->registerTimeout,
            );
            return ServiceReturn::success(data: [
                'token' => $token,
            ]);
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@verifyOtpRegister",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Đăng ký tài khoản mới.
     * @param $token -- Token dùng để đăng ký tài khoản.
     * @param string $password -- Mật khẩu tài khoản.
     * @param string $name -- Tên người dùng.
     * @param ?string $referralCode -- Mã giới thiệu.
     * @param ?Gender $gender -- Giới tính.
     * @param ?Language $language -- Ngôn ngữ.
     * @return ServiceReturn
     */
    public function register(
        string    $token,
        string    $password,
        string    $name,
        ?string   $referralCode,
        ?Gender   $gender,
        ?Language $language
    ): ServiceReturn
    {
        DB::beginTransaction();
        try {
            // Kiểm tra token
            if (!Caching::hasCache(key: CacheKey::CACHE_KEY_REGISTER_TOKEN, uniqueKey: $token)) {
                return ServiceReturn::error(message: __('auth.error.invalid_token_register'));
            }
            $dataCache = Caching::getCache(key: CacheKey::CACHE_KEY_REGISTER_TOKEN, uniqueKey: $token);

            /**
             * Tạo user mới
             * @var User $user
             */
            $user = $this->userRepository->create([
                'phone' => $dataCache['phone'],
                'phone_verified_at' => now(),
                'password' => $password,
                'name' => $name,
                'language' => $language?->value ?? Language::VIETNAMESE->value,
                // Ban đầu user là customer
                'role' => UserRole::CUSTOMER->value,
                'referral_code' => Helper::generateReferCodeUser(UserRole::CUSTOMER),
            ]);

            // Tạo user profile
            $this->userProfileRepository->create([
                'user_id' => $user->id,
                'gender' => $gender?->value ?? Gender::OTHER->value,
            ]);

            // Kiểm tra referral code
            if (!empty($referralCode)) {
                $userReferral = $this->userRepository->findByReferralCode($referralCode);
                if (!$userReferral) {
                    DB::rollBack();
                    return ServiceReturn::error(message: __('auth.error.invalid_referral_code'));
                }
                // Cập nhật user referral
                $user->update([
                    'referred_by_user_id' => $userReferral->id,
                ]);
                // Xử lý affiliate sau ở đoạn này
            }

            // Tạo wallet cho user
            $this->walletRepository->create([
                'user_id' => $user->id,
            ]);

            // Tiến hành tạo token đăng nhập
            $token = $this->createTokenAuth($user);
            DB::commit();
            // Xóa token đăng ký khỏi cache
            Caching::deleteCache(key: CacheKey::CACHE_KEY_REGISTER_TOKEN, uniqueKey: $token);

            return ServiceReturn::success(data: [
                'token' => $token,
                'user' => $user,
            ]);

        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi AuthService@register",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Đăng nhập tài khoản
     * @param string $phone
     * @param string $password
     * @return ServiceReturn
     */
    public function login(
        string $phone,
        string $password,
    ): ServiceReturn
    {
        try {
            // Kiểm tra user có tồn tại không
            $user = $this->userRepository->findByPhone($phone);
            if (!$user) {
                return ServiceReturn::error(message: __('auth.error.invalid_login'));
            }
            // Kiểm tra password
            if (!Hash::check($password, $user->password)) {
                return ServiceReturn::error(message: __('auth.error.invalid_login'));
            }
            // Kiểm tra user có bị khóa không
            if ($user->disabled) {
                return ServiceReturn::error(message: __('auth.error.disabled'));
            }
            // Tạo token đăng nhập
            $token = $this->createTokenAuth($user);
            return ServiceReturn::success(data: [
                'token' => $token,
                'user' => $user,
            ]);
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@login",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function user(): ServiceReturn
    {
        try {
            $user = Auth::user();
            return ServiceReturn::success(data: [
                'user' => $user,
            ]);
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@user",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * -------- Private methods --------
     */

    /**
     * Tạo token đăng nhập cho user.
     * @param User $user
     * @return \Laravel\Sanctum\PersonalAccessToken
     */
    private function createTokenAuth(User $user): \Laravel\Sanctum\PersonalAccessToken
    {
        return $user->createToken('auth_token', ['*'], now()->addDays(30))->accessToken;
    }


}
