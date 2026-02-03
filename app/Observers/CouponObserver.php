<?php

namespace App\Observers;

use App\Core\Helper;
use App\Models\Coupon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CouponObserver
{
    /**
     * Handle the Coupon "created" event.
     */
    public function created(Coupon $coupon): void
    {
        //
    }

    /**
     * Handle the Coupon "updated" event.
     */
    public function updated(Coupon $coupon): void
    {
//        if ($coupon->isDirty('banners')) {
//            $original = $coupon->getRawOriginal('banners');
//            $current = $coupon->getAttributes()['banners'] ?? null;
//
//            $originalFiles = is_array($original) ? $original : (is_string($original) && Str::isJson($original) ? json_decode($original, true) : [$original]);
//            $currentFiles = is_array($current) ? $current : (is_string($current) && Str::isJson($current) ? json_decode($current, true) : [$current]);
//
//            $flatCurrent = Arr::flatten($currentFiles ?? []);
//
//            foreach ($originalFiles as $key => $file) {
//                if (!in_array($file, $flatCurrent) && !empty($file)) {
//                    Helper::deleteFile($file);
//                }
//            }
//        }
    }

    /**
     * Handle the Coupon "deleted" event.
     */
    public function deleted(Coupon $coupon): void
    {
        $banners = $coupon->getAttributes()['banners'] ?? null;
        Helper::deleteFile($banners);
    }

    /**
     * Handle the Coupon "restored" event.
     */
    public function restored(Coupon $coupon): void
    {
        //
    }
}
