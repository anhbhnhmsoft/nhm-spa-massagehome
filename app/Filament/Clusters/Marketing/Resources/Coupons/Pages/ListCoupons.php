<?php

namespace App\Filament\Clusters\Marketing\Resources\Coupons\Pages;

use App\Filament\Clusters\Marketing\Resources\Coupons\CouponResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoupons extends ListRecords
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.common.action.create')),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.list');
    }
}
