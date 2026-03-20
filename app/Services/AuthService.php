<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\DirectFile;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\TypeAuthenticate;
use App\Enums\UserOtpType;
use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\AdminUserRepository;
use App\Repositories\UserDeviceRepository;
use App\Repositories\UserOtpRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;


class AuthService extends BaseService
{
    protected const RETRY_AFTER_SECONDS = 60; // Số giây tối thiểu giữa 2 lần gửi OTP
    protected const MAX_SEND_PER_DAY = 3; // Số lần tối đa gửi OTP trong ngày
    protected const MAX_OTP_ATTEMPTS = 5; // Số lần thử sai tối đa trước khi khóa tài khoản
    protected const OTP_TTL_MINUTES = 30; // Thời gian hiệu lực OTP (mặc định là 30 phút)

    public function __construct(
        protected UserRepository        $userRepository,
        protected UserProfileRepository $userProfileRepository,
        protected WalletRepository      $walletRepository,
        protected UserDeviceRepository $userDeviceRepository,
        protected ConfigService $configService,
        protected ZaloService $zaloService,
        protected MailService $mailService,
        protected UserOtpRepository $userOtpRepository,
        protected AdminUserRepository $adminUserRepository,
    )
    {
        parent::__construct();
    }

    /**
     * Xác thực đăng nhập bằng số điện thoại.
     * Nếu tồn tại tài khoản với số điện thoại này, thì sẽ cần yêu cầu thêm mật khẩu, còn không sẽ gửi OTP đăng ký.
     * @param string $username
     * @param TypeAuthenticate $typeAuthenticate
     * @return ServiceReturn
     */
    public function authenticate(string $username, TypeAuthenticate $typeAuthenticate): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($username, $typeAuthenticate) {
                $user = match ($typeAuthenticate) {
                    TypeAuthenticate::PHONE => $this->userRepository->isPhoneVerified($username),
                    TypeAuthenticate::EMAIL => $this->userRepository->isEmailVerified($username),
                    default => throw new ServiceException(__('validation.type_authenticate.invalid')),
                };
                if ($user) {
                    // nếu có user thì yêu cầu thêm mật khẩu
                    return [
                        'case' => 'need_login',
                    ];
                }
                else {
                    // nếu không có user thì kiểm tra xem có OTP đăng ký chưa
                    $otpRecord = $this->getLastOtpNotVerified(
                        username: $username,
                        type: UserOtpType::REGISTER,
                        typeAuthenticate: $typeAuthenticate,
                    );
                    if ($otpRecord) {
                        // nếu có OTP đăng ký chưa thì yêu cầu nhập OTP
                        return [
                            'case' => 'need_re_enter_otp',
                            'last_sent_at' => $otpRecord->last_sent_at,
                            'retry_after_seconds' => self::RETRY_AFTER_SECONDS,
                        ];
                    }

                    // Kiểm tra có OTP nào đã xác thực rồi mà chưa đăng ký ko
                    $otpRecord = $this->userOtpRepository->getLatestVerifiedOtp(
                        identifier: $username,
                        type: UserOtpType::REGISTER,
                        minutes: self::OTP_TTL_MINUTES,
                        typeAuthenticate: $typeAuthenticate,
                    );
                    if ($otpRecord) {
                        // nếu có OTP đăng ký và xác thực rồi thì phải đăng ký mới được
                        return [
                            'case' => 'need_re_enter_register',
                        ];
                    }

                    // Tạo OTP đăng ký và lưu vào database
                    $otpRecord = $this->createOtp(
                        username: $username,
                        type: UserOtpType::REGISTER,
                        typeAuthenticate: $typeAuthenticate,
                    );

                    // nếu không có user thì yêu cầu đăng ký
                    return [
                        'case' => 'need_register',
                        'last_sent_at' => $otpRecord->last_sent_at,
                        'retry_after_seconds' => self::RETRY_AFTER_SECONDS,
                    ];
                }
            },
            useTransaction: true,
        );
    }

    /**
     * Gửi OTP quên mật khẩu
     * @param string $username
     * @param TypeAuthenticate $typeAuthenticate
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function forgotPassword(string $username, TypeAuthenticate $typeAuthenticate): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($username, $typeAuthenticate) {
                // Kiểm tra xem số điện thoại đã được xác thực chưa
                $user = $this->userRepository->findByUserVerified($username, $typeAuthenticate);
                if (!$user) {
                    throw new ServiceException(message: __('auth.error.user_not_verified'));
                }
                // Kiểm tra user có bị khóa không
                if (!$user->is_active) {
                    throw new ServiceException(message: __('auth.error.disabled'));
                }
                // nếu có user thì kiểm tra xem có OTP gửi chưa và còn thời hạn không
                $otpRecord = $this->getLastOtpNotVerified(
                    username: $username,
                    type: UserOtpType::FORGOT_PASSWORD,
                    typeAuthenticate: $typeAuthenticate,
                );
                if ($otpRecord) {
                    // nếu có OTP gửi rồi và còn thời hạn không thì yêu cầu nhập lại OTP
                    return [
                        'case' => 'need_re_enter_otp',
                        'last_sent_at' => $otpRecord->last_sent_at,
                        'retry_after_seconds' => self::RETRY_AFTER_SECONDS,
                    ];
                }
                // Kiểm tra xem có OTP đăng ký và xác thực rồi không
                $otpRecord = $this->userOtpRepository->getLatestVerifiedOtp(
                    identifier: $username,
                    type: UserOtpType::FORGOT_PASSWORD,
                    minutes: self::OTP_TTL_MINUTES,
                    typeAuthenticate: $typeAuthenticate,
                );
                if ($otpRecord) {
                    // nếu có OTP rồi thì cần nhập yêu cầu quên mật khẩu lại
                    return [
                        'case' => 'need_re_enter_reset_password',
                    ];
                }
                // Tạo OTP đăng ký và lưu vào database
                $otpRecord = $this->createOtp(
                    username: $username,
                    type: UserOtpType::FORGOT_PASSWORD,
                    typeAuthenticate: $typeAuthenticate,
                );
                return [
                    'case' => 'success',
                    'last_sent_at' => $otpRecord->last_sent_at,
                    'retry_after_seconds' => self::RETRY_AFTER_SECONDS,
                ];
            },
            useTransaction: true
        );
    }

    /**
     * Xác thực OTP quên mật khẩu.
     * @param string $phone
     * @param string $otp
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function verifyOtpForgotPassword(string $username, TypeAuthenticate $typeAuthenticate, string $otp): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($username, $typeAuthenticate, $otp) {
                // Xác thực OTP đăng ký tài khoản
                $this->verifyOtp(
                    username: $username,
                    type: UserOtpType::FORGOT_PASSWORD,
                    typeAuthenticate: $typeAuthenticate,
                    otpCode: $otp,
                );
                return ServiceReturn::success();
            },
            useTransaction: true
        );
    }

    /**
     * Xác thực OTP đăng ký tài khoản.
     * @param string $username
     * @param string $otp
     * @return ServiceReturn
     */
    public function verifyOtpRegister(string $username, TypeAuthenticate $typeAuthenticate, string $otp): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($username, $typeAuthenticate, $otp) {
                // Xác thực OTP đăng ký tài khoản
                $this->verifyOtp(
                    username: $username,
                    typeAuthenticate: $typeAuthenticate,
                    type: UserOtpType::REGISTER,
                    otpCode: $otp,
                );
                return ServiceReturn::success();
            },
            useTransaction: true
        );
    }

    /**
     * Gửi lại OTP đăng ký tài khoản.
     * @param string $phone
     * @return ServiceReturn
     */
    public function resendOtpRegister(string $username, TypeAuthenticate $typeAuthenticate): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($username, $typeAuthenticate) {
                $otpRecord = $this->createOtp(
                    username: $username,
                    type: UserOtpType::REGISTER,
                    typeAuthenticate: $typeAuthenticate,
                );
                return [
                    'last_sent_at' => $otpRecord->last_sent_at,
                    'retry_after_seconds' => self::RETRY_AFTER_SECONDS,
                ];
            },
            useTransaction: true
        );
    }

    /**
     * Gửi lại OTP đăng ký tài khoản.
     * @param string $username
     * @param TypeAuthenticate $typeAuthenticate
     * @return ServiceReturn
     */
    public function resendOtpForgotPassword(string $username, TypeAuthenticate $typeAuthenticate): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($username, $typeAuthenticate) {
                $otpRecord = $this->createOtp(
                    username: $username,
                    type: UserOtpType::FORGOT_PASSWORD,
                    typeAuthenticate: $typeAuthenticate,
                );
                return [
                    'last_sent_at' => $otpRecord->last_sent_at,
                    'retry_after_seconds' => self::RETRY_AFTER_SECONDS,
                ];
            },
            useTransaction: true
        );
    }

    /**
     * Đăng ký tài khoản mới.
     * @param string $username -- Tên đăng nhập dùng để đăng ký tài khoản.
     * @param TypeAuthenticate $typeAuthenticate -- Loại xác thực.
     * @param string $password -- Mật khẩu tài khoản.
     * @param string $name -- Tên người dùng.
     * @param ?Gender $gender -- Giới tính.
     * @param ?Language $language -- Ngôn ngữ.
     * @return ServiceReturn
     */
    public function register(
        string    $username,
        TypeAuthenticate $typeAuthenticate,
        string    $password,
        string    $name,
        ?Gender   $gender,
        ?Language $language
    ): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($username, $typeAuthenticate, $password, $name, $gender, $language) {
                // Kiểm tra xem số điện thoại đã được xác thực chưa
                $otpRecord = $this->userOtpRepository->getLatestVerifiedOtp(
                    identifier: $username,
                    type: UserOtpType::REGISTER,
                    minutes: self::OTP_TTL_MINUTES,
                    typeAuthenticate: $typeAuthenticate,
                );
                if (!$otpRecord) {
                    throw new ServiceException(__('auth.error.otp_not_verified'));
                }
                // Kiểm tra xem số điện thoại đã được đăng ký chưa
                $user = match ($typeAuthenticate) {
                    TypeAuthenticate::PHONE => $this->userRepository->isPhoneVerified($username),
                    TypeAuthenticate::EMAIL => $this->userRepository->isEmailVerified($username),
                    default => throw new ServiceException(__('validation.type_authenticate.invalid')),
                };
                if ($user) {
                    throw new ServiceException(message: __('auth.error.account_already_used'));
                }

                // Xóa OTP đã xác thực (tránh trường hợp người dùng nhập lại)
                $this->userOtpRepository->deleteOtpHadVerified($username, UserOtpType::REGISTER, $typeAuthenticate);

                /**
                 * Tạo user mới
                 * @var User $user
                 */
                $columnAuth = match ($typeAuthenticate) {
                    TypeAuthenticate::PHONE => 'phone',
                    TypeAuthenticate::EMAIL => 'email',
                };

                $user = $this->userRepository->create([
                    $columnAuth => $username,
                    $columnAuth . '_verified_at' => now(),
                    'password' => Hash::make($password),
                    'name' => $name,
                    'language' => $language?->value ?? Language::VIETNAMESE->value,
                    // Ban đầu user là customer
                    'role' => UserRole::CUSTOMER->value,
                    'last_login_at' => now(),
                ]);

                // Tạo user profile cho user
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

                return [
                    'token' => $token,
                    'user' => $user,
                ];
            },
            useTransaction: true
        );
    }

    /**
     * Đăng nhập tài khoản
     * @param string $username
     * @param TypeAuthenticate $typeAuthenticate
     * @param string $password
     * @return ServiceReturn
     */
    public function login(
        string $username,
        TypeAuthenticate $typeAuthenticate,
        string $password,
    ): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($username, $typeAuthenticate, $password) {
                $user = $this->userRepository->findByUserVerified($username, $typeAuthenticate);
                if (!$user) {
                    throw new ServiceException(message: __('auth.error.user_not_verified'));
                }
                // Kiểm tra password
                if (!Hash::check($password, $user->password)) {
                    throw new ServiceException(message: __('auth.error.invalid_login'));
                }
                // Kiểm tra user có bị khóa không
                if (!$user->is_active) {
                    throw new ServiceException(message: __('auth.error.disabled'));
                }
                // Xóa token cũ, các thiết bị khác sẽ bị đăng xuất
                $this->logoutAllDevices($user);
                // Lưu thông tin đăng nhập mới
                $user->last_login_at = now();
                $user->save();
                // Tải thông tin hồ sơ và địa chỉ mặc định của người dùng
                $user->load(['profile', 'primaryAddress']);
                // Tạo token đăng nhập
                $token = $this->createTokenAuth($user);
                return [
                    'token' => $token,
                    'user' => $user,
                ];
            },
            useTransaction: true
        );
    }

    /**
     * Đổi mật khẩu
     * @param string $username
     * @param TypeAuthenticate $typeAuthenticate
     * @param string $password
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function resetPassword(
        string $username,
        TypeAuthenticate $typeAuthenticate,
        string $password,
    ): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($username, $typeAuthenticate, $password) {
                // Kiểm tra xem số điện thoại đã được xác thực chưa
                $otpRecord = $this->userOtpRepository->getLatestVerifiedOtp(
                    identifier: $username,
                    type: UserOtpType::FORGOT_PASSWORD,
                    minutes: self::OTP_TTL_MINUTES,
                    typeAuthenticate: $typeAuthenticate,
                );
                if (!$otpRecord) {
                    throw new ServiceException(__('auth.error.otp_not_verified'));
                }

                // Kiểm tra xem số điện thoại đã được đăng ký chưa
                $user = $this->userRepository->findByUserVerified($username, $typeAuthenticate);
                if (!$user) {
                    throw new ServiceException(message: __('auth.error.user_not_verified'));
                }

                // Cập nhật mật khẩu mới
                $user->update([
                    'password' => Hash::make($password)
                ]);

                // Xóa OTP đã xác thực (tránh trường hợp người dùng nhập lại)
                $this->userOtpRepository->deleteOtpHadVerified($username, UserOtpType::FORGOT_PASSWORD, $typeAuthenticate);

                // Xóa token cũ, các thiết bị khác sẽ bị đăng xuất
                $this->logoutAllDevices($user);

                return ServiceReturn::success();
            },
            useTransaction: true
        );
    }

    /**
     * Đăng nhập cho admin
     * @param string $username
     * @param string $password
     * @return ServiceReturn
     */
    public function loginAdmin(
        string $username,
        string $password,
        bool $remember = false,
    ): ServiceReturn
    {
       return $this->execute(
           callback: function () use ($username, $password, $remember) {
               // Kiểm tra user có tồn tại không
               $user = $this->adminUserRepository->findByUsername($username);
               if (!$user) {
                   return ServiceReturn::error(message: __('auth.error.invalid_login'));
               }
               if (!$user->is_active) {
                   throw new ServiceException(message: __('auth.error.disabled'));
               }
               // Kiểm tra password
               if (!Hash::check($password, $user->password)) {
                   return ServiceReturn::error(message: __('auth.error.invalid_login'));
               }

               // Xác thực user
               Auth::guard('web')->login($user, $remember);

               return ServiceReturn::success(data: [
                   'user' => $user,
               ]);
           },
       );
    }

    /**
     * Lấy thông tin user hiện tại.
     * @return ServiceReturn
     */
    public function user(): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user->is_active) {
                $this->logout();
                return ServiceReturn::error(message: __('auth.error.unauthorized'));
            }
            $user->last_login_at = now();
            $user->save();
            $user->load(['profile', 'primaryAddress']);
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
            $user = auth('sanctum')->user();
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
                expire: 5 // 5 phút
            );
            // --- TẦNG 2: DATABASE (LỊCH SỬ) ---
            // Kiểm tra heartbeat có quá 15 phút không
            $lastUpdate = $user->last_login_at ? Carbon::parse($user->last_login_at) : $now;
            if ($lastUpdate->diffInMinutes($now) > 15) {
                $user->timestamps = false;
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
        return $this->execute(
            callback: function () {
                $user = auth('sanctum')->user();
                if (!$user) {
                    throw new ServiceException(message: __('error.unauthorized'));
                }
                // Xóa tất cả các thiết bị đã đăng nhập của user
                $this->logoutAllDevices($user);
                return ServiceReturn::success();
            },
            useTransaction: true
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
            $this->logoutAllDevices($user);
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

    /**
     * Xóa các OTP đã hết hạn.
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function deleteExpiredOtpRecord()
    {
        return $this->execute(
            callback: function () {
                $this->userOtpRepository->deleteExpiredOtp(self::OTP_TTL_MINUTES);
                return ServiceReturn::success();
            },
            useTransaction: true
        );
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
     * Xóa tất cả các thiết bị đã đăng nhập của user.
     * @param User $user
     * @return void
     */
    protected function logoutAllDevices(User $user): void
    {
        // Xóa token cũ, các thiết bị khác sẽ bị đăng xuất
        $user->tokens()->delete();
        // Xóa tất cả các thiết bị đã đăng nhập của user
        $this->userDeviceRepository->query()
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Lấy OTP chưa được xác thực mới nhất cho email hoặc số điện thoại và loại OTP
     * @param string $username
     * @param UserOtpType $type
     * @return \App\Models\UserOtp|null
     */
    protected function getLastOtpNotVerified(string $username, UserOtpType $type, TypeAuthenticate $typeAuthenticate)
    {
        $latestOtp = $this->userOtpRepository->getLastOtpNotVerified($username, $type, $typeAuthenticate);
        if ($latestOtp &&
            $latestOtp->last_sent_at->addSeconds(self::RETRY_AFTER_SECONDS)->isFuture()
        ) {
            return $latestOtp;
        }
        return null;
    }

    /**
     * Tạo OTP
     * @param string $username
     * @param UserOtpType $type
     * @param TypeAuthenticate $typeAuthenticate
     * @return Model
     * @throws ServiceException
     * @throws \Throwable
     */
    protected function createOtp(string $username, UserOtpType $type, TypeAuthenticate $typeAuthenticate)
    {
        // Kiểm tra giới hạn gửi trong ngày
        $totalSentToday = $this->userOtpRepository->sumTotalSendOTPToday(
            identifier: $username,
            type: $type,
            typeAuthenticate: $typeAuthenticate,
        );

        if ($totalSentToday >= self::MAX_SEND_PER_DAY) {
            throw new ServiceException(__("auth.error.otp_limit_reached"));
        }

        // Kiểm tra khoảng cách giữa 2 lần gửi
        $latestOtp = $this->getLastOtpNotVerified(
            username: $username,
            type: $type,
            typeAuthenticate: $typeAuthenticate,
        );
        if ($latestOtp) {
            $secondsLeft = now()->diffInSeconds($latestOtp->last_sent_at->addSeconds(self::RETRY_AFTER_SECONDS));
            throw new ServiceException(__("auth.error.otp_retry_too_fast", ['seconds' => $secondsLeft]));
        }

        // Tạo OTP
        if (config('app.debug')) {
            $otp = 123456;
        } else {
            $otp = rand(100000, 999999);
            switch ($typeAuthenticate) {
                case TypeAuthenticate::PHONE:
                    $result = $this->zaloService->pushOTPAuthorize($username, $otp);
                    if ($result->isError()) {
                        throw new ServiceException($result->getMessage());
                    }
                    break;
                case TypeAuthenticate::EMAIL:
                    $result = $this->mailService->sendOTP($username, $otp);
                    if ($result->isError()) {
                        throw new ServiceException($result->getMessage());
                    }
                    break;
            }

        }

        // Cập nhật hoặc tạo mới record OTP
        return $this->userOtpRepository->createOrUpdateOtp(
            identifier: $username,
            type: $type,
            otp: $otp,
            ip: request()->ip(),
            typeAuthenticate: $typeAuthenticate,
        );
    }

    /**
     * @param string $username
     * @param UserOtpType $type
     * @param string $otpCode
     * @throws ServiceException
     */
    protected function verifyOtp(string $username, UserOtpType $type, TypeAuthenticate $typeAuthenticate, string $otpCode)
    {
        // Tìm OTP hợp lệ chưa được xác thực mới nhất
        $otpRecord = $this->userOtpRepository->getLastOtpNotVerified(
            identifier: $username,
            type: $type,
            typeAuthenticate: $typeAuthenticate,
        );
        if (!$otpRecord || $otpRecord->isExpired()) {
            throw new ServiceException(__("auth.error.otp_invalid_or_expired"));
        }

        // Kiểm tra số lần thử sai
        if ($otpRecord->attempts >= self::MAX_OTP_ATTEMPTS) {
            // Xóa hiệu lực của OTP này luôn vì đã thử sai quá nhiều
            $otpRecord->update(['expired_at' => now()]);
            throw new ServiceException(__("auth.error.otp_max_attempts_exceeded"));
        }

        // Kiểm tra mã OTP
        if (!Hash::check($otpCode, $otpRecord->otp_hash)) {
            // Tăng số lần thử sai (attempts increment)
            $otpRecord->increment('attempts');
            $remaining = self::MAX_OTP_ATTEMPTS - $otpRecord->attempts;
            throw new ServiceException(__("auth.error.otp_incorrect", ['remaining' => $remaining]));
        }

        // Xác thực thành công
        $otpRecord->update([
            'verified_at' => now(),
            'attempts' => $otpRecord->attempts + 1
        ]);

    }

}
