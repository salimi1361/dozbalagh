<?php

namespace App\Http\Controllers\Association;

use App\Http\Controllers\Controller;
use App\Services\PermitItemAuditService;
use App\Services\PermitItemFinancialService;
use App\Services\PermitItemTransferFinancialService;
use App\Services\PanelFeatureService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PermitWorkflowController extends Controller
{
    private const TERMINAL = ['rejected', 'archived', 'lost', 'returned_unused', 'extended', 'cancelled'];

    public function index(Request $request)
    {
        $requestedScope = $request->string('scope')->toString();
        $scope = in_array($requestedScope, ['active', 'rejected', 'circulation', 'transferred', 'archive'], true) ? $requestedScope : 'active';
        $query = $this->baseQuery();

        if ($scope === 'transferred') {
            $query->where('item.item_status', 'transferred_in')
                ->whereNotNull('transfer.id');
        } elseif ($scope === 'rejected') {
            $query->where('item.item_status', 'rejected');
        } elseif ($scope === 'archive') {
            $query->whereIn('item.item_status', array_values(array_diff(self::TERMINAL, ['rejected'])));
        } elseif ($scope === 'circulation') {
            $query->where('item.item_status', 'issued')
                ->whereExists(function ($mission) {
                    $mission->selectRaw('1')
                        ->from('permit_courier_missions as circulation_mission')
                        ->whereColumn('circulation_mission.permit_request_item_id', 'item.id')
                        ->where('circulation_mission.direction', 'association_to_company')
                        ->where('circulation_mission.status', 'completed');
                });
        } else {
            $query->whereNotIn('item.item_status', self::TERMINAL)
                ->where('item.item_status', '<>', 'transferred_in')
                ->where(function ($active) {
                    $active->where('item.item_status', '<>', 'issued')
                        ->orWhereNotExists(function ($mission) {
                            $mission->selectRaw('1')
                                ->from('permit_courier_missions as completed_mission')
                                ->whereColumn('completed_mission.permit_request_item_id', 'item.id')
                                ->where('completed_mission.direction', 'association_to_company')
                                ->where('completed_mission.status', 'completed');
                        });
                });
        }

        if ($scope === 'active' && !$request->filled('status')) {
            $query->where(function ($q) {
                $q->where('item.item_status', '!=', 'lost_reported')
                    ->orWhereNotExists(function ($seen) {
                        $seen->selectRaw('1')
                            ->from('permit_item_events as seen')
                            ->whereColumn('seen.permit_request_item_id', 'item.id')
                            ->where('seen.event_type', 'association_status_seen')
                            ->whereRaw("seen.id > COALESCE((
                                SELECT MAX(report.id)
                                FROM permit_item_events AS report
                                WHERE report.permit_request_item_id = item.id
                                  AND report.event_type = 'lost_reported_by_company'
                            ), 0)");
                    });
            });
        }

        $query->when($request->filled('status'), function ($q) use ($request) {
            $status = $request->input('status');
            $status === 'lost'
                ? $q->whereIn('item.item_status', ['lost', 'lost_reported'])
                : $q->where('item.item_status', $status);
        })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->input('search')).'%';
                $q->where(function ($q) use ($term) {
                    $q->where('request.d_code', 'like', $term)
                        ->orWhere('item.d_serial_number', 'like', $term)
                        ->orWhere('company.name_fa', 'like', $term)
                        ->orWhere('transfer_source_company.name_fa', 'like', $term)
                        ->orWhere('transfer_destination_company.name_fa', 'like', $term)
                        ->orWhere('fleet.transit_horse', 'like', $term)
                        ->orWhere('fleet.transit_trailer', 'like', $term);
                });
            });

        $unreadCitsResponses = (clone $query)
            ->whereNotNull('item.company_cits_responded_at')
            ->where(function ($query) {
                $query->whereNull('item.association_cits_response_seen_at')
                    ->orWhereColumn('item.company_cits_responded_at', '>', 'item.association_cits_response_seen_at');
            })
            ->distinct()
            ->count('request.id');

        $rejectedCount = (clone $this->baseQuery())
            ->where('item.item_status', 'rejected')
            ->distinct()
            ->count('request.id');

        $items = $this->groupedParentPage($query, 20);
        $bulkRejectableParentIds = collect();
        if ($scope === 'active') {
            $parentIds = $items->getCollection()
                ->pluck('parent_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();
            $rejectableStatuses = ['pending', 'under_review', 'approved'];

            if ($parentIds->isNotEmpty()) {
                $bulkRejectableParentIds = DB::table('permit_request_items')
                    ->whereIn('permit_request_id', $parentIds->all())
                    ->get(['permit_request_id', 'item_status'])
                    ->groupBy('permit_request_id')
                    ->filter(function ($rows) use ($rejectableStatuses) {
                        return $rows->contains(fn ($row) => in_array($row->item_status, $rejectableStatuses, true))
                            && $rows->every(fn ($row) => in_array($row->item_status, [...$rejectableStatuses, 'rejected'], true));
                    })
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->flip();
            }
        }

        return view('association.permits.index', [
            'items' => $items,
            'scope' => $scope,
            'rejectedCount' => $rejectedCount,
            'unreadCitsResponses' => $unreadCitsResponses,
            'bulkRejectableParentIds' => $bulkRejectableParentIds,
            'latestItemsUpdate' => $items->getCollection()
                ->flatMap(fn ($group) => $group->children)
                ->max('updated_at'),
        ]);
    }

    public function show(int $item)
    {
        $permit = $this->baseQuery()->where('item.id', $item)->firstOrFail();
        if ($permit->item_status === 'lost_reported') {
            $latestReportEventId = DB::table('permit_item_events')
                ->where('permit_request_item_id', $item)
                ->where('event_type', 'lost_reported_by_company')
                ->max('id');
            $alreadySeen = DB::table('permit_item_events')
                ->where('permit_request_item_id', $item)
                ->where('event_type', 'association_status_seen')
                ->when($latestReportEventId, fn ($q) => $q->where('id', '>', $latestReportEventId))
                ->exists();
            if (!$alreadySeen) {
                app(PermitItemAuditService::class)->record(
                    $permit,
                    'association_status_seen',
                    'اعلام مفقودی شرکت توسط انجمن مشاهده شد.',
                    [],
                    ['item_status' => 'lost_reported'],
                    ['source_event_id' => $latestReportEventId],
                    'lost_reported',
                    'lost_reported',
                );
            }
        }
        if ($permit->company_cits_responded_at
            && (!$permit->association_cits_response_seen_at
                || $permit->company_cits_responded_at > $permit->association_cits_response_seen_at)) {
            DB::table('permit_request_items')->where('id', $item)->update([
                'association_cits_response_seen_at' => now(),
            ]);
            $permit->association_cits_response_seen_at = now();
        }
        $issuance = null;
        if ($permit->item_status === 'ready_to_issue' && !$permit->d_serial_number) {
            $isRenewal = $permit->request_type === 'renewal';
            $validityDays = (int) ($permit->country_validity_days ?? 30);
            $expiresAt = now()->startOfDay()->addDays($validityDays);
            $suggestedSerial = $isRenewal
                ? ($permit->previous_serial_number ?: $permit->previous_d_code)
                : DB::table('dozbalagh_items')
                    ->join('dozbalagh_batches', 'dozbalagh_items.batch_id', '=', 'dozbalagh_batches.id')
                    ->where('dozbalagh_batches.country_id', $permit->country_id)
                    ->whereNotNull('dozbalagh_batches.permit_type')
                    ->where('dozbalagh_batches.permit_type', $permit->permit_type)
                    ->where(function ($query) {
                        $query->whereNull('dozbalagh_items.lifecycle_status')
                            ->orWhere('dozbalagh_items.lifecycle_status', 'raw')
                            ->orWhere('dozbalagh_items.lifecycle_status', 'in_stock');
                    })
                    ->orderBy('dozbalagh_items.serial_number')
                    ->value('dozbalagh_items.serial_number');
            $issuance = (object) [
                'is_renewal' => $isRenewal,
                'suggested_serial' => $suggestedSerial,
                'validity_days' => $validityDays,
                'expires_at_fa' => \Morilog\Jalali\Jalalian::fromCarbon($expiresAt)->format('Y/m/d'),
            ];
        }
        $events = DB::table('permit_item_events as event')
            ->leftJoin('users as user', 'user.id', '=', 'event.actor_user_id')
            ->where('event.permit_request_item_id', $item)
            ->where('event.permit_request_id', $permit->permit_request_id)
            ->select('event.*', 'user.username as actor_name')
            ->orderByDesc('event.id')
            ->get();
        $missions = DB::table('permit_courier_missions')
            ->where('permit_request_item_id', $item)
            ->latest()
            ->get();

        $companyDrivers = DB::table('drivers')
            ->where(function ($query) use ($permit) {
                $query->where(function ($query) use ($permit) {
                    $query->where('current_company_id', $permit->company_id)
                        ->where('is_active', true);
                })
                    ->orWhere('id', $permit->driver_id);
            })
            ->orderBy('last_name_fa')
            ->get(['id', 'first_name_fa', 'last_name_fa', 'national_code', 'is_active']);
        $companyFleets = DB::table('fleets')
            ->where(function ($query) use ($permit) {
                $query->where(function ($query) use ($permit) {
                    $query->where('company_id', $permit->company_id)
                        ->where('is_active', true);
                })
                    ->orWhere('id', $permit->fleet_id);
            })
            ->orderBy('transit_plate')
            ->get(['id', 'transit_plate', 'transit_horse', 'transit_trailer', 'smart_card_number', 'is_active']);
        $countries = DB::table('countries')->orderBy('name')->get(['id', 'name']);
        $transferEnabled = auth()->user()?->role?->name === 'association'
            && app(PanelFeatureService::class)->enabledForUser(auth()->user(), 'unused_permit_transfer');
        $showCourierCode = app(PanelFeatureService::class)->enabledForRole('association', 'show_courier_code');
        $transferCompanies = collect();
        $transferDrivers = collect();
        $transferFleets = collect();
        $transferFinancial = app(PermitItemTransferFinancialService::class)->settings();
        if ($transferEnabled && $permit->item_status === 'returned_unused') {
            $transferCompanies = DB::table('companies')->orderBy('name_fa')->get(['id', 'name_fa', 'company_code']);
            $transferDrivers = DB::table('drivers')->where('is_active', true)
                ->whereNotNull('current_company_id')->orderBy('last_name_fa')
                ->get(['id', 'current_company_id', 'first_name_fa', 'last_name_fa', 'national_code']);
            $transferFleets = DB::table('fleets')->where('is_active', true)
                ->orderBy('transit_plate')->get(['id', 'company_id', 'transit_plate', 'transit_horse', 'transit_trailer']);
        }

        return view('association.permits.show', compact('permit', 'events', 'missions', 'issuance', 'companyDrivers', 'companyFleets', 'countries', 'transferEnabled', 'transferCompanies', 'transferDrivers', 'transferFleets', 'transferFinancial', 'showCourierCode'));
    }

    public function transfer(
        Request $request,
        int $item,
        PermitItemAuditService $audit,
        PermitItemTransferFinancialService $finance,
        PanelFeatureService $features,
    ) {
        abort_unless(
            $request->user()?->role?->name === 'association'
            && $features->enabledForUser($request->user(), 'unused_permit_transfer'),
            403
        );
        $validated = $request->validate([
            'destination_company_id' => ['required', 'integer', 'exists:companies,id'],
            'destination_driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'destination_fleet_id' => ['required', 'integer', 'exists:fleets,id'],
            'reason' => ['required', 'string', 'max:1000'],
            'confirm_active_assignments' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($item, $validated, $audit, $finance) {
            $row = $this->lockItem($item);
            abort_unless(
                $row->item_status === 'returned_unused'
                && $row->disposition_type === 'unused'
                && !empty($row->company_return_submitted_at)
                && !empty($row->courier_received_at),
                422,
                'انتقال فقط پس از ارسال رسمی و تأیید برگشت بدون استفاده توسط انجمن مجاز است.'
            );
            abort_if(empty($row->d_serial_number), 422, 'شماره دوزوله برای انتقال ثبت نشده است.');

            $destinationCompanyId = (int) $validated['destination_company_id'];
            $destinationDriverId = (int) $validated['destination_driver_id'];
            $destinationFleetId = (int) $validated['destination_fleet_id'];
            $driver = DB::table('drivers')->where('id', $destinationDriverId)->where('is_active', true)->lockForUpdate()->first();
            $fleet = DB::table('fleets')->where('id', $destinationFleetId)->where('is_active', true)->lockForUpdate()->first();
            $activeAssignments = $this->findActiveAssignments($destinationDriverId, $destinationFleetId, (int) $row->id);
            abort_if(
                ($activeAssignments['driver']->isNotEmpty() || $activeAssignments['fleet']->isNotEmpty())
                && empty($validated['confirm_active_assignments']),
                422,
                'راننده یا ناوگان انتخاب‌شده دوزوله فعال دارد. پس از مشاهده هشدار، تأیید آگاهانه انتقال الزامی است.'
            );
            abort_unless($driver && (int) $driver->current_company_id === $destinationCompanyId, 422, 'راننده مقصد متعلق به شرکت مقصد نیست یا غیرفعال است.');
            abort_unless($fleet && (int) $fleet->company_id === $destinationCompanyId, 422, 'ناوگان مقصد متعلق به شرکت مقصد نیست یا غیرفعال است.');
            abort_if(
                (int) $row->company_id === $destinationCompanyId
                && (int) $row->driver_id === $destinationDriverId
                && (int) $row->fleet_id === $destinationFleetId,
                422,
                'مبدأ و مقصد انتقال یکسان هستند.'
            );

            $money = $finance->apply($row->id, (string) $row->d_serial_number, (int) $row->company_id, $destinationCompanyId);
            $transferId = DB::table('permit_item_transfers')->insertGetId([
                'permit_request_item_id' => $row->id,
                'serial_number' => $row->d_serial_number,
                'source_company_id' => $row->company_id,
                'source_driver_id' => $row->driver_id,
                'source_fleet_id' => $row->fleet_id,
                'destination_company_id' => $destinationCompanyId,
                'destination_driver_id' => $destinationDriverId,
                'destination_fleet_id' => $destinationFleetId,
                'transferred_by_user_id' => auth()->id(),
                'transfer_fee' => $money['fee'],
                'source_refund' => $money['refund'],
                'fee_wallet_transaction_id' => $money['fee_transaction_id'],
                'refund_wallet_transaction_id' => $money['refund_transaction_id'],
                'reason' => $validated['reason'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Ownership lives on the parent request in the legacy schema. Create a
            // destination request instead of changing the source parent, because a
            // request may contain several permit items belonging to the source company.
            $sourceRequestId = (int) $row->permit_request_id;
            $sourceRequest = DB::table('permit_requests')->where('id', $sourceRequestId)->lockForUpdate()->first();
            $destinationRequest = (array) $sourceRequest;
            unset($destinationRequest['id'], $destinationRequest['created_at'], $destinationRequest['updated_at']);
            $destinationRequest['d_code'] = 'TR'.now()->format('YmdHis').$row->id;
            $destinationRequest['company_id'] = $destinationCompanyId;
            $destinationRequest['driver_id'] = $destinationDriverId;
            $destinationRequest['fleet_id'] = $destinationFleetId;
            $destinationRequest['status'] = 'issued';
            $destinationRequest['payment_status'] = 'settled';
            $destinationRequest['total_amount'] = 0;
            $destinationRequest['company_note'] = "انتقال دوزوله شماره {$row->d_serial_number} با حفظ شماره توسط انجمن";
            if (array_key_exists('serial_number', $destinationRequest)) {
                $destinationRequest['serial_number'] = null;
            }
            $destinationRequest['created_at'] = now();
            $destinationRequest['updated_at'] = now();
            $destinationRequestId = DB::table('permit_requests')->insertGetId($destinationRequest);

            DB::table('permit_request_items')->where('id', $row->id)->update([
                'permit_request_id' => $destinationRequestId,
                'issued_company_id' => $row->issued_company_id ?: $row->company_id,
                'item_status' => 'transferred_in',
                'closed_at' => null,
                'updated_at' => now(),
            ]);
            DB::table('permit_item_transfers')->where('id', $transferId)->update([
                'source_permit_request_id' => $sourceRequestId,
                'destination_permit_request_id' => $destinationRequestId,
                'updated_at' => now(),
            ]);
            DB::table('permit_item_events')->where('permit_request_item_id', $row->id)->update([
                'permit_request_id' => $destinationRequestId,
                'updated_at' => now(),
            ]);
            $this->warehouseItemQuery($row->d_serial_number, $row->country_id, $row->permit_type)->update([
                'company_id' => $destinationCompanyId,
                'driver_id' => $destinationDriverId,
                'fleet_id' => $destinationFleetId,
                'lifecycle_status' => 'issued',
                'returned_at' => null,
                'updated_at' => now(),
            ]);

            if (DB::table('permit_request_items')->where('permit_request_id', $sourceRequestId)->exists()) {
                $this->syncParent($sourceRequestId);
            } else {
                DB::table('permit_requests')->where('id', $sourceRequestId)->update(['status' => 'archived', 'updated_at' => now()]);
            }

            $row->permit_request_id = $destinationRequestId;
            $sourceCompany = DB::table('companies')->where('id', $row->company_id)->value('name_fa');
            $destinationCompany = DB::table('companies')->where('id', $destinationCompanyId)->value('name_fa');
            $sourceDriverRow = DB::table('drivers')->where('id', $row->driver_id)->first(['first_name_fa', 'last_name_fa']);
            $sourceDriver = trim(($sourceDriverRow->first_name_fa ?? '').' '.($sourceDriverRow->last_name_fa ?? ''));
            $destinationDriver = trim(($driver->first_name_fa ?? '').' '.($driver->last_name_fa ?? ''));
            $sourceFleet = DB::table('fleets')->where('id', $row->fleet_id)->value('transit_plate');
            $destinationFleet = $fleet->transit_plate ?: trim(($fleet->transit_horse ?? '').' / '.($fleet->transit_trailer ?? ''), ' /');
            $changedParts = collect([
                (int) $row->company_id !== $destinationCompanyId ? 'شرکت' : null,
                (int) $row->driver_id !== $destinationDriverId ? 'راننده' : null,
                (int) $row->fleet_id !== $destinationFleetId ? 'ناوگان' : null,
            ])->filter()->implode('، ');
            $description = "دوزوله شماره {$row->d_serial_number} با حفظ شماره منتقل شد؛ شرکت: {$sourceCompany} ← {$destinationCompany}، راننده: {$sourceDriver} ← {$destinationDriver}، ناوگان: {$sourceFleet} ← {$destinationFleet}. موارد تغییرکرده: {$changedParts}.";
            $audit->record($row, 'unused_permit_transferred', $description, [
                'company_id' => $row->company_id, 'driver_id' => $row->driver_id, 'fleet_id' => $row->fleet_id,
            ], [
                'company_id' => $destinationCompanyId, 'driver_id' => $destinationDriverId, 'fleet_id' => $destinationFleetId,
            ], [
                'transfer_id' => $transferId, 'serial_number' => $row->d_serial_number,
                'source_request_id' => $sourceRequestId, 'destination_request_id' => $destinationRequestId,
                'changed_parts' => $changedParts,
                'transfer_fee' => $money['fee'], 'source_refund' => $money['refund'],
                'active_assignment_warning_acknowledged' => !empty($validated['confirm_active_assignments']),
                'active_driver_permits' => $activeAssignments['driver']->pluck('d_serial_number')->values()->all(),
                'active_fleet_permits' => $activeAssignments['fleet']->pluck('d_serial_number')->values()->all(),
            ], 'returned_unused', 'transferred_in');
        });

        return redirect()->route('association.permits.show', $item)->with('success', 'دوزوله با حفظ شماره به شرکت، راننده و ناوگان مقصد منتقل شد.');
    }

    public function transferConflicts(Request $request, int $item, PanelFeatureService $features)
    {
        abort_unless(
            $request->user()?->role?->name === 'association'
            && $features->enabledForUser($request->user(), 'unused_permit_transfer'),
            403
        );
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'fleet_id' => ['required', 'integer', 'exists:fleets,id'],
        ]);
        $conflicts = $this->findActiveAssignments((int) $validated['driver_id'], (int) $validated['fleet_id'], $item);

        return response()->json([
            'driver' => $conflicts['driver']->values(),
            'fleet' => $conflicts['fleet']->values(),
            'has_conflict' => $conflicts['driver']->isNotEmpty() || $conflicts['fleet']->isNotEmpty(),
        ]);
    }

    private function findActiveAssignments(int $driverId, int $fleetId, int $exceptItemId): array
    {
        $base = DB::table('permit_request_items as item')
            ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
            ->leftJoin('countries as country', 'country.id', '=', 'item.country_id')
            ->where('item.id', '<>', $exceptItemId)
            ->whereNotNull('item.d_serial_number')
            ->whereIn('item.item_status', ['issued', 'transferred_in'])
            ->select(['item.id', 'item.d_serial_number', 'item.item_status', 'permit.d_code', 'country.name as country_name']);

        return [
            'driver' => (clone $base)->where('permit.driver_id', $driverId)->orderByDesc('item.id')->get(),
            'fleet' => (clone $base)->where('permit.fleet_id', $fleetId)->orderByDesc('item.id')->get(),
        ];
    }

    public function decide(
        Request $request,
        int $item,
        PermitItemAuditService $audit,
        PermitItemFinancialService $finance,
    ) {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'reason' => 'required_if:decision,rejected|nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($item, $validated, $audit, $finance) {
            $row = $this->lockItem($item);
            if (!in_array($row->item_status, ['pending', 'under_review', 'approved'], true)) {
                abort(422, 'این دوزوله قبلاً تعیین تکلیف شده است.');
            }

            if ($validated['decision'] === 'approved') {
                $country = $row->country_id
                    ? DB::table('countries')->where('id', $row->country_id)->first()
                    : null;
                $allowedPermitTypes = $country
                    ? json_decode((string) ($country->allowed_permit_types ?? ''), true)
                    : null;
                abort_unless(
                    $country
                    && is_array($allowedPermitTypes)
                    && in_array((string) $row->permit_type, $allowedPermitTypes, true),
                    422,
                    'ترکیب کشور و نوع مجوز این دوزوله در مدیریت کشورها معتبر نیست.'
                );
            }

            if ($validated['decision'] === 'approved' && $row->driver_id) {
                $driverIsActive = DB::table('drivers')
                    ->where(function ($query) use ($row) {
                        $query->where('id', $row->driver_id)
                            ->orWhere('national_code', $row->driver_id);
                    })
                    ->where('is_active', true)
                    ->exists();
                abort_unless($driverIsActive, 422, 'راننده این پرونده توسط انجمن غیرفعال شده است و امکان تأیید وجود ندارد.');
            }
            if ($validated['decision'] === 'approved' && $row->fleet_id) {
                $fleetIsActive = DB::table('fleets')
                    ->where(function ($query) use ($row) {
                        $query->where('id', $row->fleet_id)
                            ->orWhere('smart_card_number', $row->fleet_id);
                    })
                    ->where('is_active', true)
                    ->exists();
                abort_unless($fleetIsActive, 422, 'ناوگان این پرونده توسط انجمن غیرفعال شده است و امکان تأیید وجود ندارد.');
            }

            if ($validated['decision'] === 'rejected') {
                $finance->release($row, (int) $row->company_id, "آزادسازی وجه ردیف {$row->id} به دلیل رد قطعی");
                DB::table('permit_request_items')->where('id', $row->id)->update([
                    'item_status' => 'rejected',
                    'allocation_status' => 'rejected',
                    'rejection_reason' => $validated['reason'],
                    'rejected_at' => now(),
                    'rejected_by_user_id' => auth()->id(),
                    'reviewed_at' => now(),
                    'reviewed_by_user_id' => auth()->id(),
                    'updated_at' => now(),
                ]);
                $audit->record($row, 'item_rejected', $validated['reason'], [], [
                    'financial_status' => 'released',
                ], [], $row->item_status, 'rejected');
            } else {
                $nextStatus = 'ready_to_issue';
                DB::table('permit_request_items')->where('id', $row->id)->update([
                    'item_status' => $nextStatus,
                    'allocation_status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewed_by_user_id' => auth()->id(),
                    'updated_at' => now(),
                ]);
                $audit->record(
                    $row,
                    'item_approved',
                    $row->request_type === 'renewal'
                        ? 'درخواست تمدید تأیید و برای تخصیص سریال و صدور آماده شد.'
                        : 'درخواست جدید تأیید و مستقیماً برای تخصیص سریال آماده شد.',
                    [],
                    [],
                    [],
                    $row->item_status,
                    $nextStatus,
                );
            }

            $this->syncParent((int) $row->permit_request_id);
        });

        if ($validated['decision'] === 'rejected') {
            return redirect()
                ->route('association.permits.index', ['scope' => 'rejected'])
                ->with('success', 'این دوزوله رد شد و برای مشاهده سوابق در بخش «رد شده‌ها» قرار گرفت.');
        }

        return back()->with('success', 'وضعیت این دوزوله با موفقیت ثبت شد.');
    }

    public function rejectAll(
        Request $request,
        int $parentRequest,
        PermitItemAuditService $audit,
        PermitItemFinancialService $finance,
    ) {
        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $rejectedCount = DB::transaction(function () use ($parentRequest, $validated, $audit, $finance) {
            $parent = DB::table('permit_requests')
                ->where('id', $parentRequest)
                ->lockForUpdate()
                ->firstOrFail();
            $items = DB::table('permit_request_items')
                ->where('permit_request_id', $parentRequest)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $rejectableStatuses = ['pending', 'under_review', 'approved'];

            abort_if($items->isEmpty(), 422, 'برای این پرونده هیچ مسیری ثبت نشده است.');
            abort_unless(
                $items->every(fn ($row) => in_array($row->item_status, [...$rejectableStatuses, 'rejected'], true)),
                422,
                'حداقل یکی از مسیرهای این پرونده وارد مرحله‌ای شده است که رد کامل آن امکان‌پذیر نیست.'
            );

            $itemsToReject = $items->filter(fn ($row) => in_array($row->item_status, $rejectableStatuses, true));
            abort_if($itemsToReject->isEmpty(), 422, 'این پرونده قبلاً تعیین تکلیف شده است.');
            abort_unless(
                $itemsToReject->every(fn ($row) => in_array($row->financial_status, ['reserved', 'released', 'waived'], true)),
                422,
                'وضعیت مالی یکی از مسیرها برای آزادسازی وجه معتبر نیست.'
            );

            foreach ($itemsToReject as $row) {
                try {
                    $finance->release(
                        $row,
                        (int) $parent->company_id,
                        "آزادسازی وجه ردیف {$row->id} به دلیل رد کامل درخواست {$parent->d_code}"
                    );
                } catch (\RuntimeException $exception) {
                    abort(422, $exception->getMessage());
                }
                DB::table('permit_request_items')->where('id', $row->id)->update([
                    'item_status' => 'rejected',
                    'allocation_status' => 'rejected',
                    'rejection_reason' => $validated['reason'],
                    'rejected_at' => now(),
                    'rejected_by_user_id' => auth()->id(),
                    'reviewed_at' => now(),
                    'reviewed_by_user_id' => auth()->id(),
                    'updated_at' => now(),
                ]);
                $audit->record(
                    $row,
                    'item_rejected',
                    $validated['reason'],
                    [],
                    ['financial_status' => in_array($row->financial_status, ['reserved'], true) ? 'released' : $row->financial_status],
                    [
                        'bulk_rejection' => true,
                        'parent_request_id' => $parentRequest,
                        'affected_items_count' => $itemsToReject->count(),
                    ],
                    $row->item_status,
                    'rejected',
                );
            }

            DB::table('permit_requests')->where('id', $parentRequest)->update([
                'reject_reason' => $validated['reason'],
                'rejected_at' => now(),
                'action_by_user_id' => auth()->id(),
                'updated_at' => now(),
            ]);
            $this->syncParent($parentRequest);

            return [
                'count' => $itemsToReject->count(),
                'code' => $parent->d_code,
            ];
        });

        return redirect()
            ->route('association.permits.index', ['scope' => 'rejected'])
            ->with('success', "درخواست {$rejectedCount['code']} با {$rejectedCount['count']} مسیر به‌صورت کامل رد شد و مبالغ آن آزاد شد.");
    }

    public function update(Request $request, int $item, PermitItemAuditService $audit)
    {
        $allowed = [
            'operation_type', 'loading_origin', 'loading_destination', 'cits_code', 'trip_code',
            'receipt_code', 'receipt_amount', 'cmr_date', 'tir_carnet_number', 'tir_carnet_date',
            'country_id', 'permit_type',
        ];
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'driver_id' => 'nullable|integer',
            'fleet_id' => 'nullable|integer',
            'operation_type' => 'nullable|string|max:100',
            'loading_origin' => 'nullable|string|max:255',
            'loading_destination' => 'nullable|string|max:255',
            'cits_code' => 'nullable|string|max:255',
            'trip_code' => 'nullable|string|max:255',
            'receipt_code' => 'nullable|string|max:255',
            'receipt_amount' => 'nullable|numeric|min:0',
            'cmr_date' => 'nullable|date',
            'tir_carnet_number' => 'nullable|string|max:255',
            'tir_carnet_date' => 'nullable|date',
            'country_id' => 'nullable|exists:countries,id',
            'permit_type' => 'nullable|string|max:100',
            'receipt_file' => 'nullable|image|max:5120',
            'cmr_file' => 'nullable|image|max:5120',
            'tir_file' => 'nullable|image|max:5120',
            'declaration_file' => 'nullable|image|max:5120',
        ]);

        if ($request->exists('country_id') || $request->exists('permit_type')) {
            $currentItem = DB::table('permit_request_items')->where('id', $item)->first(['country_id', 'permit_type']);
            $countryId = $validated['country_id'] ?? $currentItem?->country_id;
            $permitType = trim((string) ($validated['permit_type'] ?? $currentItem?->permit_type ?? ''));
            $country = $countryId ? DB::table('countries')->where('id', $countryId)->first() : null;
            $allowedPermitTypes = $country
                ? json_decode((string) ($country->allowed_permit_types ?? ''), true)
                : null;

            if (!$country || $permitType === '' || !is_array($allowedPermitTypes) || !in_array($permitType, $allowedPermitTypes, true)) {
                return back()
                    ->withInput()
                    ->withErrors(['permit_type' => 'ترکیب کشور و نوع مجوز انتخاب‌شده در مدیریت کشورها معتبر نیست.']);
            }
        }

        DB::transaction(function () use ($request, $item, $audit, $allowed, $validated) {
            $row = $this->lockItem($item);
            $before = [];
            $after = [];
            $update = [];

            foreach ($allowed as $field) {
                if ($request->exists($field) && (string) $row->{$field} !== (string) ($validated[$field] ?? null)) {
                    $before[$field] = $row->{$field};
                    $after[$field] = $validated[$field] ?? null;
                    $update[$field] = $validated[$field] ?? null;
                }
            }

            foreach (['receipt_file', 'cmr_file', 'tir_file', 'declaration_file'] as $field) {
                if ($request->hasFile($field)) {
                    $before[$field] = $row->{$field};
                    $path = $request->file($field)->store('permits/association-corrections', 'public');
                    $after[$field] = $path;
                    $update[$field] = $path;
                }
            }

            $parentUpdate = [];
            foreach (['driver_id', 'fleet_id'] as $field) {
                if (!$request->exists($field) || (string) $row->{$field} === (string) ($validated[$field] ?? null)) {
                    continue;
                }
                $table = $field === 'driver_id' ? 'drivers' : 'fleets';
                $belongsToCompany = DB::table($table)
                    ->where('id', $validated[$field])
                    ->where($field === 'driver_id' ? 'current_company_id' : 'company_id', $row->company_id)
                    ->where('is_active', true)
                    ->exists();
                abort_unless($belongsToCompany, 422, 'راننده یا ناوگان انتخاب‌شده متعلق به همین شرکت نیست.');
                $before[$field] = $row->{$field};
                $after[$field] = $validated[$field];
                $parentUpdate[$field] = $validated[$field];
            }

            if ($update === [] && $parentUpdate === []) {
                abort(422, 'هیچ تغییری برای ذخیره ارسال نشده است.');
            }

            $scopeChanged = array_key_exists('country_id', $update) || array_key_exists('permit_type', $update);
            if ($scopeChanged && !empty($row->d_serial_number)) {
                $warehouseUpdate = $this->warehouseItemQuery(
                    $row->d_serial_number,
                    $row->country_id,
                    $row->permit_type,
                )->update([
                    'country_id' => $update['country_id'] ?? $row->country_id,
                    'permit_type' => $update['permit_type'] ?? $row->permit_type,
                    'updated_at' => now(),
                ]);
                abort_unless($warehouseUpdate === 1, 422, 'رکورد انبار متناظر با این شماره و ترکیب مجوز پیدا نشد.');
            }

            if ($update !== []) {
                $update['updated_at'] = now();
                DB::table('permit_request_items')->where('id', $row->id)->update($update);
            }
            if ($parentUpdate !== []) {
                $parentUpdate['updated_at'] = now();
                DB::table('permit_requests')->where('id', $row->permit_request_id)->update($parentUpdate);
                DB::table('permit_request_items')->where('id', $row->id)->update(['updated_at' => now()]);
            }
            $audit->record($row, 'association_edit', $validated['reason'], $before, $after);
        });

        return back()->with('success', 'ویرایش انجمن همراه با سابقه قبل و بعد ذخیره شد.');
    }

    public function cits(Request $request, int $item, PermitItemAuditService $audit)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['processing', 'success', 'company_error', 'system_error', 'other_error'])],
            'message' => 'nullable|string|max:2000',
            'error_image' => 'nullable|image|max:5120',
        ]);

        DB::transaction(function () use ($request, $item, $audit, $validated) {
            $row = $this->lockItem($item);
            $isReturnCits = !empty($row->courier_received_at) && !empty($row->company_return_image);
            abort_unless($isReturnCits, 422, 'کنترل CITS فقط پس از دریافت لاشه انجام می‌شود.');
            abort_if($row->disposition_type === 'unused', 422, 'دوزوله برگشتی بدون استفاده نیازمند فرایند لاشه و CITS نیست.');
            $image = $request->hasFile('error_image')
                ? $request->file('error_image')->store('permits/cits-errors', 'public')
                : $row->cits_error_image;
            $newItemStatus = $validated['status'] === 'success'
                ? ($isReturnCits
                    ? match ($row->disposition_type) {
                        'unused' => 'returned_unused',
                        'renewal_with_lash' => 'collected_for_renewal',
                        default => 'archived',
                    }
                    : 'ready_to_issue')
                : 'cits_'.$validated['status'];
            $update = [
                'cits_status' => $validated['status'],
                'cits_message' => $validated['message'] ?? null,
                'cits_error_image' => $image,
                'cits_attempted_at' => now(),
                'item_status' => $newItemStatus,
                'updated_at' => now(),
            ];
            if ($isReturnCits && $validated['status'] === 'success') {
                $update['closed_at'] = $newItemStatus === 'collected_for_renewal' ? null : now();
                $this->warehouseItemQuery($row->d_serial_number, $row->country_id, $row->permit_type)->update([
                    'lifecycle_status' => $newItemStatus,
                    'returned_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('permit_request_items')->where('id', $row->id)->update($update);
            $audit->record($row, 'cits_result', $validated['message'] ?? 'نتیجه تلاش CITS ثبت شد.', [
                'cits_status' => $row->cits_status,
            ], ['cits_status' => $validated['status'], 'error_image' => $image, 'return_cits' => $isReturnCits], [], $row->item_status, $newItemStatus);
            $this->syncParent((int) $row->permit_request_id);
        });

        return back()->with('success', 'نتیجه CITS ثبت شد.');
    }

    public function disposition(Request $request, int $item, PermitItemAuditService $audit)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['return_archive', 'unused', 'lost'])],
            'reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($item, $audit, $validated) {
            $row = $this->lockItem($item);
            if (empty($row->d_serial_number)) {
                abort(422, 'فقط دوزوله صادرشده قابل تعیین تکلیف است.');
            }
            $newStatus = $validated['type'] === 'lost'
                ? 'lost'
                : (in_array($row->item_status, ['lost', 'lost_reported'], true) ? 'issued' : $row->item_status);
            $update = [
                'disposition_type' => $validated['type'],
                'disposition_note' => $validated['reason'],
                'disposition_changed_by_user_id' => auth()->id(),
                'disposition_changed_at' => now(),
                'item_status' => $newStatus,
                'updated_at' => now(),
            ];
            if ($validated['type'] === 'lost') {
                $update['closed_at'] = now();
                $this->warehouseItemQuery($row->d_serial_number, $row->country_id, $row->permit_type)->update([
                    'lifecycle_status' => 'lost', 'returned_at' => now(), 'updated_at' => now(),
                ]);
            } elseif (in_array($row->item_status, ['lost', 'lost_reported'], true)) {
                $update['closed_at'] = null;
                $this->warehouseItemQuery($row->d_serial_number, $row->country_id, $row->permit_type)->update([
                    'lifecycle_status' => 'issued', 'returned_at' => null, 'updated_at' => now(),
                ]);
            }
            DB::table('permit_request_items')->where('id', $row->id)->update($update);
            $audit->record($row, 'disposition_changed', $validated['reason'], [
                'disposition_type' => $row->disposition_type,
            ], ['disposition_type' => $validated['type']], [], $row->item_status, $newStatus);
            $this->syncParent((int) $row->permit_request_id);
        });

        return back()->with('success', 'نوع تعیین تکلیف دوزوله تغییر کرد و در تاریخچه ثبت شد.');
    }

    public function receiveLash(Request $request, int $item, PermitItemAuditService $audit, PanelFeatureService $features)
    {
        $validated = $request->validate(['courier_code' => 'nullable|string|max:20']);
        $showCourierCode = $features->enabledForRole('association', 'show_courier_code');
        $receivedAsUnused = DB::transaction(function () use ($item, $validated, $audit, $showCourierCode) {
            $row = $this->lockItem($item);
            if ($row->item_status !== 'company_returned' || empty($row->company_return_image)) {
                abort(422, 'شرکت هنوز تصویر برگشت و مشخصات پیک را ثبت نکرده است.');
            }
            $skipCodeVerification = $showCourierCode && filled($row->courier_delivery_code);
            $submittedCode = trim((string) ($validated['courier_code'] ?? ''));
            if (!$skipCodeVerification && $submittedCode === '') {
                abort(422, 'کد تحویل پیک الزامی است.');
            }
            if (!$skipCodeVerification && !hash_equals((string) $row->courier_delivery_code, $submittedCode)) {
                abort(422, 'کد تحویل پیک صحیح نیست.');
            }
            $isUnused = $row->disposition_type === 'unused';
            $newStatus = $isUnused ? 'returned_unused' : 'cits_processing';
            DB::table('permit_request_items')->where('id', $row->id)->update([
                'item_status' => $newStatus,
                'cits_status' => $isUnused ? 'not_required' : 'processing',
                'cits_message' => null,
                'courier_received_at' => now(),
                'courier_received_by_user_id' => auth()->id(),
                'collected_image' => $row->company_return_image,
                'courier_delivery_code' => null,
                'closed_at' => $isUnused ? now() : null,
                'updated_at' => now(),
            ]);
            $this->warehouseItemQuery($row->d_serial_number, $row->country_id, $row->permit_type)->update([
                'lifecycle_status' => $isUnused ? 'returned_unused' : 'collected_pending_cits',
                'returned_at' => now(),
                'updated_at' => now(),
            ]);
            $audit->record($row, $isUnused ? 'unused_return_received' : 'lash_received', $isUnused
                ? ($skipCodeVerification
                    ? 'کد پیک نمایش داده شد و دوزوله استفاده‌نشده دریافت شد؛ شماره حفظ و پرونده برای انتقال یا تمدید آماده شد.'
                    : 'کد پیک کنترل و دوزوله استفاده‌نشده دریافت شد؛ شماره حفظ و پرونده برای انتقال یا تمدید آماده شد.')
                : ($skipCodeVerification
                    ? 'کد پیک نمایش داده شد و لاشه دریافت شد؛ پرونده در انتظار ثبت نتیجه تحویل در CITS است.'
                    : 'کد پیک کنترل و لاشه دریافت شد؛ پرونده در انتظار ثبت نتیجه تحویل در CITS است.'), [], [
                'disposition_type' => $row->disposition_type,
                'item_status' => $newStatus,
                'cits_required' => !$isUnused,
                'courier_code_displayed' => $skipCodeVerification,
            ], [], $row->item_status, $newStatus);
            $this->syncParent((int) $row->permit_request_id);
            return $isUnused;
        });
        return back()->with('success', $receivedAsUnused
            ? 'دریافت دوزوله استفاده‌نشده تأیید شد. شماره حفظ شده و اکنون انتقال یا تمدید آن امکان‌پذیر است.'
            : 'دریافت فیزیکی لاشه تأیید شد. اکنون نتیجه تحویل در CITS را ثبت کنید.');
    }

    public function fleetHistory(int $item)
    {
        $current = $this->lockItem($item, false);
        return response()->json([
            'items' => $this->baseQuery()
                ->where(function ($query) use ($current) {
                    if (!empty($current->transit_horse)) {
                        $query->where('fleet.transit_horse', $current->transit_horse);
                    }
                    if (!empty($current->transit_trailer)) {
                        $method = !empty($current->transit_horse) ? 'orWhere' : 'where';
                        $query->{$method}('fleet.transit_trailer', $current->transit_trailer);
                    }
                    if (empty($current->transit_horse) && empty($current->transit_trailer)) {
                        $query->where('request.fleet_id', $current->fleet_id);
                    }
                })
                ->where('item.id', '<>', $item)
                ->orderByDesc('item.created_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function createOutboundCourier(Request $request, int $item, SmsService $sms, PermitItemAuditService $audit)
    {
        $validated = $request->validate([
            'courier_name' => 'required|string|max:255',
            'courier_mobile' => 'required|string|max:30',
            'courier_national_code' => 'required|string|max:30',
            'courier_vehicle_plate' => 'nullable|string|max:100',
            'identity_image' => 'nullable|image|max:5120',
        ]);

        $plainOtp = (string) random_int(10000, 99999);
        $missionId = DB::transaction(function () use ($request, $item, $audit, $validated, $plainOtp) {
            $row = $this->lockItem($item);
            if (empty($row->d_serial_number)) {
                abort(422, 'دوزوله هنوز چاپ و صادر نشده است.');
            }
            DB::table('permit_courier_missions')
                ->where('permit_request_item_id', $item)
                ->where('direction', 'association_to_company')
                ->where('status', 'pending')
                ->update(['status' => 'cancelled', 'updated_at' => now()]);
            $identityPath = $request->hasFile('identity_image')
                ? $request->file('identity_image')->store('permits/couriers', 'public')
                : null;
            $id = DB::table('permit_courier_missions')->insertGetId([
                'permit_request_item_id' => $item,
                'company_id' => $row->company_id,
                'direction' => 'association_to_company',
                'purpose' => 'issued_permit_pickup',
                'status' => 'pending',
                'courier_name' => $validated['courier_name'],
                'courier_mobile' => $validated['courier_mobile'],
                'courier_national_code' => $validated['courier_national_code'],
                'courier_vehicle_plate' => $validated['courier_vehicle_plate'] ?? null,
                'identity_image' => $identityPath,
                'otp_hash' => hash('sha256', $plainOtp),
                'otp_sent_at' => now(),
                'otp_expires_at' => now()->addDay(),
                'created_by_user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $audit->record($row, 'outbound_courier_assigned', 'پیک دریافت دوزوله صادرشده معرفی شد.', [], [
                'courier_name' => $validated['courier_name'],
                'courier_mobile' => $validated['courier_mobile'],
                'mission_id' => $id,
            ]);
            return $id;
        });

        $sms->send($validated['courier_mobile'], "کد تحویل دوزوله از انجمن: {$plainOtp}", 'permit_outbound_courier');
        return back()->with('success', "پیک معرفی و کد تحویل ارسال شد. شماره مأموریت: {$missionId}");
    }

    public function completeCourier(Request $request, int $mission, PermitItemAuditService $audit, PanelFeatureService $features)
    {
        $validated = $request->validate(['otp' => 'nullable|digits:5']);
        $showCourierCode = $features->enabledForRole('association', 'show_courier_code');
        DB::transaction(function () use ($mission, $validated, $audit, $showCourierCode) {
            $row = DB::table('permit_courier_missions')->where('id', $mission)->lockForUpdate()->first();
            abort_if(!$row || $row->status !== 'pending', 404);
            $skipCodeVerification = $showCourierCode && filled($row->delivery_code);
            $submittedOtp = (string) ($validated['otp'] ?? '');
            if (!$skipCodeVerification) {
                if ($submittedOtp === '') {
                    abort(422, 'کد تحویل پیک الزامی است.');
                }
                if ($row->otp_expires_at && now()->greaterThan($row->otp_expires_at)) {
                    abort(422, 'کد تحویل منقضی شده است.');
                }
                DB::table('permit_courier_missions')->where('id', $mission)->increment('otp_attempts');
                if (!hash_equals($row->otp_hash, hash('sha256', $submittedOtp))) {
                    abort(422, 'کد تحویل صحیح نیست.');
                }
            }
            DB::table('permit_courier_missions')->where('id', $mission)->update([
                'status' => 'completed',
                'delivery_code' => null,
                'completed_by_user_id' => auth()->id(),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
            $item = $this->lockItem((int) $row->permit_request_item_id);
            DB::table('permit_request_items')->where('id', $item->id)->update(['updated_at' => now()]);
            $audit->record($item, 'outbound_courier_completed', $skipCodeVerification
                ? 'کد پیک نمایش داده شد و تحویل دوزوله صادرشده ثبت شد.'
                : 'هویت پیک کنترل و دوزوله صادرشده تحویل شد.', [], [
                'mission_id' => $mission,
                'courier_name' => $row->courier_name,
                'courier_mobile' => $row->courier_mobile,
                'courier_code_displayed' => $skipCodeVerification,
            ]);
        });
        return back()->with('success', 'تحویل دوزوله به پیک با موفقیت ثبت شد.');
    }

    private function baseQuery()
    {
        return DB::table('permit_request_items as item')
            ->join('permit_requests as request', 'request.id', '=', 'item.permit_request_id')
            ->join('companies as company', 'company.id', '=', 'request.company_id')
            ->leftJoin('countries as country', 'country.id', '=', 'item.country_id')
            ->leftJoin('drivers as driver', 'driver.id', '=', 'request.driver_id')
            ->leftJoin('fleets as fleet', 'fleet.id', '=', 'request.fleet_id')
            ->leftJoin('permit_item_transfers as transfer', function ($join) {
                $join->on('transfer.permit_request_item_id', '=', 'item.id')
                    ->whereRaw('transfer.id = (SELECT MAX(latest_transfer.id) FROM permit_item_transfers latest_transfer WHERE latest_transfer.permit_request_item_id = item.id)');
            })
            ->leftJoin('companies as transfer_source_company', 'transfer_source_company.id', '=', 'transfer.source_company_id')
            ->leftJoin('companies as transfer_destination_company', 'transfer_destination_company.id', '=', 'transfer.destination_company_id')
            ->select([
                'item.*', 'request.d_code', 'request.company_id', 'request.driver_id', 'request.fleet_id',
                'request.request_type', 'request.previous_serial_number', 'request.previous_d_code',
                'request.status as parent_status', 'request.created_at as request_created_at',
                'company.name_fa as company_name', 'country.name as country_name', 'country.validity_days as country_validity_days',
                DB::raw('DATEDIFF(item.permit_valid_until, CURDATE()) as expiry_days_remaining'),
                'driver.first_name_fa', 'driver.last_name_fa', 'driver.national_code as driver_national_code',
                'fleet.transit_plate', 'fleet.transit_horse', 'fleet.transit_trailer', 'fleet.smart_card_number',
                'transfer.id as transfer_id', 'transfer.created_at as transferred_at', 'transfer.reason as transfer_reason',
                'transfer_source_company.name_fa as transfer_source_company_name',
                'transfer_destination_company.name_fa as transfer_destination_company_name',
                DB::raw("(SELECT COUNT(*) FROM permit_courier_missions pcm WHERE pcm.permit_request_item_id = item.id AND pcm.direction = 'association_to_company' AND pcm.status = 'pending') as pending_pickup_count"),
                DB::raw("(SELECT pcm.courier_name FROM permit_courier_missions pcm WHERE pcm.permit_request_item_id = item.id AND pcm.direction = 'association_to_company' AND pcm.status = 'pending' ORDER BY pcm.id DESC LIMIT 1) as pending_pickup_courier_name"),
                DB::raw("(SELECT pcm.courier_mobile FROM permit_courier_missions pcm WHERE pcm.permit_request_item_id = item.id AND pcm.direction = 'association_to_company' AND pcm.status = 'pending' ORDER BY pcm.id DESC LIMIT 1) as pending_pickup_courier_mobile"),
                DB::raw("(SELECT pcm.completed_at FROM permit_courier_missions pcm WHERE pcm.permit_request_item_id = item.id AND pcm.direction = 'association_to_company' AND pcm.status = 'completed' ORDER BY pcm.id DESC LIMIT 1) as pickup_completed_at"),
                DB::raw("(SELECT pcm.courier_name FROM permit_courier_missions pcm WHERE pcm.permit_request_item_id = item.id AND pcm.direction = 'association_to_company' AND pcm.status = 'completed' ORDER BY pcm.id DESC LIMIT 1) as pickup_completed_courier_name"),
                DB::raw("(SELECT pcm.courier_mobile FROM permit_courier_missions pcm WHERE pcm.permit_request_item_id = item.id AND pcm.direction = 'association_to_company' AND pcm.status = 'completed' ORDER BY pcm.id DESC LIMIT 1) as pickup_completed_courier_mobile"),
            ]);
    }

    /**
     * صفحهٔ کارتابل را در سطح پروندهٔ مادر paginate می‌کند و ردیف‌های همان
     * پرونده را در همان محدودهٔ وضعیت برای بازشدن زیر ردیف اصلی برمی‌گرداند.
     */
    private function groupedParentPage($query, int $perPage)
    {
        $parentPage = (clone $query)
            ->select('request.id as parent_request_id')
            ->selectRaw('MAX(item.updated_at) as parent_updated_at')
            ->groupBy('request.id')
            ->orderByDesc('parent_updated_at')
            ->paginate($perPage)
            ->withQueryString();

        $parentIds = collect($parentPage->items())
            ->pluck('parent_request_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        // The parent page is scoped (active/rejected/circulation/...), so the
        // children must use the same scoped query. Using baseQuery() here
        // would re-introduce every route of a parent and mix rejected rows
        // with approved/issued rows in the wrong tab.
        $children = $parentIds->isEmpty()
            ? collect()
            : (clone $query)
                ->whereIn('request.id', $parentIds->all())
                ->orderBy('item.id')
                ->get();

        $childrenByParent = $children->groupBy('permit_request_id');
        $groups = $parentIds->map(function (int $parentId) use ($childrenByParent) {
            $parentChildren = $childrenByParent->get($parentId, collect())->values();

            if ($parentChildren->isEmpty()) {
                return null;
            }

            return (object) [
                'parent_id' => $parentId,
                'parent' => $parentChildren->first(),
                'children' => $parentChildren,
            ];
        })->filter()->values();

        $parentPage->setCollection($groups);

        return $parentPage;
    }

    private function lockItem(int $id, bool $lock = true): object
    {
        $query = DB::table('permit_request_items as item')
            ->join('permit_requests as request', 'request.id', '=', 'item.permit_request_id')
            ->where('item.id', $id)
            ->select('item.*', 'request.company_id', 'request.driver_id', 'request.fleet_id', 'request.d_code', 'request.request_type');
        return ($lock ? $query->lockForUpdate() : $query)->firstOrFail();
    }

    private function warehouseItemQuery($serialNumber, $countryId, $permitType)
    {
        return DB::table('dozbalagh_items')
            ->where('serial_number', $serialNumber)
            ->where('country_id', $countryId)
            ->where('permit_type', $permitType);
    }

    private function syncParent(int $requestId): void
    {
        $statuses = DB::table('permit_request_items')->where('permit_request_id', $requestId)->pluck('item_status');
        $financialStatuses = DB::table('permit_request_items')->where('permit_request_id', $requestId)->pluck('financial_status');
        $hasPending = $statuses->contains('pending') || $statuses->contains('under_review');
        $hasApprovedOrReady = $statuses->contains('approved') || $statuses->contains('ready_to_issue');
        $status = match (true) {
            $statuses->every(fn ($value) => $value === 'rejected') => 'rejected',
            $statuses->contains('issued') && $statuses->contains('rejected') => 'partially_issued',
            $statuses->contains('issued') => 'issued',
            // یک یا چند ردیف تأیید/آماده صدور شده‌اند؛ اگر هم‌زمان ردیف دیگری هنوز در انتظار بررسی است
            // مادر باید «در حال بررسی» بماند نه «پندینگ»، تا صدور سریال برای ردیف آماده مسدود نشود.
            $hasApprovedOrReady && $hasPending => 'processing',
            $hasApprovedOrReady => 'approved',
            $hasPending => 'pending',
            $statuses->every(fn ($value) => in_array($value, self::TERMINAL, true)) => 'archived',
            default => 'processing',
        };
        $paymentStatus = match (true) {
            $financialStatuses->every(fn ($value) => $value === 'released') => 'refunded',
            $financialStatuses->every(fn ($value) => in_array($value, ['settled', 'waived'], true)) => 'settled',
            $financialStatuses->contains(fn ($value) => in_array($value, ['settled', 'waived'], true))
                && $financialStatuses->contains('released') => 'partially_settled',
            $financialStatuses->contains('reserved') => 'reserved',
            default => 'processing',
        };
        DB::table('permit_requests')->where('id', $requestId)->update([
            'status' => $status,
            'payment_status' => $paymentStatus,
            'updated_at' => now(),
        ]);
    }
}
