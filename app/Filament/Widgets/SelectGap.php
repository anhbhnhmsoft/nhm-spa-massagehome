<?php

namespace App\Filament\Widgets;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Grid;
use Filament\Widgets\Widget;

class SelectGap extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.widgets.select-gap';

    public ?string $start_date = null;
    public ?string $end_date = null;

    public function mount(): void
    {
        // Khởi tạo giá trị mặc định: tháng hiện tại
        $this->start_date = session('dashboard_start_date', now()->startOfMonth()->format('Y-m-d'));
        $this->end_date = session('dashboard_end_date', now()->endOfMonth()->format('Y-m-d'));

        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]);
    }

    public static function getHeading(): string
    {
        return __('dashboard.select_gap_heading');
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make()->columns(2)->schema([
                DatePicker::make('start_date')
                    ->label(__('dashboard.select.start_date_label'))
                    ->live()
                    ->required()
                    ->maxDate(fn($get) => $get('end_date') ?: now())
                    ->validationMessages([
                        'required' => __('dashboard.select.start_date_required'),
                        'maxDate' => __('dashboard.select.start_date_max_date'),
                    ])
                    ->afterStateUpdated(function ($state) {
                        $this->start_date = $state;
                        session(['dashboard_start_date' => $state]);
                        $this->dispatch('dateRangeUpdated', [
                            'start_date' => $state,
                            'end_date' => $this->end_date,
                        ]);
                    })
                    ->columnSpan(1),

                DatePicker::make('end_date')
                    ->label(__('dashboard.select.end_date_label'))
                    ->live()
                    ->required()
                    ->minDate(fn($get) => $get('start_date') ?: now())
                    ->validationMessages([
                        'required' => __('dashboard.select.end_date_required'),
                        'minDate' => __('dashboard.select.end_date_min_date'),
                    ])
                    ->afterStateUpdated(function ($state) {
                        $this->end_date = $state;
                        session(['dashboard_end_date' => $state]);
                        $this->dispatch('dateRangeUpdated', [
                            'start_date' => $this->start_date,
                            'end_date' => $state,
                        ]);
                    })
                    ->columnSpan(1),
            ])
        ];
    }
}
