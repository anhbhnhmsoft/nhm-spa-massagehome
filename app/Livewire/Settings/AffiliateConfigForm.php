<?php

namespace App\Livewire\Settings;

use App\Services\ConfigService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Livewire\Component;

class AffiliateConfigForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $settingService = app(ConfigService::class);
        $result = $settingService->getAllAffiliateConfigs();
        if ($result->isSuccess()) {
            $this->form->fill([
                'configs' => $result->getData(),
            ]);
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Repeater::make('configs')
                    ->label(__('admin.setting.section.affiliate_config'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.common.table.name'))
                            ->required(),
                        TextInput::make('commission_rate')
                            ->label(__('admin.setting.fields.commission_rate'))
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        TextInput::make('min_commission')
                            ->label(__('admin.setting.fields.min_commission'))
                            ->numeric()
                            ->prefix('point')
                            ->required(),
                        TextInput::make('max_commission')
                            ->label(__('admin.setting.fields.max_commission'))
                            ->numeric()
                            ->prefix('point')
                            ->required(),
                        Select::make('target_role')
                            ->label(__('admin.setting.fields.target_role'))
                            ->options(\App\Enums\UserRole::class)
                            ->required()
                            ->disabled(),
                        Toggle::make('is_active')->label(__('admin.setting.fields.is_active'))->disabled(),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->itemLabel(fn(array $state): ?string => $state['name'] ?? null),
            ])->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $settingService = app(ConfigService::class);
        $result = $settingService->updateAffiliateConfigs($data['configs']);

        if ($result->isSuccess()) {
            Notification::make()
                ->title(__('admin.notification.success.update_success'))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title($result->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.settings.affiliate-config-form');
    }
}
