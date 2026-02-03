<?php

namespace App\Observers;

use App\Core\Helper;
use App\Models\StaticContract;

class StaticContractObserver
{
    /**
     * Handle the StaticContract "created" event.
     */
    public function created(StaticContract $staticContract): void
    {
        //
    }

    /**
     * Handle the StaticContract "updated" event.
     */
    public function updated(StaticContract $staticContract): void
    {
//        if ($staticContract->isDirty('path')) {
//            $original = $staticContract->getRawOriginal('path');
//            $current = $staticContract->getAttributes()['path'] ?? null;
//
//            $originalFiles = is_array($original) ? $original : (is_string($original) && \Illuminate\Support\Str::isJson($original) ? json_decode($original, true) : [$original]);
//            $currentFiles = is_array($current) ? $current : (is_string($current) && \Illuminate\Support\Str::isJson($current) ? json_decode($current, true) : [$current]);
//
//            $flatCurrent = \Illuminate\Support\Arr::flatten($currentFiles ?? []);
//
//            foreach ($originalFiles as $key => $file) {
//                if (!in_array($file, $flatCurrent) && !empty($file)) {
//                    Helper::deleteFile($file);
//                }
//            }
//        }
    }

    /**
     * Handle the StaticContract "deleted" event.
     */
    public function deleted(StaticContract $staticContract): void
    {
        $path = $staticContract->getAttributes()['path'] ?? null;
        Helper::deleteFile($path);
    }

    /**
     * Handle the StaticContract "restored" event.
     */
    public function restored(StaticContract $staticContract): void
    {

    }

}
