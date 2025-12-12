<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                {{ __('admin.setting.section.system_config') }}
            </x-slot>

            @livewire('settings.config-form')
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                {{ __('admin.setting.section.affiliate_config') }}
            </x-slot>

            @livewire('settings.affiliate-config-form')
        </x-filament::section>
    </div>
</x-filament-panels::page>
