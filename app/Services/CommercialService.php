<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Repositories\BannerRepository;

class CommercialService extends BaseService
{
    public function __construct(
        protected BannerRepository $bannerRepository,
    )
    {
        parent::__construct();
    }

    /**
     * Lấy danh sách banner cho homepage
     * @return ServiceReturn
     */
    public function getBanner(): ServiceReturn
    {
        try {
            $banners = $this->bannerRepository->queryBanner()->get();
            return ServiceReturn::success(
                data: $banners
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi CommercialService@getBanner",
                ex: $exception,
            );
            return ServiceReturn::error(
                message: __("common_error.server_error")
            );
        }
    }

}
