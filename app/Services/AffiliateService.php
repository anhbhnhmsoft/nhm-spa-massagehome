<?php
// app/Services/AffiliateService.php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
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
    ) {
        parent::__construct();
    }

    /**
     * Kiểm tra ID người giới thiệu có tồn tại không
     * @param int $id
     * @return ServiceReturn
     */
    public function isValidReferrer(int $id): ServiceReturn
    {
        try {
            $exist = $this->userRepository
                ->queryUser()
                ->where('id', $id)
                ->exists();
            if (!$exist) {
                return ServiceReturn::error(
                    message: __("affiliate_link.invalid_referrer")
                );
            }
            return ServiceReturn::success();
        }catch (Exception $e) {
            LogHelper::error(
                message: "Lỗi AffiliateService@isValidReferrer",
                ex: $e
            );
            return ServiceReturn::error(
                message: $e->getMessage()
            );
        }
    }

    /**
     * Ghi nhận click từ link affiliate (Fingerprinting)
     * @param int $referrerId
     * @param string $ip
     * @param string $userAgent
     * @return ServiceReturn
     */
    public function trackClick($referrerId, $ip, $userAgent): ServiceReturn
    {
        try {
            $this->affiliateLinkRepository->create([
                'referrer_id' => $referrerId,
                'client_ip' => $ip,
                'user_agent' => $userAgent ?? null,
                'is_matched' => false,
                'expired_at' => now()->addHours(2), // Hết hạn sau 2 giờ
            ]);
            return ServiceReturn::success();
        } catch (Exception $e) {
            LogHelper::error(
                message: "Lỗi AffiliateService@trackClick",
                ex: $e
            );
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
    public function signinAffiliate(?int $referredUserId, string $ip): ServiceReturn
    {
        DB::beginTransaction();
        try {
            // Tìm bản ghi Tracking chưa được đối sánh
            $match = $this->affiliateLinkRepository->findMatch($ip);
            if (!$match) {
                throw new ServiceException(
                    message: __("affiliate_link.no_match_found")
                );
            }
            // Kiểm tra người giới thiệu có tồn tại không
            $referrerUser = $this->userRepository->queryUser()
                ->where('id', $match->referrer_id)
                ->lockForUpdate()
                ->first();
            if (!$referrerUser) {
                throw new ServiceException(
                    message: __("affiliate_link.user_not_found")
                );
            }
            // Nếu có referredUserId (người dùng đã đăng ký/đăng nhập)
            if ($referredUserId) {
                // Kiểm tra người dùng đã tồn tại chưa
                $referredUser = $this->userRepository->queryUser()
                    ->where('id', $referredUserId)
                    ->lockForUpdate()
                    ->first();
                // Kiểm tra người giới thiệu có tồn tại không
                if (!$referredUser || !$referrerUser) {
                    throw new ServiceException(
                        message: __("affiliate_link.user_not_found")
                    );
                }

                // Kiểm tra người dùng đã có người giới thiệu (referrer) chưa
                if ($referredUser->referred_by_user_id) {
                    throw new ServiceException(
                        message: __("affiliate_link.has_referrer")
                    );
                }

                // Kiểm tra xem không thể tự giới thiệu đối với bản thân được
                if ($referredUser->id === $referrerUser->id) {
                    throw new ServiceException(
                        message: __("affiliate_link.cant_referrer_your_self")
                    );
                }

                // Cập nhật user với referrer
                $this->userRepository->update($referredUserId, [
                    'referred_by_user_id' => $match->referrer_id,
                    'referred_at' => now(),
                ]);

                // Đánh dấu link đã matched
                $this->affiliateLinkRepository->update($match->id, [
                    'referred_user_id' => $referredUserId,
                    'is_matched' => true,
                ]);

                DB::commit();
                return ServiceReturn::success(data: [
                    'status' => true,
                    'need_register' => false,
                    'user_referral' => [
                        'id' => $referrerUser->id,
                        'name' => $referrerUser->name,
                    ]
                ]);
            }

            DB::commit();
            return ServiceReturn::success(data: [
                'status' => true,
                'need_register' => true,
                'user_referral' => [
                    'id' => $referrerUser->id,
                    'name' => $referrerUser->name,
                ]
            ]);
        } catch (ServiceException $e) {
            DB::rollBack();
            return ServiceReturn::error(
                message: $e->getMessage()
            );
        }
        catch (Exception $e) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi AffiliateService@signinAffiliate",
                ex: $e
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
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
