<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="flex flex-col h-[calc(100vh-14rem)] bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800">
            <div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    {{ __('admin.chat_room.fields.chat') }} #{{ $this->record->id }}
                </h2>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $this->record->created_at->format('Y-m-d H:i:s') }} • ID: {{ $this->record->id }}
                </div>
            </div>
            <div class="flex items-center gap-3"></div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-6" id="chat-messages" wire:poll.5s>
            @forelse($this->messages as $message)
            @php
            $isKtv = $message->sender_by == $this->record->ktv_id;
            $isRight = $isKtv;
            @endphp

            <div class="flex {{ $isRight ? 'justify-end' : 'justify-start' }}" wire:key="msg-{{ $message->id }}">
                <div class="flex {{ $isRight ? 'flex-row-reverse' : 'flex-row' }} max-w-[80%] gap-3">
                    {{-- Avatar --}}
                    <div class="flex-shrink-0">
                        @if($message->sender && $message->sender->profile && $message->sender->profile->avatar_url)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($message->sender->profile->avatar_url) }}" alt="Avatar" class="w-8 h-8 rounded-full object-cover border border-gray-200 shadow-sm">
                        @else
                        <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300 border border-gray-200 shadow-sm">
                            {{ substr($message->sender->name ?? 'U', 0, 1) }}
                        </div>
                        @endif
                    </div>

                    {{-- Message Content --}}
                    <div class="flex flex-col {{ $isRight ? 'items-end' : 'items-start' }}">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                {{ $message->sender->name  ?? ''}}
                                @if($isKtv)
                                <span class="text-[10px] bg-blue-100 text-blue-700 px-1 rounded">{{ __('admin.chat_room.fields.ktv')  }}</span>
                                @else
                                <span class="text-[10px] bg-gray-100 text-gray-700 px-1 rounded">{{ __('admin.chat_room.fields.user')  }}</span>
                                @endif
                            </span>
                            <span class="text-[10px] text-gray-400">
                                {{ $message->created_at->format('H:i A') }}
                            </span>
                        </div>

                        <div class="px-4 py-2 rounded-2xl shadow-sm text-sm {{ $isRight ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 text-gray-800 dark:text-gray-100 rounded-tl-none' }}">
                            {{ $message->content }}
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="flex flex-col items-center justify-center h-full text-gray-400">
                <x-heroicon-o-chat-bubble-oval-left-ellipsis class="w-12 h-12 mb-2 opacity-50" />
                <p class="text-sm">No messages yet.</p>
            </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>