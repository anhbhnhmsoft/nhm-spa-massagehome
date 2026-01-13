<?php

namespace App\Observers;

use App\Core\Helper;
use App\Models\UserFile;

class UserFileObserver
{
    /**
     * Handle the UserFile "created" event.
     */
    public function created(UserFile $userFile): void
    {
        //
    }

    /**
     * Handle the UserFile "updated" event.
     */
    public function updated(UserFile $userFile): void
    {
        if ($userFile->isDirty('file_path')) {
            $original = $userFile->getRawOriginal('file_path');
            Helper::deleteFile($original, $userFile->is_public ? 'public' : 'private');
        }
    }

    /**
     * Handle the UserFile "deleted" event.
     */
    public function deleted(UserFile $userFile): void
    {
        //
        if ($userFile->isForceDeleting()) {
            Helper::deleteFile($userFile->file_path, $userFile->is_public ? 'public' : 'private');
        }
    }

    /**
     * Handle the UserFile "restored" event.
     */
    public function restored(UserFile $userFile): void
    {
        //
    }

    /**
     * Handle the UserFile "force deleted" event.
     */
    public function forceDeleted(UserFile $userFile): void
    {
        Helper::deleteFile($userFile->file_path, $userFile->is_public ? 'public' : 'private');
    }
}
