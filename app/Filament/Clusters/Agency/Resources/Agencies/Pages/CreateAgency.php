<?php

namespace App\Filament\Clusters\Agency\Resources\Agencies\Pages;

use App\Enums\UserRole;
use App\Filament\Clusters\Agency\Resources\Agencies\AgencyResource;
use App\Services\WalletService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Enums\UserFileType;
use App\Services\UserFileService;

class CreateAgency extends CreateRecord
{
    protected static string $resource = AgencyResource::class;

    protected array $tempFiles = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::AGENCY->value;
        $data['phone_verified_at'] = now();
        $data['language'] = app()->getLocale();
        $data['is_active'] = true;

        if (array_key_exists('cccd_front_path', $data)) {
            $this->tempFiles['cccd_front_path'] = $data['cccd_front_path'];
            unset($data['cccd_front_path']);
        }
        if (array_key_exists('cccd_back_path', $data)) {
            $this->tempFiles['cccd_back_path'] = $data['cccd_back_path'];
            unset($data['cccd_back_path']);
        }
        if (array_key_exists('face_with_identity_card_path', $data)) {
            $this->tempFiles['face_with_identity_card_path'] = $data['face_with_identity_card_path'];
            unset($data['face_with_identity_card_path']);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);

        if (isset($record->id)) {
            $walletService = app(WalletService::class);
            $walletService->initWalletForStaff($record->id);

            /** @var UserFileService $service */
            $service = app(UserFileService::class);
            if (array_key_exists('cccd_front_path', $this->tempFiles)) {
                $service->syncUserFile($record->id, UserFileType::IDENTITY_CARD_FRONT, $this->tempFiles['cccd_front_path'], UserRole::AGENCY);
            }
            if (array_key_exists('cccd_back_path', $this->tempFiles)) {
                $service->syncUserFile($record->id, UserFileType::IDENTITY_CARD_BACK, $this->tempFiles['cccd_back_path'], UserRole::AGENCY);
            }
            if (array_key_exists('face_with_identity_card_path', $this->tempFiles)) {
                $service->syncUserFile($record->id, UserFileType::FACE_WITH_IDENTITY_CARD, $this->tempFiles['face_with_identity_card_path'], UserRole::AGENCY);
            }
        }

        return $record;
    }
}
