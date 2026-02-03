<?php

namespace App\Observers;

use App\Core\Helper;
use App\Models\UserProfile;

class UserProfileObserver
{
    /**
     * Handle the UserProfileObserver "created" event.
     */
    public function created(UserProfile $userProfile): void
    {
        //
    }

    /**
     * Handle the UserProfileObserver "updated" event.
     */
    public function updated(UserProfile $userProfile): void
    {
//        $original = $userProfile->getRawOriginal('avatar_url');
//        Helper::deleteFile($original, 'public');
    }

    /**
     * Handle the UserProfileObserver "deleted" event.
     */
    public function deleted(UserProfile $userProfile): void
    {
        Helper::deleteFile($userProfile->avatar_url, 'public');
    }

    /**
     * Handle the UserProfileObserver "restored" event.
     */
    public function restored(UserProfile $userProfile): void
    {
        //
    }

}
