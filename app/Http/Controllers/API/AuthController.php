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
    )
    {
    }

    /**
     * Xác thực đăng nhập bằng số điện thoại.
     * Nếu tồn tại tài khoản với số điện thoại này, thì sẽ cần yêu cầu thêm mật khẩu, còn không sẽ gửi OTP đăng ký.
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => [new PhoneRule()],
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
            'referral_code' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(Gender::cases())],
            'language' => ['required', Rule::in(Language::cases())],
        ], [
            'token.required' => __('auth.error.invalid_token_register'),
            'token.string' => __('auth.error.invalid_token_register'),
            'referral_code.string' => __('validation.referrer_code'),
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
            referralCode: $data['referral_code'] ?? null,
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
            'phone' => [new PhoneRule()],
            'password' => [new PasswordRule()],
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
            'language' => ['required', Rule::in(Language::cases())],
        ], [
            'language.required' => __('auth.error.language_invalid'),
            'language.in' => __('auth.error.language_invalid'),
        ]);
        $resService = $this->authService->setLanguage(
            language: Language::from($data['language']),
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
}
