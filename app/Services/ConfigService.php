<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Enums\ConfigName;
use App\Repositories\ConfigRepository;

class ConfigService extends BaseService
{
    public function __construct(
        protected ConfigRepository $configRepository
    )
    {
        parent::__construct();
    }

    /**
     * Lấy cấu hình theo key
     * @param ConfigName $key
     * @return ServiceReturn
     */
    public function getConfig(ConfigName $key): ServiceReturn
    {
        $config = Caching::getCache(
            key: CacheKey::CACHE_KEY_CONFIG,
            uniqueKey: $key->value,
        );
        if ($config) {
            return ServiceReturn::success(data: $config);
        }
        try {
            $config = $this->configRepository->query()
                ->where('config_key', $key->value)
                ->select('config_key', 'config_value','config_type')
                ->first();
            if ($config) {
                $config = $config->toArray();
                Caching::setCache(
                    key: CacheKey::CACHE_KEY_CONFIG,
                    value: $config,
                    uniqueKey: $key->value,
                    expire: 60 * 24 // Cache for 24 hours
                );
                return ServiceReturn::success(data: $config);
            }else{
                return ServiceReturn::success();
            }
        } catch (\Exception $e) {
            LogHelper::error(
                message: "Lỗi ConfigService@getConfig",
                ex:  $e,
            );
            return ServiceReturn::error(message: $e->getMessage());
        }
    }
}
