<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Http\Resources\Auth\AffiliateUserResource;
use App\Jobs\SigninAffiliateHandleJob; // Vẫn cần dùng Job cho Match
use App\Services\AffiliateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AffiliateController extends BaseController
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    public function handleAffiliateLink(Request $request, $referrerId): View | RedirectResponse
    {
        $chplay = env('CHPLAY_APP');
        $appstore = env('APPSTORE_APP');
        // 1. Kiểm tra ID người giới thiệu có tồn tại không
        if (! $this->affiliateService->isValidReferrer($referrerId)) {
            return redirect('/');
        }

        $userAgent = $request->header('User-Agent');

        // 2. GHI NHẬN TRACKING (TẠO FINGERPRINT RECORD)
        $this->affiliateService->trackClick($referrerId, $request->ip(), $userAgent);

        return view('affiliate', ['chplay' => $chplay, 'appstore' => $appstore]);
    }

    /**
     * @return JsonResponse
     */

    public function matchAffiliate(): JsonResponse
    {

        $userId = Auth::check() ? Auth::user()->id : null;
        $ip = request()->ip();

        $resutl = $this->affiliateService->signinAffiliate($userId, $ip);
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

    public function listReffered(ListRequest $request): JsonResponse
    {

        $dto = $request->getFilterOptions();
        $dto->addFilter('user_id', Auth::user()->id);
        $result = $this->affiliateService->listAffiliateReffered($dto);
        if (!$result->isSuccess()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        return $this->sendSuccess(
            data: AffiliateUserResource::collection($result->getData())->response()->getData(true),
        );
    }
}
