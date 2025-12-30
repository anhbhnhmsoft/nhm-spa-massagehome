<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies\Pages;

use App\Filament\Clusters\KTV\Resources\KTVApplies\KTVApplyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use App\Services\UserFileService;
use App\Enums\UserFileType;
use App\Enums\UserRole;

class EditKTVApply extends EditRecord
{
    protected static string $resource = KTVApplyResource::class;

    protected array $tempFiles = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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
        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
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
    }
}
