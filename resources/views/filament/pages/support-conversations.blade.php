<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div wire:poll.10s class="space-y-5">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
            @foreach([
                ['label' => 'Chờ nhận', 'value' => $this->queueSummary['pending'], 'icon' => 'heroicon-o-inbox', 'icon_class' => 'text-amber-500'],
                ['label' => 'Đang xử lý', 'value' => $this->queueSummary['assigned'], 'icon' => 'heroicon-o-user-circle', 'icon_class' => 'text-blue-500'],
                ['label' => 'Chưa đọc', 'value' => $this->queueSummary['unread'], 'icon' => 'heroicon-o-envelope', 'icon_class' => 'text-rose-500'],
                ['label' => 'SLA cảnh báo', 'value' => $this->queueSummary['warning'], 'icon' => 'heroicon-o-clock', 'icon_class' => 'text-orange-500'],
                ['label' => 'Đã quá SLA', 'value' => $this->queueSummary['breached'], 'icon' => 'heroicon-o-exclamation-triangle', 'icon_class' => 'text-red-500'],
                ['label' => 'CSKH online', 'value' => $this->queueSummary['online'], 'icon' => 'heroicon-o-signal', 'icon_class' => 'text-emerald-500'],
            ] as $metric)
                <div class="flex min-h-[92px] flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $metric['label'] }}</span><x-filament::icon :icon="$metric['icon']" class="h-4 w-4 {{ $metric['icon_class'] }}" /></div>
                    <strong class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $metric['value'] }}</strong>
                </div>
            @endforeach
        </div>

        <div class="grid min-h-[720px] grid-cols-1 gap-4 xl:grid-cols-[22rem_minmax(0,1fr)_19rem]">
            <x-filament::section heading="Hàng đợi ticket" class="overflow-hidden">
                <div class="mb-4 grid grid-cols-2 gap-2">
                    <select wire:model.live="statusFilter" class="fi-input rounded-lg border-gray-300 text-sm"><option value="active">Đang hoạt động</option><option value="all">Tất cả</option><option value="pending">Chờ nhận</option><option value="assigned">Đã nhận</option><option value="in_progress">Đang xử lý</option><option value="closed">Đã đóng</option></select>
                    <select wire:model.live="slaFilter" class="fi-input rounded-lg border-gray-300 text-sm"><option value="all">Tất cả SLA</option><option value="warning">Cảnh báo SLA</option><option value="breached">Quá SLA</option></select>
                </div>
                <div class="mb-3 grid grid-cols-2 gap-2">
                    <select wire:model.live="staffFilter" class="fi-input rounded-lg border-gray-300 text-sm"><option value="">Tất cả CSKH</option>@foreach(\App\Models\AdminUser::query()->where('role', \App\Enums\Admin\AdminRole::CUSTOMER_SUPPORT->value)->where('is_active', true)->orderBy('name')->get() as $staff)<option value="{{ $staff->id }}">{{ $staff->name }}</option>@endforeach</select>
                    <select wire:model.live="categoryFilter" class="fi-input rounded-lg border-gray-300 text-sm"><option value="">Tất cả danh mục</option>@foreach(\App\Models\SupportCategory::query()->where('is_active', true)->orderBy('position')->get() as $category)<option value="{{ $category->id }}">{{ $category->getTranslation('name', app()->getLocale()) }}</option>@endforeach</select>
                </div>
                <div class="mb-3 rounded-lg border border-sky-100 bg-sky-50 px-3 py-2 text-[11px] leading-5 text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-200">
                    <strong>{{ __('admin.support_monitor.sla_legend') }}</strong>{{ __('admin.support_monitor.sla_legend_description') }}
                </div>
                <div class="-mx-4 -mb-4 max-h-[calc(100vh-23rem)] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($this->tickets as $ticket)
                        @php($isSelected = (string) $ticket->id === (string) $ticketId)
                        <div class="border-l-2 px-4 py-3 transition {{ $isSelected ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/40' : 'border-transparent hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                            <button type="button" wire:click="$set('ticketId', '{{ $ticket->id }}')" class="block w-full text-left">
                            <div class="flex items-start justify-between gap-2"><span class="truncate font-semibold text-gray-950 dark:text-white">{{ $ticket->customer?->name ?? 'Khách ẩn danh' }}</span><span class="shrink-0 text-[10px] text-gray-500">#{{ $ticket->id }}</span></div>
                            <div class="mt-1 flex items-center justify-between gap-2 text-xs text-gray-500"><span class="truncate">{{ $ticket->category?->getTranslation('name', app()->getLocale()) ?? 'Hỗ trợ' }}</span><span>{{ $ticket->last_message_at?->diffForHumans() ?? $ticket->created_at?->diffForHumans() }}</span></div>
                            <div class="mt-2 flex items-center gap-1.5 text-[11px]"><span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $ticket->statusEnum()->label() }}</span><span class="truncate text-gray-500">{{ $ticket->assignedStaff?->name ?? 'Chưa nhận' }}</span>@if($ticket->unread_count)<span class="ml-auto rounded-full bg-rose-100 px-2 py-0.5 font-semibold text-rose-700">{{ $ticket->unread_count }}</span>@endif</div>
                            @if($ticket->sla_breached_at)<div class="mt-2 text-[11px] font-semibold text-red-600">Đã quá SLA {{ $ticket->sla_breached_at->diffForHumans() }}</div>@elseif($ticket->sla_warning_at)<div class="mt-2 text-[11px] font-semibold text-orange-600">SLA cần chú ý</div>@endif
                            </button>
                            <a href="{{ $this->ticketUrl($ticket) }}" wire:navigate class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-3.5 w-3.5" />
                                Mở ticket chi tiết
                            </a>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-500">Không có ticket phù hợp bộ lọc.</div>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section class="flex min-h-[720px] flex-col !p-0">
                @if($this->selectedTicket)
                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800"><div><div class="flex items-center gap-2"><h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ $this->selectedTicket->customer?->name ?? 'Khách ẩn danh' }}</h2><span class="rounded-full bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700">{{ $this->selectedTicket->statusEnum()->label() }}</span></div><p class="mt-1 text-xs text-gray-500">{{ $this->selectedTicket->category?->getTranslation('name', app()->getLocale()) ?? 'Hỗ trợ' }} · Ticket #{{ $this->selectedTicket->id }}</p></div><div class="text-right text-xs text-gray-500"><div>{{ $this->selectedTicket->assignedStaff?->name ?? 'Chưa phân công' }}</div><div>{{ $this->selectedTicket->assigned_at?->format('d/m H:i') ?? 'Chưa nhận' }}</div></div></div>
                    <div wire:key="support-messages-{{ $this->selectedTicket->id }}" x-data x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)" class="flex-1 space-y-4 overflow-y-auto bg-gray-50/70 p-5 dark:bg-gray-950/20">
                        @forelse($this->messages as $message)
                            @php($staffMessage = $this->isStaffMessage($message))
                            <div class="flex {{ $staffMessage ? 'justify-end' : 'justify-start' }}"><div class="max-w-[82%] rounded-2xl px-4 py-3 text-sm shadow-sm {{ $staffMessage ? 'rounded-br-sm bg-primary-600 text-white' : 'rounded-bl-sm bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100' }}"><div class="mb-1 text-[11px] font-semibold opacity-70">{{ $staffMessage ? $message->staff?->name : $message->customer?->name }}</div><div class="whitespace-pre-wrap leading-6">{{ $message->content }}</div><div class="mt-2 text-[10px] opacity-60">{{ $message->created_at?->format('d/m/Y H:i') }}</div></div></div>
                        @empty<div class="flex h-full items-center justify-center text-sm text-gray-500">Chưa có tin nhắn.</div>@endforelse
                    </div>
                @else
                    <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center text-gray-500"><x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-10 w-10 text-gray-300" /><strong>Chọn một ticket để bắt đầu theo dõi</strong><span class="text-sm">Danh sách bên trái được sắp xếp theo unread và SLA.</span></div>
                @endif
            </x-filament::section>

            <x-filament::section heading="Thông tin & audit" class="min-h-[720px]">
                @if($this->selectedTicket)
                    <div class="space-y-5 text-sm"><div><div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Khách hàng</div><div class="mt-2 font-semibold text-gray-950 dark:text-white">{{ $this->selectedTicket->customer?->name ?? 'Ẩn danh' }}</div><div class="text-xs text-gray-500">{{ $this->selectedTicket->customer?->phone ?? 'Không có số điện thoại' }}</div></div><div class="grid grid-cols-2 gap-3"><div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"><div class="text-[11px] text-gray-500">Unread</div><strong>{{ $this->selectedTicket->unread_count ?? 0 }}</strong></div><div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"><div class="text-[11px] text-gray-500">SLA</div><strong class="{{ $this->selectedTicket->sla_breached_at ? 'text-red-600' : ($this->selectedTicket->sla_warning_at ? 'text-orange-600' : 'text-emerald-600') }}">{{ $this->selectedTicket->sla_breached_at ? 'Quá hạn' : ($this->selectedTicket->sla_warning_at ? 'Cảnh báo' : 'Bình thường') }}</strong></div></div><div><div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mốc thời gian</div><dl class="mt-2 space-y-2 text-xs"><div class="flex justify-between gap-2"><dt class="text-gray-500">Tạo</dt><dd>{{ $this->selectedTicket->created_at?->format('d/m/Y H:i') }}</dd></div><div class="flex justify-between gap-2"><dt class="text-gray-500">Nhận</dt><dd>{{ $this->selectedTicket->assigned_at?->format('d/m/Y H:i') ?? '-' }}</dd></div><div class="flex justify-between gap-2"><dt class="text-gray-500">Phản hồi đầu</dt><dd>{{ $this->selectedTicket->first_response_at?->format('d/m/Y H:i') ?? '-' }}</dd></div><div class="flex justify-between gap-2"><dt class="text-gray-500">Đóng</dt><dd>{{ $this->selectedTicket->closed_at?->format('d/m/Y H:i') ?? '-' }}</dd></div></dl></div><div><div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lịch sử gần đây</div><div class="mt-2 space-y-3">@forelse($this->events as $event)<div class="border-l-2 border-gray-200 pl-3 dark:border-gray-700"><div class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ str_replace('_', ' ', $event->event_type) }}</div><div class="text-[11px] text-gray-500">{{ $event->actor?->name ?? 'Hệ thống' }} · {{ $event->created_at?->diffForHumans() }}</div></div>@empty<div class="text-xs text-gray-500">Chưa có audit.</div>@endforelse</div></div></div>
                @else<div class="text-sm text-gray-500">Chưa chọn ticket.</div>@endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
