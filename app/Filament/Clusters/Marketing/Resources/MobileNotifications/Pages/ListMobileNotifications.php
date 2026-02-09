<?php

namespace App\Filament\Clusters\Marketing\Resources\MobileNotifications\Pages;

use App\Enums\RecieverNotification;
use App\Filament\Clusters\Marketing\Resources\MobileNotifications\MobileNotificationResource;
use App\Services\NotificationService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;

class ListMobileNotifications extends ListRecords
{
    protected static string $resource = MobileNotificationResource::class;

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.list');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.mobile_notification.action.create'))
                ->modalDescription(__('admin.mobile_notification.action.modal_description'))
                ->successNotificationTitle(__('admin.mobile_notification.action.success_notification_title'))
                ->schema([
                    Section::make()
                        ->columns(2)
                        ->schema([
                            TextInput::make('title_vi')
                                ->label(__('admin.mobile_notification.fields.title_vi'))
                                ->required()
                                ->maxLength(255)
                                ->validationMessages([
                                    'required' => __('admin.mobile_notification.validation_messages.required'),
                                    'max_length' => __('admin.mobile_notification.validation_messages.max_length', ['max' => 255]),
                                ]),
                            TextInput::make('description_vi')
                                ->label(__('admin.mobile_notification.fields.description_vi'))
                                ->required()
                                ->maxLength(255)
                                ->validationMessages([
                                    'required' => __('admin.mobile_notification.validation_messages.required'),
                                    'max_length' => __('admin.mobile_notification.validation_messages.max_length', ['max' => 255]),
                                ]),
                            TextInput::make('title_en')
                                ->label(__('admin.mobile_notification.fields.title_en'))
                                ->required()
                                ->maxLength(255)
                                ->validationMessages([
                                    'required' => __('admin.mobile_notification.validation_messages.required'),
                                    'max_length' => __('admin.mobile_notification.validation_messages.max_length', ['max' => 255]),
                                ]),
                            TextInput::make('description_en')
                                ->label(__('admin.mobile_notification.fields.description_en'))
                                ->required()
                                ->maxLength(255)
                                ->validationMessages([
                                    'required' => __('admin.mobile_notification.validation_messages.required'),
                                    'max_length' => __('admin.mobile_notification.validation_messages.max_length', ['max' => 255]),
                                ]),
                            TextInput::make('title_cn')
                                ->label(__('admin.mobile_notification.fields.title_cn'))
                                ->required()
                                ->maxLength(255)
                                ->validationMessages([
                                    'required' => __('admin.mobile_notification.validation_messages.required'),
                                    'max_length' => __('admin.mobile_notification.validation_messages.max_length', ['max' => 255]),
                                ]),
                            TextInput::make('description_cn')
                                ->label(__('admin.mobile_notification.fields.description_cn'))
                                ->required()
                                ->maxLength(255)
                                ->validationMessages([
                                    'required' => __('admin.mobile_notification.validation_messages.required'),
                                    'max_length' => __('admin.mobile_notification.validation_messages.max_length', ['max' => 255]),
                                ]),
                            Select::make('receiver')
                                ->label(__('admin.mobile_notification.receiver.label'))
                                ->placeholder(__('common.placeholder.type'))
                                ->options(fn() => RecieverNotification::toOptions())
                                ->required(),
                        ])
                ])
                ->action(function ($data) {
                    $service = app(NotificationService::class);
                    $receiver = RecieverNotification::from($data['receiver']);
                    $service->sendGlobalNotification($data, $receiver);
                }),
        ];
    }
}
