<?php

namespace App\Filament\Clusters\Agency\Resources\Agencies\Pages;

use App\Core\Helper;
use App\Enums\ReviewApplicationStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\Agency\Resources\Agencies\AgencyResource;
use App\Services\WalletService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAgency extends CreateRecord
{
    protected static string $resource = AgencyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::AGENCY->value;
        $data['phone_verified_at'] = now();
        $data['language'] = app()->getLocale();
        $data['is_active'] = true;
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);

        if(isset($record->id) ){
            $walletService = app(WalletService::class);
            $walletService->initWalletForStaff($record->id);
        }

        return $record;
    }
}
