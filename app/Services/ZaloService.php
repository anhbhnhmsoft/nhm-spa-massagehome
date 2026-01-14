<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Helper;
use App\Core\LogHelper;
use App\Enums\ZaloEndPointExtends;
use Illuminate\Support\Facades\Http;
use Zalo\Zalo;

class ZaloService
{
    private Zalo $zalo;
    private string $appId;
    private string $appSecret;
    private string $oaId;

    private string $templateId;

    private const ACCESS_TOKEN_EXPIRE_BUFFER = 60 * 5; // 5 minutes

    public function __construct()
    {
        $this->appId = config('services.zalo.app_id');
        $this->appSecret = config('services.zalo.app_secret');
        $this->oaId = config('services.zalo.oa_id');
        $this->templateId = config('services.zalo.otp_template');

        if (!$this->appId || !$this->appSecret || !$this->oaId || !$this->templateId) {
            throw new \RuntimeException('Missing Zalo configuration');
        }

        $this->zalo = new Zalo([
            'app_id' => $this->appId,
            'app_secret' => $this->appSecret,
        ]);
    }

    /* =======================================================
     * AUTHORIZATION
     * =======================================================
     */

    public function getAuthorizationUrlForCustomer(string $callbackUrl, string $state): string
    {
        return 'https://oauth.zaloapp.com/v4/oa/permission?' . http_build_query([
                'app_id' => $this->appId,
                'redirect_uri' => $callbackUrl,
                'state' => $state,
                'scope' => 'oa_info',
            ]);
    }

    /* =======================================================
     * TOKEN HANDLING
     * =======================================================
     */

    public function exchangeCodeForToken(string $code): array
    {
        return $this->requestToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        if (!$refreshToken) {
            return $this->fail('Missing refresh token');
        }

        return $this->requestToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * Get valid OA access token (cache → refresh → fail)
     */
    public function getAccessTokenForOA(): ?string
    {
        $accessToken = Caching::getCache(CacheKey::CACHE_KEY_ZALO_TOKEN);
        if ($accessToken) {
            return $accessToken;
        }

        $refreshToken = Caching::getCache(CacheKey::CACHE_KEY_ZALO_REFRESH_TOKEN);
        if (!$refreshToken) {
            LogHelper::error('ZaloService - No refresh token available');
            return null;
        }

        $result = $this->refreshAccessToken($refreshToken);
        if (!$result['success']) {
            return null;
        }

        return Caching::getCache(CacheKey::CACHE_KEY_ZALO_TOKEN);
    }

    public function getAccessTokenFromCodeForOA(string $code): string
    {
        $result = $this->exchangeCodeForToken($code);
        if (!$result['success']) {
            return '';
        }

        return $result['access_token'];
    }

    public function pushOTPAuthorize(string $phoneNumber, string $otp): array
    {
        try {
            // Format phone number
            $formattedPhone = Helper::formatPhone($phoneNumber);

            // Validate phone
            if (!Helper::isValidPhone($formattedPhone)) {
                return [
                    'success' => false,
                    'message' => __('error.unvalid_phonenumber')
                ];
            }

            // Get access token
            $accessToken = $this->getAccessTokenForOA();
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => __('error.unable_to_get_access_token')
                ];
            }

            // Prepare template data
            $templateData = [
                'otp' => $otp,
            ];

            // Prepare request params
            $params = [
                'phone' => $formattedPhone,
                'template_id' => $this->templateId,
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
                LogHelper::error('ZaloService::sendOTP failed',null ,[
                    'phone' => $formattedPhone,
                    'error_code' => $responseData['error'],
                    'error_message' => $responseData['message'] ?? 'Unknown error',
                ]);

                $errorCode = $responseData['error'];
                $errorMessage = $responseData['message'] ?? 'Unknown error';

                // Xử lý cụ thể cho trường hợp không có Zalo
                if ($errorCode == -212) {
                    return [
                        'success' => false,
                        'is_zalo_user' => false, // Flag để bên ngoài biết và gọi SMS thay thế
                        'message' => __('error.not_zalo_user'),
                    ];
                }

                if ($errorCode == -213) {
                    return [
                        'success' => false,
                        'is_blocked' => true,
                        'message' => __('error.user_blocked'),
                    ];
                }
                if($errorCode == -118) {
                    return [
                        'success' => false,
                        'message' => __('error.zalo_service_unavailable'),
                        'error_code' => $responseData['error'],
                    ];
                }

                return [
                    'success' => false,
                    'message' => __('error.couldnot_send_otp'),
                    'error_code' => $responseData['error'],
                ];
            }

            LogHelper::debug('ZaloService::sendOTP success', [
                'phone' => $formattedPhone,
                'purpose' => 'register',
                'msg_id' => $responseData['data']['msg_id'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => __('error.send_otp_success'),
                'data' => $responseData['data'] ?? [],
            ];
        } catch (\HttpException $e) {
            LogHelper::error('ZaloService::sendOTP HttpException',$e ,[
                'phone' => $formattedPhone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (\Throwable $th) {
            LogHelper::error('ZaloService::sendOTP Exception', $th,[
                'phone' => $formattedPhone,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => __('error.couldnot_send_otp')
            ];
        }
    }

    /* =======================================================
     * INTERNAL METHODS
     * =======================================================
     */

    private function requestToken(array $payload): array
    {
        try {
            $response = Http::asForm()
                ->withHeaders([
                    'secret_key' => $this->appSecret,
                ])
                ->post(ZaloEndPointExtends::API_OA_ACCESS_TOKEN, array_merge([
                    'app_id' => $this->appId,
                ], $payload));

            if (!$response->successful()) {
                LogHelper::error('Zalo API HTTP error', null, [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $this->fail('Zalo API HTTP error');
            }

            $data = $response->json();
            if (($data['error'] ?? 0) !== 0) {
                LogHelper::error('Zalo API error response', null, $data);
                return $this->fail($data['message'] ?? 'Zalo API error');
            }

            $this->storeTokens(
                $data['access_token'],
                $data['refresh_token'],
                $data['expires_in']
            );

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_in' => $data['expires_in'],
            ];
        } catch (\Throwable $e) {
            LogHelper::error('ZaloService exception', $e);
            return $this->fail($e->getMessage());
        }
    }

    private function storeTokens(string $accessToken, string $refreshToken, int $expiresIn): void
    {
        Caching::setCache(
            CacheKey::CACHE_KEY_ZALO_TOKEN,
            $accessToken,
            null,
            now()->addSeconds($expiresIn - self::ACCESS_TOKEN_EXPIRE_BUFFER)->timestamp
        );

        Caching::setCache(
            CacheKey::CACHE_KEY_ZALO_REFRESH_TOKEN,
            $refreshToken,
            null,
            now()->addDays(12)->timestamp
        );

        LogHelper::action('Zalo tokens cached successfully');
    }

    private function fail(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
        ];
    }
}
