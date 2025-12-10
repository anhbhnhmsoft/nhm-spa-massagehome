<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies\Pages;

use App\Enums\ReviewApplicationStatus;
use App\Filament\Clusters\KTV\Resources\KTVApplies\KTVApplyResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewKTVApply extends ViewRecord
{
    protected static string $resource = KTVApplyResource::class;

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
                    $this->record->is_active = true;
                    $this->record->save();

                    if ($this->record->reviewApplication) {
                        $this->record->reviewApplication->status = ReviewApplicationStatus::APPROVED;
                        $this->record->reviewApplication->effective_date = now();
                        $this->record->reviewApplication->save();
                    }

                    Notification::make()
                        ->success()
                        ->title(__('admin.ktv_apply.actions.approve.success_title'))
                        ->body(__('admin.ktv_apply.actions.approve.success_body'))
                        ->send();

                    return redirect()->to(KTVApplyResource::getUrl('index'));
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
                        ->maxLength(500),
                ])
                ->visible(fn() => $this->record->reviewApplication?->status === ReviewApplicationStatus::PENDING)
                ->action(function (array $data) {
                    if ($this->record->reviewApplication) {
                        $this->record->reviewApplication->status = ReviewApplicationStatus::REJECTED;
                        $this->record->reviewApplication->note = $data['note'] ?? null;
                        $this->record->reviewApplication->save();
                    }

                    Notification::make()
                        ->warning()
                        ->title(__('admin.ktv_apply.actions.reject.success_title'))
                        ->body(__('admin.ktv_apply.actions.reject.success_body'))
                        ->send();

                    return redirect()->to(KTVApplyResource::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load profile data
        if ($this->record->profile) {
            $data['profile'] = [
                'avatar_url' => $this->record->profile->avatar_url,
                'bio' => $this->record->profile->bio,
                'gender' => $this->record->profile->gender,
                'date_of_birth' => $this->record->profile->date_of_birth,
            ];
        }

        // Load review application data
        if ($this->record->reviewApplication) {
            $data['reviewApplication'] = [
                'status' => $this->record->reviewApplication->status,
                'province_code' => $this->record->reviewApplication->province_code,
                'address' => $this->record->reviewApplication->address,
                'bio' => $this->record->reviewApplication->bio,
                'experience' => $this->record->reviewApplication->experience,
                'agency_id' => $this->record->reviewApplication->agency_id,
                ''
            ];
        }

        // Load files data
        if ($this->record->files) {
            $data['files'] = $this->record->files->toArray();
        }


        return $data;
    }
}
