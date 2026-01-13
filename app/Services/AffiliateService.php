<?php
// app/Services/AffiliateService.php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Repositories\AffiliateLinkRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;

class AffiliateService extends BaseService
{
    public function __construct(
        protected AffiliateLinkRepository $affiliateLinkRepository,
        protected UserRepository $userRepository
    ) {}

    /**
     * Kiểm tra ID người giới thiệu có tồn tại không
     * @param int $id
     * @return bool
     */
    public function isValidReferrer($id)
    {
        return $this->userRepository->query()->where('id', $id)->exists();
    }

    /**
     * Ghi nhận click từ link affiliate (Fingerprinting)
     * @param int $referrerId
     * @param string $ip
     * @param string $userAgent
     * @return AffiliateLink
     */
    public function trackClick($referrerId, $ip, $userAgent) : ServiceReturn
    {
        DB::beginTransaction();
        try {
            $this->affiliateLinkRepository->create([
                'referrer_id' => $referrerId,
                'client_ip' => $ip,
                'user_agent' => $userAgent,
                'is_matched' => false,
                'expired_at' => now()->addHours(2),
                ]);
                DB::commit();
                return ServiceReturn::success();
        } catch (Exception $e) {
            LogHelper::error(
                message: "Lỗi AffiliateService@trackClick",
                ex: $e
            );
            DB::rollBack();
            return ServiceReturn::error(
                message: $e->getMessage()
            );
        }
    }

    /**
     * Xử lý logic đối sánh từ Mobile App
     * @param ?int $referredUserId
     * @param string $ip
     * @return ServiceReturn
     */
    public function signinAffiliate(?int $referredUserId, string $ip)
    {
        try {
            return DB::transaction(function () use ($referredUserId, $ip) {
                // 1. Tìm bản ghi Tracking chưa được đối sánh (Fingerprinting Lookup)
                $match = $this->affiliateLinkRepository->findMatch($ip);

                if (!$match) {
                    return ServiceReturn::error(
                        message: __("affiliate_link.no_match_found")
                    );
                }

                // 2. Nếu có referredUserId (người dùng đã đăng ký/đăng nhập)
                if ($referredUserId) {
                    $referredUser = $this->userRepository->query()->lockForUpdate()->find($referredUserId);

                    if (!$referredUser) {
                        return ServiceReturn::error(
                            message: __("affiliate_link.user_not_found")
                        );
                    }

                    // Kiểm tra người dùng đã có người giới thiệu (referrer) chưa
                    if ($referredUser->referred_by_user_id) {
                        return ServiceReturn::error(
                            message: __("affiliate_link.has_referrer")
                        );
                    }

                    // Cập nhật user với referrer
                    $this->userRepository->update($referredUserId, [
                        'referred_by_user_id' => $match->referrer_id,
                    ]);

                    // Đánh dấu link đã matched
                    $this->affiliateLinkRepository->update($match->id, [
                        'referred_user_id' => $referredUserId,
                        'is_matched' => true,
                    ]);

                    return ServiceReturn::success(
                        message: __("affiliate_link.match_success")
                    );
                }

                // 3. Nếu chưa có referredUserId (tracking trước khi đăng ký)
                // Chỉ xác nhận rằng link tồn tại và hợp lệ
                return ServiceReturn::success(
                    data: [
                        'referrer' => $match->referrer
                    ],
                    message: __("affiliate_link.tracking_confirmed")
                );
            });
        } catch (Exception $e) {
            LogHelper::error(
                message: "Lỗi AffiliateService@signinAffiliate",
                ex: $e
            );
            return ServiceReturn::error(
                message: $e->getMessage()
            );
        }
    }

    /**
     * Lấy danh sách người người được giới thiệu liên quan đến bảnt thân
     * @param FilterDTO $dto
     * @return ServiceReturn
     */
    public function listAffiliateReferred(FilterDTO $dto): ServiceReturn
    {

        try {
            $userId = Auth::user()->id;
            $list = $this->userRepository->query()
                ->where('referred_by_user_id', $userId)
                ->with(['affiliateRecords' => function ($query) {
                    $query->where('is_matched', true);
                }])
                ->paginate(
                    perPage: $dto->perPage,
                    page: $dto->page
                );
            return ServiceReturn::success(
                data: $list
            );
        } catch (Exception $e) {
            LogHelper::error(
                message: "Lỗi AffiliateService@listAffiliateReferred",
                ex: $e
            );
            return ServiceReturn::error(
                message: $e->getMessage()
            );
        }
    }
}
