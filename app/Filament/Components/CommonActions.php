<?php

namespace App\Filament\Components;

use Filament\Actions\Action;

class CommonActions
{
    public static function backAction($resource): Action
    {
        return Action::make('back')
            ->label(__('admin.common.back'))
            ->color('gray')
            ->url($resource::getUrl('index'))
            ->icon('heroicon-m-chevron-left');
    }

}
