<div>
    {{ $this->form }}
    <div class="mt-4 flex justify-end">
        <x-filament::button wire:click="save">
            {{ __('admin.setting.actions.save') }}
        </x-filament::button>
    </div>
</div>
