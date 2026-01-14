<?php

namespace App\Observers;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Helper;
use App\Models\Config;

class ConfigObserver
{
    public function created(Config $config): void
    {

    }

    public function updated(Config $config): void
    {
        Caching::deleteCache(CacheKey::CACHE_KEY_CONFIG, $config->config_key);
    }

    public function deleted(Config $config): void
    {
        Caching::deleteCache(CacheKey::CACHE_KEY_CONFIG, $config->config_key);
    }

    public function restored(Config $config): void
    {

    }

    public function forceDeleted(Config $config): void
    {

    }
}
