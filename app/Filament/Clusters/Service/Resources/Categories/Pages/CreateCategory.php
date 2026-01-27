<?php

namespace App\Filament\Clusters\Service\Resources\Categories\Pages;

use App\Filament\Clusters\Service\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
