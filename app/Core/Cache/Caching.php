<?php

namespace App\Core\Cache;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class Caching
{

    /**
     * Private helper để generate key chuẩn nhất quán.
     */
    private static function makeKey(CacheKey $key, ?string $uniqueKey = null): string
    {
        return $uniqueKey ? "{$key->value}:{$uniqueKey}" : $key->value;
    }

    /**
     * Lấy cache, nếu không có thì thực thi callback và lưu lại.
     * @param CacheKey $key
     * @param \Closure $callback Hàm lấy dữ liệu từ DB nếu chưa có cache
     * @param string|null $uniqueKey
     * @param int|Carbon $expire Expire time in minutes or Carbon instance
     * @return mixed
     */
    public static function remember(CacheKey $key, \Closure $callback, ?string $uniqueKey = null, int | Carbon $expire = 60): mixed
    {
        $cacheKey = self::makeKey($key, $uniqueKey);

        if (!$expire instanceof Carbon) {
            $expire = now()->addMinutes($expire);
        }
        return Cache::remember($cacheKey, $expire, $callback);
    }


    /**
     * Get cache value by key and unique key
     * @param CacheKey $key
     * @param string|null $uniqueKey
     * @return mixed|null
     */
    public static function getCache(CacheKey $key, string $uniqueKey = null): mixed
    {
        $cacheKey = self::makeKey($key, $uniqueKey);
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
        $cacheKey = self::makeKey($key, $uniqueKey);
        return Cache::has($cacheKey);
    }

    /**
     * Set cache (toàn cục)
     * @param CacheKey $key
     * @param $value
     * @param string|null $uniqueKey
     * @param int|Carbon $expire Expire time in minutes or Carbon instance
     * @return bool
     */
    public static function setCache(CacheKey $key, $value, string $uniqueKey = null, int | Carbon $expire = 60): bool
    {
        $cacheKey = self::makeKey($key, $uniqueKey);
        if (!$expire instanceof Carbon) {
            $expire = now()->addMinutes($expire);
        }
        return Cache::put($cacheKey, $value, $expire);
    }

    /**
     * Xóa cache theo key và unique key
     * @param CacheKey $key
     * @param string|null $uniqueKey
     * @return bool
     */
    public static function deleteCache(CacheKey $key, string $uniqueKey = null): bool
    {
        $cacheKey = self::makeKey($key, $uniqueKey);
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
        $cacheKey = self::makeKey($key, $uniqueKey);
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
        $cacheKey = self::makeKey($key, $uniqueKey);
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
