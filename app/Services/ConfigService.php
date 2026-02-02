<?php

namespace App\Services;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceException;
use App\Core\Service\ServiceReturn;
use App\Core\Helper;
use App\Enums\ConfigName;
use App\Enums\ConfigType;
use App\Enums\UserRole;
use App\Repositories\AffiliateConfigRepository;
use App\Repositories\ConfigRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ConfigService extends BaseService
{
    public function __construct(
        protected ConfigRepository $configRepository,
        protected AffiliateConfigRepository $affiliateConfigRepository,
    ) {
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
                ->select('config_key', 'config_value', 'config_type')
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
            } else {
                return ServiceReturn::success();
            }
        } catch (\Exception $e) {
            LogHelper::error(
                message: "Lỗi ConfigService@getConfig",
                ex: $e,
            );
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
            $configs = $this->configRepository->query()->get();
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
                $oldConfig = $this->configRepository->query()
                    ->where('config_key', $key)
                    ->first();

                $this->configRepository->query()->updateOrCreate(
                    ['config_key' => $key],
                    [
                        'config_value' => $value ?? '',
                        'config_type' => is_numeric($value) ? ConfigType::NUMBER : ConfigType::STRING,
                    ]
                );

                // Nếu cấu hình là ảnh QR Wechat, xóa file cũ khi đổi sang file mới
                if ($key === ConfigName::SP_WECHAT_QR_IMAGE->value && $oldConfig) {
                    $oldPath = $oldConfig->config_value ?? null;
                    if (!empty($oldPath) && $oldPath !== $value) {
                        Helper::deleteFile($oldPath, 'public');
                    }
                }
                // Xóa cache config
                Caching::deleteCache(
                    key: CacheKey::CACHE_KEY_CONFIG,
                    uniqueKey: $key
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
     * Lấy danh sách các kênh hỗ trợ
     * @return ServiceReturn
     */
    public function getSupportChannels(): ServiceReturn
    {
        try {
            $supportKeys = [
                ConfigName::SP_ZALO,
                ConfigName::SP_FACEBOOK,
                ConfigName::SP_WECHAT,
                ConfigName::SP_PHONE,
            ];

            $channels = array_reduce($supportKeys, function ($carry, $item) {
                $config = $this->getConfig($item);
                if ($config->isSuccess()) {
                    $config = $config->getData();
                } else {
                    return $carry;
                }
                if ($config && !empty($config['config_value'])) {
                    $carry[] = [
                        'key' => $item->value,
                        'value' => $config['config_value'],
                    ];
                }
                return $carry;
            }, []);


            return ServiceReturn::success(data: $channels);
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi ConfigService@getSupportChannels",
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
                message: __('common_error.server_error')
            );
        }
    }

    /**
     * Lấy affiliate config theo role
     * @param UserRole $role
     * @return ServiceReturn
     */
    public function getAffiliateConfigByRole(UserRole $role): ServiceReturn
    {
        try {
            $config = $this->affiliateConfigRepository->query()
                ->where('target_role', $role->value)
                ->where('is_active', true)
                ->first();
            if (!$config) {
                return ServiceReturn::error(
                    message: __('error.config_not_found')
                );
            }
            return ServiceReturn::success(
                data: [
                    'target_role' => $config->target_role,
                    'commission_rate' => $config->commission_rate,
                    'min_commission' => $config->min_commission,
                    'max_commission' => $config->max_commission,
                ]
            );
        } catch (\Exception $exception) {
            LogHelper::error(
                message: "Lỗi SettingService@getAffiliateConfigByRole",
                ex: $exception
            );
            return ServiceReturn::error(
                message: __('common_error.server_error')
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

    /**
     * Lấy cấu hình theo key
     * @param UserRole $role
     * @return ServiceReturn
     */
    public function getConfigAffiliate(UserRole $role): ServiceReturn
    {
        $config = Caching::getCache(
            key: CacheKey::CACHE_KEY_CONFIG_AFFILIATE,
            uniqueKey: $role->value,
        );
        if ($config) {
            return ServiceReturn::success(data: $config);
        }
        try {
            $config = $this->affiliateConfigRepository->query()
                ->where('target_role', $role->value)
                ->select('commission_rate', 'min_commission', 'max_commission')
                ->first();
            if ($config) {
                $config = $config->toArray();
                Caching::setCache(
                    key: CacheKey::CACHE_KEY_CONFIG_AFFILIATE,
                    value: $config,
                    uniqueKey: $role->value,
                    expire: 60 * 24 // Cache for 24 hours
                );
                return ServiceReturn::success(data: $config);
            } else {
                return ServiceReturn::error(message: __("error.config_not_found"));
            }
        } catch (\Exception $e) {
            LogHelper::error(
                message: "Lỗi ConfigService@getConfig",
                ex: $e,
            );
            return ServiceReturn::error(message: $e->getMessage());
        }
    }

    /**
     * Lấy giá trị cấu hình theo key
     * @param ConfigName $configName
     * @return mixed|null
     * @throws ServiceException
     */
    public function getConfigValue(ConfigName $configName): mixed
    {
        $config = $this->getConfig($configName);
        if ($config->isError()) {
            throw new ServiceException(
                message: __("error.config_not_found")
            );
        }
        return $config->getData()['config_value'] ?? null;
    }

    /**
     * Lấy số lượng KTV tối thiểu cần giới thiệu để trở thành trưởng nhóm KTV
     * @return int
     */
    public function getKtvLeaderMinReferrals(): int
    {
        try {
            $value = $this->getConfigValue(ConfigName::KTV_LEADER_MIN_REFERRALS);
            $intValue = (int) $value;

            return $intValue > 0 ? $intValue : 10;
        } catch (\Throwable) {
            return 10;
        }
    }
}
