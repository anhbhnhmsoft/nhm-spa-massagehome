<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Repositories\ProvinceRepository;

class ProvinceService extends BaseService
{
    public function __construct(
        protected ProvinceRepository $provinceRepository,
    ) {
        parent::__construct();
    }

    /**
     * Lấy danh sách tỉnh/thành
     */
    public function getProvinces(?string $keyword = null): ServiceReturn
    {
        try {
            $query = $this->provinceRepository->queryProvinces();
            $query = $this->provinceRepository->filterQuery($query, [
                'name' => $keyword,
            ]);
            $query = $this->provinceRepository->sortQuery($query, 'name', 'ASC');

            $items = $query->get(['id', 'code', 'name'])
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                ])
                ->values();

            return ServiceReturn::success(data: $items, message: __('common.data_list'));
        } catch (\Throwable $exception) {
            LogHelper::error(
                message: 'Lỗi ProvinceService@getProvinces',
                ex: $exception
            );

            return ServiceReturn::error(
                message: __('common_error.server_error')
            );
        }
    }
}


