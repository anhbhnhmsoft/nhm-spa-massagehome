<?php

namespace App\Filament\Clusters\User\Resources\Reviews\Pages;

use App\Filament\Clusters\User\Resources\Reviews\ReviewResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReview extends CreateRecord
{
    protected static string $resource = ReviewResource::class;
}
