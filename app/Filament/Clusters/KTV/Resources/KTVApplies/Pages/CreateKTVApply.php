<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies\Pages;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\KTV\Resources\KTVApplies\KTVApplyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKTVApply extends CreateRecord
{
    protected static string $resource = KTVApplyResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::KTV->value;
        $data['is_active'] = false;
        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();

        $data['reviewApplication']['status'] = ReviewApplicationStatus::PENDING->value;

        // Handle profile data
        if (isset($data['profile'])) {
            $this->record->profile()->updateOrCreate(
                ['user_id' => $this->record->id],
                $data['profile']
            );
        }

        // Handle review application data
        if (isset($data['reviewApplication'])) {
            $this->record->reviewApplication()->updateOrCreate(
                ['user_id' => $this->record->id],
                $data['reviewApplication']
            );
        }
    }
}
