<?php

namespace App\Filament\Clusters\ReviewApplication\Resources\KTVs\Pages;

use App\Enums\ReviewApplicationStatus;
use App\Filament\Clusters\ReviewApplication\Resources\KTVs\KTVResource;
use App\Enums\UserRole;
use App\Enums\UserFileType;
use App\Filament\Components\CommonActions;
use App\Models\UserFile;
use App\Services\UserService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditKTV extends EditRecord
{
    protected static string $resource = KTVResource::class;

    protected array $tempFiles = [];

    protected UserService $userService;

    public function boot(UserService $userService): void
    {
        $this->userService = $userService;
    }
    protected function getHeaderActions(): array
    {
        return [
            CommonActions::backAction(static::getResource()),
            // Hiển thị nút Approve nếu trạng thái là PENDING hoặc REJECTED
            Action::make('approve')
                ->label(__('admin.ktv_apply.actions.approve.label'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('admin.ktv_apply.actions.approve.heading'))
                ->modalDescription(__('admin.ktv_apply.actions.approve.description'))
                ->visible(function() {
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
                            ->title(__('admin.ktv_apply.actions.approve.success_title'))
                            ->body(__('admin.ktv_apply.actions.approve.success_body'))
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
                ->label(__('admin.ktv_apply.actions.reject.label'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('admin.ktv_apply.actions.reject.heading'))
                ->modalDescription(__('admin.ktv_apply.actions.reject.description'))
                ->schema([
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
                ->visible(fn() => $this->record->reviewApplication?->status == ReviewApplicationStatus::PENDING)
                ->action(function (array $data) {
                    $result = $this->userService->rejectStaffApply($this->record->id, $data['note']);

                    if ($result->isSuccess()) {
                        Notification::make()
                            ->warning()
                            ->title(__('admin.ktv_apply.actions.reject.success_title'))
                            ->body(__('admin.ktv_apply.actions.reject.success_body'))
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

            DeleteAction::make(),
        ];
    }

    protected function resolveRecord($key): Model
    {
        return static::getResource()::getModel()::with(['reviewApplication', 'files'])->findOrFail($key);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!isset($data['reviewApplication']) || !is_array($data['reviewApplication'])) {
            $data['reviewApplication'] = [];
        }
        $data['reviewApplication']['role'] = UserRole::KTV->value;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['reviewApplication'])) {
            if (is_array($data['reviewApplication']) && isset($data['reviewApplication'][0])) {
                // Nếu là array với phần tử đầu tiên
                $data['reviewApplication'][0]['role'] = UserRole::KTV->value;
            } elseif (is_array($data['reviewApplication']) && !isset($data['reviewApplication'][0])) {
                // Nếu là array rỗng hoặc associative array
                $data['reviewApplication']['role'] = UserRole::KTV->value;
            }
        }

        if (isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $key => $file) {
                if (is_array($file)) {
                    $data['files'][$key]['role'] = UserRole::KTV->value;
                }
            }
        }

        if (array_key_exists('cccd_front_path', $data)) {
            $this->tempFiles['cccd_front_path'] = $data['cccd_front_path'];
            unset($data['cccd_front_path']);
        }
        if (array_key_exists('cccd_back_path', $data)) {
            $this->tempFiles['cccd_back_path'] = $data['cccd_back_path'];
            unset($data['cccd_back_path']);
        }
        if (array_key_exists('certificate_path', $data)) {
            $this->tempFiles['certificate_path'] = $data['certificate_path'];
            unset($data['certificate_path']);
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

        if (array_key_exists('cccd_front_path', $this->tempFiles)) {
            UserFile::updateOrCreate(
                ['user_id' => $record->id, 'type' => UserFileType::IDENTITY_CARD_FRONT],
                ['file_path' => $this->tempFiles['cccd_front_path'], 'role' => UserRole::KTV->value, 'is_public' => false]
            );
        }
        if (array_key_exists('cccd_back_path', $this->tempFiles)) {
            UserFile::updateOrCreate(
                ['user_id' => $record->id, 'type' => UserFileType::IDENTITY_CARD_BACK],
                ['file_path' => $this->tempFiles['cccd_back_path'], 'role' => UserRole::KTV->value, 'is_public' => false]
            );
        }
        if (array_key_exists('certificate_path', $this->tempFiles)) {
            if ($this->tempFiles['certificate_path'] === null) {
                UserFile::where('user_id', $record->id)
                    ->where('type', UserFileType::LICENSE)
                    ->delete();
            } else {
                UserFile::updateOrCreate(
                    ['user_id' => $record->id, 'type' => UserFileType::LICENSE],
                    ['file_path' => $this->tempFiles['certificate_path'], 'role' => UserRole::KTV->value, 'is_public' => false]
                );
            }
        }
        if (array_key_exists('face_with_identity_card_path', $this->tempFiles)) {
            if ($this->tempFiles['face_with_identity_card_path'] === null) {
                UserFile::where('user_id', $record->id)
                    ->where('type', UserFileType::FACE_WITH_IDENTITY_CARD)
                    ->delete();
            } else {
                UserFile::updateOrCreate(
                    ['user_id' => $record->id, 'type' => UserFileType::FACE_WITH_IDENTITY_CARD],
                    ['file_path' => $this->tempFiles['face_with_identity_card_path'], 'role' => UserRole::KTV->value, 'is_public' => false]
                );
            }
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
    protected function mutateFormDataBeforeValidate(array $data): array
    {
        if (isset($data['reviewApplication'])) {
            if (is_array($data['reviewApplication']) && isset($data['reviewApplication'][0])) {
                $data['reviewApplication'][0]['role'] = UserRole::KTV->value;
            } elseif (is_array($data['reviewApplication']) && !isset($data['reviewApplication'][0])) {
                $data['reviewApplication']['role'] = UserRole::KTV->value;
            }
        } else {
            $data['reviewApplication'] = ['role' => UserRole::KTV->value];
        }

        if (isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $key => $file) {
                if (is_array($file)) {
                    $data['files'][$key]['role'] = UserRole::KTV->value;
                }
            }
        }

        return $data;
    }
}
