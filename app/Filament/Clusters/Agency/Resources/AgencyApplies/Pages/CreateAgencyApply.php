<?php

namespace App\Filament\Clusters\Agency\Resources\AgencyApplies\Pages;

use App\Core\Helper;
use App\Enums\UserRole;
use App\Filament\Clusters\Agency\Resources\AgencyApplies\AgencyApplyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAgencyApply extends CreateRecord
{
    protected static string $resource = AgencyApplyResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::AGENCY->value;
        $data['phone_verified_at'] = now();
        $data['language'] = app()->getLocale();
        $data['is_active'] = true;
        $data['referral_code'] = Helper::generateReferCodeUser(UserRole::AGENCY);
        return $data;
    }
}
