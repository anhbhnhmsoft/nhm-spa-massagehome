<?php

namespace App\Filament\Clusters\Agency\Resources\AgencyApplies\Pages;

use App\Enums\ReviewApplicationStatus;
use App\Filament\Clusters\Agency\Resources\AgencyApplies\AgencyApplyResource;
use App\Services\UserService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewAgencyApply extends ViewRecord
{
    protected static string $resource = AgencyApplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('admin.ktv_apply.actions.approve.label'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('admin.ktv_apply.actions.approve.heading'))
                ->modalDescription(__('admin.ktv_apply.actions.approve.description'))
                ->visible(fn() => $this->record->reviewApplication?->status === ReviewApplicationStatus::PENDING || $this->record->reviewApplication?->status === ReviewApplicationStatus::REJECTED)
                ->action(function () {

                    $userService = app(UserService::class);
                    $result = $userService->activeKTVapply($this->record->id);
                    if ($result->isSuccess()) {
                        Notification::make()
                            ->success()
                            ->title(__('admin.ktv_apply.actions.approve.success_title'))
                            ->body(__('admin.ktv_apply.actions.approve.success_body'))
                            ->send();
                    } else {
                        Notification::make()
                            ->error()
                            ->title(__('admin.ktv_apply.actions.approve.error_title'))
                            ->body($result->getMessage())
                            ->send();
                    }

                    return redirect()->to(AgencyApplyResource::getUrl('index'));
                }),

            Action::make('reject')
                ->label(__('admin.ktv_apply.actions.reject.label'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('admin.ktv_apply.actions.reject.heading'))
                ->modalDescription(__('admin.ktv_apply.actions.reject.description'))
                ->form([
                    Textarea::make('note')
                        ->label(__('admin.ktv_apply.actions.reject.reason_label'))
                        ->required()
                        ->rows(3)
                        ->maxLength(500)
                        ->validationMessages([
                            'required' => __('common.error.required'),
                            'max'      => __('common.error.max_length', ['max' => 500]),
                        ]),
                ])
                ->visible(fn() => $this->record->reviewApplication?->status === ReviewApplicationStatus::PENDING)
                ->action(function (array $data) {

                    if ($this->record->reviewApplication) {
                        $this->record->reviewApplication->status = ReviewApplicationStatus::REJECTED;
                        $this->record->reviewApplication->note = $data['note'] ?? null;
                        $this->record->reviewApplication->save();;
                    }

                    Notification::make()
                        ->warning()
                        ->title(__('admin.ktv_apply.actions.reject.success_title'))
                        ->body(__('admin.ktv_apply.actions.reject.success_body'))
                        ->send();

                    return redirect()->to(AgencyApplyResource::getUrl('index'));
                }),
        ];
    }
}
