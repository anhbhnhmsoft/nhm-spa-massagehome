@use('App\Enums\ProposalStatus')

<div class="space-y-4">
    <div class="p-3 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-lg text-sm space-y-1.5">
        <p><strong class="text-gray-700 dark:text-gray-300">{{ __('admin.service_request.fields.customer') }}:</strong> <span class="font-medium text-gray-900 dark:text-white">{{ $record->customer?->name }}</span></p>
        @php
            $cat = $record->category ?? $record->service?->category;
            $serviceName = $cat ? (is_array($cat->name) ? ($cat->name[app()->getLocale()] ?? $cat->name['vi'] ?? reset($cat->name)) : $cat->name) : '—';
        @endphp
        <p><strong class="text-gray-700 dark:text-gray-300">{{ __('admin.service_request.fields.service') }}:</strong> <span class="font-medium text-gray-900 dark:text-white">{{ $serviceName }}</span></p>
        <div class="flex items-center gap-2"><strong class="text-gray-700 dark:text-gray-300">{{ __('admin.service_request.modal.request_status') }}:</strong> <x-filament::badge :color="$record->status->color()">{{ $record->status->label() }}</x-filament::badge></div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-4 py-2.5">{{ __('admin.service_request.modal.proposed_ktv') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('admin.service_request.modal.proposed_by_cskh') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('admin.service_request.modal.response_status') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('admin.service_request.modal.time') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($proposals as $p)
                    @php
                        $statusEnum = $p->status instanceof ProposalStatus 
                            ? $p->status 
                            : (is_numeric($p->status) ? ProposalStatus::tryFrom((int)$p->status) : null);
                        $statusLabel = $statusEnum ? $statusEnum->label() : ($p->status ?? '—');
                        $statusColor = $statusEnum ? $statusEnum->color() : 'gray';
                    @endphp
                    <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $p->ktv?->name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $p->cskh?->name ?? 'System' }}
                        </td>
                        <td class="px-4 py-3">
                            <x-filament::badge :color="$statusColor">
                                {{ $statusLabel }}
                            </x-filament::badge>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ $p->created_at?->format('H:i d/m/Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">
                            {{ __('admin.service_request.modal.no_proposals') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
