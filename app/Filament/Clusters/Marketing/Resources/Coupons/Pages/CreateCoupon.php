<?php

namespace App\Filament\Clusters\Marketing\Resources\Coupons\Pages;

use App\Filament\Clusters\Marketing\Resources\Coupons\CouponResource;
use App\Filament\Components\CommonActions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
        ];
    }

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::user()->id;
        return $data;
    }

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.create');
    }
}
