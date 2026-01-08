<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs\Pages;

use App\Filament\Clusters\KTV\Resources\KTVs\KTVResource;
use App\Enums\UserRole;
use App\Enums\UserFileType;
use App\Models\UserFile;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditKTV extends EditRecord
{
    protected static string $resource = KTVResource::class;

    protected array $tempFiles = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function resolveRecord($key): Model
    {
        return static::getResource()::getModel()::with(['reviewApplication', 'files'])->findOrFail($key);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!isset($data['reviewApplication']) || !is_array($data['reviewApplication'])) {
            $data['reviewApplication'] = [];
        }
        $data['reviewApplication']['role'] = UserRole::KTV->value;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['reviewApplication'])) {
            if (is_array($data['reviewApplication']) && isset($data['reviewApplication'][0])) {
                // Nếu là array với phần tử đầu tiên
                $data['reviewApplication'][0]['role'] = UserRole::KTV->value;
            } elseif (is_array($data['reviewApplication']) && !isset($data['reviewApplication'][0])) {
                // Nếu là array rỗng hoặc associative array
                $data['reviewApplication']['role'] = UserRole::KTV->value;
            }
        }

        if (isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $key => $file) {
                if (is_array($file)) {
                    $data['files'][$key]['role'] = UserRole::KTV->value;
                }
            }
        }

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

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if (array_key_exists('cccd_front_path', $this->tempFiles)) {
            UserFile::updateOrCreate(
                ['user_id' => $record->id, 'type' => UserFileType::IDENTITY_CARD_FRONT],
                ['file_path' => $this->tempFiles['cccd_front_path'], 'role' => UserRole::KTV->value, 'is_public' => false]
            );
        }
        if (array_key_exists('cccd_back_path', $this->tempFiles)) {
            UserFile::updateOrCreate(
                ['user_id' => $record->id, 'type' => UserFileType::IDENTITY_CARD_BACK],
                ['file_path' => $this->tempFiles['cccd_back_path'], 'role' => UserRole::KTV->value, 'is_public' => false]
            );
        }
        if (array_key_exists('certificate_path', $this->tempFiles)) {
            if ($this->tempFiles['certificate_path'] === null) {
                UserFile::where('user_id', $record->id)
                    ->where('type', UserFileType::LICENSE)
                    ->delete();
            } else {
                UserFile::updateOrCreate(
                    ['user_id' => $record->id, 'type' => UserFileType::LICENSE],
                    ['file_path' => $this->tempFiles['certificate_path'], 'role' => UserRole::KTV->value, 'is_public' => false]
                );
            }
        }
        if (array_key_exists('face_with_identity_card_path', $this->tempFiles)) {
            if ($this->tempFiles['face_with_identity_card_path'] === null) {
                UserFile::where('user_id', $record->id)
                    ->where('type', UserFileType::FACE_WITH_IDENTITY_CARD)
                    ->delete();
            } else {
                UserFile::updateOrCreate(
                    ['user_id' => $record->id, 'type' => UserFileType::FACE_WITH_IDENTITY_CARD],
                    ['file_path' => $this->tempFiles['face_with_identity_card_path'], 'role' => UserRole::KTV->value, 'is_public' => false]
                );
            }
        }
    }

    protected function mutateFormDataBeforeValidate(array $data): array
    {
        if (isset($data['reviewApplication'])) {
            if (is_array($data['reviewApplication']) && isset($data['reviewApplication'][0])) {
                $data['reviewApplication'][0]['role'] = UserRole::KTV->value;
            } elseif (is_array($data['reviewApplication']) && !isset($data['reviewApplication'][0])) {
                $data['reviewApplication']['role'] = UserRole::KTV->value;
            }
        } else {
            $data['reviewApplication'] = ['role' => UserRole::KTV->value];
        }

        if (isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $key => $file) {
                if (is_array($file)) {
                    $data['files'][$key]['role'] = UserRole::KTV->value;
                }
            }
        }

        return $data;
    }
}
