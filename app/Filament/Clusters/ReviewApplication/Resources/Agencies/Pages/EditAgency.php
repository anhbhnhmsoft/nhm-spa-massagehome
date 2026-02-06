<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\Agencies\Pages;

use App\Enums\ReviewApplicationStatus;
use App\Enums\UserFileType;
use App\Enums\UserRole;
use App\Filament\Clusters\ReviewApplication\Resources\Agencies\AgencyResource;
use App\Filament\Components\CommonActions;
use App\Services\UserFileService;
use App\Services\UserService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAgency extends EditRecord
{
    protected static string $resource = AgencyResource::class;

    protected array $tempFiles = [];

    protected UserService $userService;

    public function getBreadcrumb(): string
    {
        return __('common.breadcrumb.edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
            // Hiển thị nút Approve nếu trạng thái là PENDING hoặc REJECTED
            Action::make('approve')
                ->label(__('admin.agency_apply.actions.approve.label'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('admin.agency_apply.actions.approve.heading'))
                ->modalDescription(__('admin.agency_apply.actions.approve.description'))
                ->visible(function () {
                    $status = $this->record->reviewApplication?->status;
                    return in_array($status, [
                        ReviewApplicationStatus::PENDING,
                        ReviewApplicationStatus::REJECTED,
                    ]);
                })
                ->action(function () {
                    $result = $this->userService->activeStaffApply($this->record->id);

                    if ($result->isSuccess()) {
                        Notification::make()
                            ->success()
                            ->title(__('admin.agency_apply.actions.approve.success_title'))
                            ->body(__('admin.agency_apply.actions.approve.success_body'))
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title(__('common.error.title'))
                            ->body($result->getMessage())
                            ->send();
                    }
                    return redirect()->to($this->getResource()::getUrl('index'));
                }),

            Action::make('reject')
                ->label(__('admin.agency_apply.actions.reject.label'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('admin.agency_apply.actions.reject.heading'))
                ->modalDescription(__('admin.agency_apply.actions.reject.description'))
                ->schema([
                    Textarea::make('note')
                        ->label(__('admin.agency_apply.actions.reject.reason_label'))
                        ->required()
                        ->rows(3)
                        ->maxLength(500)
                        ->validationMessages([
                            'required' => __('common.error.required'),
                            'max' => __('common.error.max_length', ['max' => 500]),
                        ]),
                ])
                ->visible(fn() => $this->record->reviewApplication?->status == ReviewApplicationStatus::PENDING)
                ->action(function (array $data) {
                    $result = $this->userService->rejectStaffApply($this->record->id, $data['note']);

                    if ($result->isSuccess()) {
                        Notification::make()
                            ->warning()
                            ->title(__('admin.agency_apply.actions.reject.success_title'))
                            ->body(__('admin.agency_apply.actions.reject.success_body'))
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title(__('common.error.title'))
                            ->body($result->getMessage())
                            ->send();
                    }
                    return redirect()->to($this->getResource()::getUrl('index'));
                }),

            DeleteAction::make()
                ->label(__('common.action.delete')),
        ];
    }

    public function boot(UserService $userService): void
    {
        $this->userService = $userService;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('cccd_front_path', $data)) {
            $this->tempFiles['cccd_front_path'] = $data['cccd_front_path'];
            unset($data['cccd_front_path']);
        }
        if (array_key_exists('cccd_back_path', $data)) {
            $this->tempFiles['cccd_back_path'] = $data['cccd_back_path'];
            unset($data['cccd_back_path']);
        }
        if (array_key_exists('face_with_identity_card_path', $data)) {
            $this->tempFiles['face_with_identity_card_path'] = $data['face_with_identity_card_path'];
            unset($data['face_with_identity_card_path']);
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        /** @var UserFileService $service */
        $service = app(UserFileService::class);

        if (array_key_exists('cccd_front_path', $this->tempFiles)) {
            $service->syncUserFile($record->id, UserFileType::IDENTITY_CARD_FRONT, $this->tempFiles['cccd_front_path'], UserRole::AGENCY);
        }
        if (array_key_exists('cccd_back_path', $this->tempFiles)) {
            $service->syncUserFile($record->id, UserFileType::IDENTITY_CARD_BACK, $this->tempFiles['cccd_back_path'], UserRole::AGENCY);
        }
        if (array_key_exists('face_with_identity_card_path', $this->tempFiles)) {
            $service->syncUserFile($record->id, UserFileType::FACE_WITH_IDENTITY_CARD, $this->tempFiles['face_with_identity_card_path'], UserRole::AGENCY);
        }
    }

    protected function getSaveFormAction(): Action
    {
        $record = $this->getRecord();
        $status = $record->reviewApplication?->status;
        $isLocked = in_array($status, [
            ReviewApplicationStatus::PENDING,
            ReviewApplicationStatus::REJECTED,
        ]);
        return parent::getSaveFormAction()
            // 3. Đổi màu sang xám nếu bị khóa (tạo cảm giác disabled)
            ->color($isLocked ? 'gray' : 'primary')
            ->icon($isLocked ? 'heroicon-m-lock-closed' : 'heroicon-m-check')
            // 4. Thêm Tooltip giải thích lý do
            ->tooltip($isLocked ? __('admin.common.tooltip.cant_not_save_review_application') : null)
            ->disabled($isLocked);
    }


    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(__('common.action.save')),
            $this->getCancelFormAction()
                ->label(__('common.action.cancel')),
        ];
    }
}
