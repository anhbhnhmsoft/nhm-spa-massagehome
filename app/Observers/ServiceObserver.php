<?php

namespace App\Observers;

use App\Core\Helper;
use App\Models\Service;

class ServiceObserver
{
    /**
     * Handle the Service "created" event.
     */
    public function created(Service $service): void
    {
        //
    }

    /**
     * Handle the Service "updated" event.
     */
    public function updated(Service $service): void
    {
        if ($service->isDirty('image_url')) {
            $original = $service->getRawOriginal('image_url');
            Helper::deleteFile($original);
        }
    }

    /**
     * Handle the Service "deleted" event.
     */
    public function deleted(Service $service): void
    {
        Helper::deleteFile($service->image_url);
    }

    /**
     * Handle the Service "restored" event.
     */
    public function restored(Service $service): void
    {
        //
    }

}
