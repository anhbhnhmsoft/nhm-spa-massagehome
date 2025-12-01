<?php

namespace App\Core\Cache;

use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class Caching
{
    /**
     * Get cache value by key and unique key
     * @param CacheKey $key
     * @param string|null $uniqueKey
     * @return mixed|null
     */
    public static function getCache(CacheKey $key, string $uniqueKey = null): mixed
    {
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return Cache::get($cacheKey);
    }

    /**
     * Kiểm tra cache có tồn tại hay không
     * @param CacheKey $key
     * @param string|null $uniqueKey
     * @return bool
     */
    public static function hasCache(CacheKey $key, string $uniqueKey = null): bool
    {
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return Cache::has($cacheKey);
    }

    /**
     * Set cache (toàn cục)
     * @param CacheKey $key
     * @param $value
     * @param string|null $uniqueKey
     * @param int $expire Expire time in minutes
     * @return bool
     */
    public static function setCache(CacheKey $key, $value, string $uniqueKey = null, int $expire = 60): bool
    {
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return Cache::put($cacheKey, $value, now()->addMinutes($expire));
    }

    /**
     * Xóa cache theo key và unique key
     * @param CacheKey $key
     * @param string|null $uniqueKey
     * @return bool
     */
    public static function deleteCache(CacheKey $key, string $uniqueKey = null): bool
    {
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return Cache::forget($cacheKey);
    }

    /**
     * Tăng giá trị của một mục trong cache.
     * @param CacheKey $key
     * @param string|null $uniqueKey
     * @param int $amount
     * @return bool|int
     */
    public static function incrementCache(CacheKey $key, string $uniqueKey = null, int $amount = 1): bool|int
    {
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return Cache::increment($cacheKey, $amount);
    }

    /**
     * Giảm giá trị của một mục trong cache.
     * @param CacheKey $key
     * @param string|null $uniqueKey
     * @param int $amount
     * @return bool|int
     */
    public static function decrementCache(CacheKey $key, string $uniqueKey = null, int $amount = 1)
    {
        $cacheKey = $key->value . ($uniqueKey ? '_' . $uniqueKey : '');
        return Cache::decrement($cacheKey, $amount);
    }

    /**
     * Xóa tất cả các mục trong cache.
     * @return bool
     */
    public static function flushCache(): bool
    {
        return Cache::flush();
    }

}
