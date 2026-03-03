<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\BookingStatus;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;

class ServiceRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Service::class;
    }



}
