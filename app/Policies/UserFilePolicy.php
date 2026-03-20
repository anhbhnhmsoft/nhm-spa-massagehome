<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AdminUser;
use App\Models\User;
use App\Models\UserFile;

class UserFilePolicy
{
    /**
     * @param User|AdminUser $user
     * @param UserFile $userFile
     * @return bool
     */
    public function download(User|AdminUser $user, UserFile $userFile): bool
    {
        // Nếu người dùng là AdminUser (từ guard 'web') -> Cho phép tải mọi file
        if ($user instanceof AdminUser) {
            return true;
        }
        return $user->id === $userFile->user_id;
    }
}
