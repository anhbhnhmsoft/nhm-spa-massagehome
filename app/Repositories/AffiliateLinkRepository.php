<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\AffiliateLink;
use Illuminate\Database\Eloquent\Builder;

class AffiliateLinkRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return AffiliateLink::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query;
    }

    public function sortQuery(Builder $query, ?string $sortBy, string $direction = 'desc'): Builder
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        $column = $sortBy ?? 'created_at';
        $query->orderBy($column, $direction);
        return $query;
    }
    public function findMatch(string $ip): ?AffiliateLink
    {
        $query = $this->query()
            ->where('is_matched', false)
            ->where('expired_at', '>', now())
            ->where('client_ip', $ip)
            ->orderByDesc('created_at');


        return $query->first();
    }
}
