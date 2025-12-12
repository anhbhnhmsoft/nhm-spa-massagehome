<?php

namespace App\Jobs;

use App\Services\LocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessCachingPlaceJob implements ShouldQueue
{
    use Queueable;

    public ?array $places;

    /**
     * @param array|null $places
     * @return void
     */
    public function __construct(?array $places)
    {
        $this->places = $places;
    }

    /**
     * @param LocationService $locationService
     * @return void
     */
    public function handle(LocationService $locationService): void
    {

        $locationService->processCachingPlace($this->places);
    }
}
