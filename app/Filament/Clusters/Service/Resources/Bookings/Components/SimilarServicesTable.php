<?php

namespace App\Filament\Clusters\Service\Resources\Bookings\Components;

use App\Core\Helper;
use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Filament\Components\CommonFields;
use App\Services\BookingService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SimilarServicesTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithForms;
    use InteractsWithTable;
    use InteractsWithActions;

    public int $bookingId;

    public function table(Table $table): Table
    {
        return $table
            ->records(function (BookingService $bookingService) {
                $result = $bookingService->findNearbyAvailableKtvs($this->bookingId);
                if ($result->isError()){
                    return [];
                }
                return $result->getData()->toArray();
            })
            ->columns([
                CommonFields::IdColumn(),
                ImageColumn::make('profile.avatar_url')
                    ->label(__('admin.common.table.avatar'))
                    ->width('80px')
                    ->disk('public')
                    ->alignCenter()
                    ->defaultImageUrl(url('/images/avatar-default.svg')),
                TextColumn::make('name')
                    ->label(__('admin.common.table.name')),
                TextColumn::make('distance_in_meters')
                    ->label(__('admin.booking.fields.distance_to_client'))
                    ->formatStateUsing(fn($state) => Helper::formatDistanceMeter($state)),
            ])
            ->recordActions([
                Action::make('reassign')
                    ->label(__('admin.booking.actions.reassign.label')) // Reusing existing translation key 'label' => 'Chuyển KTV'
                    ->button()
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.booking.actions.reassign.heading'))
                    ->modalDescription(__('admin.booking.actions.reassign.description'))
                    ->modalSubmitActionLabel(__('admin.booking.actions.reassign.modal_submit'))
                    ->action(function ($record, BookingService $service) {
                        $result = $service->handleReassignBooking(
                            bookingId:  $this->bookingId,
                            newKtvId: $record['id'],
                        );

                        if ($result->isError()) {
                            Notification::make()
                                ->title(__('admin.booking.actions.reassign.error_title'))
                                ->danger()
                                ->send();
                        }else{
                            Notification::make()
                                ->title(__('admin.booking.actions.reassign.success_title'))
                                ->body(__('admin.booking.actions.reassign.processing_body'))
                                ->success()
                                ->send();
                            $this->redirect(BookingResource::getUrl('view', ['record' => $this->bookingId]));
                        }
                    }),
            ])
            ->paginated(false);
    }

    public function render(): View
    {
        return view('livewire.booking.similar-services-table');
    }
}
