<?php

namespace App\Filament\Clusters\Service\Resources\Services\Schemas;

use App\Enums\DirectFile;
use App\Enums\UserRole;
use App\Models\CategoryPrice;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        $lang = app()->getLocale();
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                FileUpload::make('image_url')
                                    ->label(__('admin.service.fields.image'))
                                    ->required()
                                    ->disk('public')
                                    ->image()
                                    ->directory(DirectFile::SERVICE->value)
                                    ->image()
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                                TextInput::make('name.vi')
                                    ->label(__('admin.service.fields.name') . "(VI)")
                                    ->required()
                                    ->maxLength(255)
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                        'max' => __('common.error.max_length', ['max' => 255]),
                                    ]),
                                TextInput::make('name.en')
                                    ->label(__('admin.service.fields.name') . "(EN)")
                                    ->maxLength(255),
                                TextInput::make('name.cn')
                                    ->label(__('admin.service.fields.name') . "(CN)")
                                    ->maxLength(255),
                                Select::make('category_id')
                                    ->label(__('admin.service.fields.category'))
                                    ->placeholder(__('common.placeholder.type'))
                                    ->relationship(
                                        name: 'category',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query) => $query
                                            ->orderByRaw("name->>'{$lang}' ASC")
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($set) {
                                        $set('options', []);
                                    })
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                                Select::make('user_id')
                                    ->label(__('admin.service.fields.provider'))
                                    ->placeholder(__('common.placeholder.type'))
                                    ->placeholder(__('common.placeholder.type'))
                                    ->relationship(
                                        name: 'provider',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query) => $query
                                            ->where('is_active', true)->where('role', UserRole::KTV->value),
                                    )
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),

                                Toggle::make('is_active')
                                    ->label(__('admin.service.fields.status'))
                                    ->required()
                                    ->default(true)
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                                Textarea::make('description.vi')
                                    ->label(__('admin.service.fields.description') . "(VI)")
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('common.error.required'),
                                    ]),
                                Textarea::make('description.en')
                                    ->label(__('admin.service.fields.description') . "(EN)"),
                                Textarea::make('description.cn')
                                    ->label(__('admin.service.fields.description') . "(CN)"),
                            ]),
                        Section::make()
                            ->schema([
                                CheckboxList::make('optionCategoryPrices') // Tên này PHẢI khớp với tên hàm relation trong Model Service
                                    ->label(__('admin.service.fields.option_category_prices'))
                                    ->relationship(
                                        name: 'optionCategoryPrices',
                                        titleAttribute: 'id' // Chúng ta sẽ override hiển thị bằng hàm options() bên dưới
                                    )
                                    // Logic load dữ liệu phụ thuộc
                                    ->options(function ($get) {
                                        $categoryId = $get('category_id');
                                        // Nếu chưa chọn danh mục thì không hiện gì cả
                                        if (!$categoryId) {
                                            return [];
                                        }
                                        // Lấy các CategoryPrice thuộc category_id đã chọn
                                        return CategoryPrice::where('category_id', $categoryId)
                                            ->get()
                                            // Map thành mảng
                                            ->mapWithKeys(fn($item) => [
                                                $item->id => $item->duration . " " . __('admin.common.minute') . ' - ' . number_format($item->price, 0, ',', '.') . ' ' . __('admin.currency'),
                                            ]);
                                    })
                                    ->columns(2) // Chia làm 2 cột cho đẹp
                                    ->gridDirection('row')
                                    ->bulkToggleable() // Cho phép chọn tất cả nhanh
                                    ->noSearchResultsMessage(__('admin.service.fields.no_option_category_prices'))
                                    ->required() // Bắt buộc phải chọn
                                    ->validationMessages([
                                        'required' => __('admin.service.error.option_category_prices'), // Hoặc: 'Vui lòng chọn ít nhất 1 gói dịch vụ'
                                    ])

                            ])

                    ])
                    ->columnSpan('full')
            ]);
    }
}
