<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies\Pages;

use App\Core\Helper;
use App\Enums\UserRole;
use App\Filament\Clusters\KTV\Resources\KTVApplies\KTVApplyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKTVApply extends CreateRecord
{
    protected static string $resource = KTVApplyResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::KTV->value;
        $data['phone_verified_at'] = now();
        $data['language'] = app()->getLocale();
        $data['is_active'] = true;
        $data['referral_code'] = Helper::generateReferCodeUser(UserRole::KTV);
        return $data;
    }
}
