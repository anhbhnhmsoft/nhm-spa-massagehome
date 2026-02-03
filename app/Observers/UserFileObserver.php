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
        $original = $userFile->getRawOriginal('file_path');
        Helper::deleteFile($original, $userFile->is_public ? 'public' : 'private');
    }

    /**
     * Handle the UserFile "deleted" event.
     */
    public function deleted(UserFile $userFile): void
    {
        //
        Helper::deleteFile($userFile->file_path, $userFile->is_public ? 'public' : 'private');
    }

    /**
     * Handle the UserFile "restored" event.
     */
    public function restored(UserFile $userFile): void
    {
        //
    }
}
