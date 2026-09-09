<?php

namespace App\Filament\Clusters\Service\Resources\ServiceRequests\Tables;

use App\Enums\ProposalStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UrgencyLevel;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\ServiceRequestService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ServiceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'service.category', 'category', 'cskh', 'proposals.ktv']))
            ->columns([
                TextColumn::make('id')
                    ->label('#ID')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label(__('admin.service_request.fields.customer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('province')
                    ->label(__('admin.service_request.fields.province'))
                    ->default('—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ward')
                    ->label(__('admin.service_request.fields.ward'))
                    ->default('—')
                    ->searchable(),

                TextColumn::make('service_display_name')
                    ->label(__('admin.service_request.fields.service'))
                    ->state(function (ServiceRequest $record) {
                        $cat = $record->category ?? $record->service?->category;
                        if (!$cat) {
                            return '—';
                        }
                        $name = $cat->name;
                        if (is_array($name)) {
                            return $name[app()->getLocale()] ?? $name['vi'] ?? reset($name);
                        }
                        if (is_string($name) && str_starts_with(trim($name), '{')) {
                            $decoded = json_decode($name, true);
                            if (is_array($decoded)) {
                                return $decoded[app()->getLocale()] ?? $decoded['vi'] ?? reset($decoded);
                            }
                        }
                        return (string) $name;
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->whereHas('category', function ($catQ) use ($search) {
                                $catQ->where('name', 'ILIKE', "%{$search}%");
                            })->orWhereHas('service.category', function ($catQ) use ($search) {
                                $catQ->where('name', 'ILIKE', "%{$search}%");
                            });
                        });
                    }),

                TextColumn::make('urgency_level')
                    ->label(__('admin.service_request.fields.urgency_level'))
                    ->badge()
                    ->color(fn (UrgencyLevel $state): string => match ($state) {
                        UrgencyLevel::NEED_NOW => 'danger',
                        UrgencyLevel::TODAY => 'warning',
                        UrgencyLevel::SCHEDULED => 'info',
                    })
                    ->formatStateUsing(fn (UrgencyLevel $state) => $state->label()),

                TextColumn::make('status')
                    ->label(__('admin.service_request.fields.status'))
                    ->badge()
                    ->color(fn (ServiceRequestStatus $state): string => match ($state) {
                        ServiceRequestStatus::NEW => 'gray',
                        ServiceRequestStatus::ASSIGNED => 'warning',
                        ServiceRequestStatus::SEARCHING_KTV => 'info',
                        ServiceRequestStatus::PROPOSAL_SENT, ServiceRequestStatus::WAITING_CUSTOMER_CONFIRM => 'primary',
                        ServiceRequestStatus::MATCHED => 'success',
                        ServiceRequestStatus::BOOKING_CREATED => 'success',
                        ServiceRequestStatus::CLOSED, ServiceRequestStatus::CANCELED => 'danger',
                    })
                    ->formatStateUsing(fn (ServiceRequestStatus $state) => $state->label()),

                TextColumn::make('note')
                    ->label(__('admin.service_request.fields.note'))
                    ->limit(25)
                    ->tooltip(fn (Model $record): ?string => !empty($record->note) ? $record->note : null)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('cskh_note')
                    ->label(__('admin.service_request.fields.cskh_notes'))
                    ->limit(25)
                    ->tooltip(fn (Model $record): ?string => !empty($record->cskh_note) ? $record->cskh_note : null)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('cskh.name')
                    ->label(__('admin.service_request.fields.cskh'))
                    ->placeholder(__('admin.unassigned')),

                TextColumn::make('created_at')
                    ->label(__('admin.common.table.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('urgency_level')
                    ->label(__('admin.service_request.fields.urgency_level'))
                    ->options(UrgencyLevel::toOptions()),

                SelectFilter::make('status')
                    ->label(__('admin.service_request.fields.status'))
                    ->options(ServiceRequestStatus::toOptions()),
            ])
            ->actions([
                Action::make('assign_me')
                    ->label(__('admin.service_request.fields.assign_me'))
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->visible(fn (ServiceRequest $record) => is_null($record->cskh_id))
                    ->action(function (ServiceRequest $record) {
                        $record->update([
                            'cskh_id' => Auth::id(),
                            'status' => ServiceRequestStatus::ASSIGNED,
                        ]);
                        Notification::make()
                            ->title(__('admin.notification.success.update_success'))
                            ->success()
                            ->send();
                    }),

                Action::make('propose_ktv')
                    ->label(__('admin.service_request.fields.propose_ktv'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->form([
                        Placeholder::make('previous_proposals')
                            ->hiddenLabel()
                            ->visible(fn (?ServiceRequest $record) => $record && $record->proposals()->exists())
                            ->content(function (?ServiceRequest $record) {
                                if (!$record) {
                                    return null;
                                }
                                $proposals = $record->proposals()->with(['ktv.profile', 'cskh'])->latest()->get();
                                if ($proposals->isEmpty()) {
                                    return null;
                                }
                                return view('filament.clusters.service.previous-proposals', [
                                    'proposals' => $proposals,
                                ]);
                            }),

                        Select::make('ktv_id')
                            ->label(__('admin.service_request.action.recommend_ktv'))
                            ->allowHtml()
                            ->options(function (ServiceRequest $record) {
                                $province = $record->province ?? $record->customer?->province ?? $record->customer?->profile?->province;
                                $ward = $record->ward ?? $record->customer?->ward ?? $record->customer?->profile?->ward;

                                $query = User::where('role', UserRole::KTV->value)
                                    ->where('is_active', true);

                                if (!empty($province)) {
                                    $query->where(function ($q) use ($province, $ward) {
                                        $q->where(function ($sub) use ($province) {
                                            $sub->where('work_province', 'ILIKE', "%{$province}%")
                                                ->orWhere('province', 'ILIKE', "%{$province}%")
                                                ->orWhereHas('reviewApplication', function ($appQ) use ($province) {
                                                    $appQ->where('work_province', 'ILIKE', "%{$province}%");
                                                });
                                        });

                                        if (!empty($ward)) {
                                            $cleanWard = preg_replace('/\s*\(.*?\)\s*$/', '', $ward);
                                            $q->where(function ($wq) use ($ward, $cleanWard) {
                                                $wq->where('ward', 'ILIKE', "%{$cleanWard}%")
                                                    ->orWhere('work_wards', 'ILIKE', "%{$cleanWard}%")
                                                    ->orWhereJsonContains('work_wards', $ward)
                                                    ->orWhereJsonContains('work_wards', $cleanWard)
                                                    ->orWhereHas('reviewApplication', function ($appQ) use ($ward, $cleanWard) {
                                                        $appQ->where('work_wards', 'ILIKE', "%{$cleanWard}%")
                                                            ->orWhereJsonContains('work_wards', $ward)
                                                            ->orWhereJsonContains('work_wards', $cleanWard);
                                                    });
                                            });
                                        }
                                    });
                                }

                                $ktvs = $query->get();

                                // Nếu tìm chính xác Phường/Xã không có KTV, mở rộng tìm trong cùng Tỉnh/Thành phố của khách
                                if ($ktvs->isEmpty() && !empty($province)) {
                                    $ktvs = User::where('role', UserRole::KTV->value)
                                        ->where('is_active', true)
                                        ->where(function ($sub) use ($province) {
                                            $sub->where('work_province', 'ILIKE', "%{$province}%")
                                                ->orWhere('province', 'ILIKE', "%{$province}%")
                                                ->orWhereHas('reviewApplication', function ($appQ) use ($province) {
                                                    $appQ->where('work_province', 'ILIKE', "%{$province}%");
                                                });
                                        })->get();
                                }

                                // Nếu trong Tỉnh/Thành phố của khách vẫn chưa có KTV, lấy toàn bộ KTV đang hoạt động trên hệ thống để CSKH có thể linh hoạt chọn đề xuất
                                if ($ktvs->isEmpty()) {
                                    $ktvs = User::where('role', UserRole::KTV->value)
                                        ->where('is_active', true)
                                        ->get();
                                }

                                return $ktvs->mapWithKeys(function ($ktv) {
                                    $isOnline = $ktv->is_online ?? true;
                                    $statusBadge = $isOnline
                                        ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 mr-2"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span> Online</span>'
                                        : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300 mr-2"><span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span> Offline</span>';

                                    $phone = $ktv->phone ? '<span class="text-xs text-gray-500 font-mono ml-1.5"> ' . e($ktv->phone) . '</span>' : '';
                                    $ktvProvince = $ktv->reviewApplication?->work_province ?? $ktv->work_province ?? '';
                                    $ktvWard = $ktv->reviewApplication?->work_wards[0] ?? $ktv->work_wards[0] ?? $ktv->ward ?? '';
                                    $loc = array_filter([$ktvWard, $ktvProvince]);
                                    $locStr = !empty($loc) ? '<span class="text-xs text-amber-600 dark:text-amber-400 ml-2 font-medium"> ' . e(implode(', ', $loc)) . '</span>' : '';

                                    $html = '<div class="inline-flex items-center py-1 flex-wrap">' . $statusBadge . '<span class="font-semibold text-gray-900 dark:text-white">' . e($ktv->name) . '</span>' . $phone . $locStr . '</div>';
                                    return [$ktv->id => $html];
                                });
                            })
                            ->searchable()
                            ->searchPrompt(__('admin.service_request.action.search_ktv_prompt'))
                            ->noOptionsMessage(__('admin.service_request.action.no_ktv_available'))
                            ->required(),
                    ])
                    ->action(function (ServiceRequest $record, array $data, ServiceRequestService $service) {
                        $result = $service->proposeKtvForRequest($record->id, $data['ktv_id'], Auth::id());
                        if ($result->isSuccess()) {
                            Notification::make()
                                ->title(__('admin.service_request.messages.propose_success'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title($result->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('create_booking')
                    ->label(__('admin.service_request.fields.create_booking'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ServiceRequest $record) => in_array($record->status, [ServiceRequestStatus::MATCHED, ServiceRequestStatus::WAITING_CUSTOMER_CONFIRM]))
                    ->action(function (ServiceRequest $record, ServiceRequestService $service) {
                        $acceptedProposal = $record->proposals()->where('status', ProposalStatus::CUSTOMER_ACCEPTED->value)->first()
                            ?? $record->proposals()->first();

                        if (!$acceptedProposal) {
                            Notification::make()
                                ->title(__('admin.service_request.messages.proposal_not_found'))
                                ->warning()
                                ->send();
                            return;
                        }

                        $result = $service->createBookingFromRequest($record, $acceptedProposal->ktv_id);
                        if ($result->isSuccess()) {
                            Notification::make()
                                ->title(__('admin.service_request.messages.booking_created_success'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title($result->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('view_proposals')
                    ->label(__('admin.service_request.action.invite_history'))
                    ->icon('heroicon-o-queue-list')
                    ->color('info')
                    ->modalHeading(__('admin.service_request.action.invite_history'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('admin.service_request.modal.close'))
                    ->modalContent(function (ServiceRequest $record) {
                        $proposals = $record->proposals()->with(['ktv', 'cskh'])->latest()->get();
                        return view('filament.clusters.service.proposals-modal', [
                            'record' => $record,
                            'proposals' => $proposals,
                        ]);
                    }),
            ]);
    }
}
