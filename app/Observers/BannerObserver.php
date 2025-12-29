<?php

namespace App\Observers;

use App\Core\Helper;
use App\Models\Banner;

class BannerObserver
{
    /**
     * Handle the Banner "created" event.
     */
    public function created(Banner $banner): void
    {
        //
    }

    /**
     * Handle the Banner "updated" event.
     */
    public function updated(Banner $banner): void
    {
        if ($banner->isDirty('image_url')) {
            $original = $banner->getRawOriginal('image_url');
            $current = $banner->getAttributes()['image_url'] ?? null;

            $originalFiles = is_array($original) ? $original : (is_string($original) && \Illuminate\Support\Str::isJson($original) ? json_decode($original, true) : [$original]);
            $currentFiles = is_array($current) ? $current : (is_string($current) && \Illuminate\Support\Str::isJson($current) ? json_decode($current, true) : [$current]);

            $flatCurrent = \Illuminate\Support\Arr::flatten($currentFiles ?? []);

            foreach ($originalFiles as $key => $file) {
                 if (!in_array($file, $flatCurrent) && !empty($file)) {
                     Helper::deleteFile($file);
                 }
            }
        }
    }

    /**
     * Handle the Banner "deleted" event.
     */
    public function deleted(Banner $banner): void
    {
        if ($banner->isForceDeleting()) {
             $image = $banner->getAttributes()['image_url'] ?? null;
             Helper::deleteFile($image);
        }
    }

    /**
     * Handle the Banner "restored" event.
     */
    public function restored(Banner $banner): void
    {
        //
    }

    /**
     * Handle the Banner "force deleted" event.
     */
    public function forceDeleted(Banner $banner): void
    {
        $image = $banner->getAttributes()['image_url'] ?? null;
        Helper::deleteFile($image);
    }
}
