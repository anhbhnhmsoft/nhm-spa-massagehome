<x-filament-panels::page>
    <div wire:poll.5s class="grid min-h-[650px] grid-cols-1 gap-4 xl:grid-cols-[22rem_minmax(0,1fr)]">
        <x-filament::section :heading="__('admin.support_monitor.active_tickets')" class="overflow-hidden">
            <div class="mb-4 grid grid-cols-1 gap-2 px-1 sm:grid-cols-3">
                <select wire:model.live="statusFilter" class="fi-input rounded-lg border-gray-300 text-sm"><option value="active">Đang hoạt động</option><option value="all">Tất cả</option><option value="pending">Chờ nhận</option><option value="assigned">Đã nhận</option><option value="in_progress">Đang xử lý</option><option value="closed">Đã đóng</option></select>
                <select wire:model.live="staffFilter" class="fi-input rounded-lg border-gray-300 text-sm"><option value="">Tất cả CSKH</option>@foreach(\App\Models\AdminUser::query()->where('role', \App\Enums\Admin\AdminRole::CUSTOMER_SUPPORT->value)->where('is_active', true)->orderBy('name')->get() as $staff)<option value="{{ $staff->id }}">{{ $staff->name }}</option>@endforeach</select>
                <select wire:model.live="categoryFilter" class="fi-input rounded-lg border-gray-300 text-sm"><option value="">Tất cả danh mục</option>@foreach(\App\Models\SupportCategory::query()->where('is_active', true)->orderBy('position')->get() as $category)<option value="{{ $category->id }}">{{ $category->getTranslation('name', app()->getLocale()) }}</option>@endforeach</select>
                <select wire:model.live="slaFilter" class="fi-input rounded-lg border-gray-300 text-sm"><option value="all">Tất cả SLA</option><option value="warning">Cảnh báo SLA</option><option value="breached">Quá SLA</option></select>
            </div>
            <div class="-mx-4 -mb-4 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($this->tickets as $ticket)
                    <button type="button" wire:click="$set('ticketId', '{{ $ticket->id }}')" class="block w-full px-4 py-3 text-left transition {{ (string) $ticket->id === (string) $ticketId ? 'bg-primary-50 dark:bg-primary-950/40' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate font-semibold text-gray-950 dark:text-white">{{ $ticket->customer?->name ?? __('admin.support_monitor.anonymous') }}</span>
                            <span class="text-xs text-gray-500">#{{ $ticket->id }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between gap-2 text-xs text-gray-500">
                            <span class="truncate">{{ $ticket->category?->getTranslation('name', app()->getLocale()) ?? __('admin.support_monitor.support') }}</span>
                            <span class="shrink-0">{{ $ticket->last_message_at?->diffForHumans() ?? $ticket->created_at?->diffForHumans() }}</span>
                        </div>
                        <div class="mt-2 flex items-center gap-2 text-xs">
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $ticket->statusEnum()->label() }}</span>
                            <span class="truncate text-gray-500">{{ $ticket->assignedStaff?->name ?? __('admin.support_monitor.unassigned') }}</span>
                        </div>
                    </button>
                @empty
                    <div class="p-8 text-center text-sm text-gray-500">{{ __('admin.support_monitor.no_active') }}</div>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section :heading="$this->selectedTicket ? __('admin.support_monitor.conversation') . ' #' . $this->selectedTicket->id : __('admin.support_monitor.select_ticket')" class="flex min-h-[650px] flex-col">
            @if($this->selectedTicket)
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <div class="font-semibold text-gray-950 dark:text-white">{{ $this->selectedTicket->customer?->name ?? __('admin.support_monitor.anonymous') }}</div>
                        <div class="text-xs text-gray-500">{{ $this->selectedTicket->customer?->phone ?? '-' }} · {{ $this->selectedTicket->assignedStaff?->name ?? __('admin.support_monitor.unassigned') }}</div>
                    </div>
                    <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 dark:bg-primary-950/40 dark:text-primary-300">{{ $this->selectedTicket->statusEnum()->label() }}</span>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto pr-2">
                    @forelse($this->messages as $message)
                        @php($staffMessage = $this->isStaffMessage($message))
                        <div class="flex {{ $staffMessage ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[78%] rounded-2xl px-4 py-3 text-sm {{ $staffMessage ? 'rounded-br-sm bg-primary-600 text-white' : 'rounded-bl-sm bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100' }}">
                                <div class="mb-1 text-[11px] font-semibold opacity-70">{{ $staffMessage ? $message->staff?->name : $message->customer?->name }}</div>
                                <div class="whitespace-pre-wrap">{{ $message->content }}</div>
                                <div class="mt-2 text-[10px] opacity-60">{{ $message->created_at?->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full items-center justify-center text-sm text-gray-500">{{ __('admin.support_monitor.no_messages') }}</div>
                    @endforelse
                </div>
            @else
                <div class="flex flex-1 items-center justify-center text-sm text-gray-500">{{ __('admin.support_monitor.select_ticket') }}</div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
