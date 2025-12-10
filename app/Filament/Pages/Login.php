<?php

namespace App\Filament\Pages;

use App\Enums\Language;
use App\Services\AuthService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\LoginResponse;
use Filament\Auth\Pages\Login as PagesLogin;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Vite;
use Throwable;

class Login extends PagesLogin
{
    protected AuthService $authService;

    public function mount(): void
    {
        parent::mount();
        if (! session()->has('locale')) {
            session(['locale' => Language::VIETNAMESE->value]);
        }
        $locale = session('locale');
        App::setLocale($locale);
    }

    public function boot()
    {
        $this->authService = app(AuthService::class);
        FilamentAsset::register([
            Css::make('app-css', Vite::asset('resources/css/app.css')),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('phone')
                ->label(__('auth.admin.phone'))
                ->string()
                ->required()
                ->autocomplete('phone')
                ->extraInputAttributes(['tabindex' => 1])
                ->validationMessages([
                    'required' => __('common.error.required'),
                ]),
            TextInput::make('password')
                ->label(__('auth.admin.password'))
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->autocomplete('current-password')
                ->required()
                ->extraInputAttributes(['tabindex' => 2])
                ->validationMessages([
                    'required' => __('common.error.required'),
                ]),
        ]);
    }

    public function switchLanguage(string $locale): void
    {
        session(['locale' => $locale]);
        App::setLocale($locale);

        $this->dispatch('$refresh');
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::auth/pages/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => $exception->minutesUntilAvailable,
                ]))
                ->body(array_key_exists('body', __('filament-panels::auth/pages/login.notifications.throttled') ?: []) ? __('filament-panels::auth/pages/login.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => $exception->minutesUntilAvailable,
                ]) : null)
                ->danger()
                ->send();
        }
        $data = $this->form->getState();

        $result = $this->authService->loginAdmin($data['phone'], $data['password']);

        if ($result->isError()) {
            Notification::make()
                ->title($result->getMessage())
                ->danger()
                ->send();
        }
        return app(LoginResponse::class);
    }

    public function getView(): string
    {
        return 'filament.pages.login';
    }

    public function getCachedSubNavigation(): array
    {
        return [];
    }
}
