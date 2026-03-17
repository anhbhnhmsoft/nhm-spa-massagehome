<?php

namespace App\Filament\Components;

use App\Enums\Language;
use App\Enums\ReviewApplicationStatus;
use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Repositories\ServiceRepository;
use App\Services\GeminiService;
use App\Services\ReviewService;
use App\Services\ServiceService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;

class CommonActions
{
    public static function backAction($resource): Action
    {
        return Action::make('back')
            ->label(__('admin.common.back'))
            ->color('gray')
            ->url($resource::getUrl('index'))
            ->icon('heroicon-m-chevron-left');
    }

    /**
     * Tạo đánh giá ảo (dùng cho các màn của KTV)
     * @return Action
     */
    public static function reviewVirtualAction()
    {
        return Action::make('create_virtual_review')
            ->label(__('admin.review.action.virtual.title'))
            ->icon('heroicon-o-sparkles')
            ->color('info')
            ->modal()
            ->schema([
                TextInput::make('virtual_name')
                    ->label(__('admin.review.action.virtual.virtual_name'))
                    ->placeholder('Ex: Nguyễn Văn A')
                    ->helperText(__('admin.review.action.virtual.virtual_name_helper_text')),

                Select::make('rating')
                    ->label(__('admin.review.action.virtual.rating'))
                    ->options([
                        5 => '⭐⭐⭐⭐⭐ (5)',
                        4 => '⭐⭐⭐⭐ (4)',
                        3 => '⭐⭐⭐ (3)',
                        2 => '⭐⭐ (2)',
                        1 => '⭐ (1)',
                    ])
                    ->default(5)
                    ->required()
                    ->validationMessages([
                        'required' => __('common.error.required'),
                    ]),
                Select::make('target_language')
                    ->label(__('admin.review.action.virtual.target_language'))
                    ->options(Language::options())
                    ->helperText(__('admin.review.action.virtual.target_language_helper_text'))
                    ->default(Language::VIETNAMESE->value)
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->required()
                    ->validationMessages([
                        'required' => __('common.error.required'),
                    ])
                    ->live(),
                Textarea::make('comment')
                    ->label(__('admin.review.action.virtual.comment'))
                    ->rows(3)
                    ->helperText(__('admin.review.action.virtual.comment_helper_text'))
                    ->hint(__('admin.review.action.virtual.comment_hint'))
                    // --- BẮT ĐẦU PHẦN THÊM BUTTON AI ---
                    ->hintAction(
                        Action::make('generate_ai_comment')
                            ->label('AI Tự Viết')
                            ->icon('heroicon-m-sparkles')
                            ->color('success')
                            ->tooltip(__('admin.review.action.virtual.generate_ai_comment_tooltip'))
                            // Chức năng chính khi bấm nút
                            ->action(function (Set $set, Get $get, GeminiService $geminiService) {
                                $rating = (int)($get('rating') ?? 5);
                                $langValue = $get('target_language') ?? Language::VIETNAMESE->value;
                                $langEnum = Language::tryFrom($langValue) ?? Language::VIETNAMESE;
                                try {
                                    // 3. Gọi hàm generateReview đã viết trong Service
                                    $aiContent = $geminiService->generateReview(
                                        rating: $rating,
                                        lang: $langEnum
                                    );
                                    // 4. Cập nhật nội dung vào Textarea
                                    $set('comment', $aiContent);
                                    // Thông báo nhỏ cho người dùng (tùy chọn)
                                    Notification::make()
                                        ->title(__('admin.review.action.virtual.generate_ai_comment_success'))
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title(__('admin.review.action.virtual.generate_ai_comment_error'))
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            })
                    ),
            ])
            ->action(function (User $record, array $data, ReviewService $reviewService): void {
                $result = $reviewService->createReviewVirtual([
                    'user_id' => (string)$record->id,
                    'virtual_name' => $data['virtual_name'] ?? null,
                    'rating' => (int)$data['rating'],
                    'target_language' => $data['target_language'],
                    'comment' => $data['comment'] ?? null,
                ]);
                if ($result->isSuccess()) {
                    Notification::make()
                        ->title('Đã tạo đánh giá ảo thành công')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Lỗi khi tạo đánh giá ảo')
                        ->body($result->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Xem dịch vụ của KTV (dùng cho các màn của KTV)
     * @return Action
     */
    public static function viewServiceAction(): Action
    {
        return Action::make('view_service')
            ->label(__('admin.common.action.view_service'))
            ->icon('heroicon-o-tag')
            ->visible(fn(User $record) => $record->reviewApplication->status === ReviewApplicationStatus::APPROVED)
            ->color('success')
            ->fillForm(fn(User $record) => [
                'categories' => $record->categories->pluck('id')->toArray(),
            ])
            ->action(function (User $record, array $data): void {
                $record->categories()->sync($data['categories']);
                Notification::make()
                    ->success()
                    ->title(__('common.success.success'))
                    ->body(__('common.success.data_updated'))
                    ->send();
            })
            ->modal()
            ->modalSubmitActionLabel(__('admin.common.action.save'))
            ->modalCancelActionLabel(__('admin.common.action.cancel'))
            ->schema([
                CheckboxList::make('categories')
                    ->label(__('admin.ktv.action.choose_categories'))
                    ->options(fn(CategoryRepository $repo) => $repo->pluckNameAndId())
                    ->columns(2)
            ]);
    }

    /**
     * Cập nhật số lượng dịch vụ đã thực hiện (buff ảo)
     * @return Action
     */
    public static function buffServiceAction()
    {
        return Action::make('buff_service')
            ->label(__('admin.common.action.buff_service'))
            ->icon('heroicon-o-tag')
            ->visible(fn(User $record) => $record->reviewApplication?->status === ReviewApplicationStatus::APPROVED)
            ->color('success')
            ->modalWidth(Width::FiveExtraLarge)
            ->modal()
            ->fillForm(fn(User $record, ServiceRepository $categoryRepository) => [
                'services_list' => $categoryRepository->getListServiceByUserId($record->id),
            ])
            ->action(function (User $record, array $data, ServiceService $serviceService): void {
                // Gọi service để cập nhật
                $result = $serviceService->updateServicePerformedCount($data['services_list']);
                if ($result->isSuccess()) {
                    Notification::make()
                        ->success()
                        ->title(__('common.success.success'))
                        ->body(__('common.success.data_updated'))
                        ->send();
                }else{
                    Notification::make()
                        ->danger()
                        ->title(__('common.error.title'))
                        ->body($result->getMessage())
                        ->send();
                }
            })
            ->schema([
                Repeater::make('services_list')
                    ->hiddenLabel()
                    ->grid(2)
                    ->compact()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Hidden::make('id'),
                                TextEntry::make('category.name.' . app()->getLocale())
                                    ->label(__('admin.common.table.name')),
                                // Ô nhập số lượng cần buff thêm
                                TextInput::make('performed_count')
                                    ->label(__('admin.common.table.performed_count'))
                                    ->numeric()
                                    ->default(0)
                                    ->live(onBlur: true)
                            ]),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false),
            ]);
    }

    /**
     * Hiển QR code giới thiệu đối tác (dùng cho các màn của KTV - Agency - Customer)
     * @return Action
     */
    public static function qrAffiliateAction(): Action
    {
        return Action::make('qr_affiliate')
            ->label(__('admin.common.affiliate_qr'))
            ->icon('heroicon-o-qr-code')
            ->modalHeading(__('admin.common.affiliate_qr'))
            ->modalSubmitAction(false) // Ẩn nút Submit vì chỉ để xem
            ->modalWidth('sm')
            ->schema([
                TextEntry::make('qr_code_placeholder')
                    ->hiddenLabel()
                    ->state(function (User $record) {
                        $url = route('affiliate.link', ['referrerId' => $record->id]);
                        $qrUrl = "https://quickchart.io/qr?text=" . urlencode($url) . "&size=250";
                        return new HtmlString("
                                        <div class='flex flex-col items-center justify-center space-y-4'>
                                              <img src='{$qrUrl}' alt='QR Code' class='w-64 h-64'>
                                            <div class='text-center text-sm font-mono text-gray-500 break-all'>
                                                {$url}
                                            </div>
                                        </div>
                                    ");
                    })
            ]);
    }

}
