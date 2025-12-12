<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Enums\ConfigName;
use App\Enums\ConfigType;
use App\Repositories\AffiliateConfigRepository;
use App\Repositories\ConfigRepository;
use Illuminate\Support\Facades\DB;

class ConfigService extends BaseService
{
    public function __construct(
        protected ConfigRepository $configRepository,
        protected AffiliateConfigRepository $affiliateConfigRepository,

    )
    {
        parent::__construct();
    }

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
            return ServiceReturn::error(message: $e->getMessage());
        }
    }



    /**
     * Lấy tất cả config settings
     * @return ServiceReturn
     */
    public function getAllConfigs(): ServiceReturn
    {
        try {
            $configs = $this->configRepository->query()->all();
            $data = [];
            foreach ($configs as $config) {
                $data[$config->config_key] = $config->config_value;
            }
            return ServiceReturn::success(
                data: $data
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi SettingService@getAllConfigs",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Cập nhật config settings
     * @param array $data
     * @return ServiceReturn
     */
    public function updateConfigs(array $data): ServiceReturn
    {
        DB::beginTransaction();
        try {
            foreach ($data as $key => $value) {
                $this->configRepository->query()->updateOrCreate(
                    ['config_key' => $key],
                    [
                        'config_value' => $value,
                        'config_type' => is_numeric($value) ? ConfigType::NUMBER : ConfigType::STRING,
                    ]
                );
            }
            DB::commit();
            return ServiceReturn::success(
                message: __('admin.notification.success.update_success')
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi SettingService@updateConfigs",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Lấy tất cả affiliate configs
     * @return ServiceReturn
     */
    public function getAllAffiliateConfigs(): ServiceReturn
    {
        try {
            $configs = $this->affiliateConfigRepository->query()->get()->toArray();
            return ServiceReturn::success(
                data: $configs
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi SettingService@getAllAffiliateConfigs",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }

    /**
     * Cập nhật affiliate configs (CRUD)
     * @param array $configsData
     * @return ServiceReturn
     */
    public function updateAffiliateConfigs(array $configsData): ServiceReturn
    {
        DB::beginTransaction();
        try {
            $currentIds = [];
            foreach ($configsData as $configData) {
                if (isset($configData['id'])) {
                    $config = $this->affiliateConfigRepository->find($configData['id']);
                    if ($config) {
                        $this->affiliateConfigRepository->update($configData['id'], $configData);
                        $currentIds[] = $config->id;
                    }
                } else {
                    $new = $this->affiliateConfigRepository->create($configData);
                    $currentIds[] = $new->id;
                }
            }

            // Xóa các config không được gửi lên
            $this->affiliateConfigRepository->query()->whereNotIn('id', $currentIds)->delete();

            DB::commit();
            return ServiceReturn::success(
                message: __('admin.notification.success.update_success')
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            LogHelper::error(
                message: "Lỗi SettingService@updateAffiliateConfigs",
                ex: $exception
            );
            return ServiceReturn::error(
                message: $exception->getMessage()
            );
        }
    }
}
