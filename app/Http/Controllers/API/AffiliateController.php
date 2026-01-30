<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Core\Controller\ListRequest;
use App\Http\Resources\Auth\AffiliateUserResource;
use App\Http\Resources\Commercial\BannerResource;
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
        $chplay = config('services.store.chplay');
        $appstore = config('services.store.appstore');

        // Kiểm tra ID người giới thiệu có tồn tại không
        $resultValidate = $this->affiliateService->isValidReferrer($referrerId);
        if ($resultValidate->isError()) {
            return redirect('/');
        }
        $userAgent = $request->header('User-Agent');
        $ip = $request->ip();

        // GHI NHẬN TRACKING (TẠO FINGERPRINT RECORD)
        $resultTrack = $this->affiliateService->trackClick(
            referrerId: $referrerId,
            ip: $ip,
            userAgent: $userAgent,
        );
        if ($resultTrack->isError()) {
            return redirect('/');
        }

        return view('web.affiliate', ['chplay' => $chplay, 'appstore' => $appstore]);
    }

    /**
     * Đối chiếu Affiliate Link
     * @return JsonResponse
     */

    public function matchAffiliate(): JsonResponse
    {
        $userId = auth('sanctum')->user()->id ?? null;
        $ip = request()->ip();
        $result = $this->affiliateService->signinAffiliate($userId, $ip);
        if ($result->isError()) {
            return $this->sendSuccess(
                data: [
                    'status' => false
                ],
            );
        }
        return $this->sendSuccess(
            data: $result->getData(),
        );
    }

    /**
     * Lấy danh sách người giới thiệu
     * @param ListRequest $request
     * @return JsonResponse
     */
    public function listReferred(ListRequest $request): JsonResponse
    {
        $dto = $request->getFilterOptions();
        $dto->addFilter('user_id', Auth::user()->id);
        $result = $this->affiliateService->listAffiliateReferred($dto);
        if (!$result->isSuccess()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        return $this->sendSuccess(
            data: AffiliateUserResource::collection($result->getData())->response()->getData(true),
        );
    }

    /**
     * Lấy cấu hình affiliate dựa trên vai trò của người dùng
     * @return JsonResponse
     */
    public function getConfigAffiliate()
    {
        $user = Auth::user();
        $result = $this->affiliateService->getConfigAffiliate($user);
        if ($result->isError()) {
            return $this->sendError(
                message: $result->getMessage()
            );
        }
        $data = $result->getData();
        return $this->sendSuccess(
            data: [
                'target_role' => $user->role,
                'banner' => $data['banner'] ? new BannerResource($data['banner']) : null,
            ],
        );
    }
}
