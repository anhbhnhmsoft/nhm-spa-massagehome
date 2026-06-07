<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\BookingApplicationStatus;
use App\Models\BookingApplication;
use Illuminate\Database\Eloquent\Builder;

class BookingApplicationRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return BookingApplication::class;
    }

    public function queryWithRelations(): Builder
    {
        return $this->query()
            ->with([
                'booking',
                'booking.user.profile',
                'booking.service',
                'booking.ktvUser.profile',
                'ktv.profile',
                'ktv.reviewApplication',
                'ktv.schedule',
                'ktv.primaryAddress',
            ]);
    }

    public function activeApplicationStatuses(): array
    {
        return [
            BookingApplicationStatus::APPLIED->value,
        ];
    }
}
