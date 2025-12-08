<?php

namespace App\Filament\Clusters\KTV\Resources\KTVs\Pages;

use App\Filament\Clusters\KTV\Resources\KTVs\KTVResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditKTV extends EditRecord
{
    protected static string $resource = KTVResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load profile data into form
        if ($this->record->profile) {
            $data['profile'] = [
                'avatar_url' => $this->record->profile->avatar_url,
                'bio' => $this->record->profile->bio,
                'gender' => $this->record->profile->gender,
                'date_of_birth' => $this->record->profile->date_of_birth,
            ];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        // Handle profile data separately
        if (isset($data['profile'])) {
            $this->record->profile()->updateOrCreate(
                ['user_id' => $this->record->id],
                $data['profile']
            );
        }
    }
}
