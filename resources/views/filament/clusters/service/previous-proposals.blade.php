@use('App\Enums\ProposalStatus')
@use('Illuminate\Support\Facades\Storage')

<div class="prev-proposals-box">
    <div class="prev-proposals-header">
        <div class="prev-proposals-title">
            <svg class="prev-proposals-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ __('admin.service_request.fields.previous_proposals') }}</span>
        </div>
        <span class="prev-proposals-count">
            {{ $proposals->count() }}
        </span>
    </div>

    <div class="prev-proposals-list">
        @foreach($proposals as $p)
            @php
                $statusEnum = $p->status instanceof ProposalStatus 
                    ? $p->status 
                    : (is_numeric($p->status) ? ProposalStatus::tryFrom((int)$p->status) : null);
                $statusLabel = $statusEnum ? $statusEnum->label() : ($p->status ?? '—');
                $statusColor = $statusEnum ? $statusEnum->color() : 'gray';
                $avatarUrl = $p->ktv?->profile?->avatar_url;
            @endphp
            <div class="prev-proposal-row">
                <div class="prev-proposal-main">
                    @if($avatarUrl)
                        <img src="{{ Storage::url($avatarUrl) }}" alt="{{ $p->ktv?->name }}" class="prev-proposal-avatar-img">
                    @else
                        <div class="prev-proposal-avatar-initial">
                            {{ mb_strtoupper(mb_substr($p->ktv?->name ?? 'K', 0, 1)) }}
                        </div>
                    @endif

                    <div class="prev-proposal-info">
                        <div class="prev-proposal-top">
                            <span class="prev-proposal-name">
                                {{ $p->ktv?->name ?? 'KTV #' . $p->ktv_id }}
                            </span>
                            @if($p->ktv?->phone)
                                <span class="prev-proposal-phone">
                                    {{ $p->ktv->phone }}
                                </span>
                            @endif
                        </div>

                        <div class="prev-proposal-meta">
                            @if($p->cskh?->name)
                                <span>{{ __('admin.service_request.modal.proposed_by_cskh') }}: <strong class="prev-proposal-author">{{ $p->cskh->name }}</strong></span>
                            @endif
                            @if($p->cskh?->name && $p->created_at)
                                <span class="prev-proposal-dot">•</span>
                            @endif
                            @if($p->created_at)
                                <span class="prev-proposal-time">{{ $p->created_at->format('H:i d/m/Y') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="prev-proposal-badge">
                    <x-filament::badge :color="$statusColor">
                        {{ $statusLabel }}
                    </x-filament::badge>
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
    .prev-proposals-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 14px;
    }
    .dark .prev-proposals-box {
        background-color: rgba(255, 255, 255, 0.03);
        border-color: rgba(255, 255, 255, 0.08);
    }
    .prev-proposals-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 8px;
        margin-bottom: 8px;
        border-bottom: 1px solid #edf2f7;
    }
    .dark .prev-proposals-header {
        border-bottom-color: rgba(255, 255, 255, 0.06);
    }
    .prev-proposals-title {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }
    .dark .prev-proposals-title {
        color: #94a3b8;
    }
    .prev-proposals-icon {
        width: 15px;
        height: 15px;
        color: #3b82f6;
    }
    .prev-proposals-count {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        background-color: #e2e8f0;
        padding: 1px 7px;
        border-radius: 9999px;
    }
    .dark .prev-proposals-count {
        background-color: rgba(255, 255, 255, 0.1);
        color: #cbd5e1;
    }
    .prev-proposals-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 200px;
        overflow-y: auto;
    }
    .prev-proposal-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .prev-proposal-row + .prev-proposal-row {
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
    }
    .dark .prev-proposal-row + .prev-proposal-row {
        border-top-color: rgba(255, 255, 255, 0.05);
    }
    .prev-proposal-main {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1;
    }
    .prev-proposal-avatar-img {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 1px solid #e2e8f0;
    }
    .prev-proposal-avatar-initial {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #e0f2fe;
        color: #0284c7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        flex-shrink: 0;
        border: 1px solid #bae6fd;
    }
    .dark .prev-proposal-avatar-initial {
        background-color: rgba(14, 165, 233, 0.15);
        color: #38bdf8;
        border-color: rgba(14, 165, 233, 0.3);
    }
    .prev-proposal-info {
        min-width: 0;
        flex: 1;
    }
    .prev-proposal-top {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .prev-proposal-name {
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
    }
    .dark .prev-proposal-name {
        color: #f8fafc;
    }
    .prev-proposal-phone {
        font-size: 11px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        color: #64748b;
        background-color: #f1f5f9;
        padding: 1px 6px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
    }
    .dark .prev-proposal-phone {
        background-color: rgba(255, 255, 255, 0.06);
        color: #94a3b8;
        border-color: rgba(255, 255, 255, 0.1);
    }
    .prev-proposal-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
        flex-wrap: wrap;
    }
    .dark .prev-proposal-meta {
        color: #94a3b8;
    }
    .prev-proposal-author {
        color: #334155;
        font-weight: 500;
    }
    .dark .prev-proposal-author {
        color: #cbd5e1;
    }
    .prev-proposal-dot {
        color: #cbd5e1;
    }
    .prev-proposal-time {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        color: #94a3b8;
    }
    .prev-proposal-badge {
        flex-shrink: 0;
    }
</style>
