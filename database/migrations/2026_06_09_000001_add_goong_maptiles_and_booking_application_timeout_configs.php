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
            ConfigName::GOONG_MAPTILES_KEY->value => [
                'config_value' => '',
                'config_type' => ConfigType::STRING->value,
                'description' => 'Mã Maptiles key Goong dùng để render bản đồ trên mobile.',
            ],
            ConfigName::BOOKING_APPLICATION_TIMEOUT_MINUTES->value => [
                'config_value' => '15',
                'config_type' => ConfigType::NUMBER->value,
                'description' => 'Số phút chờ KTV xác nhận hoặc khách chọn KTV ứng đơn trước khi tự hủy và hoàn tiền.',
            ],
        ];
    }
};
