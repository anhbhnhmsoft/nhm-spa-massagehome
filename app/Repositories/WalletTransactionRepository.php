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

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction): Builder
    {
        if ($sortBy === 'created_at') {
            $query->orderBy($sortBy, $direction);
        }
        return $query;
    }
}
