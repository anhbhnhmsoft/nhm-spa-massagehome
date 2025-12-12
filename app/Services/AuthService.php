<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\UserRole;
use App\Models\Service;
use App\Models\User;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class AuthService extends BaseService
{
    protected int $otpTtl = 15;     // OTP tồn tại 1 phút
    protected int $blockTime = 60;   // Khóa 60 phút (tránh gửi OTP quá nhiều)
    protected int $maxAttempts = 5;  // Tối đa 5 lần thử sai
    protected int $maxResendOtp = 3;  // Tối đa 3 lần gửi OTP
    protected int $registerTimeout = 30;  // Thời gian chờ sau khi đăng ký

    public function __construct(
        protected UserRepository        $userRepository,
        protected UserProfileRepository $userProfileRepository,
        protected WalletRepository      $walletRepository,
    ) {
        parent::__construct();
    }

    /**
     * Xác thực đăng nhập bằng số điện thoại.
     * Nếu tồn tại tài khoản với số điện thoại này, thì sẽ cần yêu cầu thêm mật khẩu, còn không sẽ gửi OTP đăng ký.
     * @param string $phone
     * @return ServiceReturn
     */
    public function authenticate(string $phone): ServiceReturn
    {
        try {
            // Kiểm tra xem số điện thoại có tồn tại không
            $user = $this->userRepository->isPhoneVerified($phone);
            if ($user) {
                // nếu có user thì yêu cầu thêm mật khẩu
                return ServiceReturn::success(data: [
                    'need_register' => false,
                ]);
            } else {
                // Kiểm tra xem số điện thoại có đang có OTP đang chờ xác thực không
                if (Caching::hasCache(key: CacheKey::CACHE_KEY_OTP_REGISTER, uniqueKey: $phone)) {
                    return ServiceReturn::error(message: __('auth.error.already_sent'));
                }
                // Tạo OTP đăng ký và lưu vào cache
                $this->createCacheRegisterOtp($phone);

                // nếu không có user thì yêu cầu đăng ký
                return ServiceReturn::success(data: [
                    'need_register' => true,
                    'expire_minutes' => $this->otpTtl,
                ]);
            }
        } catch (ServiceException $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@authenticate",
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
            if (!Caching::hasCache(key: CacheKey::CACHE_KEY_OTP_REGISTER_ATTEMPTS, uniqueKey: $phone)) {
                Caching::setCache(
                    key: CacheKey::CACHE_KEY_OTP_REGISTER_ATTEMPTS,
                    value: 0,
                    uniqueKey: $phone,
                    expire: $this->otpTtl
                );
            }
            $attempts = Caching::incrementCache(key: CacheKey::CACHE_KEY_OTP_REGISTER_ATTEMPTS, uniqueKey: $phone);
            if ($attempts >= $this->maxAttempts) {
                return ServiceReturn::error(message: __('auth.error.attempts_left', ['minutes' => $this->blockTime]));
            }

            // Lấy OTP từ cache
            $cacheData = Caching::getCache(key: CacheKey::CACHE_KEY_OTP_REGISTER, uniqueKey: $phone);
            if (!$cacheData) {
                return ServiceReturn::error(message: __('auth.error.not_sent'));
            }

            if ($cacheData['otp'] != $otp) {
                return ServiceReturn::error(message: __('auth.error.invalid_otp'));
            }

            // Xác thực thành công, xóa OTP khỏi cache
            Caching::deleteCache(key: CacheKey::CACHE_KEY_OTP_REGISTER, uniqueKey: $phone);
            Caching::deleteCache(key: CacheKey::CACHE_KEY_OTP_REGISTER_ATTEMPTS, uniqueKey: $phone);
            Caching::deleteCache(key: CacheKey::CACHE_KEY_RESEND_REGISTER_OTP, uniqueKey: request()->ip() || request()->userAgent() || $phone);

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
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@verifyOtpRegister",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function resendOtpRegister(string $phone): ServiceReturn
    {
        try {
            // Tạo OTP đăng ký và lưu vào cache
            $this->createCacheRegisterOtp($phone);

            return ServiceReturn::success(data: [
                'expire_minutes' => $this->otpTtl,
            ]);
        } catch (ServiceException $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@resendOtpRegister",
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
    ): ServiceReturn {
        DB::beginTransaction();
        try {
            // Kiểm tra token
            if (!Caching::hasCache(key: CacheKey::CACHE_KEY_REGISTER_TOKEN, uniqueKey: $token)) {
                throw new ServiceException(message: __('auth.error.invalid_token_register'));
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
                'last_login_at' => now(),
            ]);

            // Tạo user profile
            $this->userProfileRepository->create([
                'user_id' => $user->id,
                'gender' => $gender?->value ?? Gender::MALE->value,
            ]);

            // Kiểm tra referral code
            if (!empty($referralCode)) {
                $userReferral = $this->userRepository->findByReferralCode($referralCode);
                if (!$userReferral) {
                    throw new ServiceException(message: __('auth.error.invalid_referral_code'));
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
        } catch (ServiceException $exception) {
            DB::rollBack();
            return ServiceReturn::error(message: $exception->getMessage());
        } catch (Exception $exception) {
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
    ): ServiceReturn {
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
            // Cập nhật last login time
            $user->last_login_at = now();
            $user->save();
            // Tạo token đăng nhập
            $token = $this->createTokenAuth($user);
            return ServiceReturn::success(data: [
                'token' => $token,
                'user' => $user,
            ]);
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@login",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Đăng nhập cho admin
     * @param string $phone
     * @param string $password
     * @return ServiceReturn
     */
    public function loginAdmin(
        string $phone,
        string $password,
    ): ServiceReturn {
        try {
            // Kiểm tra user có tồn tại không
            $user = $this->userRepository->findByPhone($phone);
            if (!$user) {
                return ServiceReturn::error(message: __('auth.error.invalid_login'));
            }
            if ($user->role != UserRole::ADMIN->value) {
                return ServiceReturn::error(message: __('auth.error.invalid_login'));
            }
            // Kiểm tra password
            if (!Hash::check($password, $user->password)) {
                return ServiceReturn::error(message: __('auth.error.invalid_login'));
            }
            // // Kiểm tra user có bị khóa không
            // if ($user->is_active == false) {
            //     return ServiceReturn::error(message: __('auth.error.disabled'));
            // }
            // // Cập nhật last login time
            // $user->last_login_at = now();
            // $user->save();

            Auth::login($user);

            return ServiceReturn::success(data: [
                'user' => $user,
            ]);
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@loginAdmin",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy thông tin user hiện tại.
     * @return ServiceReturn
     */
    public function user(): ServiceReturn
    {
        try {
            $user = Auth::user();
            $user->last_login_at = now();
            $user->save();
            return ServiceReturn::success(data: [
                'user' => $user,
            ]);
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@user",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Cập nhật ngôn ngữ cho user.
     * @param Language $language
     * @return ServiceReturn
     */
    public function setLanguage(
        Language $language,
    ): ServiceReturn {
        try {
            $user = Auth::user();
            $user->update([
                'language' => $language->value,
            ]);
            $user->save();
            return ServiceReturn::success(message: __('auth.success.set_language'));
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@setLanguage",
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
     * @return string
     */
    protected function createTokenAuth(User $user): string
    {
        return $user->createToken(
            name: 'api-token',
            abilities: ['*'],
            expiresAt: now()->addDays(30),
        )->plainTextToken;
    }

    /**
     * Tạo OTP đăng ký và lưu vào cache.
     * @param string $phone
     * @throws ServiceException
     */
    protected function createCacheRegisterOtp(string $phone): void
    {
        //        $otp = rand(100000, 999999);
        $otp = 123456; // Test
        // Set OTP limit số lần gửi lại
        if (!Caching::hasCache(key: CacheKey::CACHE_KEY_RESEND_REGISTER_OTP, uniqueKey: $phone)) {
            Caching::setCache(
                key: CacheKey::CACHE_KEY_RESEND_REGISTER_OTP,
                value: 0,
                uniqueKey: request()->ip() || request()->userAgent() || $phone,
                expire: $this->blockTime
            );
        }
        $attempts = Caching::incrementCache(key: CacheKey::CACHE_KEY_RESEND_REGISTER_OTP, uniqueKey: $phone);

        // Kiểm tra số lần gửi lại OTP
        if ($attempts > $this->maxResendOtp) {
            throw new ServiceException(__('auth.error.resend_otp', ['minutes' => $this->blockTime]));
        }
        // Lưu OTP vào cache
        Caching::setCache(
            key: CacheKey::CACHE_KEY_OTP_REGISTER,
            value: [
                'otp' => $otp,
                'phone' => $phone,
            ],
            uniqueKey: $phone,
            expire: $this->otpTtl
        );
    }

    /**
     * Cập nhật heartbeat cho user.
     * @return ServiceReturn
     */
    public function heartbeat(): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('auth.error.unauthorized'));
            }

            Caching::setCache(
                key: CacheKey::CACHE_USER_HEARTBEAT,
                value: true,
                uniqueKey: $user->id,
                expire: 5 // 5 phút
            );
            // --- TẦNG 2: DATABASE (LỊCH SỬ) ---
            // Kiểm tra heartbeat có quá 15 phút không
            $now = now();
            $lastUpdate = $user->last_login_at ? Carbon::parse($user->last_login_at) : $now->subYears(1);
            if ($lastUpdate->diffInMinutes($now) > 15) {
                $user->timestamps = false; // Không đổi updated_at
                $user->last_login_at = $now;
                $user->save();
            }
            return ServiceReturn::success();
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@heartbeat",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Cập nhật device cho user.
     * @param string $token
     * @param string $deviceId
     * @param string|null $platform
     * @param string|null $deviceName
     * @return ServiceReturn
     */
    public function setDevice(
        string  $token,
        string  $deviceId,
        ?string $platform = null,
        ?string $deviceName = null,
    ): ServiceReturn {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('auth.error.unauthorized'));
            }
            $user->devices()->updateOrCreate(
                [
                    'device_id' => $deviceId,
                ],
                [
                    'token'       => $token,
                    'device_type'  => $platform ?? 'unknown',
                    'device_name' => $deviceName ?? 'Unknown Device',
                    'updated_at'  => now(),
                ]
            );
            return ServiceReturn::success();
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@setDevice",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Cập nhật thông tin user.
     * @param array $data
     * @return ServiceReturn
     */
    public function editInfoUser(array $data): ServiceReturn
    {
        DB::beginTransaction();
        try {
            /** @var User $user */
            $user = Auth::user();

            $userUpdateData = [];

            if (isset($data['name'])) {
                $userUpdateData['name'] = $data['name'];
            }

            if (isset($data['address'])) {
                $userUpdateData['address'] = $data['address'];
            }

            if (!empty($data['password'])) {
                $userUpdateData['password'] = Hash::make($data['password']);
            }

            if (!empty($userUpdateData)) {
                $user->fill($userUpdateData)->save();
            }

            $profileUpdateData = [];

            if (isset($data['bio'])) {
                $profileUpdateData['bio'] = $data['bio'];
            }
            if (isset($data['gender'])) {
                $profileUpdateData['gender'] = $data['gender'];
            }
            if (isset($data['date_of_birth'])) {
                $profileUpdateData['date_of_birth'] = $data['date_of_birth'];
            }

            if (!empty($profileUpdateData)) {
                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $profileUpdateData
                );
            }
            DB::commit();
            return ServiceReturn::success($user);
        } catch (\Throwable $e) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi AuthService@editInfoUser",
                ex: $e
            );

            return ServiceReturn::error(__('common_error.server_error'));
        }
    }
}
