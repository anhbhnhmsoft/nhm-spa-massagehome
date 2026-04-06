<?php

namespace App\Http\Controllers\API;

use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\TypeAuthenticate;
use App\Http\Requests\API\Auth\RegisterRequest;
use App\Http\Resources\Auth\UserResource;
use App\Rules\EmailOrPhoneRule;
use App\Rules\PhoneRule;
use App\Core\Cache\Caching;
use App\Rules\PasswordRule;
use App\Core\Cache\CacheKey;
use App\Services\ConfigService;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Core\Controller\BaseController;

class AuthController extends BaseController
{
    public function __construct(
        protected AuthService $authService,
        protected ConfigService $configService,
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
            'username' => ['required', new EmailOrPhoneRule()],
            'type_authenticate' => ['required', Rule::enum(TypeAuthenticate::class)],
        ],[
            'username.required' => __('validation.username.required'),
            'type_authenticate.required' => __('validation.type_authenticate.required'),
            'type_authenticate.enum' => __('validation.type_authenticate.invalid'),
        ]);
        $resService = $this->authService->authenticate(
            username: $data['username'],
            typeAuthenticate: TypeAuthenticate::from($data['type_authenticate']),
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        $dataService = $resService->getData();
        return $this->sendSuccess(
            data: $dataService,
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
            'username' => ['required', new EmailOrPhoneRule()],
            'type_authenticate' => ['required', Rule::enum(TypeAuthenticate::class)],
            'otp' => ['required', 'numeric'],
        ], [
            'username.required' => __('validation.username.required'),
            'type_authenticate.required' => __('validation.type_authenticate.required'),
            'type_authenticate.enum' => __('validation.type_authenticate.invalid'),
            'otp.required' => __('auth.error.invalid_otp'),
            'otp.numeric' => __('auth.error.invalid_otp'),
        ]);
        // Kiểm tra OTP và lấy token
        $resService = $this->authService->verifyOtpRegister(
            username: $data['username'],
            typeAuthenticate: TypeAuthenticate::from($data['type_authenticate']),
            otp: $data['otp'],
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        return $this->sendSuccess();
    }

    /**
     * Gửi lại OTP đăng ký.
     * @param Request $request
     * @return JsonResponse
     */
    public function resendOtpRegister(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', new EmailOrPhoneRule()],
            'type_authenticate' => ['required', Rule::enum(TypeAuthenticate::class)],
        ],[
            'username.required' => __('validation.username.required'),
            'type_authenticate.required' => __('validation.type_authenticate.required'),
            'type_authenticate.enum' => __('validation.type_authenticate.invalid'),
        ]);

        // Gửi lại OTP đăng ký
        $resService = $this->authService->resendOtpRegister(
            username: $data['username'],
            typeAuthenticate: TypeAuthenticate::from($data['type_authenticate']),
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        return $this->sendSuccess(
            data: $resService->getData(),
        );
    }

    /**
     * Đăng ký tài khoản mới.
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Đăng ký tài khoản
        $resService = $this->authService->register(
            username: $data['username'],
            phone: $data['phone'],
            typeAuthenticate: TypeAuthenticate::from($data['type_authenticate']),
            password: $data['password'],
            name: $data['name'],
            gender: Gender::from($data['gender']),
            language: Language::from($data['language']),
            address: $data['address'],
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
            'username' => ['required', new EmailOrPhoneRule()],
            'type_authenticate' => ['required', Rule::enum(TypeAuthenticate::class)],
            'password' => ['required', new PasswordRule()],
        ], [
            'username.required' => __('validation.username.required'),
            'type_authenticate.required' => __('validation.type_authenticate.required'),
            'type_authenticate.enum' => __('validation.type_authenticate.invalid'),
            'password.required' => __('validation.password.required'),
        ]);
        // Đăng nhập tài khoản
        $resService = $this->authService->login(
            username: $data['username'],
            typeAuthenticate: TypeAuthenticate::from($data['type_authenticate']),
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
     * Quên mật khẩu.
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', new EmailOrPhoneRule()],
            'type_authenticate' => ['required', Rule::enum(TypeAuthenticate::class)],
        ]);
        // Gửi lại OTP đăng ký
        $resService = $this->authService->forgotPassword(
            username: $data['username'],
            typeAuthenticate: TypeAuthenticate::from($data['type_authenticate']),
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        return $this->sendSuccess(
            data: $resService->getData(),
        );
    }

    /**
     * Xác thực OTP quên mật khẩu.
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function verifyOtpForgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', new EmailOrPhoneRule()],
            'type_authenticate' => ['required', Rule::enum(TypeAuthenticate::class)],
            'otp' => ['required', 'numeric'],
        ], [
            'username.required' => __('validation.username.required'),
            'type_authenticate.required' => __('validation.type_authenticate.required'),
            'type_authenticate.enum' => __('validation.type_authenticate.invalid'),
            'otp.required' => __('auth.error.invalid_otp'),
            'otp.numeric' => __('auth.error.invalid_otp'),
        ]);
        // Kiểm tra OTP và lấy token
        $resService = $this->authService->verifyOtpForgotPassword(
            username: $data['username'],
            typeAuthenticate: TypeAuthenticate::from($data['type_authenticate']),
            otp: $data['otp'],
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        return $this->sendSuccess();
    }

    /**
     * Gửi lại OTP quên mật khẩu.
     * @param Request $request
     * @return JsonResponse
     */
    public function resendOtpForgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', new EmailOrPhoneRule()],
            'type_authenticate' => ['required', Rule::enum(TypeAuthenticate::class)],
        ]);

        // Gửi lại OTP đăng ký
        $resService = $this->authService->resendOtpForgotPassword(
            username: $data['username'],
            typeAuthenticate: TypeAuthenticate::from($data['type_authenticate']),
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        return $this->sendSuccess(
            data: $resService->getData(),
        );
    }


    /**
     * Đổi mật khẩu.
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', new EmailOrPhoneRule()],
            'type_authenticate' => ['required', Rule::enum(TypeAuthenticate::class)],
            'password' => ['required', new PasswordRule()],
        ]);
        // Đổi mật khẩu
        $resService = $this->authService->resetPassword(
            username: $data['username'],
            typeAuthenticate: TypeAuthenticate::from($data['type_authenticate']),
            password: $data['password'],
        );
        if ($resService->isError()) {
            return $this->sendError(
                message: $resService->getMessage(),
            );
        }
        return $this->sendSuccess();
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
    public function lockAccount(): JsonResponse
    {
        $result = $this->authService->lockAccount();
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage(),
            );
        }
        return $this->sendSuccess(
            message: __('auth.success.lock_account'),
        );
    }
}
