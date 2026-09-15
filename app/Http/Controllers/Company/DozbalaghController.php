<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Wallet;
use App\Models\PermitRequest;
use App\Models\CompanyQuota;
use App\Models\CargoDocumentRule; 
use App\Models\WorldCountry; 
use App\Services\CargoDocumentRuleService;
use App\Services\PermitRequestWindowService;
use App\Services\PermitItemAuditService;
use App\Services\PermitItemFinancialService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DozbalaghController extends Controller
{
    private function generateDCode()
    {
        $datePrefix = \Hekmatinasser\Verta\Verta::now()->format('Ymd');
        $lastSequence = PermitRequest::whereNotNull('d_code')
            ->pluck('d_code')
            ->map(function ($code) {
                $code = (string) $code;

                if (preg_match('/^KHD\d{8}(\d+)$/', $code, $matches)) {
                    return (int) $matches[1];
                }

                if (preg_match('/^D\d{8}(\d+)$/', $code, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max();

        $nextSequence = ((int) $lastSequence) + 1;

        return 'KHD' . $datePrefix . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    }

    private function activePermitStatuses(): array
    {
        return ['draft', 'pending', 'under_review', 'returned', 'approved', 'issued'];
    }

    /**
     * تعرفهٔ کشور را بر اساس عضویت شرکت برمی‌گرداند.
     * ستون price برای سازگاری با داده‌ها و نسخه‌های قدیمی به‌عنوان fallback نگه داشته شده است.
     */
    private function countryPriceForCompany(Country $country, bool $isAssociationMember): float
    {
        $membershipPrice = $isAssociationMember ? $country->member_price : $country->non_member_price;

        return (float) ($membershipPrice ?? $country->price ?? 0);
    }

    private function destinationPermitTypeError(array $destinations): ?string
    {
        $permitTypeLabels = config('permit_types.labels', []);

        foreach ($destinations as $destination) {
            $country = Country::find($destination['country_id'] ?? null);
            $permitType = trim((string) ($destination['permit_type'] ?? ''));
            if (!$country || !in_array($permitType, $country->allowed_permit_types ?? [], true)) {
                $countryName = $country?->name ?? 'انتخاب‌شده';
                $permitLabel = $permitTypeLabels[$permitType] ?? ($permitType ?: 'نامشخص');

                return "نوع مجوز «{$permitLabel}» برای کشور «{$countryName}» در مدیریت کشورها تعریف نشده است.";
            }
        }

        return null;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        if (!$companyId) {
            return redirect()->route('dashboard')->with('error', 'حساب کاربری شما به شرکتی متصل نیست.');
        }

        $query = $this->baseIndexQuery($companyId);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function($q) use ($search) {
                $q->where('permit.d_code', 'like', "%{$search}%")
                    ->orWhere('permit.previous_d_code', 'like', "%{$search}%")
                    ->orWhere('item.d_serial_number', 'like', "%{$search}%")
                    ->orWhere('driver.first_name_fa', 'like', "%{$search}%")
                    ->orWhere('driver.last_name_fa', 'like', "%{$search}%")
                    ->orWhere('driver.national_code', 'like', "%{$search}%")
                    ->orWhere('fleet.transit_plate', 'like', "%{$search}%")
                    ->orWhere('fleet.smart_card_number', 'like', "%{$search}%")
                    ->orWhere('country.name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $statusFilter = $request->status;
            if (in_array($statusFilter, ['renewal', 'renewed'], true)) {
                $query->where('permit.request_type', 'renewal');
            } elseif ($statusFilter === 'new') {
                $query->where(function ($q) {
                    $q->whereNull('permit.request_type')
                      ->orWhere('permit.request_type', 'new')
                      ->orWhere('permit.request_type', '');
                });
            } elseif ($statusFilter === 'active') {
                $this->applyActiveListScope($query);
            } elseif ($statusFilter === 'collected') {
                $query->whereIn('item.item_status', ['company_returned', 'collected_for_renewal']);
            } elseif ($statusFilter === 'archived') {
                $query->whereIn('item.item_status', ['archived', 'returned_unused', 'extended', 'cancelled']);
            } elseif ($statusFilter === 'pending') {
                $query->whereIn('item.item_status', ['pending', 'under_review', 'approved', 'cits_processing', 'cits_company_error', 'cits_system_error', 'cits_other_error', 'ready_to_issue']);
            } elseif ($statusFilter === 'lost') {
                $query->whereIn('item.item_status', ['lost_reported', 'lost']);
            } else {
                $query->where('item.item_status', $statusFilter);
            }
        } else {
            $this->applyActiveListScope($query);
        }

        $items = $this->groupedParentPage($query, $companyId, 15);

        $requestWindowStatus = app(PermitRequestWindowService::class)->status();
        $latestCompanyItemUpdate = DB::table('permit_request_items as item')
            ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
            ->where('permit.company_id', $companyId)
            ->max('item.updated_at');

        $companyPermitStats = DB::table('permit_request_items as item')
            ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
            ->where('permit.company_id', $companyId)
            ->selectRaw('COUNT(DISTINCT permit.id) as total')
            ->selectRaw("COUNT(DISTINCT CASE WHEN item.item_status IN ('pending', 'under_review', 'approved', 'cits_processing', 'cits_company_error', 'cits_system_error', 'cits_other_error', 'ready_to_issue') THEN permit.id END) as in_progress")
            ->selectRaw("COUNT(DISTINCT CASE WHEN item.item_status = 'issued' THEN permit.id END) as issued")
            ->selectRaw("COUNT(DISTINCT CASE WHEN permit.request_type = 'renewal' THEN permit.id END) as renewals")
            ->first();

        return view('company.dozbalagh.items-index', compact(
            'items',
            'requestWindowStatus',
            'latestCompanyItemUpdate',
            'companyPermitStats',
        ));
    }

    private function baseIndexQuery(int $companyId)
    {
        return DB::table('permit_request_items as item')
            ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
            ->leftJoin('drivers as driver', 'driver.id', '=', 'permit.driver_id')
            ->leftJoin('fleets as fleet', 'fleet.id', '=', 'permit.fleet_id')
            ->leftJoin('countries as country', 'country.id', '=', 'item.country_id')
            ->where('permit.company_id', $companyId)
            ->select([
                'item.*',
                'permit.d_code',
                'permit.request_type',
                'permit.previous_d_code',
                'permit.status as parent_status',
                'permit.payment_status as parent_payment_status',
                'permit.created_at as request_created_at',
                'driver.first_name_fa',
                'driver.last_name_fa',
                'driver.national_code as driver_national_code',
                'fleet.transit_plate',
                'fleet.transit_horse',
                'fleet.transit_trailer',
                'fleet.smart_card_number',
                'country.name as country_name',
                DB::raw("(SELECT pcm.completed_at FROM permit_courier_missions pcm WHERE pcm.permit_request_item_id = item.id AND pcm.direction = 'association_to_company' AND pcm.status = 'completed' ORDER BY pcm.id DESC LIMIT 1) as pickup_completed_at"),
                DB::raw("(SELECT pcm.courier_name FROM permit_courier_missions pcm WHERE pcm.permit_request_item_id = item.id AND pcm.direction = 'association_to_company' AND pcm.status = 'completed' ORDER BY pcm.id DESC LIMIT 1) as pickup_completed_courier_name"),
                DB::raw("(SELECT pcm.courier_mobile FROM permit_courier_missions pcm WHERE pcm.permit_request_item_id = item.id AND pcm.direction = 'association_to_company' AND pcm.status = 'completed' ORDER BY pcm.id DESC LIMIT 1) as pickup_completed_courier_mobile"),
             ]);
    }

    /**
     * مسیرهای نیازمند اقدام شرکت را از وضعیت‌های صادرشده، لاشه/تمدید و مفقودی
     * جدا می‌کند. دوزولهٔ issued فقط تا قبل از تحویل موفق پیک در اقدام می‌ماند.
     */
    private function applyActiveListScope($query)
    {
        return $query->where(function ($active) {
            $active->whereNotIn('item.item_status', [
                'rejected', 'archived', 'returned_unused', 'extended', 'lost', 'cancelled', 'transferred_in',
                'issued', 'company_returned', 'collected_for_renewal', 'lost_reported',
            ])->orWhere(function ($issued) {
                $issued->where('item.item_status', 'issued')
                    ->whereNotExists(function ($mission) {
                        $mission->selectRaw('1')
                            ->from('permit_courier_missions as completed_pickup')
                            ->whereColumn('completed_pickup.permit_request_item_id', 'item.id')
                            ->where('completed_pickup.direction', 'association_to_company')
                            ->where('completed_pickup.status', 'completed');
                    });
            });
        });
    }

    private function groupedParentPage($query, int $companyId, int $perPage)
    {
        $parentPage = (clone $query)
            ->select('permit.id as parent_request_id')
            ->selectRaw('MAX(item.updated_at) as parent_updated_at')
            ->selectRaw("MIN(FIELD(item.item_status, 'pending', 'under_review', 'approved', 'cits_processing', 'cits_company_error', 'cits_system_error', 'cits_other_error', 'ready_to_issue', 'issued', 'company_returned', 'collected_for_renewal', 'rejected', 'archived', 'returned_unused', 'extended', 'lost_reported', 'lost', 'cancelled')) as parent_status_order")
            ->groupBy('permit.id')
            ->orderBy('parent_status_order')
            ->orderByDesc('parent_updated_at')
            ->paginate($perPage)
            ->withQueryString();

        $parentIds = collect($parentPage->items())
            ->pluck('parent_request_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        // Reuse the scoped query so a rejected/issued path cannot leak into
        // another company tab when the parent has multiple routes.
        $children = $parentIds->isEmpty()
            ? collect()
            : (clone $query)
                ->whereIn('permit.id', $parentIds->all())
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

    public function transfers(Request $request)
    {
        $user = $request->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;
        abort_unless($companyId, 403);

        $query = DB::table('permit_item_transfers as transfer')
            ->join('permit_request_items as item', 'item.id', '=', 'transfer.permit_request_item_id')
            ->leftJoin('permit_requests as destination_request', 'destination_request.id', '=', 'transfer.destination_permit_request_id')
            ->leftJoin('companies as source_company', 'source_company.id', '=', 'transfer.source_company_id')
            ->leftJoin('companies as destination_company', 'destination_company.id', '=', 'transfer.destination_company_id')
            ->leftJoin('drivers as source_driver', 'source_driver.id', '=', 'transfer.source_driver_id')
            ->leftJoin('drivers as destination_driver', 'destination_driver.id', '=', 'transfer.destination_driver_id')
            ->leftJoin('fleets as source_fleet', 'source_fleet.id', '=', 'transfer.source_fleet_id')
            ->leftJoin('fleets as destination_fleet', 'destination_fleet.id', '=', 'transfer.destination_fleet_id')
            ->where('transfer.destination_company_id', $companyId)
            ->select([
                'transfer.*', 'item.item_status', 'destination_request.d_code as destination_d_code',
                'source_company.name_fa as source_company_name', 'destination_company.name_fa as destination_company_name',
                'source_driver.first_name_fa as source_driver_first_name', 'source_driver.last_name_fa as source_driver_last_name',
                'destination_driver.first_name_fa as destination_driver_first_name', 'destination_driver.last_name_fa as destination_driver_last_name',
                'source_fleet.transit_plate as source_fleet_plate', 'destination_fleet.transit_plate as destination_fleet_plate',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($scope) use ($search) {
                $scope->where('transfer.serial_number', 'like', "%{$search}%")
                    ->orWhere('destination_request.d_code', 'like', "%{$search}%")
                    ->orWhere('destination_company.name_fa', 'like', "%{$search}%");
            });
        }

        $transfers = $query->orderByDesc('transfer.id')->paginate(20)->withQueryString();

        return view('company.dozbalagh.transfers', compact('transfers', 'companyId'));
    }

    public function showItem(int $item)
    {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;
        $permit = DB::table('permit_request_items as item')
            ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
            ->leftJoin('drivers as driver', 'driver.id', '=', 'permit.driver_id')
            ->leftJoin('fleets as fleet', 'fleet.id', '=', 'permit.fleet_id')
            ->leftJoin('countries as country', 'country.id', '=', 'item.country_id')
            ->where('item.id', $item)
            ->where('permit.company_id', $companyId)
            ->select([
                'item.*', 'permit.d_code', 'permit.request_type', 'permit.previous_d_code',
                'permit.status as parent_status', 'permit.payment_status as parent_payment_status',
                'permit.created_at as request_created_at',
                'driver.first_name_fa', 'driver.last_name_fa', 'driver.national_code as driver_national_code',
                'fleet.transit_plate', 'fleet.smart_card_number', 'country.name as country_name',
                'fleet.transit_horse', 'fleet.transit_trailer',
                DB::raw("(SELECT pcm.courier_name FROM permit_courier_missions pcm WHERE pcm.permit_request_item_id = item.id AND pcm.direction = 'association_to_company' AND pcm.status = 'completed' ORDER BY pcm.id DESC LIMIT 1) as pickup_completed_courier_name"),
                DB::raw("(SELECT pcm.courier_mobile FROM permit_courier_missions pcm WHERE pcm.permit_request_item_id = item.id AND pcm.direction = 'association_to_company' AND pcm.status = 'completed' ORDER BY pcm.id DESC LIMIT 1) as pickup_completed_courier_mobile"),
            ])
            ->firstOrFail();
        $events = DB::table('permit_item_events')
            ->where('permit_request_item_id', $item)
            ->where('permit_request_id', $permit->permit_request_id)
            ->where('created_at', '>=', $permit->created_at)
            ->orderByDesc('id')
            ->get();
        $missions = DB::table('permit_courier_missions')
            ->where('permit_request_item_id', $item)
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $permit->created_at)
            ->orderByDesc('id')
            ->get();

        return view('company.dozbalagh.show-item', compact('permit', 'events', 'missions'));
    }

    public function submitCitsResponse(
        Request $request,
        int $id,
        PermitItemAuditService $audit,
    ) {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'response_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        DB::transaction(function () use ($request, $id, $validated, $companyId, $audit) {
            $item = DB::table('permit_request_items as item')
                ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
                ->where('item.id', $id)
                ->where('permit.company_id', $companyId)
                ->whereIn('item.cits_status', ['company_error', 'system_error', 'other_error'])
                ->select('item.*')
                ->lockForUpdate()
                ->first();
            abort_if(!$item, 404);

            $file = $request->hasFile('response_file')
                ? $request->file('response_file')->store('permits/cits-company-responses', 'public')
                : null;

            DB::table('permit_request_items')->where('id', $item->id)->update([
                'company_cits_response' => $validated['message'],
                'company_cits_response_file' => $file,
                'company_cits_responded_by_user_id' => auth()->id(),
                'company_cits_responded_at' => now(),
                'cits_status' => 'recheck_requested',
                'item_status' => 'cits_processing',
                'updated_at' => now(),
            ]);

            $audit->record(
                $item,
                'company_cits_response',
                $validated['message'],
                ['cits_status' => $item->cits_status],
                ['cits_status' => 'recheck_requested', 'response_file' => $file],
                [],
                $item->item_status,
                'cits_processing',
            );
        });

        return back()->with('success', 'پاسخ شما برای انجمن ارسال شد و پرونده در انتظار بررسی مجدد CITS قرار گرفت.');
    }

    public function create()
    {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;
        $allWorldCountries = \App\Models\WorldCountry::orderBy('name_fa', 'asc')->get();

        if (empty($companyId) || $companyId == 0) {
            return view('company.dozbalagh.create', [
                'drivers' => collect(), 
                'fleets' => collect(), 
                'countries' => \App\Models\Country::where('is_active', 1)->get(), 
                'cargoRules' => collect(),
                'balance' => 0,
                'wallet' => null,
                'newDCode' => '',
                'allWorldCountries' => $allWorldCountries,
                'isAssociationMember' => false,
            ])->with('error', 'حساب کاربری شما به هیچ شرکتی متصل نیست.');
        }

        $drivers = \App\Models\Driver::active()->where('current_company_id', $companyId)->get();
        foreach ($drivers as $driver) {
            $activeDriverPermit = \App\Models\PermitRequest::where('driver_id', $driver->id)
                ->where('company_id', $companyId)
                ->whereIn('status', $this->activePermitStatuses())
                ->orderBy('id', 'desc')
                ->first();
            $driver->active_dozbalaghs = $activeDriverPermit ? 1 : 0;
            $driver->last_active_number = $activeDriverPermit ? $activeDriverPermit->d_code : '';
        }

        $fleets = \App\Models\Fleet::active()->where('company_id', $companyId)->get();
        foreach ($fleets as $fleet) {
            $activeFleetPermit = \App\Models\PermitRequest::where('fleet_id', $fleet->id)
                ->where('company_id', $companyId)
                ->whereIn('status', $this->activePermitStatuses())
                ->orderBy('id', 'desc')
                ->first();
            $fleet->active_dozbalaghs = $activeFleetPermit ? 1 : 0;
            $fleet->last_active_number = $activeFleetPermit ? $activeFleetPermit->d_code : '';
        }

        $countries = \App\Models\Country::where('is_active', 1)->get(); 
        $cargoRules = app(CargoDocumentRuleService::class)->frontendRules();
        
        $wallet = \App\Models\Wallet::firstOrCreate(
            ['company_id' => $companyId],
            ['balance' => 50000000, 'blocked_balance' => 0] 
        );

        $isAssociationMember = (bool) DB::table('companies')
            ->where('id', $companyId)
            ->value('is_association_member');
        
        $newDCode = $this->generateDCode(); 
        $balance = $wallet->balance;
        $requestWindowStatus = app(PermitRequestWindowService::class)->status();

        return view('company.dozbalagh.create', compact('drivers', 'fleets', 'countries', 'cargoRules', 'wallet', 'newDCode', 'balance', 'allWorldCountries', 'requestWindowStatus', 'isAssociationMember'));
    }

    public function renewableList(Request $request)
    {
        try {
            $user = auth()->user();
            $companyId = optional($user->company)->id ?? $user->company_id ?? null;

            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'items' => [],
                    'message' => 'شرکت یافت نشد'
                ], 200);
            }

            $dCode = $this->normalizeRenewalLookupCode((string) $request->input('d_code', ''));
            $fleetId = $request->input('fleet_id');
            $driverId = $request->input('driver_id');

            /*
            |--------------------------------------------------------------------------
            | حالت اصلی تمدید با کد رهگیری / شماره دوزوله
            |--------------------------------------------------------------------------
            | در این حالت اول پرونده را پیدا می‌کنیم، سپس مرحله به مرحله دلیل
            | مجاز یا غیرمجاز بودن تمدید را مشخص می‌کنیم.
            */
            if (!empty($dCode)) {
                $permit = $this->findRenewalSource($dCode, (int) $companyId);

                if (!$permit) {
                    $candidate = DB::table('permit_request_items as pri')
                        ->join('permit_requests as pr', 'pri.permit_request_id', '=', 'pr.id')
                        ->leftJoin('countries as country', 'country.id', '=', 'pri.country_id')
                        ->where('pr.company_id', $companyId)
                        ->where(function ($query) use ($dCode) {
                            $query->where('pr.d_code', $dCode)
                                ->orWhere('pr.serial_number', $dCode)
                                ->orWhere('pri.d_serial_number', $dCode);
                        })
                        ->select([
                            'pri.item_status', 'pri.disposition_type', 'pri.d_serial_number',
                            'pri.permit_valid_until', 'pr.permit_valid_until as parent_valid_until',
                            'pr.status', 'pr.payment_status', 'country.validity_days',
                        ])
                        ->orderByDesc('pri.id')
                        ->first();

                    $message = match (true) {
                        !$candidate => 'پرونده‌ای با این کد رهگیری یا سریال در همین شرکت پیدا نشد.',
                        $candidate->disposition_type === 'return_archive' =>
                            'این دوزوله با گزینه «ثبت لاشه و پایان فرایند» بایگانی شده است و به‌عنوان «تمدید با لاشه» ثبت نشده است.',
                        $candidate->disposition_type === 'unused' =>
                            'این دوزوله با وضعیت «برگشت بدون استفاده» پایان یافته و درخواست تمدید با لاشه برای آن ثبت نشده است.',
                        !in_array($candidate->payment_status, ['settled', 'partially_settled'], true) =>
                            'وضعیت مالی این دوزوله هنوز قطعی و تسویه‌شده نیست.',
                        !in_array($candidate->item_status, ['issued', 'archived', 'returned_unused', 'collected_for_renewal'], true) =>
                            'فرایند این دوزوله هنوز نهایی نشده است و فعلاً امکان ثبت تمدید وجود ندارد.',
                        !empty($candidate->permit_valid_until ?? $candidate->parent_valid_until)
                            && \Carbon\Carbon::parse($candidate->permit_valid_until ?? $candidate->parent_valid_until)->isFuture() =>
                            'اعتبار این دوزوله هنوز به پایان نرسیده است؛ تاریخ پایان اعتبار: '
                                .\Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($candidate->permit_valid_until ?? $candidate->parent_valid_until))->format('Y/m/d'),
                        default => 'این پرونده شرایط لازم برای ثبت تمدید را ندارد.',
                    };

                    return response()->json([
                        'success' => true,
                        'items' => [],
                        'message' => $message,
                    ], 200);
                }

                $sourcePermits = collect([$permit]);
                [$parentCode, $selectedItemId] = $this->parseRenewalLookupCode($dCode);
                if (!$selectedItemId && strcasecmp((string) $permit->d_code, $parentCode) === 0) {
                    $sourcePermits = DB::table('permit_request_items')
                        ->where('permit_request_id', $permit->id)
                        ->orderBy('id')
                        ->pluck('id')
                        ->map(fn ($sourceItemId) => $this->findRenewalSource($permit->d_code.'-'.$sourceItemId, (int) $companyId))
                        ->filter()
                        ->values();
                }

                $sourcePermits = $sourcePermits->reject(function ($source) use ($companyId) {
                    return DB::table('permit_request_items as pri')
                        ->join('permit_requests as pr', 'pri.permit_request_id', '=', 'pr.id')
                        ->where('pr.company_id', $companyId)
                        ->where('pr.request_type', 'renewal')
                        ->where('pri.renewed_from_item_id', $source->previous_item_id)
                        ->whereIn('pr.status', ['pending', 'approved', 'returned', 'under_review'])
                        ->exists();
                })->values();

                if ($sourcePermits->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'items' => [],
                        'message' => 'برای ردیف‌های قابل تمدید این پرونده قبلاً درخواست ثبت شده یا هیچ ردیف آماده تمدیدی باقی نمانده است.'
                    ], 200);
                }

                $items = $sourcePermits->map(fn ($source) => $this->renewalSourcePayload($source))->values();

                return response()->json([
                    'success' => true,
                    'items' => $items,
                    'message' => $items->count() > 1
                        ? $items->count().' ردیف قابل تمدید پیدا شد؛ ردیف موردنظر را انتخاب کنید.'
                        : 'پرونده قابل تمدید است.'
                ], 200);
            }

            /*
            |--------------------------------------------------------------------------
            | حالت قدیمی بر اساس راننده / ناوگان
            |--------------------------------------------------------------------------
            | برای سازگاری با بخش‌های قبلی نگه داشته شده، اما فقط صادرشده‌های منقضی
            | و تسویه‌شده را برمی‌گرداند.
            */
            $query = DB::table('permit_requests as pr')
                ->leftJoin('drivers as d', 'pr.driver_id', '=', 'd.id')
                ->leftJoin('fleets as f', 'pr.fleet_id', '=', 'f.id')
                ->leftJoin('permit_request_items as renewal_item', function ($join) {
                    $join->on('renewal_item.permit_request_id', '=', 'pr.id')
                        ->whereRaw('renewal_item.id = (select min(pri2.id) from permit_request_items pri2 where pri2.permit_request_id = pr.id)');
                })
                ->leftJoin('countries as renewal_country', 'renewal_item.country_id', '=', 'renewal_country.id')
                ->where('pr.company_id', $companyId)
                ->where('pr.status', 'issued')
                ->where('pr.payment_status', 'settled')
                ->whereNotNull('pr.serial_number')
                ->where('pr.serial_number', '<>', '')
                ->whereNotNull('pr.issued_at')
                ->whereNotNull('pr.permit_valid_until')
                ->whereDate('pr.permit_valid_until', '<=', now()->toDateString());

            if (!empty($fleetId)) {
                $query->where('pr.fleet_id', $fleetId);
            }

            if (!empty($driverId)) {
                $query->where('pr.driver_id', $driverId);
            }

            $rows = $query->select([
                'pr.id as permit_request_id',
                'pr.d_code',
                'pr.serial_number',
                'pr.status',
                'pr.payment_status',
                'pr.issued_at',
                'pr.permit_valid_until',
                'pr.driver_id',
                'pr.fleet_id',
                'd.first_name_fa',
                'd.last_name_fa',
                'f.transit_plate',
            ])->orderByDesc('pr.id')->get();

            $items = $rows->map(function ($row) {
                $destinations = DB::table('permit_request_items as pri')
                    ->leftJoin('countries as c', 'pri.country_id', '=', 'c.id')
                    ->where('pri.permit_request_id', $row->permit_request_id)
                    ->select(
                        'pri.country_id',
                        'c.name as country_name',
                        'c.validity_days as country_validity_days',
                        'pri.permit_type',
                        'pri.operation_type',
                        'pri.loading_origin',
                        'pri.loading_destination',
                        'pri.cits_code',
                        'pri.trip_code',
                        'pri.cmr_date',
                        'pri.receipt_code',
                        'pri.receipt_amount',
                        'pri.tir_carnet_number',
                        'pri.tir_carnet_date',
                        'pri.d_serial_number'
                    )
                    ->get();

                $firstDetail = $destinations->first();

                return [
                    'id' => $row->permit_request_id,
                    'd_code' => $row->d_code,
                    'serial_number' => $row->serial_number,
                    'status' => $row->status,
                    'payment_status' => $row->payment_status,
                    'issued_at' => $row->issued_at,
                    'permit_valid_until' => $row->permit_valid_until,
                    'validity_days' => (int) ($firstDetail->country_validity_days ?? 30),
                    'driver_id' => $row->driver_id,
                    'fleet_id' => $row->fleet_id,
                    'cargo_type' => $firstDetail->operation_type ?? '',
                    'loading_origin' => $firstDetail->loading_origin ?? '',
                    'loading_destination' => $firstDetail->loading_destination ?? '',
                    'cits_code' => $firstDetail->cits_code ?? '',
                    'trip_code' => $firstDetail->trip_code ?? '',
                    'cmr_date' => $firstDetail->cmr_date ?? '',
                    'receipt_code' => $firstDetail->receipt_code ?? '',
                    'receipt_amount' => $firstDetail->receipt_amount ?? '',
                    'tir_carnet_number' => $firstDetail->tir_carnet_number ?? '',
                    'tir_carnet_date' => $firstDetail->tir_carnet_date ?? '',
                    'driver_name' => trim(($row->first_name_fa ?? '') . ' ' . ($row->last_name_fa ?? '')),
                    'fleet_plate' => $row->transit_plate ?: 'ناوگان نامشخص',
                    'countries_text' => $destinations->pluck('country_name')->implode('، '),
                    'destinations' => $destinations,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'items' => $items,
                'message' => $items->count() ? 'پرونده یافت شد' : 'پرونده قابل تمدیدی یافت نشد.'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'items' => []], 200);
        }
    }

    
    /**
     * پرونده مرجع تمدید فقط وقتی معتبر است که صادر نهایی و تسویه شده باشد.
     */
    private function findRenewalSource(string $code, int $companyId)
    {
        [$code, $selectedItemId] = $this->parseRenewalLookupCode($code);

        return DB::table('permit_request_items as pri')
            ->join('permit_requests as pr', 'pri.permit_request_id', '=', 'pr.id')
            ->leftJoin('drivers as d', 'pr.driver_id', '=', 'd.id')
            ->leftJoin('fleets as f', 'pr.fleet_id', '=', 'f.id')
            ->leftJoin('countries as renewal_country', 'pri.country_id', '=', 'renewal_country.id')
            ->where('pr.company_id', $companyId)
            ->whereIn('pr.status', ['issued', 'partially_issued', 'archived', 'processing'])
            ->whereIn('pr.payment_status', ['settled', 'partially_settled'])
            ->whereNotNull('pri.d_serial_number')
            ->where('pri.d_serial_number', '<>', '')
            ->whereNotNull('pri.issued_at')
            ->whereIn('pri.item_status', ['issued', 'archived', 'returned_unused', 'collected_for_renewal'])
            ->where(function ($renewable) {
                $renewable->whereRaw('COALESCE(pri.permit_valid_until, pr.permit_valid_until) IS NULL')
                    ->orWhereDate(DB::raw('COALESCE(pri.permit_valid_until, pr.permit_valid_until)'), '<=', now()->toDateString());
            })
            ->where(function ($q) use ($code) {
                $q->where('pr.d_code', $code)
                  ->orWhere('pr.serial_number', $code)
                  ->orWhere('pri.d_serial_number', $code);
            })
            ->when($selectedItemId, fn ($query) => $query->where('pri.id', $selectedItemId))
            ->select([
                'pr.*',
                'pri.id as previous_item_id',
                'pri.country_id as previous_item_country_id',
                'pri.permit_type as previous_item_permit_type',
                'pri.d_serial_number as previous_item_serial_number',
                'pri.issued_at as previous_item_issued_at',
                'pri.permit_valid_until as previous_item_valid_until',
                'renewal_country.validity_days as country_validity_days',
                'd.first_name_fa',
                'd.last_name_fa',
                'f.transit_plate',
            ])
            ->orderByDesc('pr.id')
            ->orderBy('pri.id')
            ->first();
    }

    private function normalizeRenewalLookupCode(string $code): string
    {
        $code = trim(strtr($code, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]));

        return strtoupper($code);
    }

    private function parseRenewalLookupCode(string $code): array
    {
        $code = $this->normalizeRenewalLookupCode($code);

        return preg_match('/^(KHD\d+)-(\d+)$/i', $code, $matches)
            ? [strtoupper($matches[1]), (int) $matches[2]]
            : [$code, null];
    }

    private function renewalSourcePayload(object $permit): array
    {
        $destinations = DB::table('permit_request_items as pri')
            ->leftJoin('countries as c', 'pri.country_id', '=', 'c.id')
            ->where('pri.id', $permit->previous_item_id)
            ->select(
                'pri.id', 'pri.country_id', 'c.name as country_name',
                'c.validity_days as country_validity_days', 'pri.permit_type',
                'pri.operation_type', 'pri.loading_origin', 'pri.loading_destination',
                'pri.cits_code', 'pri.trip_code', 'pri.cmr_date', 'pri.receipt_code',
                'pri.receipt_amount', 'pri.tir_carnet_number', 'pri.tir_carnet_date',
                'pri.d_serial_number'
            )->get();
        $detail = $destinations->first();

        return [
            'id' => $permit->id,
            'previous_item_id' => $permit->previous_item_id,
            'd_code' => $permit->d_code,
            'lookup_code' => $permit->d_code.'-'.$permit->previous_item_id,
            'serial_number' => $permit->previous_item_serial_number,
            'status' => $permit->status,
            'payment_status' => $permit->payment_status,
            'issued_at' => $permit->previous_item_issued_at,
            'permit_valid_until' => $permit->previous_item_valid_until,
            'validity_days' => (int) ($detail->country_validity_days ?? 30),
            'driver_id' => $permit->driver_id,
            'fleet_id' => $permit->fleet_id,
            'cargo_type' => $detail->operation_type ?? '',
            'permit_type' => $detail->permit_type ?? '',
            'loading_origin' => $detail->loading_origin ?? '',
            'loading_destination' => $detail->loading_destination ?? '',
            'cits_code' => $detail->cits_code ?? '',
            'trip_code' => $detail->trip_code ?? '',
            'cmr_date' => $detail->cmr_date ?? '',
            'receipt_code' => $detail->receipt_code ?? '',
            'receipt_amount' => $detail->receipt_amount ?? '',
            'tir_carnet_number' => $detail->tir_carnet_number ?? '',
            'tir_carnet_date' => $detail->tir_carnet_date ?? '',
            'driver_name' => trim(($permit->first_name_fa ?? '').' '.($permit->last_name_fa ?? '')),
            'fleet_plate' => $permit->transit_plate ?: 'ناوگان نامشخص',
            'countries_text' => $destinations->pluck('country_name')->implode('، '),
            'destinations' => $destinations,
        ];
    }


    public function store(Request $request, CargoDocumentRuleService $cargoRuleService)
    {
        if ($request->filled('receipt_amount')) {
            $request->merge(['receipt_amount' => str_replace(',', '', $request->receipt_amount)]);
        }

        // اگر شماره/کد پرونده مرجع ارسال شده باشد، این درخواست قطعاً تمدید است.
        // این کار باعث می‌شود اگر request_type از فرانت اشتباه یا خالی رسید، تمدید به عنوان new ذخیره نشود.
        if ($request->filled('previous_dozouleh_number')) {
            $request->merge(['request_type' => 'renewal']);
        }

        $requestWindowStatus = app(PermitRequestWindowService::class)->status();
        if (!$requestWindowStatus['allowed']) {
            return back()
                ->withErrors(['error' => $requestWindowStatus['message']])
                ->withInput();
        }

        $validationRules = [
            'tracking_code' => 'required|string',
            'driver_id' => 'required|exists:drivers,id',
            'fleet_id' => 'required|exists:fleets,id',
            'request_type' => 'required|in:new,renewal', 
            'previous_dozouleh_number' => 'required_if:request_type,renewal|nullable|string',
            'cargo_type' => 'required|string|exists:cargo_document_rules,cargo_type',
            'destinations' => 'required|array|min:1',
            'destinations.*.country_id' => 'required|integer|exists:countries,id',
            'destinations.*.permit_type' => 'required|string|max:100',
        ];

        $validationRules = array_merge(
            $validationRules,
            $cargoRuleService->submissionRules($request->input('cargo_type'), 'store'),
        );

        $request->validate($validationRules);

        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        $isAssociationMember = (bool) DB::table('companies')
            ->where('id', $companyId)
            ->value('is_association_member');

        $renewalSource = null;

        if ($request->input('request_type') === 'renewal') {
            $previousNumber = trim((string) $request->previous_dozouleh_number);
            $renewalSource = $this->findRenewalSource($previousNumber, (int) $companyId);

            if (!$renewalSource) {
                [$renewalLookupCode, $renewalItemId] = $this->parseRenewalLookupCode($previousNumber);
                $validUntil = DB::table('permit_request_items as pri')
                    ->join('permit_requests as pr', 'pri.permit_request_id', '=', 'pr.id')
                    ->where('pr.company_id', $companyId)
                    ->where(function ($query) use ($renewalLookupCode) {
                        $query->where('pr.d_code', $renewalLookupCode)
                            ->orWhere('pr.serial_number', $renewalLookupCode)
                            ->orWhere('pri.d_serial_number', $renewalLookupCode);
                    })
                    ->when($renewalItemId, fn ($query) => $query->where('pri.id', $renewalItemId))
                    ->selectRaw('COALESCE(pri.permit_valid_until, pr.permit_valid_until) as valid_until')
                    ->orderByDesc('pri.id')
                    ->value('valid_until');

                if ($validUntil && \Carbon\Carbon::parse($validUntil)->isFuture()) {
                    $jalaliDate = \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($validUntil))->format('Y/m/d');

                    return back()
                        ->withErrors(['previous_dozouleh_number' => "این دوزوله تا تاریخ {$jalaliDate} اعتبار دارد و پیش از پایان اعتبار امکان تمدید آن وجود ندارد."])
                        ->withInput();
                }

                return back()
                    ->withErrors(['previous_dozouleh_number' => 'این دوزوله هنوز صادر نهایی نشده است و امکان ثبت درخواست تمدید برای آن وجود ندارد. لطفاً پس از تأیید نهایی و صدور دوزوله، مجدداً اقدام نمایید.'])
                    ->withInput();
            }

            $request->merge([
                'driver_id' => $renewalSource->driver_id,
                'fleet_id'  => $renewalSource->fleet_id,
                'destinations' => [[
                    'country_id' => $renewalSource->previous_item_country_id,
                    'permit_type' => $renewalSource->previous_item_permit_type,
                ]],
            ]);
        } else {
            $quotaError = $this->quotaErrorForDestinations((int) $companyId, $request->destinations);
            if ($quotaError) {
                return back()
                    ->withErrors(['error' => $quotaError])
                    ->withInput();
            }

            // در متد ذخیره‌سازی فقط ناوگان کنترل می‌شود (راننده کاملاً آزاد شد)
            $activePermitCheck = PermitRequest::where('fleet_id', $request->fleet_id)
                ->where('company_id', $companyId)
                ->whereIn('status', $this->activePermitStatuses())
                ->exists();

            if ($activePermitCheck) {
                return back()->withErrors(['error' => 'این ناوگان در حال حاضر یک درخواست فعال در سیستم دارد.']);
            }
        }

        $destinationPermitTypeError = $this->destinationPermitTypeError($request->destinations);
        if ($destinationPermitTypeError) {
            return back()
                ->withErrors(['destinations' => $destinationPermitTypeError])
                ->withInput();
        }

        $activeDriver = Driver::active()
            ->whereKey($request->driver_id)
            ->where('current_company_id', $companyId)
            ->exists();
        if (! $activeDriver) {
            return back()->withErrors(['driver_id' => 'راننده انتخاب‌شده فعال نیست یا به این شرکت تعلق ندارد.'])->withInput();
        }

        $activeFleet = Fleet::active()
            ->whereKey($request->fleet_id)
            ->where('company_id', $companyId)
            ->exists();
        if (! $activeFleet) {
            return back()->withErrors(['fleet_id' => 'ناوگان انتخاب‌شده فعال نیست یا به این شرکت تعلق ندارد.'])->withInput();
        }

        $isRenewal = $request->input('request_type') === 'renewal';
        $isFreeRenewal = $isRenewal && $isAssociationMember;
        $totalRequestAmount = 0;
        foreach ($request->destinations as $item) {
            $countryData = Country::findOrFail($item['country_id']);
            if (!$isFreeRenewal) {
                $totalRequestAmount += $this->countryPriceForCompany($countryData, $isAssociationMember);
            }
        }
        
        $wallet = Wallet::where('company_id', $companyId)->first();
        if (!$isFreeRenewal && (!$wallet || $wallet->available_balance < $totalRequestAmount)) {
            return back()->withErrors(['error' => $isRenewal
                ? 'برای تمدید دوزوله، موجودی کیف پول شرکت غیرعضو کافی نیست.'
                : 'موجودی کیف پول کافی نیست.']);
        }

        $filePaths = [];
        if ($request->hasFile('receipt_file')) $filePaths['receipt_file'] = $request->file('receipt_file')->store('permits/receipts', 'public');
        if ($request->hasFile('cmr_file')) $filePaths['cmr_file'] = $request->file('cmr_file')->store('permits/cmr', 'public');
        if ($request->hasFile('tir_file')) $filePaths['tir_file'] = $request->file('tir_file')->store('permits/tir', 'public');
        if ($request->hasFile('declaration_file')) $filePaths['declaration_file'] = $request->file('declaration_file')->store('permits/declarations', 'public');

        DB::beginTransaction();
        try {
            $permitRequest = PermitRequest::create([
                'd_code'              => $this->generateDCode(),
                'company_id'          => $companyId,
                'driver_id'           => $request->driver_id,
                'fleet_id'            => $request->fleet_id,
                'status'              => 'pending',
                'payment_status'      => $isFreeRenewal ? 'settled' : 'reserved',
                'total_amount'        => $totalRequestAmount,

                'request_type'             => $request->input('request_type', 'new'),
                'previous_request_id'      => $request->input('request_type') === 'renewal' ? ($renewalSource->id ?? null) : null,
                'previous_d_code'          => $request->input('request_type') === 'renewal' ? ($renewalSource->d_code ?? null) : null,
                'previous_serial_number'   => $request->input('request_type') === 'renewal' ? ($renewalSource->previous_item_serial_number ?? $renewalSource->serial_number ?? null) : null,

                'company_note'        => $isRenewal
                    ? ($isFreeRenewal ? 'تمدید رایگان دوزوله شماره ' : 'تمدید دوزوله با تعرفه غیرعضو شماره ') . ($renewalSource->previous_item_serial_number ?? $renewalSource->serial_number ?? '-') . ' | مرجع: ' . ($renewalSource->d_code ?? $request->previous_dozouleh_number)
                    : 'ثبت درخواست جدید',
            ]);

            foreach ($request->destinations as $item) {
                $countryData = Country::findOrFail($item['country_id']);
                $countryPrice = $isFreeRenewal ? 0 : $this->countryPriceForCompany($countryData, $isAssociationMember);
                $itemId = DB::table('permit_request_items')->insertGetId([
                    'permit_request_id'   => $permitRequest->id,
                    'country_id'          => $item['country_id'],
                    'permit_type'         => $item['permit_type'],
                    'operation_type'      => $request->cargo_type ?? '', 
                    'loading_origin'      => $request->loading_origin,
                    'loading_destination' => $request->loading_destination,
                    'cits_code'           => $request->cits_code,
                    'trip_code'           => $request->trip_code,
                    'receipt_code'        => $request->receipt_code,
                    'receipt_amount'      => $request->receipt_amount ?? 0,
                    'cmr_date'            => $request->cmr_date,
                    'tir_carnet_number'   => $request->tir_carnet_number,
                    'tir_carnet_date'     => $request->tir_carnet_date,
                    'receipt_file'        => $filePaths['receipt_file'] ?? null,
                    'cmr_file'            => $filePaths['cmr_file'] ?? null,
                    'tir_file'            => $filePaths['tir_file'] ?? null,
                    'declaration_file'    => $filePaths['declaration_file'] ?? null,
                    'price'               => $countryPrice,
                    'reserved_amount'     => 0,
                    'financial_status'    => $isFreeRenewal ? 'waived' : 'reserved',
                    'financial_settled_at'=> $isFreeRenewal ? now() : null,
                    'allocation_status'   => 'pending',
                    'item_status'         => 'pending',
                    'renewed_from_item_id'=> $request->input('request_type') === 'renewal' ? ($renewalSource->previous_item_id ?? null) : null,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                $permitItem = DB::table('permit_request_items')->where('id', $itemId)->first();
                if (!$isFreeRenewal) {
                    app(PermitItemFinancialService::class)->reserve(
                        (int) $companyId,
                        $itemId,
                        $countryPrice,
                        "بلوکه مبلغ درخواست دوزوله {$permitRequest->d_code} / ردیف {$itemId}"
                    );
                }
                app(PermitItemAuditService::class)->record(
                    $permitItem,
                    'request_submitted',
                    $isFreeRenewal
                        ? 'درخواست تمدید رایگان توسط شرکت عضو ثبت شد و این ردیف از پرداخت معاف است.'
                        : ($isRenewal
                            ? 'درخواست تمدید توسط شرکت غیرعضو ثبت شد و مبلغ تعرفه در کیف پول بلوکه شد.'
                            : 'درخواست دوزوله توسط شرکت ثبت و مبلغ آن در کیف پول بلوکه شد.'),
                    [],
                    ['price' => $countryPrice, 'status' => 'pending'],
                    ['request_type' => $permitRequest->request_type],
                    null,
                    'pending',
                );
            }

            DB::commit();
            return redirect()->route('dozbalagh.index')->with('success', 'درخواست با موفقیت ثبت شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطای سرور: ' . $e->getMessage()]);
        }
    }

    private function quotaErrorForDestinations(int $companyId, array $destinations, ?int $excludePermitId = null): ?string
    {
        $requestedByCountry = collect($destinations)
            ->pluck('country_id')
            ->filter()
            ->map(fn ($countryId) => (int) $countryId)
            ->countBy();

        if ($requestedByCountry->isEmpty()) {
            return null;
        }

        $countryIds = $requestedByCountry->keys()->all();
        $countries = Country::whereIn('id', $countryIds)->get()->keyBy('id');
        $companyQuotas = CompanyQuota::where('company_id', $companyId)
            ->whereIn('country_id', $countryIds)
            ->get()
            ->keyBy('country_id');

        $activeCounts = DB::table('permit_request_items as pri')
            ->join('permit_requests as pr', 'pri.permit_request_id', '=', 'pr.id')
            ->where('pr.company_id', $companyId)
            ->whereIn('pr.status', $this->activePermitStatuses())
            ->whereIn('pri.country_id', $countryIds)
            ->when($excludePermitId, fn ($query) => $query->where('pr.id', '<>', $excludePermitId))
            ->select('pri.country_id', DB::raw('count(*) as total'))
            ->groupBy('pri.country_id')
            ->pluck('total', 'country_id');

        foreach ($requestedByCountry as $countryId => $requestedCount) {
            $country = $countries->get((int) $countryId);
            if (!$country) {
                continue;
            }

            $companyQuota = $companyQuotas->get((int) $countryId);
            $limit = $companyQuota ? $companyQuota->custom_limit : $country->default_quota;

            if (is_null($limit)) {
                continue;
            }

            $message = $companyQuota?->reject_message
                ?: $country->reject_message
                ?: "امکان ثبت درخواست دوزوله برای {$country->name} وجود ندارد یا سقف مجاز تکمیل شده است.";

            if ((int) $limit === 0) {
                return $message;
            }

            $usedCount = (int) ($activeCounts[(int) $countryId] ?? 0);
            if (($usedCount + (int) $requestedCount) > (int) $limit) {
                return $message;
            }
        }

        return null;
    }

    public function checkFleetStatus(Request $request)
    {
        $fleetId = $request->input('fleet_id') ?? $request->input('id');
        $driverId = $request->input('driver_id');
        $requestType = $request->input('request_type'); // دریافت نوع درخواست
        $destinations = $request->input('destinations', []);

        if (!$fleetId) return response()->json(['success' => false, 'message' => 'شناسه ناوگان ارسال نشده است.'], 200);

        try {
            $fleet = Fleet::find($fleetId);
            if (!$fleet) return response()->json(['success' => false, 'message' => 'ناوگان یافت نشد.'], 200);

            if (! $fleet->isActive()) {
                return response()->json(['success' => false, 'message' => 'این ناوگان توسط انجمن غیرفعال شده و امکان استفاده از آن وجود ندارد.'], 200);
            }

            $companyId = optional(auth()->user()->company)->id ?? auth()->user()->company_id;
            if ($companyId && (int) $fleet->company_id !== (int) $companyId) {
                return response()->json(['success' => false, 'message' => 'این ناوگان به شرکت شما تعلق ندارد.'], 200);
            }

            if ($driverId) {
                if (! Driver::active()->whereKey($driverId)->where('current_company_id', $companyId)->exists()) {
                    return response()->json(['success' => false, 'message' => 'راننده انتخاب‌شده غیرفعال است یا به این شرکت تعلق ندارد.'], 200);
                }
            }

            // دژ امنیتی تمدید: اگر حالت درخواست تمدید (renewal) باشد، نباید جلوی ناوگان گرفته شود
            if ($requestType !== 'renewal') {
                $activeFleetPermit = PermitRequest::where('fleet_id', $fleetId)
                    ->where('company_id', $companyId)
                    ->whereIn('status', $this->activePermitStatuses())
                    ->first();
                if ($activeFleetPermit) {
                    return response()->json(['success' => false, 'message' => "این ناوگان درخواست فعالی به شماره {$activeFleetPermit->d_code} دارد."], 200);
                }
                if ($driverId) {
                    $activeDriverPermit = PermitRequest::where('driver_id', $driverId)
                        ->where('company_id', $companyId)
                        ->whereIn('status', $this->activePermitStatuses())
                        ->first();
                    if ($activeDriverPermit) {
                        return response()->json(['success' => false, 'message' => "راننده درخواست فعالی به شماره {$activeDriverPermit->d_code} دارد."], 200);
                    }
                }
            }

            $allocationCodes = [];
            $allocationCodeRows = [];

            foreach ($destinations as $dest) {
                $countryId = $dest['country_id'] ?? null;
                if (!$countryId) continue;

                $permitType = trim((string) ($dest['permit_type'] ?? ''));
                $allocatedSerial = DB::table('dozbalagh_items as di')
                    ->join('dozbalagh_batches as db', 'di.batch_id', '=', 'db.id')
                    ->where('db.country_id', $countryId)
                    ->whereNotNull('db.permit_type')
                    ->where('db.permit_type', $permitType)
                    ->where(function ($query) {
                        $query->whereNull('di.lifecycle_status')
                            ->orWhereIn('di.lifecycle_status', ['raw', 'in_stock']);
                    })
                    ->orderBy('di.serial_number')
                    ->value('di.serial_number');

                $allocationKey = $countryId . '|' . ($permitType !== '' ? $permitType : 'unclassified');
                $allocationCodes[$allocationKey] = $allocatedSerial ?? 'بدون موجودی سریال';
                $allocationCodeRows[] = [
                    'country_id' => (int) $countryId,
                    'permit_type' => $permitType ?: null,
                    'serial_number' => $allocatedSerial,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'وضعیت ناوگان و راننده معتبر است.',
                'data' => [
                    'plate_number' => $fleet->transit_plate ?? $fleet->plate_number,
                    'fleet_type'   => $fleet->truck_type ?? $fleet->fleet_type ?? 'نامشخص',
                    'allocation_codes' => $allocationCodes,
                    'allocation_code_rows' => $allocationCodeRows,
                ]
            ]);

        } catch (\Throwable $e) { 
            return response()->json(['success' => false, 'message' => 'خطای داخلی سرور: ' . $e->getMessage()], 200);
        }
    }

    public function edit($id)
    {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        $permitRequest = PermitRequest::where('id', $id)
            ->where('company_id', $companyId)
            ->where('status', 'returned')
            ->firstOrFail();

        if ($permitRequest->driver_id && ! Driver::active()->whereKey($permitRequest->driver_id)->exists()) {
            return redirect()->route('dozbalagh.index')->withErrors(['error' => 'راننده این پرونده توسط انجمن غیرفعال شده و امکان اصلاح یا ارسال مجدد ندارد.']);
        }

        if ($permitRequest->fleet_id && ! Fleet::active()->whereKey($permitRequest->fleet_id)->exists()) {
            return redirect()->route('dozbalagh.index')->withErrors(['error' => 'ناوگان این پرونده توسط انجمن غیرفعال شده و امکان اصلاح یا ارسال مجدد ندارد.']);
        }

        $allWorldCountries = \App\Models\WorldCountry::orderBy('name_fa', 'asc')->get();
        $drivers = \App\Models\Driver::active()->where('current_company_id', $companyId)->get();
        $fleets = \App\Models\Fleet::active()->where('company_id', $companyId)->get();
        $countries = \App\Models\Country::where('is_active', 1)->get(); 
        $cargoRules = app(CargoDocumentRuleService::class)->frontendRules();
        
        $wallet = \App\Models\Wallet::where('company_id', $companyId)->first();
        $balance = $wallet ? $wallet->balance : 0;

        $isAssociationMember = (bool) DB::table('companies')
            ->where('id', $companyId)
            ->value('is_association_member');

        $previousDestinations = DB::table('permit_request_items')
            ->where('permit_request_id', $id)
            ->get()
            ->map(function($item) {
                return [
                    'country_id' => $item->country_id,
                    'permit_type' => $item->permit_type,
                ];
            });

        return view('company.dozbalagh.create', compact(
            'permitRequest', 'drivers', 'fleets', 'countries', 
            'cargoRules', 'wallet', 'balance', 'allWorldCountries', 'previousDestinations', 'isAssociationMember'
        ));
    }

    public function update(Request $request, CargoDocumentRuleService $cargoRuleService, $id)
    {
        if ($request->filled('receipt_amount')) {
            $request->merge(['receipt_amount' => str_replace(',', '', $request->receipt_amount)]);
        }

        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        $permitRequest = PermitRequest::where('id', $id)
            ->where('company_id', $companyId)
            ->where('status', 'returned')
            ->firstOrFail();

        $oldFirstItem = DB::table('permit_request_items')
            ->where('permit_request_id', $permitRequest->id)
            ->first();

        $validationRules = [
            'cargo_type' => 'required|string|exists:cargo_document_rules,cargo_type',
            'destinations' => 'required|array|min:1',
            'destinations.*.country_id' => 'required|integer|exists:countries,id',
            'destinations.*.permit_type' => 'required|string|max:100',
        ];

        $validationRules = array_merge(
            $validationRules,
            $cargoRuleService->submissionRules(
                $request->input('cargo_type'),
                'update',
                $oldFirstItem,
            ),
        );

        $request->validate($validationRules);

        $destinationPermitTypeError = $this->destinationPermitTypeError($request->destinations);
        if ($destinationPermitTypeError) {
            return back()
                ->withErrors(['destinations' => $destinationPermitTypeError])
                ->withInput();
        }

        $quotaError = $this->quotaErrorForDestinations((int) $companyId, $request->destinations, $permitRequest->id);
        if ($quotaError) {
            return back()
                ->withErrors(['error' => $quotaError])
                ->withInput();
        }

        $filePaths = [];
        if ($request->hasFile('receipt_file')) $filePaths['receipt_file'] = $request->file('receipt_file')->store('permits/receipts', 'public');
        if ($request->hasFile('cmr_file')) $filePaths['cmr_file'] = $request->file('cmr_file')->store('permits/cmr', 'public');
        if ($request->hasFile('tir_file')) $filePaths['tir_file'] = $request->file('tir_file')->store('permits/tir', 'public');
        if ($request->hasFile('declaration_file')) $filePaths['declaration_file'] = $request->file('declaration_file')->store('permits/declarations', 'public');

        DB::beginTransaction();
        try {
            $isAssociationMember = (bool) DB::table('companies')
                ->where('id', $companyId)
                ->value('is_association_member');
            $isFreeRenewal = $permitRequest->request_type === 'renewal' && $isAssociationMember;

            DB::table('permit_request_items')->where('permit_request_id', $id)->delete();

            $totalRequestAmount = 0;
            foreach ($request->destinations as $item) {
                $countryData = Country::findOrFail($item['country_id']);
                $countryPrice = $isFreeRenewal ? 0 : $this->countryPriceForCompany($countryData, $isAssociationMember);
                $totalRequestAmount += $countryPrice;

                DB::table('permit_request_items')->insert([
                    'permit_request_id'   => $permitRequest->id,
                    'country_id'          => $item['country_id'],
                    'permit_type'         => $item['permit_type'],
                    'operation_type'      => $request->cargo_type ?? '', 
                    'loading_origin'      => $request->loading_origin,
                    'loading_destination' => $request->loading_destination,
                    'cits_code'           => $request->cits_code,
                    'trip_code'           => $request->trip_code,
                    'receipt_code'        => $request->receipt_code,
                    'receipt_amount'      => $request->receipt_amount ?? 0,
                    'cmr_date'            => $request->cmr_date,
                    'tir_carnet_number'   => $request->tir_carnet_number,
                    'tir_carnet_date'     => $request->tir_carnet_date,
                    'receipt_file'        => $filePaths['receipt_file'] ?? ($oldFirstItem->receipt_file ?? null),
                    'cmr_file'            => $filePaths['cmr_file'] ?? ($oldFirstItem->cmr_file ?? null),
                    'tir_file'            => $filePaths['tir_file'] ?? ($oldFirstItem->tir_file ?? null),
                    'declaration_file'    => $filePaths['declaration_file'] ?? ($oldFirstItem->declaration_file ?? null),
                    'price'               => $countryPrice,
                    'allocation_status'   => 'pending',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            $diff = $totalRequestAmount - $permitRequest->total_amount;
            if ($diff > 0) {
                $wallet = Wallet::where('company_id', $companyId)->first();
                if (!$wallet || $wallet->balance < $diff) {
                    DB::rollBack();
                    return back()->withErrors(['error' => 'موجودی کیف پول برای اعمال تغییرات مقاصد کافی نیست.']);
                }
                $wallet->decrement('balance', $diff);
                $wallet->increment('blocked_balance', $diff);
            } elseif ($diff < 0) {
                $wallet = Wallet::where('company_id', $companyId)->first();
                if ($wallet) {
                    $wallet->increment('balance', abs($diff));
                    $wallet->decrement('blocked_balance', abs($diff));
                }
            }

            $permitRequest->update([
                'status' => 'pending',
                'total_amount' => $totalRequestAmount,
                'company_note' => 'اصلاح و ارسال مجدد توسط شرکت در تاریخ ' . \Hekmatinasser\Verta\Verta::now()->format('Y/m/d'),
            ]);

            DB::commit();
            return redirect()->route('dozbalagh.index')->with('success', 'اصلاحیه پرونده با موفقیت ثبت و مجدداً برای بررسی ادمین فرستاده شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت اصلاحیه: ' . $e->getMessage()]);
        }
    }

    public function submitReturnLash(Request $request, $id)
    {
        $request->validate([
            'disposition_type' => 'required|in:return_archive,unused',
            'return_image' => 'required|image|max:5120',
            'courier_name' => 'required|string|max:255',
            'courier_mobile' => 'required|string|max:30',
        ]);

        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;
        $hasItemStatus = Schema::hasColumn('permit_request_items', 'item_status');
        $hasCompanyReturnFields = Schema::hasColumn('permit_request_items', 'company_return_image')
            && Schema::hasColumn('permit_request_items', 'courier_name')
            && Schema::hasColumn('permit_request_items', 'courier_mobile')
            && Schema::hasColumn('permit_request_items', 'courier_national_code')
            && Schema::hasColumn('permit_request_items', 'courier_vehicle_plate')
            && Schema::hasColumn('permit_request_items', 'courier_delivery_code')
            && Schema::hasColumn('permit_request_items', 'courier_code_sent_at')
            && Schema::hasColumn('permit_request_items', 'company_return_submitted_at');

        $permitItemQuery = DB::table('permit_request_items as pri')
            ->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
            ->where('pri.id', $id)
            ->where('pr.company_id', $companyId)
            ->whereIn('pr.status', ['issued', 'partially_issued'])
            ->whereNotNull('pri.d_serial_number');

        if ($hasItemStatus) {
            $permitItemQuery->whereIn('pri.item_status', ['issued', 'transferred_in']);
        } else {
            $permitItemQuery->where(function ($query) {
                $query->whereNull('pri.return_status')
                    ->orWhereNotIn('pri.return_status', ['lost', 'collected', 'archived']);
            });
        }

        $permitItem = $permitItemQuery
            ->select('pri.*', 'pr.d_code', 'pr.company_id')
            ->first();

        abort_if(!$permitItem, 404);

        $file = $request->file('return_image');
        $serial = $permitItem->d_serial_number ?: $permitItem->d_code;
        $fileName = $serial . '-company-return-' . time() . '.' . $file->getClientOriginalExtension();
        $imagePath = $file->storeAs('permits/company-returns', $fileName, 'public');
        $deliveryCode = (string) random_int(100000, 999999);
        $smsSent = false;

        if ($hasCompanyReturnFields) {
            $updated = DB::table('permit_request_items')
                ->where('id', $permitItem->id)
                ->whereIn('item_status', ['issued', 'transferred_in'])
                ->update([
                'item_status' => 'company_returned',
                'disposition_type' => $request->disposition_type,
                'disposition_note' => 'انتخاب شرکت هنگام ثبت و ارسال تصویر لاشه',
                'disposition_changed_by_user_id' => auth()->id(),
                'disposition_changed_at' => now(),
                'company_return_image' => $imagePath,
                'courier_name' => $request->courier_name,
                'courier_mobile' => $request->courier_mobile,
                'courier_national_code' => null,
                'courier_vehicle_plate' => null,
                'courier_delivery_code' => $deliveryCode,
                'courier_code_sent_at' => null,
                'company_return_submitted_at' => now(),
                'updated_at' => now(),
            ]);
            if ($updated !== 1) {
                Storage::disk('public')->delete($imagePath);
                return response()->json([
                    'success' => false,
                    'message' => 'تعیین تکلیف این دوزوله قبلاً ثبت شده است و امکان تولید کد جدید ندارد.',
                ], 409);
            }
        } else {
            $fallbackMeta = json_encode([
                'cn' => Str::limit((string) $request->courier_name, 60, ''),
                'cm' => Str::limit((string) $request->courier_mobile, 24, ''),
                'nc' => '',
                'vp' => '',
                'dc' => $deliveryCode,
            ], JSON_UNESCAPED_UNICODE);

            DB::table('permit_request_items')->where('id', $permitItem->id)->update([
                'return_status' => 'company_returned',
                'return_cmr_file' => $imagePath,
                'rejection_reason' => Str::limit($fallbackMeta ?: '', 250, ''),
                'updated_at' => now(),
            ]);
        }

        $smsSent = $this->sendCourierDeliveryCode(
            $request->courier_mobile,
            $deliveryCode,
            $serial
        );
        if ($hasCompanyReturnFields && $smsSent) {
            DB::table('permit_request_items')->where('id', $permitItem->id)->update([
                'courier_code_sent_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('permit_requests')->where('id', $permitItem->permit_request_id)->update([
            'company_note' => 'لاشه یکی از مجوزهای این پرونده توسط شرکت ثبت و برای تحویل به انجمن به پیک سپرده شد.',
            'updated_at' => now(),
        ]);

        app(PermitItemAuditService::class)->record(
            $permitItem,
            'company_lash_submitted',
            'شرکت تصویر لاشه و مشخصات پیک را ثبت کرد.',
            ['disposition_type' => $permitItem->disposition_type ?? null],
            [
                'disposition_type' => $request->disposition_type,
                'company_return_image' => $imagePath,
                'courier_name' => $request->courier_name,
                'courier_mobile' => $request->courier_mobile,
            ],
            ['delivery_code_created' => true],
            $permitItem->item_status ?? null,
            'company_returned',
        );

        // تحویل لاشه به شرکت، پایان ردیابی عملیاتی این سفر است.
        DB::table('dozbalagh_items')
            ->where('serial_number', $permitItem->d_serial_number)
            ->where('country_id', $permitItem->country_id)
            ->where('permit_type', $permitItem->permit_type)
            ->update(['returned_at' => now(), 'updated_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'لاشه با موفقیت ثبت شد. کد تحویل برای پیک ساخته شد.',
            'delivery_code' => $deliveryCode,
            'sms_sent' => $smsSent,
        ]);
    }

    private function sendCourierDeliveryCode(?string $mobile, string $code, string $serial): bool
    {
        $mobile = trim((string) $mobile);
        $apiKey = config('services.kavenegar.key');

        if ($mobile === '' || empty($apiKey)) {
            return false;
        }

        $message = "سامانه دوزوله\nکد تحویل لاشه دوزوله سریال {$serial}:\n{$code}\nاین کد را هنگام تحویل به انجمن اعلام کنید.";
        $url = "https://api.kavenegar.com/v1/{$apiKey}/sms/send.json";

        try {
            $response = Http::asForm()->post($url, [
                'receptor' => $mobile,
                'message' => $message,
            ]);

            if (!$response->successful()) {
                Log::warning('Kavenegar courier code send failed: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Kavenegar courier code connection failed: ' . $e->getMessage());
            return false;
        }
    }

    public function reportLost(Request $request, $id)
    {
        $request->validate([
            'lost_reason' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;
        $hasItemStatus = Schema::hasColumn('permit_request_items', 'item_status');
        $hasLostFields = $hasItemStatus
            && Schema::hasColumn('permit_request_items', 'lost_reported_at')
            && Schema::hasColumn('permit_request_items', 'lost_reason')
            && Schema::hasColumn('permit_request_items', 'closed_at');

        DB::beginTransaction();
        try {
            $permitItem = DB::table('permit_request_items as pri')
                ->join('permit_requests as pr', 'pr.id', '=', 'pri.permit_request_id')
                ->where('pri.id', $id)
                ->where('pr.company_id', $companyId)
                ->whereIn('pr.status', ['issued', 'partially_issued'])
                ->whereNotNull('pri.d_serial_number')
                ->when($hasItemStatus, fn ($query) => $query->whereIn('pri.item_status', ['issued', 'transferred_in']))
                ->select('pri.*', 'pr.driver_id', 'pr.fleet_id', 'pr.d_code')
                ->lockForUpdate()
                ->first();

            if (!$permitItem) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'مجوز کشور مورد نظر پیدا نشد یا در وضعیت قابل ثبت مفقودی نیست.',
                ], 404);
            }

            if ($hasLostFields) {
                DB::table('permit_request_items')->where('id', $permitItem->id)->update([
                    'item_status' => 'lost_reported',
                    'disposition_type' => 'lost',
                    'disposition_note' => $request->lost_reason,
                    'disposition_changed_by_user_id' => auth()->id(),
                    'disposition_changed_at' => now(),
                    'lost_reported_at' => now(),
                    'lost_reason' => $request->lost_reason,
                    'closed_at' => null,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('permit_request_items')->where('id', $permitItem->id)->update([
                    'return_status' => 'lost_reported',
                    'rejection_reason' => Str::limit((string) $request->lost_reason, 250, ''),
                    'updated_at' => now(),
                ]);
            }

            DB::table('permit_requests')->where('id', $permitItem->permit_request_id)->update([
                'company_note' => 'مفقودی لاشه یکی از مجوزهای این پرونده توسط شرکت ثبت شد.',
                'updated_at' => now(),
            ]);

            app(PermitItemAuditService::class)->record(
                $permitItem,
                'lost_reported_by_company',
                $request->lost_reason ?: 'مفقودی دوزوله توسط شرکت اعلام شد و منتظر تصمیم انجمن است.',
                [],
                ['item_status' => 'lost_reported', 'disposition_type' => 'lost'],
                [],
                $permitItem->item_status ?? null,
                'lost_reported',
            );

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'مفقودی لاشه برای همین کشور ثبت شد و سایر کشورهای پرونده فعال می‌مانند.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت مفقودی: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function nominatePickupCourier(
        Request $request,
        int $id,
        SmsService $sms,
        PermitItemAuditService $audit,
    ) {
        $validated = $request->validate([
            'courier_name' => 'required|string|max:255',
            'courier_mobile' => 'required|string|max:30',
        ]);
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id;
        $otp = (string) random_int(10000, 99999);

        $missionId = DB::transaction(function () use ($request, $id, $validated, $companyId, $otp, $audit) {
            $item = DB::table('permit_request_items as item')
                ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
                ->where('item.id', $id)
                ->where('permit.company_id', $companyId)
                ->whereNotNull('item.d_serial_number')
                ->whereIn('item.item_status', ['issued', 'transferred_in'])
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('permit_courier_missions as completed_mission')
                        ->whereColumn('completed_mission.permit_request_item_id', 'item.id')
                        ->where('completed_mission.direction', 'association_to_company')
                        ->where('completed_mission.status', 'completed');
                })
                ->select('item.*', 'permit.company_id', 'permit.d_code')
                ->lockForUpdate()
                ->first();
            abort_if(!$item, 404);

            DB::table('permit_courier_missions')
                ->where('permit_request_item_id', $id)
                ->where('direction', 'association_to_company')
                ->where('status', 'pending')
                ->update(['status' => 'cancelled', 'updated_at' => now()]);

            $missionId = DB::table('permit_courier_missions')->insertGetId([
                'permit_request_item_id' => $id,
                'company_id' => $companyId,
                'direction' => 'association_to_company',
                'purpose' => 'issued_permit_pickup',
                'status' => 'pending',
                'courier_name' => $validated['courier_name'],
                'courier_mobile' => $validated['courier_mobile'],
                'courier_national_code' => null,
                'courier_vehicle_plate' => null,
                'identity_image' => null,
                'otp_hash' => hash('sha256', $otp),
                'delivery_code' => $otp,
                'otp_sent_at' => null,
                'otp_expires_at' => null,
                'created_by_user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $audit->record($item, 'outbound_courier_assigned', 'شرکت پیک دریافت دوزوله صادرشده را معرفی کرد.', [], [
                'mission_id' => $missionId,
                'courier_name' => $validated['courier_name'],
                'courier_mobile' => $validated['courier_mobile'],
            ]);
            DB::table('permit_request_items')->where('id', $id)->update(['updated_at' => now()]);
            return $missionId;
        });

        $smsSent = $sms->send($validated['courier_mobile'], "کد تحویل دوزوله از انجمن: {$otp}", 'permit_company_pickup_courier');
        if ($smsSent) {
            DB::table('permit_courier_missions')->where('id', $missionId)->update([
                'otp_sent_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $redirect = back()->with('success', $smsSent
            ? 'پیک معرفی و کد تحویل برای او پیامک شد.'
            : 'پیک ثبت شد، اما پیامک به دلیل اختلال یا اعتبار ناکافی ارسال نشد.');
        $redirect->with('courier_sms_sent', $smsSent);
        return $redirect;
    }

    public function resendPickupCourierCode(
        int $mission,
        SmsService $sms,
    ) {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id;
        $row = DB::table('permit_courier_missions as mission')
            ->join('permit_request_items as item', 'item.id', '=', 'mission.permit_request_item_id')
            ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
            ->where('mission.id', $mission)
            ->where('mission.company_id', $companyId)
            ->where('permit.company_id', $companyId)
            ->where('mission.direction', 'association_to_company')
            ->where('mission.status', 'pending')
            ->select('mission.*')
            ->first();
        abort_if(!$row, 404);

        $otp = (string) random_int(10000, 99999);
        $smsSent = $sms->send($row->courier_mobile, "کد تحویل دوزوله از انجمن: {$otp}", 'permit_company_pickup_courier_resend');
        DB::table('permit_courier_missions')->where('id', $row->id)->update([
            'otp_hash' => hash('sha256', $otp),
            'delivery_code' => $otp,
            'otp_sent_at' => $smsSent ? now() : null,
            'otp_expires_at' => null,
            'updated_at' => now(),
        ]);

        $redirect = back()->with('success', $smsSent
            ? 'کد جدید برای پیک پیامک شد.'
            : 'ارسال پیامک انجام نشد؛ کد جدید را مستقیماً در اختیار پیک قرار دهید.');
        $redirect->with('courier_sms_sent', $smsSent);
        return $redirect;
    }
}
