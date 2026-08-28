<div class="space-y-4">
    <div class="p-3 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-lg text-sm space-y-1">
        <p><strong class="text-gray-700 dark:text-gray-300">{{ __('admin.service_request.fields.customer') }}:</strong> <span class="font-medium text-gray-900 dark:text-white">{{ $record->customer?->name }}</span></p>
        <p><strong class="text-gray-700 dark:text-gray-300">{{ __('admin.service_request.fields.service') }}:</strong> <span class="font-medium text-gray-900 dark:text-white">{{ is_array($record->service?->category?->name) ? ($record->service?->category?->name[app()->getLocale()] ?? $record->service?->category?->name['vi'] ?? reset($record->service->category->name)) : $record->service?->category?->name }}</span></p>
        <p><strong class="text-gray-700 dark:text-gray-300">{{ __('admin.service_request.modal.request_status') }}:</strong> <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-primary-100 text-primary-800 dark:bg-primary-900/50 dark:text-primary-300">{{ $record->status->label() }}</span></p>
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
                    <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $p->ktv?->name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $p->cskh?->name ?? 'System' }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusValue = $p->status?->value;
                                $statusClass = match($statusValue) {
                                    1 => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                    2 => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
                                    3, 5 => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                    4 => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $statusClass }}">
                                {{ $p->status?->label() }}
                            </span>
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
