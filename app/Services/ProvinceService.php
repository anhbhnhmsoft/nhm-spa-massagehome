<?php

namespace App\Services;

use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Models\Province;
use App\Repositories\ProvinceRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ProvinceService extends BaseService
{
    public function __construct(
        protected ProvinceRepository $provinceRepository,
    ) {
        parent::__construct();
    }

    /**
     * Lấy cây dữ liệu Tỉnh / Thành - Quận / Huyện - Phường / Xã đầy đủ của Việt Nam
     */
    public static function getProvincesTree(): array
    {
        return Cache::remember('vn_provinces_tree', 86400 * 7, function () {
            $path = storage_path('app/data/vn_provinces.json');
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true);
                if (!empty($data)) {
                    return $data;
                }
            }

            try {
                $res = Http::timeout(5)->get('https://provinces.open-api.vn/api/?depth=3');
                if ($res->successful()) {
                    $tree = $res->json();
                    if (!empty($tree)) {
                        @mkdir(storage_path('app/data'), 0755, true);
                        @file_put_contents($path, json_encode($tree, JSON_UNESCAPED_UNICODE));
                        return $tree;
                    }
                }
            } catch (\Throwable $e) {
                LogHelper::error(message: 'Lỗi tải API provinces.open-api.vn', ex: $e);
            }

            return [];
        });
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

    /**
     * Helper hỗ trợ Filament Select options tỉnh thành gọn gàng
     */
    public static function toOptions(): array
    {
        return Province::toOptions();
    }

    /**
     * Lấy danh sách toàn bộ Phường / Xã của một Tỉnh / Thành phố từ dữ liệu chuẩn
     */
    public static function getWardsByProvince(?string $provinceName): array
    {
        if (empty($provinceName)) {
            return [];
        }

        $tree = self::getProvincesTree();
        if (empty($tree)) {
            return [];
        }

        $cleanSearch = Str::slug($provinceName);
        $cleanSearch = preg_replace('/^(tp|thanh-pho|tinh)-/', '', $cleanSearch);

        $found = null;
        foreach ($tree as $p) {
            $cleanP = Str::slug($p['name']);
            $cleanP = preg_replace('/^(tp|thanh-pho|tinh)-/', '', $cleanP);
            if ($cleanP === $cleanSearch || str_contains($cleanP, $cleanSearch) || str_contains($cleanSearch, $cleanP)) {
                $found = $p;
                break;
            }
        }

        if (!$found || empty($found['districts'])) {
            return [];
        }

        $options = [];
        foreach ($found['districts'] as $d) {
            if (empty($d['wards'])) {
                continue;
            }
            foreach ($d['wards'] as $w) {
                $label = $w['name'] . ' (' . $d['name'] . ')';
                $options[$label] = $label;
            }
        }

        return $options;
    }
}
