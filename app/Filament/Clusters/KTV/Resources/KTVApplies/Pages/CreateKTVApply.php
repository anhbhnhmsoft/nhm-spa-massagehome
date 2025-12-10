<?php

namespace App\Filament\Clusters\KTV\Resources\KTVApplies\Pages;

use App\Core\Service\ServiceReturn;
use App\Enums\UserRole;
use App\Filament\Clusters\KTV\Resources\KTVApplies\KTVApplyResource;
use App\Services\UserService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateKTVApply extends CreateRecord
{
    protected static string $resource = KTVApplyResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::KTV->value;
        $data['is_active'] = false;
        return $data;
    }

    /**
     * @param array $data
     * @return Model
     */
    protected function handleRecordCreation(array $data): Model
    {
        $userService = app(UserService::class);
        /**
         * @var ServiceReturn $result
         */
        $result = $userService->makeNewApplyKTV($data);

        if ($result->isSuccess()) {
            // Notification::make()
            //     ->title(__('admin.notification.success.create_success'))
            //     ->success()
            //     ->send();
            return $result->getData();
        }

        Notification::make()
            ->title(__('admin.notification.error.create_error'))
            ->body($result->getMessage())
            ->warning()
            ->send();
        $this->halt();
        $modelClass = static::getModel();

        return new $modelClass();
    }
}
