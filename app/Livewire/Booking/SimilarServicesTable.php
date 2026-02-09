<?php

namespace App\Livewire\Booking;

use App\Enums\BookingStatus;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Services\BookingService;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
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
            ->query(function (BookingService $bookingService) {
                $booking = ServiceBooking::find($this->bookingId);
                if (!$booking || $booking->status !== BookingStatus::WAITING_CANCEL->value) {
                    return Service::query()->whereNull('id');
                }
                return $bookingService->getSimilarServicesQuery($booking);
            })
            ->columns([
                TextColumn::make('provider.name')
                    ->label(__('admin.booking.fields.ktv_user'))
                    ->description(fn(Service $record) => $record->category?->name)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin.booking.fields.service'))
                    ->searchable(),
                TextColumn::make('price_range')
                    ->label(__('admin.booking.fields.price'))
                    ->state(function (Service $record) {
                        $min = $record->optionCategoryPrices->min('price') ?? 0;
                        $max = $record->optionCategoryPrices->max('price') ?? 0;
                        return number_format($min) . ' - ' . number_format($max);
                    }),
            ])
            ->recordActions([
                Action::make('reassign')
                    ->label(__('admin.booking.reassign.label')) // Reusing existing translation key 'label' => 'Chuyển KTV'
                    ->button()
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.booking.reassign.heading'))
                    ->modalDescription(__('admin.booking.reassign.description'))
                    ->modalSubmitActionLabel(__('admin.booking.reassign.modal_submit'))
                    ->action(function (Service $record) {
                        \App\Jobs\WalletTransactionBookingJob::dispatch(
                            $this->bookingId,
                            \App\Enums\Jobs\WalletTransCase::REASSIGN_BOOKING,
                            [
                                'new_service_id' => $record->id,
                                'new_ktv_id' => $record->user_id,   
                            ]
                        );

                        Notification::make()
                            ->title(__('admin.booking.reassign.success_title'))
                            ->body(__('admin.booking.reassign.processing_body'))
                            ->success()
                            ->send();

                        $this->redirect(route('filament.admin.clusters.service.resources.bookings.view', ['record' => $this->bookingId]));
                    }),
            ])
            ->paginated(false);
    }

    public function render(): View
    {
        return view('livewire.booking.similar-services-table');
    }
}
