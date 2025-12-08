<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs\Pages;

use App\Core\Helper;
use App\Enums\UserRole;
use App\Filament\Clusters\KTV\Resources\KTVs\KTVResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKTV extends CreateRecord
{
    protected static string $resource = KTVResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set role to KTV
        $data['role'] = UserRole::KTV->value;
        $data['phone_verified_at'] = now();
        $data['language'] = 'vi';
        $data['is_active'] = true;
        $data['last_login_at'] = now();
        $data['referral_code'] = Helper::generateReferCodeUser(UserRole::KTV);
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
