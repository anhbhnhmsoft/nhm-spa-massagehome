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

    public function filterQuery(Builder $query, array $filters): Builder
    {
        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction = 'desc'): Builder
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        if (empty($column)) {
            $column = 'created_at';
        }
        $query->orderBy($column, $direction);
        return $query;
    }
}
