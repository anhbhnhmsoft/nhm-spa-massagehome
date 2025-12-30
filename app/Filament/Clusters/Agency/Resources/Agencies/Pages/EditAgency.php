<?php

namespace App\Filament\Clusters\Agency\Resources\Agencies\Pages;

use App\Filament\Clusters\Agency\Resources\Agencies\AgencyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use App\Enums\UserFileType;
use App\Enums\UserRole;
use App\Services\UserFileService;

class EditAgency extends EditRecord
{
    protected static string $resource = AgencyResource::class;

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
        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        /** @var UserFileService $service */
        $service = app(UserFileService::class);

        if (array_key_exists('cccd_front_path', $this->tempFiles)) {
            $service->syncUserFile($record->id, UserFileType::IDENTITY_CARD_FRONT, $this->tempFiles['cccd_front_path'], UserRole::AGENCY);
        }
        if (array_key_exists('cccd_back_path', $this->tempFiles)) {
            $service->syncUserFile($record->id, UserFileType::IDENTITY_CARD_BACK, $this->tempFiles['cccd_back_path'], UserRole::AGENCY);
        }
    }
}
