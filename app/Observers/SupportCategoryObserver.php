<?php

namespace App\Observers;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Models\SupportCategory;

class SupportCategoryObserver
{
    public function created(SupportCategory $supportCategory): void
    {
        Caching::deleteCache(CacheKey::CACHE_KEY_SUPPORT_CATEGORY);
    }

    public function updated(SupportCategory $supportCategory): void
    {
        Caching::deleteCache(CacheKey::CACHE_KEY_SUPPORT_CATEGORY);
    }

    public function deleted(SupportCategory $supportCategory): void
    {
        Caching::deleteCache(CacheKey::CACHE_KEY_SUPPORT_CATEGORY);
    }

    public function restored(SupportCategory $supportCategory): void
    {
        Caching::deleteCache(CacheKey::CACHE_KEY_SUPPORT_CATEGORY);
    }
}
