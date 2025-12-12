<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Setting extends Page
{
    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('admin.setting.label');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;
    protected string $view = 'filament.pages.setting';
    protected static int|null $navigationSort = 9999;
}
