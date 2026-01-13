<?php

namespace App\Observers;

use App\Core\Helper;
use App\Models\Page;

class PageObserver
{
    /**
     * Handle the Page "created" event.
     */
    public function created(Page $page): void
    {
        //
    }

    /**
     * Handle the Page "updated" event.
     */
    public function updated(Page $page): void
    {
        if ($page->isDirty('og_image')) {
            $original = $page->getRawOriginal('og_image');
            Helper::deleteFile($original);
        }
    }

    /**
     * Handle the Page "deleted" event.
     */
    public function deleted(Page $page): void
    {
        if ($page->isForceDeleting()) {
            Helper::deleteFile($page->og_image);
        }
    }

    /**
     * Handle the Page "restored" event.
     */
    public function restored(Page $page): void
    {
        //
    }

    /**
     * Handle the Page "force deleted" event.
     */
    public function forceDeleted(Page $page): void
    {
        Helper::deleteFile($page->og_image);
    }
}
