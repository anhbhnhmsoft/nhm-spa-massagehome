<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies\Pages;

use App\Enums\UserRole;
use App\Filament\Clusters\KTV\Resources\KTVApplies\KTVApplyResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\UserFileService;
use App\Enums\UserFileType;
use Illuminate\Database\Eloquent\Model;

class CreateKTVApply extends CreateRecord
{
    protected static string $resource = KTVApplyResource::class;

    protected array $tempFiles = [];

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::KTV->value;
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
        if (array_key_exists('certificate_path', $data)) {
            $this->tempFiles['certificate_path'] = $data['certificate_path'];
            unset($data['certificate_path']);
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

        /** @var UserFileService $service */
        $service = app(UserFileService::class);

        if (array_key_exists('cccd_front_path', $this->tempFiles)) {
            $service->syncUserFile($record->id, UserFileType::IDENTITY_CARD_FRONT, $this->tempFiles['cccd_front_path'], UserRole::KTV);
        }
        if (array_key_exists('cccd_back_path', $this->tempFiles)) {
            $service->syncUserFile($record->id, UserFileType::IDENTITY_CARD_BACK, $this->tempFiles['cccd_back_path'], UserRole::KTV);
        }
        if (array_key_exists('certificate_path', $this->tempFiles)) {
            $service->syncUserFile($record->id, UserFileType::LICENSE, $this->tempFiles['certificate_path'], UserRole::KTV);
        }
        if (array_key_exists('face_with_identity_card_path', $this->tempFiles)) {
            $service->syncUserFile($record->id, UserFileType::FACE_WITH_IDENTITY_CARD, $this->tempFiles['face_with_identity_card_path'], UserRole::KTV);
        }

        return $record;
    }
}
