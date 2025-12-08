<?php

namespace App\Filament\Clusters\Organization\Resources\KTVs\Pages;

use App\Enums\UserRole;
use App\Filament\Clusters\Organization\Resources\KTVs\KTVResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKTV extends CreateRecord
{
    protected static string $resource = KTVResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {   
        // Set role to KTV
        $data['role'] = UserRole::KTV->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();

        // Handle profile data separately
        if (isset($data['profile'])) {
            $this->record->profile()->updateOrCreate(
                ['user_id' => $this->record->id],
                $data['profile']
            );
        }
    }
}
