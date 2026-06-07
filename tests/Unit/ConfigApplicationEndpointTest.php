<?php

use App\Enums\ConfigName;
use App\Enums\ConfigType;
use App\Models\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

uses(Tests\TestCase::class);

beforeEach(function () {
    Cache::flush();

    Schema::dropIfExists('configs');
    Schema::create('configs', function (Blueprint $table) {
        $table->id();
        $table->string('config_key')->unique();
        $table->smallInteger('config_type');
        $table->text('config_value');
        $table->text('description')->nullable();
        $table->timestamps();
    });

    $configs = [
        ConfigName::IOS_MIN_SUPPORTED_VERSION->value => '1.1.0',
        ConfigName::IOS_LATEST_VERSION->value => '1.2.0',
        ConfigName::ANDROID_MIN_SUPPORTED_VERSION->value => '2.1.0',
        ConfigName::ANDROID_LATEST_VERSION->value => '2.3.0',
        ConfigName::APPSTORE_URL->value => 'https://example.com/appstore',
        ConfigName::CHPLAY_URL->value => 'https://example.com/chplay',
        ConfigName::MOBILE_MAINTENANCE->value => '0',
    ];

    foreach ($configs as $key => $value) {
        Config::query()->updateOrCreate(
            ['config_key' => $key],
            [
                'config_value' => $value,
                'config_type' => is_numeric($value) ? ConfigType::NUMBER->value : ConfigType::STRING->value,
            ]
        );
    }
});

it('returns legacy fields without app version headers', function () {
    $this->getJson('/api/config/config-application')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.maintenance', false)
        ->assertJsonPath('data.ios_version', '1.2.0')
        ->assertJsonPath('data.android_version', '2.3.0')
        ->assertJsonPath('data.appstore_url', 'https://example.com/appstore')
        ->assertJsonPath('data.chplay_url', 'https://example.com/chplay')
        ->assertJsonPath('data.update.platform', null)
        ->assertJsonPath('data.update.current_version', null)
        ->assertJsonPath('data.update.required', false)
        ->assertJsonPath('data.update.recommended', false);
});

it('requires update when ios version is below minimum supported version', function () {
    $this->withHeaders([
        'X-App-Platform' => 'ios',
        'X-App-Version' => '1.0.0',
    ])->getJson('/api/config/config-application')
        ->assertOk()
        ->assertJsonPath('data.update.platform', 'ios')
        ->assertJsonPath('data.update.current_version', '1.0.0')
        ->assertJsonPath('data.update.min_supported_version', '1.1.0')
        ->assertJsonPath('data.update.latest_version', '1.2.0')
        ->assertJsonPath('data.update.required', true)
        ->assertJsonPath('data.update.recommended', false)
        ->assertJsonPath('data.update.store_url', 'https://example.com/appstore');
});

it('recommends update when ios version is supported but behind latest', function () {
    $this->withHeaders([
        'X-App-Platform' => 'ios',
        'X-App-Version' => '1.1',
    ])->getJson('/api/config/config-application')
        ->assertOk()
        ->assertJsonPath('data.update.current_version', '1.1.0')
        ->assertJsonPath('data.update.required', false)
        ->assertJsonPath('data.update.recommended', true);
});

it('uses android version policy for android clients', function () {
    $this->withHeaders([
        'X-App-Platform' => 'android',
        'X-App-Version' => '2.0.9',
    ])->getJson('/api/config/config-application')
        ->assertOk()
        ->assertJsonPath('data.update.platform', 'android')
        ->assertJsonPath('data.update.min_supported_version', '2.1.0')
        ->assertJsonPath('data.update.latest_version', '2.3.0')
        ->assertJsonPath('data.update.required', true)
        ->assertJsonPath('data.update.store_url', 'https://example.com/chplay');
});

it('returns maintenance from database config', function () {
    Config::query()
        ->where('config_key', ConfigName::MOBILE_MAINTENANCE->value)
        ->update(['config_value' => '1']);

    Cache::flush();

    $this->getJson('/api/config/config-application')
        ->assertOk()
        ->assertJsonPath('data.maintenance', true);
});
