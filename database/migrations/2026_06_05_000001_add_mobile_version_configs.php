<?php

use App\Enums\ConfigName;
use App\Enums\ConfigType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->configs() as $key => $config) {
            DB::table('configs')->insertOrIgnore(
                array_merge($config, [
                    'config_key' => $key,
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('configs')
            ->whereIn('config_key', array_keys($this->configs()))
            ->delete();
    }

    private function configs(): array
    {
        return [
            ConfigName::IOS_MIN_SUPPORTED_VERSION->value => [
                'config_value' => config('services.application_mobile.ios_version', '1.0.0'),
                'config_type' => ConfigType::STRING->value,
                'description' => 'Phiên bản iOS thấp nhất còn được hỗ trợ.',
            ],
            ConfigName::IOS_LATEST_VERSION->value => [
                'config_value' => config('services.application_mobile.ios_version', '1.0.0'),
                'config_type' => ConfigType::STRING->value,
                'description' => 'Phiên bản iOS mới nhất trên App Store.',
            ],
            ConfigName::ANDROID_MIN_SUPPORTED_VERSION->value => [
                'config_value' => config('services.application_mobile.android_version', '1.0.0'),
                'config_type' => ConfigType::STRING->value,
                'description' => 'Phiên bản Android thấp nhất còn được hỗ trợ.',
            ],
            ConfigName::ANDROID_LATEST_VERSION->value => [
                'config_value' => config('services.application_mobile.android_version', '1.0.0'),
                'config_type' => ConfigType::STRING->value,
                'description' => 'Phiên bản Android mới nhất trên Google Play.',
            ],
            ConfigName::APPSTORE_URL->value => [
                'config_value' => config('services.store.appstore', ''),
                'config_type' => ConfigType::STRING->value,
                'description' => 'Link ứng dụng trên App Store.',
            ],
            ConfigName::CHPLAY_URL->value => [
                'config_value' => config('services.store.chplay', ''),
                'config_type' => ConfigType::STRING->value,
                'description' => 'Link ứng dụng trên Google Play.',
            ],
            ConfigName::MOBILE_MAINTENANCE->value => [
                'config_value' => config('services.application_mobile.maintenance') ? '1' : '0',
                'config_type' => ConfigType::NUMBER->value,
                'description' => 'Bật/tắt chế độ bảo trì ứng dụng mobile.',
            ],
        ];
    }
};
