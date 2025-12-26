<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs\Pages;

use App\Core\Helper;
use App\Enums\UserRole;
use App\Filament\Clusters\KTV\Resources\KTVs\KTVResource;
use App\Services\WalletService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateKTV extends CreateRecord
{
    protected static string $resource = KTVResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set role to KTV
        $data['role'] = UserRole::KTV->value;
        $data['phone_verified_at'] = now();
        $data['language'] = app()->getLocale();
        $data['is_active'] = true;

        if (isset($data['reviewApplication']) && is_array($data['reviewApplication'])) {
            $data['reviewApplication']['role'] = UserRole::KTV->value;
        }

        if (isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $key => $file) {
                $data['files'][$key]['role'] = UserRole::KTV->value;
            }
        }

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
