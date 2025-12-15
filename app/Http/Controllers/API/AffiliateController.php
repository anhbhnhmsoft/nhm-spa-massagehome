<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Jobs\SigninAffiliateHandleJob; // Vẫn cần dùng Job cho Match
use App\Services\AffiliateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends BaseController
{
    const string CHPLAY_APP = 'https://play.google.com/store/apps/details?id=com.yourapp.package';
    const string APPSTORE_APP = 'https://apps.apple.com/app/your-app-id';
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    public function handleAffiliateLink(Request $request, $referrerId): View | RedirectResponse
    {
        // 1. Kiểm tra ID người giới thiệu có tồn tại không
        if (! $this->affiliateService->isValidReferrer($referrerId)) {
            return redirect('/');
        }

        $userAgent = $request->header('User-Agent');

        // 2. GHI NHẬN TRACKING (TẠO FINGERPRINT RECORD)
        $this->affiliateService->trackClick($referrerId, $request->ip(), $userAgent);

        // 3. Xử lý Chuyển hướng Store
        if (! $this->isMobileDevice($userAgent)) {
            return view('affiliate.qr_code', ['url' => $request->fullUrl()]);
        }

        if (stripos($userAgent, 'android') !== false) {
            $storeUrl = self::CHPLAY_APP;
        } elseif (stripos($userAgent, 'iphone') !== false || stripos($userAgent, 'ipad') !== false) {
            $storeUrl = self::APPSTORE_APP;
        } else {
            $storeUrl = '/';
        }

        // Trả về view cho mobile để redirect bằng JS
        return view('affiliate.redirect', [
            'storeUrl' => $storeUrl
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */

    public function matchAffiliate(Request $request): JsonResponse
    {
        // 1. Validate Input từ App
        $data = $request->validate(
            [
                'referred_user_id' => 'nullable|string',
            ],
            []
        );

        $ip = $request->ip();
        
        $resutl = $this->affiliateService->signinAffiliate($data['referred_user_id'] ?? null, $ip);
        if (!$resutl->isSuccess()) {
            return $this->sendError(
                message: $resutl->getMessage()
            );
        }
        return $this->sendSuccess(
            data: $resutl->getData(),
            message: $resutl->getMessage()
        );
    }

    // Hàm trợ giúp đơn giản
    private function isMobileDevice($userAgent)
    {
        return preg_match('/(android|iphone|ipad|mobile)/i', $userAgent);
    }
}
