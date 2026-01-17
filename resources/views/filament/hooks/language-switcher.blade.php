<div class="flex gap-2 justify-center">
    <button wire:click="switchLanguage('vi')" type="button" @class([
        'flex items-center text-gray-700  gap-2 px-3 py-2 rounded-lg transition-all duration-200 border shadow-sm',

        app()->getLocale() == App\Enums\Language::VIETNAMESE->value
            ? 'bg-primary-600 shadow-md scale-105 border-blue-500'
            : 'bg-white border-gray-200 hover:bg-gray-50 hover:border-primary-300',
    ]) title="{{ __('Tiếng Việt') }}">
        <img src="{{ asset('images/vietnam.png') }}" alt="Vietnam flag" class="w-6 h-4 rounded-sm object-cover" />
        <span
            class="font-medium text-sm">VI</span>
    </button>

    <button wire:click="switchLanguage('en')" type="button" @class([
        'flex items-center gap-2 text-gray-700  px-3 py-2 rounded-lg transition-all duration-200 border shadow-sm',

        app()->getLocale() == App\Enums\Language::ENGLISH->value
            ? 'bg-primary-600 shadow-md scale-105 border-blue-500'
            : 'bg-white border-gray-200 hover:bg-gray-50 hover:border-primary-300',
    ]) title="{{ __('English') }}">
        <img src="{{ asset('images/english.png') }}" alt="English flag"
            class="w-6 h-4 rounded-sm object-cover" />
        <span
            class="font-medium text-sm">EN</span>
    </button>

    <button wire:click="switchLanguage('cn')" type="button" @class([
        'flex items-center gap-2 text-gray-700  px-3 py-2 rounded-lg transition-all duration-200 border shadow-sm',

        app()->getLocale() == App\Enums\Language::CHINESE->value
            ? 'bg-primary-600 shadow-md scale-105 border-blue-500'
            : 'bg-white border-gray-200 hover:bg-gray-50 hover:border-primary-300',
    ]) title="{{ __('English') }}">
        <img src="{{ asset('images/china.png') }}" alt="China flag"
            class="w-6 h-4 rounded-sm object-cover" />
        <span
            class="font-medium text-sm"> 中国人 </span>
    </button>
</div>
