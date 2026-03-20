<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\AdminUser;

class AdminUserRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return AdminUser::class;
    }

    public function queryAdminUser()
    {
        return $this->model->query()
            ->where('is_active', true);
    }

    /**
     * Tìm kiếm AdminUser theo username
     * @param string $username
     * @return AdminUser|null
     */
    public function findByUsername(string $username): ?AdminUser
    {
        return $this->model->query()->where('username', $username)->first();
    }
}
