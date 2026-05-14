<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\SupportCategory;

class SupportCategoryRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return SupportCategory::class;
    }
}
