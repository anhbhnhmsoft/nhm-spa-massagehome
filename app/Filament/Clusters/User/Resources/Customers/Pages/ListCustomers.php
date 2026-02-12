<?php

namespace App\Filament\Clusters\User\Resources\Customers\Pages;

use App\Filament\Clusters\User\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }


    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.list');
    }

}
