<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard as PagesDashboard;
use App\Filament\Pages\Login;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Sky,
            ])
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('3rem')
            ->brandName(config('app.name'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->globalSearch(false)
            ->pages([
                PagesDashboard::class,
            ])->sidebarCollapsibleOnDesktop(true)
            ->sidebarWidth('14rem')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\SetWebLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn(): View => view('livewire.language-switcher'),
            )
            ->databaseTransactions()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->navigationItems([
                NavigationItem::make(__('System Logs'))
                    ->url(url('system-log-viewer'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-document-magnifying-glass'),
            ])
            ->maxContentWidth(Width::Full)
            ->renderHook(
                'panels::body.end',
                fn (): string => Blade::render(<<<'BLADE'
    <script>
        document.addEventListener('livewire:initialized', () => {
            console.log('🔊 Sound Script Loaded - Ready to catch Database Events');

            let lastCount = 0;

            // 1. Hàm tìm số lượng thông báo hiện tại trên giao diện
            const getUnreadCount = () => {
                // Tìm thành phần chứa con số trên cái chuông.
                const badges = document.querySelectorAll('.fi-topbar-item-badge, .fi-badge');

                // Lấy số lớn nhất tìm được (đề phòng bắt nhầm badge khác)
                let maxCount = 0;
                badges.forEach(badge => {
                    const val = parseInt(badge.innerText.trim());
                    if (!isNaN(val) && val > maxCount) maxCount = val;
                });
                return maxCount;
            };

            // Khởi tạo số lượng ban đầu
            lastCount = getUnreadCount();
            console.log('Initial Unread Count:', lastCount);

            // 2. Hàm phát âm thanh
            const playSound = (source) => {
                console.log(`🔔 Ting! Có thông báo mới từ: ${source}`);
                const audio = new Audio('/sound/notification.wav');
                audio.play().catch(error => {
                    console.warn('⚠️ Autoplay bị chặn:', error);
                });
            };

            // 3. "Nghe trộm" Livewire update (Giải pháp cho Database Polling)
            Livewire.hook('commit', ({ component, succeed }) => {
                // Chỉ theo dõi component thông báo (tên thường chứa 'database-notifications')
                if (component.name.includes('database-notifications')) {
                    succeed(() => {
                        // Đợi 1 chút cho DOM cập nhật xong số mới
                        setTimeout(() => {
                            const newCount = getUnreadCount();
                            console.log(`Polling check: Old=${lastCount}, New=${newCount}`);

                            // Nếu số mới lớn hơn số cũ -> Có tin mới -> Kêu
                            if (newCount > lastCount) {
                                playSound('Database Polling');
                            }

                            // Cập nhật lại số cũ
                            lastCount = newCount;
                        }, 200);
                    });
                }
            });

            // 4. Vẫn giữ cái này cho nút Test Notification (Toast)
            Livewire.on('notificationsSent', () => playSound('Direct Toast'));
        });
    </script>
    BLADE)
            );
    }
}
