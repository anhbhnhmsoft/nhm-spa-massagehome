<?php

namespace App\Filament\Clusters\Service\Resources\Services\Pages;

use App\Filament\Clusters\Service\Resources\Services\ServiceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('admin.common.back')) // Hoặc "Quay lại"
                ->color('gray')
                ->url($this->getResource()::getUrl('index')) // Dẫn về trang danh sách của Resource này
                ->icon('heroicon-m-chevron-left'),
        ];
    }

}
