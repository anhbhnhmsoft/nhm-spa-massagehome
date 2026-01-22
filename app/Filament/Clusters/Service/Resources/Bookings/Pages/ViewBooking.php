<?php

namespace App\Filament\Clusters\Service\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Filament\Clusters\Service\Resources\Bookings\BookingResource;
use App\Services\BookingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('admin.common.back')) // Hoặc "Quay lại"
                ->color('gray')
                ->url($this->getResource()::getUrl('index')) // Dẫn về trang danh sách của Resource này
                ->icon('heroicon-m-chevron-left'),

            // Hiển thị nút "Hủy" nếu trạng thái là "Chờ hủy"
            Action::make('confirm_cancel')
                ->hidden(fn ($record) => $record->status !== BookingStatus::WAITING_CANCEL->value)
                ->label(__('admin.booking.actions.confirm_cancel'))
                ->color('success')
                ->icon('heroicon-m-check-circle')
                ->requiresConfirmation()
                ->modalHeading(__('admin.booking.actions.cancel.heading'))
                ->modalDescription(__('admin.booking.actions.cancel.description'))
                ->action(function (BookingService $service, $record) {
                    $result = $service->confirmCancelBooking(
                        bookingId: $record->id,
                    );
                    if ($result->isSuccess()) {
                        Notification::make()
                            ->title(__('admin.booking.success.cancel'))
                            ->success()
                            ->send();
                        return redirect(request()->header('Referer'));
                    }else{
                        Notification::make()
                            ->title($result->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
