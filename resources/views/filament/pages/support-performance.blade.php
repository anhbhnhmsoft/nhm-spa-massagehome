<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-4" wire:poll.30s>
        <x-filament::section>
            <div class="flex flex-wrap items-end gap-4">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.support_monitor.date') }}</span>
                    <input type="date" wire:model.live="date" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm" />
                </label>
                <label class="block min-w-64">
                    <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.support_monitor.staff') }}</span>
                    <select wire:model.live="staffId" class="fi-select block w-full rounded-lg border-gray-300 shadow-sm">
                        <option value="">{{ __('admin.support_monitor.all_staff') }}</option>
                        @foreach($this->staffOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block min-w-48">
                    <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.support_monitor.period') }}</span>
                    <select wire:model.live="period" class="fi-select block w-full rounded-lg border-gray-300 shadow-sm">
                        <option value="day">{{ __('admin.support_monitor.period_day') }}</option>
                        <option value="week">{{ __('admin.support_monitor.period_week') }}</option>
                        <option value="month">{{ __('admin.support_monitor.period_month') }}</option>
                    </select>
                </label>
            </div>
            <div class="mt-4 rounded-lg border border-sky-100 bg-sky-50 px-4 py-3 text-xs text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-200">
                <strong>{{ __('admin.support_monitor.sla_legend') }}</strong>
                {{ __('admin.support_monitor.sla_legend_description') }}
            </div>
        </x-filament::section>

        <x-filament::section :heading="__('admin.support_monitor.performance_table')">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-700">
                        <tr>
                            <th class="px-3 py-3">{{ __('admin.support_monitor.staff') }}</th>
                            <th class="px-3 py-3">{{ __('admin.support_monitor.presence') }}</th>
                            <th class="px-3 py-3">{{ __('admin.support_monitor.received') }}</th>
                            <th class="px-3 py-3">{{ __('admin.support_monitor.claimed') }}</th>
                            <th class="px-3 py-3">{{ __('admin.support_monitor.active') }}</th>
                            <th class="px-3 py-3">{{ __('admin.support_monitor.closed') }}</th>
                            <th class="px-3 py-3">{{ __('admin.support_monitor.messages') }}</th>
                            <th class="px-3 py-3">{{ __('admin.support_monitor.unread') }}</th>
                            <th class="px-3 py-3">{{ __('admin.support_monitor.avg_response') }}</th>
                            <th class="px-3 py-3">{{ __('admin.support_monitor.avg_processing') }}</th>
                            <th class="px-3 py-3">{{ __('admin.support_monitor.reassigned') }}</th><th class="px-3 py-3">{{ __('admin.support_monitor.sla_warning_breached') }}</th><th class="px-3 py-3">{{ __('admin.support_monitor.sla_rate') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($this->rows as $row)
                            <tr>
                                <td class="px-3 py-3"><div class="font-semibold text-gray-950 dark:text-white">{{ $row['name'] }}</div><div class="text-xs text-gray-500">{{ $row['username'] }}</div></td>
                                <td class="px-3 py-3"><span class="inline-flex items-center gap-1.5 text-xs"><span class="h-2 w-2 rounded-full {{ $row['online'] ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>{{ $row['online'] ? __('admin.support_monitor.online') : __('admin.support_monitor.offline') }}</span></td>
                                <td class="px-3 py-3 font-medium">{{ $row['received'] }}</td>
                                <td class="px-3 py-3">{{ $row['claimed'] }}</td>
                                <td class="px-3 py-3">{{ $row['active'] }}</td>
                                <td class="px-3 py-3">{{ $row['closed'] }}</td>
                                <td class="px-3 py-3">{{ $row['messages'] }}</td>
                                <td class="px-3 py-3">{{ $row['unread'] }}</td>
                                <td class="px-3 py-3">{{ $row['avg_response_seconds'] === null ? '-' : gmdate('H:i:s', $row['avg_response_seconds']) }}</td>
                                <td class="px-3 py-3">{{ $row['avg_processing_seconds'] === null ? '-' : gmdate('H:i:s', $row['avg_processing_seconds']) }}</td>
                                <td class="px-3 py-3">{{ $row['reassigned'] }}</td><td class="px-3 py-3">{{ $row['sla_warning'] }}/{{ $row['sla_breached'] }}</td><td class="px-3 py-3">{{ $row['sla_rate'] === null ? '-' : $row['sla_rate'].'%' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="px-3 py-10 text-center text-gray-500">{{ __('admin.support_monitor.no_staff') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
