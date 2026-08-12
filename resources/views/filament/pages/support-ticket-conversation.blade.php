<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if($this->ticket)
        <div wire:poll.10s class="space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-semibold text-gray-950 dark:text-white">{{ $this->ticket->customer?->name ?? __('admin.support_monitor.anonymous') }}</h1>
                        <span class="rounded-full bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700">{{ $this->ticket->statusEnum()->label() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ __('admin.support_monitor.ticket') }} #{{ $this->ticket->id }} · {{ $this->ticket->category?->getTranslation('name', app()->getLocale()) ?? __('admin.support_monitor.support') }}</p>
                </div>
                <a href="{{ \App\Filament\Clusters\Support\Pages\SupportConversations::getUrl() }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                    {{ __('admin.common.back') }}
                </a>
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
                <x-filament::section :heading="__('admin.support_monitor.conversation_detail')" class="overflow-hidden !p-0">
                    <div x-data x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)" class="max-h-[calc(100vh-18rem)] space-y-4 overflow-y-auto bg-gray-50/70 p-5 dark:bg-gray-950/20">
                        @if($this->hasMoreMessages)
                            <button wire:click="loadOlderMessages" type="button" class="mx-auto flex items-center gap-2 rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100">
                                <x-filament::icon icon="heroicon-o-arrow-up" class="h-3.5 w-3.5" />
                                {{ __('admin.support_monitor.load_older_messages') }}
                            </button>
                        @endif
                        @forelse($this->messages as $message)
                            @php($staffMessage = $this->isStaffMessage($message))
                            <div class="flex {{ $staffMessage ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm shadow-sm {{ $staffMessage ? 'rounded-br-sm bg-primary-600 text-white' : 'rounded-bl-sm bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100' }}">
                                    <div class="mb-1 text-[11px] font-semibold opacity-70">{{ $staffMessage ? ($message->staff?->name ?? __('admin.support_monitor.support')) : ($message->customer?->name ?? __('admin.support_monitor.anonymous')) }}</div>
                                    <div class="whitespace-pre-wrap leading-6">{{ $message->content }}</div>
                                    <div class="mt-2 text-[10px] opacity-60">{{ $message->created_at?->format('d/m/Y H:i:s') }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center text-sm text-gray-500">{{ __('admin.support_monitor.no_messages') }}</div>
                        @endforelse
                    </div>
                </x-filament::section>

                <x-filament::section :heading="__('admin.support_monitor.ticket')">
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-xs text-gray-500">{{ __('admin.support_ticket.fields.customer') }}</dt><dd class="font-medium">{{ $this->ticket->customer?->name ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ __('admin.support_ticket.fields.assigned_staff') }}</dt><dd class="font-medium">{{ $this->ticket->assignedStaff?->name ?? __('admin.support_monitor.unassigned') }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ __('admin.support_ticket.fields.sla') }}</dt><dd class="font-medium {{ $this->ticket->sla_breached_at ? 'text-red-600' : ($this->ticket->sla_warning_at ? 'text-orange-600' : 'text-emerald-600') }}">{{ $this->ticket->sla_breached_at ? __('admin.support_ticket.sla_status.breached') : ($this->ticket->sla_warning_at ? __('admin.support_ticket.sla_status.warning') : __('admin.support_ticket.sla_status.ok')) }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ __('admin.support_ticket.fields.sla_warning_at') }}</dt><dd>{{ $this->ticket->sla_warning_at?->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ __('admin.support_ticket.fields.sla_breached_at') }}</dt><dd>{{ $this->ticket->sla_breached_at?->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ __('admin.support_ticket.fields.assigned_at') }}</dt><dd>{{ $this->ticket->assigned_at?->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ __('admin.support_ticket.fields.first_response_at') }}</dt><dd>{{ $this->ticket->first_response_at?->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">{{ __('admin.support_ticket.fields.closed_at') }}</dt><dd>{{ $this->ticket->closed_at?->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
                    </dl>
                    <div class="mt-5 rounded-lg border border-sky-100 bg-sky-50 px-3 py-2 text-xs leading-5 text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-200">{{ __('admin.support_ticket.fields.sla_hint') }}</div>
                </x-filament::section>
            </div>

            <x-filament::section :heading="__('admin.support_monitor.audit')">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($this->events as $event)
                        <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-800"><div class="font-medium">{{ str_replace('_', ' ', $event->event_type) }}</div><div class="mt-1 text-xs text-gray-500">{{ $event->actor?->name ?? __('admin.support_monitor.system') }} · {{ $event->created_at?->format('d/m/Y H:i:s') }}</div></div>
                    @empty
                        <div class="text-sm text-gray-500">{{ __('admin.support_monitor.no_audit') }}</div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    @else
        <x-filament::section><div class="py-12 text-center text-sm text-gray-500">{{ __('admin.support_monitor.ticket_not_found') }}</div></x-filament::section>
    @endif
</x-filament-panels::page>
