<?php

namespace App\Livewire\Settings;

use App\Enums\ConfigName;
use App\Services\ConfigService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use Filament\Schemas\Components\Form;

class ConfigForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    public function mount(): void
    {
        $settingService = app(ConfigService::class);
        $result = $settingService->getAllConfigs();

        if ($result->isSuccess()) {
            $this->data = $result->getData();
            $this->form->fill($this->data);
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Form::make([
                    Grid::make()
                        ->schema([
                            TextInput::make((string) ConfigName::PAYOS_CLIENT_ID->value)
                                ->label(__('admin.setting.fields.payos_client_id'))
                                ->required()
                                ->rules([
                                    'required',
                                    'string',
                                ])
                                ->validationMessages([
                                    'required' => __('common.error.required'),
                                    'string' => __('common.error.string'),
                                ]),
                            TextInput::make((string) ConfigName::PAYOS_API_KEY->value)
                                ->label(__('admin.setting.fields.payos_api_key'))
                                ->required()
                                ->rules([
                                    'required',
                                    'string',
                                ])
                                ->validationMessages([
                                    'required' => __('common.error.required'),
                                    'string' => __('common.error.string'),
                                ]),
                            TextInput::make((string) ConfigName::PAYOS_CHECKSUM_KEY->value)
                                ->label(__('admin.setting.fields.payos_checksum_key'))
                                ->required()
                                ->rules([
                                    'required',
                                    'string',
                                ])
                                ->validationMessages([
                                    'required' => __('common.error.required'),
                                    'string' => __('common.error.string'),
                                ]),

                            TextInput::make((string) ConfigName::GOONG_API_KEY->value)
                                ->label(__('admin.setting.fields.goong_api_key'))
                                ->required()
                                ->rules([
                                    'required',
                                    'string',
                                ])
                                ->validationMessages([
                                    'required' => __('common.error.required'),
                                    'string' => __('common.error.string'),
                                ]),
                            TextInput::make((string) ConfigName::CURRENCY_EXCHANGE_RATE->value)
                                ->label(__('admin.setting.fields.currency_exchange_rate'))
                                ->numeric()
                                ->required()
                                ->rules([
                                    'required',
                                    'numeric',
                                    'min:0',
                                ])
                                ->validationMessages([
                                    'required' => __('common.error.required'),
                                    'numeric' => __('common.error.numeric'),
                                    'min' => __('common.error.min', ['min' => 0]),
                                ]),
                            TextInput::make((string) ConfigName::BREAK_TIME_GAP->value)
                                ->label(__('admin.setting.fields.break_time_gap'))
                                ->numeric()
                                ->required()
                                ->suffix(__('common.unit.minute'))
                                ->rules([
                                    'required',
                                    'numeric',
                                    'min:0',
                                ])
                                ->validationMessages([
                                    'required' => __('common.error.required'),
                                    'numeric' => __('common.error.numeric'),
                                    'min' => __('common.error.min', ['min' => 0]),
                                ]),
                            TextInput::make((string) ConfigName::DISCOUNT_RATE->value)
                                ->label(__('admin.setting.fields.discount_rate'))
                                ->helperText(__('admin.setting.fields.discount_rate_helper'))
                                ->numeric()
                                ->required()
                                ->suffix('%')
                                ->rules([
                                    'required',
                                    'numeric',
                                    'min:0',
                                ])
                                ->validationMessages([
                                    'required' => __('common.error.required'),
                                    'numeric' => __('common.error.numeric'),
                                    'min' => __('common.error.min', ['min' => 0]),
                                ]),
                        ])
                ])
            ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settingService = app(ConfigService::class);
        $result = $settingService->updateConfigs($data);

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
        return view('livewire.settings.config-form');
    }
}
