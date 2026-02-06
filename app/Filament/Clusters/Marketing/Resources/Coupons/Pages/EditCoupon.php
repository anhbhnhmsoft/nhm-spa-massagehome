<?php

namespace App\Filament\Clusters\Marketing\Resources\Coupons\Pages;

use App\Filament\Clusters\Marketing\Resources\Coupons\CouponResource;
use App\Filament\Components\CommonActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.edit');
    }
}
