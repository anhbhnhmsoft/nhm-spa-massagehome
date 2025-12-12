<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionRepository extends BaseRepository
{

    protected function getModel(): string
    {
        return WalletTransaction::class;
    }

    public function queryTransaction(): Builder
    {
        return $this->model->query();
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        // Mặc định sắp xếp theo created_at desc
        $query->orderBy('created_at', "desc");
        return $query;
    }
}
