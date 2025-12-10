<?php

namespace App\Livewire;

use Illuminate\Support\Facades\App;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    /**
     * Phương thức xử lý sự kiện chuyển đổi ngôn ngữ.
     * @param string $locale Mã ngôn ngữ
     */
    public function switchLanguage(string $locale): void
    {
        session(['locale' => $locale]);

        $this->redirect(url()->previous());
    }

    public function render()
    {
        return view('filament.hooks.language-switcher');
    }
}