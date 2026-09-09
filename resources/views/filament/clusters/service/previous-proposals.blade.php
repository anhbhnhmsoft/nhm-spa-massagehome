<div class="rounded-xl border border-gray-200 bg-gray-50/80 p-3.5 dark:border-gray-700 dark:bg-gray-800/80 space-y-2.5">
    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">
        <svg class="h-4 w-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ __('admin.service_request.fields.previous_proposals') }}</span>
    </div>

    <div class="space-y-2">
        @foreach($proposals as $p)
            @php
                $statusEnum = $p->status instanceof \App\Enums\ProposalStatus 
                    ? $p->status 
                    : (is_numeric($p->status) ? \App\Enums\ProposalStatus::tryFrom((int)$p->status) : null);
                $statusLabel = $statusEnum ? $statusEnum->label() : ($p->status ?? '—');
                $statusColor = $statusEnum ? $statusEnum->color() : 'gray';
            @endphp
            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-white p-2.5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-sm text-gray-900 dark:text-white">
                            {{ $p->ktv?->name ?? 'KTV #' . $p->ktv_id }}
                        </span>
                        @if($p->ktv?->phone)
                            <span class="font-mono text-xs text-gray-500 dark:text-gray-400">
                                ({{ $p->ktv->phone }})
                            </span>
                        @endif
                        @if($p->cskh?->name)
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                • bởi <span class="font-medium text-gray-600 dark:text-gray-300">{{ $p->cskh->name }}</span>
                            </span>
                        @endif
                    </div>
                    @if($p->created_at)
                        <div class="mt-0.5 flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500 font-mono">
                            <span>{{ $p->created_at->format('H:i d/m/Y') }}</span>
                        </div>
                    @endif
                </div>
                <div class="shrink-0">
                    <x-filament::badge :color="$statusColor">
                        {{ $statusLabel }}
                    </x-filament::badge>
                </div>
            </div>
        @endforeach
    </div>
</div>
