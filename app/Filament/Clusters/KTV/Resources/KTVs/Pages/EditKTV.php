<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs\Pages;

use App\Filament\Clusters\KTV\Resources\KTVs\KTVResource;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditKTV extends EditRecord
{
    protected static string $resource = KTVResource::class;

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
        return $data;
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
