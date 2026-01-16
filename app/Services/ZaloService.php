<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Helper;
use App\Core\LogHelper;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Enums\ConfigName;
use App\Enums\ZaloEndPointExtends;
use Exception;
use Illuminate\Support\Facades\Http;
use Zalo\Exceptions\ZaloSDKException;
use Zalo\Zalo;

class ZaloService
{
    // Thời gian buffer trước khi access token hết hạn (5 phút)
    private const ACCESS_TOKEN_EXPIRE_BUFFER = 60 * 5; // 5 minutes

    // Trạng thái đã boot hay chưa
    protected bool $isBooted = false;
    // Zalo SDK instance
    protected ?Zalo $zalo = null;
    /**
     * Configs for Zalo SDK
     * @var array{
     *       app_id:string,
     *       app_secret:string,
     *       oa_id:string,
     *       template_id:string,
     *       merchant_id:string,
     *       merchant_key1:string,
     *  }
     */
    protected array $configs = [];

    public function __construct(
        protected ConfigService $configService,
    )
    {
    }

    /**
     * Lấy access token từ cache (nếu có) hoặc refresh token (nếu có)
     * @return ServiceReturn
     */
    public function getAccessTokenForOA(): ServiceReturn
    {
        try {
            // Boot the service if not yet
            $this->boot();
            $accessToken = Caching::getCache(CacheKey::CACHE_KEY_ZALO_TOKEN);
            if ($accessToken) {
                return ServiceReturn::success(
                    data: $accessToken,
                );
            }
            $refreshToken = Caching::getCache(CacheKey::CACHE_KEY_ZALO_REFRESH_TOKEN);
            if (!$refreshToken) {
                throw new ServiceException('Zalo refresh token not found');
            }
            $result = $this->requestToken([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);
            if ($result->isError()) {
                throw new ServiceException($result->getMessage());
            }
            return ServiceReturn::success(
                data: Caching::getCache(CacheKey::CACHE_KEY_ZALO_TOKEN),
            );
        } catch (Exception $e) {
            LogHelper::error('ZaloService - getAccessTokenForOA failed: ' . $e->getMessage());
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Khởi tạo access token lần đầu tiên
     * @param string $code
     * @return ServiceReturn
     */
    public function initAccessToken(string $code): ServiceReturn
    {
        try {
            $this->boot();
            $result = $this->requestToken([
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);
            if ($result->isError()) {
                throw new ServiceException($result->getMessage());
            }
            return ServiceReturn::success(
                data: $result->getData()['access_token'],
            );
        } catch (Exception $e) {
            LogHelper::error(
                message: $e->getMessage(),
                ex: $e
            );
            return ServiceReturn::error(
                message: $e->getMessage(),
            );
        }
    }

    /**
     * Gửi OTP xác thực qua Zalo
     * @param string $phoneNumber
     * @param string $otp
     * @return ServiceReturn
     */
    public function pushOTPAuthorize(string $phoneNumber, string $otp): ServiceReturn
    {
        // Format phone number
        $formattedPhone = Helper::formatPhone($phoneNumber);
        try {
            $this->boot();

            // Validate phone
            if (!Helper::isValidPhone($formattedPhone)) {
                throw new ServiceException(__('error.invalid_phone_number'));
            }

            // Get access token
            $accessToken = $this->getAccessTokenForOA();
            if ($accessToken->isError()) {
                throw new ServiceException(__('error.unable_to_get_access_token_zalo'));
            }

            // Prepare template data
            $templateData = [
                'otp' => $otp,
            ];

            // Prepare request params
            $params = [
                'phone' => $formattedPhone,
                'template_id' => $this->configs['template_id'],
                'template_data' => $templateData,
                'tracking_id' => uniqid('otp_', true),
            ];

            // Send ZNS via HTTP request
            $response = Http::withHeaders([
                'access_token' => $accessToken,
                'Content-Type' => 'application/json',
            ])
                ->post(ZaloEndPointExtends::API_OA_SEND_ZNS, $params);

            $responseData = $response->json();

            // Check response
            if (isset($responseData['error']) && $responseData['error'] !== 0) {
                $errorCode = $responseData['error'];
                $errorMessage = $responseData['message'] ?? 'Unknown error';

                LogHelper::error('ZaloService::sendOTP failed', null, [
                    'phone' => $formattedPhone,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                ]);


                // Xử lý cụ thể cho trường hợp không có Zalo
                if ($errorCode == -212) {
                    throw new ServiceException(__('error.not_zalo_user'));
                }

                // Xử lý cụ thể cho trường hợp user bị block
                if ($errorCode == -213) {
                    throw new ServiceException(__('error.user_blocked'));
                }

                // Xử lý cụ thể cho trường hợp Zalo service không khả dụng
                if ($errorCode == -118) {
                    throw new ServiceException(__('error.zalo_service_unavailable'));
                }

                // Xử lý các lỗi khác
                throw new ServiceException(__('error.could_not_send_otp'));
            }

            return ServiceReturn::success(
                data: $responseData['data'],
            );

        } catch (ServiceException $e) {
            return ServiceReturn::error($e->getMessage());
        } catch (\Throwable $th) {
            LogHelper::error('ZaloService::sendOTP Exception', $th, [
                'phone' => $formattedPhone,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return ServiceReturn::error(__('error.could_not_send_otp'));

        }
    }


    /**
     * Tạo đơn hàng thanh toán qua ZaloPay
     * @param int $amount
     * @param int $orderCode
     * @param string $description
     * @param int $userId
     * @return ServiceReturn
     */
    public function createOrder(
        int    $amount,
        int    $orderCode,
        string $description,
        int    $userId
    ): ServiceReturn
    {
        $debug = config('app.debug');
        try {
            $this->boot();
            $data = [
                'app_id' => $this->configs['merchant_id'],
                'app_trans_id' => date('ymd') . '_' . $orderCode,
                'app_user' => (string)$userId,
                'amount' => $amount,
                'item' => json_encode([]),
                'description' => $description,
                'embed_data' => json_encode([
                    'redirecturl' => route('home'),
                ]),
                'callback_url' => route('webhook.zalopay'),
            ];

            $data['mac'] = hash_hmac(
                'sha256',
                implode('|', [
                    $data['app_id'],
                    $data['app_trans_id'],
                    $data['app_user'],
                    $data['amount'],
                    $data['app_time'] = round(microtime(true) * 1000),
                    $data['embed_data'],
                    $data['item'],
                ]),
                $this->configs['merchant_key1'],
            );

            $response = Http::asForm()->post($debug ? ZaloEndPointExtends::API_CREATE_ORDER : ZaloEndPointExtends::SB_API_CREATE_ORDER, $data);

            $result = $response->json();

            if (($result['return_code'] ?? -1) !== 1) {
                throw new Exception($result['return_message']);
            }

            return ServiceReturn::success($result);
        } catch (ServiceException $e) {
            return ServiceReturn::error($e->getMessage());
        } catch (\Throwable $e) {
            LogHelper::error('ZaloService@createOrder', $e);
            return ServiceReturn::error(__('error.unable_to_get_access_token_zalo'));
        }
    }

    /* =======================================================
     * INTERNAL METHODS
     * =======================================================
     */

    /**
     * Boot the service (bắt buộc phải gọi trước khi sử dụng method)
     * @return void
     * @throws ZaloSDKException|ServiceException
     * @throws Exception
     */
    protected function boot(): void
    {
        if ($this->isBooted) {
            return;
        }
        // Load toàn bộ config từ DB thông qua ConfigService
        $this->configs = [
            'app_id' => strval($this->configService->getConfigValue(ConfigName::ZALO_APP_ID)),
            'app_secret' => strval($this->configService->getConfigValue(ConfigName::ZALO_APPSECRET_KEY)),
            'oa_id' => strval($this->configService->getConfigValue(ConfigName::ZALO_OA_ID)),
            'template_id' => strval($this->configService->getConfigValue(ConfigName::ZALO_TEMPLATE_ID)),
            'merchant_id' => strval($this->configService->getConfigValue(ConfigName::ZALO_MERCHANT_ID)),
            'merchant_key1' => strval($this->configService->getConfigValue(ConfigName::ZALO_MERCHANT_KEY_1)),
        ];
        // Loại bỏ các phần tử rỗng và kiểm tra độ dài
        if (count(array_filter($this->configs)) !== count($this->configs)) {
            throw new ServiceException(__('error.zalo_service_configuration_error'));
        }
        $this->zalo = new Zalo([
            'app_id' => $this->configs['app_id'],
            'app_secret' => $this->configs['app_secret'],
        ]);
        $this->isBooted = true;
    }

    /**
     * Request access token from Zalo API
     * @param array $payload
     * @return ServiceReturn
     */
    protected function requestToken(array $payload): ServiceReturn
    {
        try {
            $response = Http::asForm()
                ->withHeaders([
                    'secret_key' => $this->configs['app_secret'],
                ])
                ->post(ZaloEndPointExtends::API_OA_ACCESS_TOKEN, array_merge([
                    'app_id' => $this->configs['app_id'],
                ], $payload));

            if (!$response->successful()) {
                throw new ServiceException(message: 'Zalo API HTTP error');
            }

            $data = $response->json();
            if (($data['error'] ?? 0) !== 0) {
                throw new ServiceException(message: $data['message'] ?? 'Zalo API error');
            }

            $this->storeTokens(
                $data['access_token'],
                $data['refresh_token'],
                $data['expires_in']
            );
            return ServiceReturn::success(
                data: [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_in' => $data['expires_in'],
                ]
            );
        } catch (\Throwable $e) {
            LogHelper::error('ZaloService exception', $e);
            return ServiceReturn::error($e->getMessage());
        }
    }

    /**
     * Store access token and refresh token to cache
     * @param string $accessToken
     * @param string $refreshToken
     * @param int $expiresIn
     * @return void
     */
    protected function storeTokens(string $accessToken, string $refreshToken, int $expiresIn): void
    {
        Caching::setCache(
            key: CacheKey::CACHE_KEY_ZALO_TOKEN,
            value: $accessToken,
            expire: now()->addSeconds($expiresIn - self::ACCESS_TOKEN_EXPIRE_BUFFER)
        );

        Caching::setCache(
            key: CacheKey::CACHE_KEY_ZALO_REFRESH_TOKEN,
            value: $refreshToken,
            expire: now()->addDays(12)
        );
    }

}
