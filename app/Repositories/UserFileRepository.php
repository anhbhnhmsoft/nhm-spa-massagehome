<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\UserFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserFileRepository extends BaseRepository
{
    public function getModel(): string
    {
        return UserFile::class;
    }

    public function filterQuery(Builder $query, array $filters): Builder
    {
        // Lọc theo loại file
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Lọc theo loại file gốc 
        if (isset($filters['file_type'])) {
            $query->where('file_type', $filters['file_type']);
        }

        // Lọc theo user
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        return $query;
    }

    /**
     * Sắp xếp query theo cột và hướng
     * @param Builder $query
     * @param string|null $sortBy
     * @param string $direction
     * @return Builder
     */
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
