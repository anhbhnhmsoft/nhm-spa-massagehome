<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\ConfigName;
use App\Enums\DirectFile;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserDeviceRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redis as RedisFacade;
use Illuminate\Support\Facades\Storage;


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
        protected UserDeviceRepository $userDeviceRepository,
        protected ZaloService $zaloService,
        protected ConfigService $configService
    )
    {
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
     * @param ?Gender $gender -- Giới tính.
     * @param ?Language $language -- Ngôn ngữ.
     * @return ServiceReturn
     */
    public function register(
        string    $token,
        string    $password,
        string    $name,
        ?Gender   $gender,
        ?Language $language
    ): ServiceReturn
    {
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
                'password' => Hash::make($password),
                'name' => $name,
                'language' => $language?->value ?? Language::VIETNAMESE->value,
                // Ban đầu user là customer
                'role' => UserRole::CUSTOMER->value,
                'last_login_at' => now(),
            ]);

            // Tạo user profile
            $this->userProfileRepository->create([
                'user_id' => $user->id,
                'gender' => $gender?->value ?? Gender::MALE->value,
            ]);

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
            if (!$user->is_active) {
                return ServiceReturn::error(message: __('auth.error.disabled'));
            }
            // Cập nhật last login time
            $user->last_login_at = now();
            $user->save();
            // Tạo token đăng nhập
            $token = $this->createTokenAuth($user);
            // Lưu token vào Redis
            $this->setRedisAuthChatToken($token, $user);

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
    ): ServiceReturn
    {
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
            $user = auth('sanctum')->user();
            if (!$user->is_active) {
                $this->logout();
                return ServiceReturn::error(message: __('auth.error.unauthorized'));
            }
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
     * Lấy thông tin người dùng + các config về app
     * @return ServiceReturn
     */
    public function configApplication(): ServiceReturn
    {
        try {
            return ServiceReturn::success(data: [
                'maintenance' => config('services.application_mobile.maintenance'),
                'ios_version' => config('services.application_mobile.ios_version'),
                'android_version' => config('services.application_mobile.android_version'),
                'appstore_url' => config('services.store.appstore'),
                'chplay_url' => config('services.store.chplay'),
            ]);
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@checkAccess",
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
    ): ServiceReturn
    {
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
            $token = $user->currentAccessToken()->token;
            if (!$token || !$user) {
                throw new ServiceException(message: __('common_error.unauthorized'));
            }
            $now = now();
            Caching::setCache(
                key: CacheKey::CACHE_USER_HEARTBEAT,
                value: true,
                uniqueKey: $user->id,
                expire: $now->copy()->addMinutes(5) // 5 phút
            );
            // --- TẦNG 2: DATABASE (LỊCH SỬ) ---
            // Kiểm tra heartbeat có quá 15 phút không
            $lastUpdate = $user->last_login_at ? Carbon::parse($user->last_login_at) : $now;
            if ($lastUpdate->diffInMinutes($now) > 15) {
                $user->timestamps = false;
                $user->last_login_at = $now;
                $user->save();
            }

            // --- TẦNG 3: REDIS CHAT AUTH ---
            // Lưu token vào Redis
            $this->setRedisAuthChatToken($token, $user);

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
    ): ServiceReturn {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('auth.error.unauthorized'));
            }

            // Cơ chế "Chiếm quyền"
            // Tìm và xóa token này (hoặc device_id này) nếu nó đang thuộc về user KHÁC.
            // Điều này đảm bảo 1 thiết bị tại 1 thời điểm chỉ thuộc về 1 user duy nhất.
            $this->userDeviceRepository->query()
                ->where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->forceDelete(); // Xóa cứng (xóa luôn khỏi bảng)

            // Cập nhật hoặc tạo mới device_id này cho user hiện tại
            $this->userDeviceRepository->query()->updateOrCreate(
                [
                    'device_id' => $deviceId,
                    'user_id' => $user->id,
                ],
                [
                    'token' => $token,
                    'device_type' => $platform ?? 'unknown',
                    'updated_at' => now(),
                ]
            );
            DB::commit();
            return ServiceReturn::success();
        } catch (Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi AuthService@setDevice",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Chỉnh sửa avatar người dùng.
     * @param  $file
     * @return ServiceReturn
     */
    public function editInfoAvatar($file): ServiceReturn
    {
        try {
            $user = $this->userRepository->queryUser()->find(Auth::id());
            if (!$user) {
                return ServiceReturn::error(message: __('auth.error.unauthorized'));
            }
            if (!$file instanceof UploadedFile) {
                throw new ServiceException(__('error.file_invalid'));
            }
            $profile = $user->profile;
            $avatarPathNew = $file->store(DirectFile::makePathById(
                type: DirectFile::AVATAR_USER,
                id: $user->id
            ), 'public');
            if (!$avatarPathNew) {
                throw new ServiceException(__('common_error.server_error'));
            }
            // Xóa avatar cũ nếu có
            if ($profile->avatar_url && Storage::disk('public')->exists($profile->avatar_url)) {
                Storage::disk('public')->delete($profile->avatar_url);
            }
            // Cập nhật avatar_path trong bảng user_profiles
            $profile->avatar_url = $avatarPathNew;
            $profile->save();
            // Load lại quan hệ profile để trả về dữ liệu mới
            $user->load('profile');
            return ServiceReturn::success(
                data: $user
            );
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@editInfoAvatar",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Xóa avatar người dùng.
     * @return ServiceReturn
     */
    public function deleteAvatar(): ServiceReturn
    {
        try {
            $user = $this->userRepository->queryUser()->find(Auth::id());
            if (!$user) {
                return ServiceReturn::error(message: __('auth.error.unauthorized'));
            }
            $profile = $user->profile;
            // Xóa avatar cũ nếu có
            if ($profile->avatar_url && Storage::disk('public')->exists($profile->avatar_url)) {
                Storage::disk('public')->delete($profile->avatar_url);
            }
            // Cập nhật avatar_path trong bảng user_profiles
            $profile->avatar_url = null;
            $profile->save();
            // Load lại quan hệ profile để trả về dữ liệu mới
            $user->load('profile');
            return ServiceReturn::success(
                data: $user
            );
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@deleteAvatar",
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

            if (isset($data['old_password']) && isset($data['new_password'])) {
                if (!Hash::check($data['old_password'], $user->password)) {
                    return ServiceReturn::error(message: __('auth.error.wrong_password'));
                }
                $userUpdateData['password'] = Hash::make($data['new_password']);
            }

            if (isset($data['name'])) {
                $userUpdateData['name'] = $data['name'];
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
//            $reviewApplyUpdateData = [];
//            if (isset($data['language'])) {
//                $reviewApplyUpdateData['language'] = $data['language'];
//            }
//            if (!empty($reviewApplyUpdateData)) {
//                $user->reviewApplication()->updateOrCreate(
//                    ['user_id' => $user->id],
//                    ['status' => ReviewApplicationStatus::APPROVED->value],
//                    $reviewApplyUpdateData
//                );
//            }
            // Load lại quan hệ profile để trả về dữ liệu mới
            $user->load('profile');
            DB::commit();
            return ServiceReturn::success(
                data: $user
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi AuthService@editInfoUser",
                ex: $e
            );

            return ServiceReturn::error(__('common_error.server_error'));
        }
    }

    /**
     * Đăng xuất tài khoản.
     * @return ServiceReturn
     */
    public function logout(): ServiceReturn
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return ServiceReturn::error(message: __('error.unauthorized'));
            }
            $user->currentAccessToken()->delete();

            // Xóa tất cả các thiết bị đã đăng nhập của user
            $this->userDeviceRepository->query()
                ->where('user_id', $user->id)
                ->forceDelete(); // Xóa cứng (xóa luôn khỏi bảng)
            return ServiceReturn::success(
                message: __('auth.success.logout'),
            );
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@logout",
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

        if (config('app.debug')) {
            $otp = 123456;
        } else {
            $otp = rand(100000, 999999);
            $result = $this->zaloService->pushOTPAuthorize($phone, $otp);
            if ($result->isError()) {
                throw new ServiceException($result->getMessage());
            }
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
     * Lưu token vào Redis cho việc xác thực chat.
     * @param string $token
     * @param User $user
     * @return void
     */
    protected function setRedisAuthChatToken(string $token, $user): void
    {
        $key = config('services.node_server.channel_chat_auth') . ":{$token}";
        $redisPayload = [
            'id' => (string)$user->id,
            'name' => $user->name,
            'role' => $user->role,
        ];
        RedisFacade::connection()->setex(
            $key,
            60 * 60 * 1, // 1 giờ
            json_encode($redisPayload)
        );
    }

    /**
     * Khóa tài khoản.
     * @return ServiceReturn
     */
    public function lockAccount(): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('error.unauthorized'));
            }
            $user->is_active = false;
            $user->save();
            $user->currentAccessToken()->delete();
            return ServiceReturn::success(
                message: __('auth.success.lock_account'),
            );
        } catch (Exception $exception) {
            LogHelper::error(
                message: "Lỗi AuthService@lockAccount",
                ex: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}
