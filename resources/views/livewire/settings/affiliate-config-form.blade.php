<div>
    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit">
                {{ __('admin.setting.actions.save') }}
            </x-filament::button>
        </div>
    </form>
</div>
