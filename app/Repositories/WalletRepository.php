<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;

class WalletRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Wallet::class;
    }

    public function queryWallet()
    {
        return $this->query()
            ->where('is_active', true);
    }

    /**
     * Lấy ví của người dùng
     * @param $userId
     * @param bool $lockForUpdate
     * @return null
     */
    public function getWalletByUserId($userId, bool $lockForUpdate = false)
    {
        $query = $this->queryWallet()
            ->where('user_id', $userId);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        return $query->first();
    }


}
