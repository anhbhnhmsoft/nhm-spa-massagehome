<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserFile;

class UserFilePolicy
{
    /**
     * @param User $user
     * @param UserFile $userFile
     * @return bool
     */
    public function download(User $user, UserFile $userFile): bool
    {
        if ($user->role === UserRole::ADMIN->value) {
            return true;
        }
        return $user->id === $userFile->user_id;
    }
}
