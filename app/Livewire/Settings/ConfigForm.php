<?php

namespace App\Livewire\Settings;

use App\Enums\ConfigName;
use App\Services\ConfigService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                            Section::make(__('admin.setting.label_setting'))
                                ->columns(2)
                                ->schema([
                                    TextInput::make((string)ConfigName::PAYOS_CLIENT_ID->value)
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
                                    TextInput::make((string)ConfigName::PAYOS_API_KEY->value)
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
                                    TextInput::make((string)ConfigName::PAYOS_CHECKSUM_KEY->value)
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

                                    TextInput::make((string)ConfigName::GOONG_API_KEY->value)
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
                                    TextInput::make((string)ConfigName::CURRENCY_EXCHANGE_RATE->value)
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
                                    TextInput::make((string)ConfigName::BREAK_TIME_GAP->value)
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
                                    TextInput::make((string)ConfigName::SP_ZALO->value)
                                        ->label(__('admin.setting.fields.sp_zalo'))
                                        ->rules([
                                            'string',
                                        ])
                                        ->validationMessages([
                                            'required' => __('common.error.required'),
                                            'string' => __('common.error.string'),
                                        ]),
                                    TextInput::make((string)ConfigName::SP_FACEBOOK->value)
                                        ->label(__('admin.setting.fields.sp_facebook'))
                                        ->rules([
                                            'string',
                                        ])
                                        ->validationMessages([
                                            'required' => __('common.error.required'),
                                            'string' => __('common.error.string'),
                                        ]),
                                    TextInput::make((string)ConfigName::SP_PHONE->value)
                                        ->label(__('admin.setting.fields.sp_phone'))
                                        ->rules([
                                            'string',
                                        ])
                                        ->validationMessages([
                                            'string' => __('common.error.string'),
                                        ]),
                                    TextInput::make((string)ConfigName::SP_WECHAT->value)
                                        ->label(__('admin.setting.fields.sp_wechat'))
                                        ->rules([
                                            'string',
                                        ])
                                        ->validationMessages([
                                            'string' => __('common.error.string'),
                                        ]),
                                    TextInput::make((string)ConfigName::ZALO_MERCHANT_ID->value)
                                        ->label(__('admin.setting.fields.zalo_merchant_id'))
                                        ->helperText(__('admin.setting.fields.zalo_merchant_id_helper'))
                                        ->required()
                                        ->rules([
                                            'required',
                                            'min:0',
                                        ])
                                        ->validationMessages([
                                            'required' => __('common.error.required'),
                                            'min' => __('common.error.min', ['min' => 0]),
                                        ]),
                                    TextInput::make((string)ConfigName::ZALO_MERCHANT_KEY_1->value)
                                        ->label(__('admin.setting.fields.zalo_merchant_key_1'))
                                        ->helperText(__('admin.setting.fields.zalo_merchant_key_1_helper'))
                                        ->required()
                                        ->rules([
                                            'required',
                                        ])
                                        ->validationMessages([
                                            'required' => __('common.error.required'),
                                        ]),
                                    TextInput::make((string)ConfigName::ZALO_MERCHANT_KEY_2->value)
                                        ->label(__('admin.setting.fields.zalo_merchant_key_2'))
                                        ->helperText(__('admin.setting.fields.zalo_merchant_key_2_helper'))
                                        ->required()
                                        ->rules([
                                            'required',
                                        ])
                                        ->validationMessages([
                                            'required' => __('common.error.required'),
                                        ]),
                                    TextInput::make((string)ConfigName::ZALO_APP_ID->value)
                                        ->label(__('admin.setting.fields.zalo_app_id'))
                                        ->helperText(__('admin.setting.fields.zalo_app_id_helper'))
                                        ->required()
                                        ->rules([
                                            'required',
                                        ])
                                        ->validationMessages([
                                            'required' => __('common.error.required'),
                                        ]),
                                    TextInput::make((string)ConfigName::ZALO_APPSECRET_KEY->value)
                                        ->label(__('admin.setting.fields.zalo_appsecret_key'))
                                        ->helperText(__('admin.setting.fields.zalo_appsecret_key_helper'))
                                        ->required()
                                        ->rules([
                                            'required',
                                        ])
                                        ->validationMessages([
                                            'required' => __('common.error.required'),
                                        ]),
                                    TextInput::make((string)ConfigName::ZALO_OA_ID->value)
                                        ->label(__('admin.setting.fields.zalo_oa_id'))
                                        ->helperText(__('admin.setting.fields.zalo_oa_id_helper'))
                                        ->required()
                                        ->rules([
                                            'required',
                                        ])
                                        ->validationMessages([
                                            'required' => __('common.error.required'),
                                        ]),
                                    TextInput::make((string)ConfigName::ZALO_TEMPLATE_ID->value)
                                        ->label(__('admin.setting.fields.zalo_template_id'))
                                        ->helperText(__('admin.setting.fields.zalo_template_id_helper'))
                                        ->required()
                                        ->rules([
                                            'required',
                                        ])
                                        ->validationMessages([
                                            'required' => __('common.error.required'),
                                        ]),
                                ]),
                            Section::make(__('admin.setting.label_config_discount_rate'))
                                ->columns(2)
                                ->schema([
                                    TextInput::make((string)ConfigName::DISCOUNT_RATE->value)
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
                                    TextInput::make((string)ConfigName::DISCOUNT_RATE_REFERRER_AGENCY->value)
                                        ->label(__('admin.setting.fields.discount_rate_referrer_agency'))
                                        ->helperText(__('admin.setting.fields.discount_rate_referrer_agency_helper'))
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
                                    TextInput::make((string)ConfigName::DISCOUNT_RATE_REFERRER_KTV->value)
                                        ->label(__('admin.setting.fields.discount_rate_referrer_ktv'))
                                        ->helperText(__('admin.setting.fields.discount_rate_referrer_ktv_helper'))
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
                                    TextInput::make((string)ConfigName::DISCOUNT_RATE_REFERRER_KTV_LEADER->value)
                                        ->label(__('admin.setting.fields.discount_rate_referrer_ktv_leader'))
                                        ->helperText(__('admin.setting.fields.discount_rate_referrer_ktv_leader_helper'))
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
