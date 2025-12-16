<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $affiliateLink = route('affiliate.link', $user->id);
        $user->affiliate_link = $affiliateLink;
        $user->save();
    }
}
