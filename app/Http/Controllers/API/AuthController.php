<?php

namespace App\Http\Controllers\API;

use App\Enums\Gender;
use App\Enums\Language;
use App\Http\Resources\Auth\UserResource;
use App\Rules\PhoneRule;
use App\Core\Cache\Caching;
use App\Rules\PasswordRule;
use App\Core\Cache\CacheKey;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Core\Controller\BaseController;

class AuthController extends BaseController
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    /**
     * Xác thực đăng nhập bằng số điện thoại.
     * Nếu tồn tại tài khoản với số điện thoại này, thì sẽ cần yêu cầu thêm mật khẩu, còn không sẽ gửi OTP đăng ký.
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', new PhoneRule()],
        ]);
        $resService = $this->authService->authenticate(
            phone: $data['phone'],
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $dataService = $resService->getData();
        return $this->sendSuccess(
            data: [
                'need_register' => $dataService['need_register'],
                'expire_minutes' => $dataService['expire_minutes'] ?? null,
            ],
            message: __('auth.success.authenticate'),
        );
    }

    /**
     * Kiểm tra OTP đăng ký và đăng ký tài khoản.
     * Trả về token dùng để xác thực đăng ký tài khoản mới
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyOtpRegister(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => [new PhoneRule()],
            'otp' => ['required', 'numeric'],
        ], [
            'otp.required' => __('auth.error.invalid_otp'),
            'otp.numeric' => __('auth.error.invalid_otp'),
        ]);
        // Kiểm tra OTP và lấy token
        $resService = $this->authService->verifyOtpRegister(
            phone: $data['phone'],
            otp: $data['otp'],
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $dataService = $resService->getData();
        return $this->sendSuccess(
            data: [
                'token' => $dataService['token'],
            ],
            message: __('auth.success.verify_register')
        );
    }

    /**
     * Gửi lại OTP đăng ký.
     * @param Request $request
     * @return JsonResponse
     */
    public function resendOtpRegister(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => [new PhoneRule()],
        ]);

        // Gửi lại OTP đăng ký
        $resService = $this->authService->resendOtpRegister(
            phone: $data['phone'],
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $dataService = $resService->getData();
        return $this->sendSuccess(
            data: [
                'expire_minutes' => $dataService['expire_minutes'],
            ],
            message: __('auth.success.resend_register_otp'),
        );
    }

    /**
     * Đăng ký tài khoản mới.
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!Caching::hasCache(key: CacheKey::CACHE_KEY_REGISTER_TOKEN, uniqueKey: $value)) {
                    $fail(__('auth.error.invalid_token_register'));
                }
            }],
            'password' => [new PasswordRule()],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(Gender::cases())],
            'language' => ['required', Rule::in(Language::cases())],
        ], [
            'token.required' => __('auth.error.invalid_token_register'),
            'token.string' => __('auth.error.invalid_token_register'),
            'name.required' => __('validation.name.required'),
            'name.string' => __('validation.name.string'),
            'name.max' => __('validation.name.max', ['max' => 255]),
            'gender.required' => __('validation.gender.required'),
            'gender.in' => __('validation.gender.in'),
            'language.required' => __('validation.language.required'),
            'language.in' => __('validation.language.in'),
        ]);

        // Đăng ký tài khoản
        $resService = $this->authService->register(
            token: $data['token'],
            password: $data['password'],
            name: $data['name'],
            gender: Gender::from($data['gender']),
            language: Language::from($data['language']),
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $dataService = $resService->getData();
        return $this->sendSuccess(
            data: [
                'token' => $dataService['token'],
                'user' => new UserResource($dataService['user']),
            ],
            message: __('auth.success.register'),
        );
    }

    /**
     * Đăng nhập tài khoản.
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', new PhoneRule()],
            'password' => ['required', new PasswordRule()],
        ]);
        // Đăng nhập tài khoản
        $resService = $this->authService->login(
            phone: $data['phone'],
            password: $data['password'],
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $dataService = $resService->getData();
        return $this->sendSuccess(
            data: [
                'token' => $dataService['token'],
                'user' => new UserResource($dataService['user']),
            ],
            message: __('auth.success.login'),
        );
    }

    /**
     * Lấy thông tin người dùng.
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
        $resService = $this->authService->user();
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $dataService = $resService->getData();
        return $this->sendSuccess(
            data: [
                'user' => new UserResource($dataService['user']),
            ],
            message: __('auth.success.user'),
        );
    }

    /**
     * Cập nhật ngôn ngữ cho user.
     * @param Request $request
     * @return JsonResponse
     */
    public function setLanguage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lang' => ['required', Rule::in(Language::cases())],
        ], [
            'lang.required' => __('auth.error.language_invalid'),
            'lang.in' => __('auth.error.language_invalid'),
        ]);
        $resService = $this->authService->setLanguage(
            language: Language::from($data['lang']),
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        return $this->sendSuccess(
            message: __('auth.success.set_language'),
        );
    }

    /**
     * Cập nhật heartbeat cho user.
     * @return JsonResponse
     */
    public function heartbeat(): JsonResponse
    {
        $resService = $this->authService->heartbeat();
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        return $this->sendSuccess();
    }

    /**
     * Cập nhật device cho user.
     * @return JsonResponse
     */
    public function setDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'       => 'required|string',
            'device_id'   => 'required|string',
            'platform'    => 'nullable|in:ios,android',
            'device_name' => 'nullable|string',
        ]);

        $resService = $this->authService->setDevice(
            token: $data['token'],
            deviceId: $data['device_id'],
            platform: $data['platform'] ?? null,
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        return $this->sendSuccess(
            message: __('auth.success.logout'),
        );
    }

    /**
     * Chỉnh sửa avatar người dùng.
     * @param Request $request
     * @return JsonResponse
     */
    public function editAvatar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg|max:10240'
        ],[
            'file.required' => __('auth.validation.avatar_invalid'),
            'file.image' => __('auth.validation.avatar_invalid'),
            'file.mimes' => __('auth.validation.avatar_invalid'),
        ]);
        $result = $this->authService->editInfoAvatar(
            file: $data['file'],
        );
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        return $this->sendSuccess(
            data: [
                'user' => new UserResource($result->getData()),
            ],
        );
    }

     /**
     * Xóa avatar người dùng.
     * @return JsonResponse
     */
    public function deleteAvatar(): JsonResponse
    {
        $result = $this->authService->deleteAvatar();
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        return $this->sendSuccess(
            data: [
                'user' => new UserResource($result->getData()),
            ],
        );
    }

    /**
     * Cập nhật thông tin người dùng.
     * @param Request $request
     * @return JsonResponse
     */
    public function editProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => ['nullable', 'string', 'min:4', 'max:255'],
            'bio'     => ['nullable', 'string'],
            'gender' => ['nullable', Rule::in(Gender::cases())],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'old_password' => ['nullable', 'string', 'min:8'],
            'new_password' => ['nullable', 'string', 'min:8'],
        ], [
            'name.string'   => __('auth.validation.name_required'),
            'name.min'      => __('auth.validation.name_min'),
            'name.max'      => __('auth.validation.name_max'),

            'gender.required' => __('validation.gender.required'),
            'gender.in' => __('validation.gender.in'),

            'bio.string' => __('auth.validation.introduce_invalid'),

            'old_password.string' => __('auth.validation.password_required'),
            'old_password.min'    => __('auth.validation.password_min'),
            'new_password.string' => __('auth.validation.password_required'),
            'new_password.min'    => __('auth.validation.password_min'),

            'date_of_birth.date'  => __('auth.validation.date_invalid'),
            'date_of_birth.before' => __('auth.validation.date_before'),
        ]);


        $result = $this->authService->editInfoUser($data);

        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }

        return $this->sendSuccess(
            data: [
                'user' => new UserResource($result->getData()),
            ],
        );
    }

    /**
     * Đăng xuất tài khoản.
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        $result = $this->authService->logout();
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        return $this->sendSuccess(
            message: __('auth.success.logout'),
        );
    }

    /**
     * Khóa tài khoản.
     * @param Request $request
     * @return JsonResponse
     */
    public function lockAccount(Request $request): JsonResponse
    {
        $result = $this->authService->lockAccount();
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        $res = $this->authService->lockAccount();
        if ($res->isError()) {
            return $this->sendError(
                message: $res->getMessage(),
            );
        }
        return $this->sendSuccess(
            message: __('auth.success.lock_account'),
        );
    }
}
