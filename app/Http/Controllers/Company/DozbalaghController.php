<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Wallet;
use App\Models\PermitRequest;
use App\Models\CargoDocumentRule; 
use App\Models\WorldCountry; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
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

    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        if (!$companyId) {
            return redirect()->route('dashboard')->with('error', 'حساب کاربری شما به شرکتی متصل نیست.');
        }

        $query = PermitRequest::where('company_id', $companyId)->with(['driver', 'fleet']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('d_code', 'like', "%{$search}%")
                  ->orWhereHas('driver', function($d) use ($search) {
                      $d->where('first_name_fa', 'like', "%{$search}%")
                        ->orWhere('last_name_fa', 'like', "%{$search}%")
                        ->orWhere('national_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('fleet', function($f) use ($search) {
                      $f->where('transit_plate', 'like', "%{$search}%")
                        ->orWhere('smart_card_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $statusFilter = $request->status;

            // استانداردسازی: تمدید ماهیت درخواست است، نه وضعیت چرخه کار.
            // برای سازگاری با لینک قدیمی status=renewed هم همین فیلتر تمدید اعمال می‌شود.
            if (in_array($statusFilter, ['renewal', 'renewed'], true)) {
                $query->where('request_type', 'renewal');
            } elseif ($statusFilter === 'new') {
                $query->where(function ($q) {
                    $q->whereNull('request_type')
                      ->orWhere('request_type', 'new')
                      ->orWhere('request_type', '');
                });
            } elseif ($statusFilter === 'active') {
                $query->whereIn('status', $this->activePermitStatuses());
            } elseif ($statusFilter === 'collected') {
                $query->whereIn('status', ['collected', 'archived']);
            } else {
                $query->where('status', $statusFilter);
            }
        } else {
            $query->orderByRaw("FIELD(status, 'pending', 'approved', 'issued', 'rejected', 'returned', 'collected', 'archived', 'lost') ASC");
        }

        $query->orderBy('id', 'desc');
        $requests = $query->paginate(10)->withQueryString();

        $correctionCount = PermitRequest::where('company_id', $companyId)
            ->where('status', 'returned')
            ->count();

        return view('company.dozbalagh.index', compact('requests', 'correctionCount'));
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
            ])->with('error', 'حساب کاربری شما به هیچ شرکتی متصل نیست.');
        }

        $drivers = \App\Models\Driver::where('current_company_id', $companyId)->get();
        foreach ($drivers as $driver) {
            $activeDriverPermit = \App\Models\PermitRequest::where('driver_id', $driver->id)
                ->whereIn('status', $this->activePermitStatuses())
                ->orderBy('id', 'desc')
                ->first();
            $driver->active_dozbalaghs = $activeDriverPermit ? 1 : 0;
            $driver->last_active_number = $activeDriverPermit ? $activeDriverPermit->d_code : '';
        }

        $fleets = \App\Models\Fleet::where('company_id', $companyId)->get();
        foreach ($fleets as $fleet) {
            $activeFleetPermit = \App\Models\PermitRequest::where('fleet_id', $fleet->id)
                ->whereIn('status', $this->activePermitStatuses())
                ->orderBy('id', 'desc')
                ->first();
            $fleet->active_dozbalaghs = $activeFleetPermit ? 1 : 0;
            $fleet->last_active_number = $activeFleetPermit ? $activeFleetPermit->d_code : '';
        }

        $countries = \App\Models\Country::where('is_active', 1)->get(); 
        $cargoRules = \App\Models\CargoDocumentRule::all()->keyBy('cargo_type');
        
        $wallet = \App\Models\Wallet::firstOrCreate(
            ['company_id' => $companyId],
            ['balance' => 50000000, 'blocked_balance' => 0] 
        );
        
        $newDCode = $this->generateDCode(); 
        $balance = $wallet->balance;

        return view('company.dozbalagh.create', compact('drivers', 'fleets', 'countries', 'cargoRules', 'wallet', 'newDCode', 'balance', 'allWorldCountries'));
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

            $dCode = trim((string) $request->input('d_code', ''));
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
                $permit = DB::table('permit_requests as pr')
                    ->leftJoin('drivers as d', 'pr.driver_id', '=', 'd.id')
                    ->leftJoin('fleets as f', 'pr.fleet_id', '=', 'f.id')
                    ->where(function ($q) use ($dCode) {
                        $q->where('pr.d_code', $dCode)
                          ->orWhere('pr.serial_number', $dCode)
                          ->orWhereExists(function ($sub) use ($dCode) {
                              $sub->select(DB::raw(1))
                                  ->from('permit_request_items as pri_lookup')
                                  ->whereColumn('pri_lookup.permit_request_id', 'pr.id')
                                  ->where('pri_lookup.d_serial_number', $dCode);
                          });
                    })
                    ->select([
                        'pr.id as permit_request_id',
                        'pr.company_id',
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
                    ])
                    ->orderByDesc('pr.id')
                    ->first();

                if (!$permit) {
                    return response()->json([
                        'success' => true,
                        'items' => [],
                        'message' => 'پرونده‌ای با این کد رهگیری یا شماره دوزوله یافت نشد.'
                    ], 200);
                }

                if ((int) $permit->company_id !== (int) $companyId) {
                    return response()->json([
                        'success' => true,
                        'items' => [],
                        'message' => 'این دوزوله متعلق به شرکت شما نیست و امکان تمدید آن وجود ندارد.'
                    ], 200);
                }

                if ($permit->status !== 'issued') {
                    return response()->json([
                        'success' => true,
                        'items' => [],
                        'message' => 'این دوزوله هنوز صادر نهایی نشده است و امکان ثبت درخواست تمدید برای آن وجود ندارد.'
                    ], 200);
                }

                if ($permit->payment_status !== 'settled') {
                    return response()->json([
                        'success' => true,
                        'items' => [],
                        'message' => 'پرداخت این دوزوله هنوز نهایی نشده است و امکان ثبت درخواست تمدید برای آن وجود ندارد.'
                    ], 200);
                }

                if (empty($permit->serial_number)) {
                    return response()->json([
                        'success' => true,
                        'items' => [],
                        'message' => 'شماره دوزوله هنوز برای این پرونده تخصیص داده نشده است.'
                    ], 200);
                }

                if (empty($permit->issued_at)) {
                    return response()->json([
                        'success' => true,
                        'items' => [],
                        'message' => 'تاریخ صدور دوزوله ثبت نشده است و امکان تمدید وجود ندارد.'
                    ], 200);
                }

                if (empty($permit->permit_valid_until)) {
                    return response()->json([
                        'success' => true,
                        'items' => [],
                        'message' => 'تاریخ اعتبار دوزوله ثبت نشده است و امکان تمدید وجود ندارد.'
                    ], 200);
                }

                if (\Carbon\Carbon::parse($permit->permit_valid_until)->gt(now()->startOfDay())) {
                    return response()->json([
                        'success' => true,
                        'items' => [],
                        'message' => 'این دوزوله صادر شده و تا تاریخ ' . $permit->permit_valid_until . ' معتبر است. در حال حاضر امکان ثبت درخواست تمدید وجود ندارد.'
                    ], 200);
                }

                // جلوگیری از ثبت چندباره تمدید برای یک دوزوله
                if (Schema::hasColumn('permit_requests', 'previous_request_id')) {
                    $openRenewalExists = DB::table('permit_requests')
                        ->where('company_id', $companyId)
                        ->where('request_type', 'renewal')
                        ->where('previous_request_id', $permit->permit_request_id)
                        ->whereIn('status', ['pending', 'approved', 'returned', 'under_review'])
                        ->exists();

                    if ($openRenewalExists) {
                        return response()->json([
                            'success' => true,
                            'items' => [],
                            'message' => 'برای این دوزوله قبلاً درخواست تمدید ثبت شده و پرونده هنوز در حال رسیدگی است.'
                        ], 200);
                    }
                }

                $destinations = DB::table('permit_request_items as pri')
                    ->leftJoin('countries as c', 'pri.country_id', '=', 'c.id')
                    ->where('pri.permit_request_id', $permit->permit_request_id)
                    ->select(
                        'pri.country_id',
                        'c.name as country_name',
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

                $item = [
                    'id' => $permit->permit_request_id,
                    'd_code' => $permit->d_code,
                    'serial_number' => $permit->serial_number,
                    'status' => $permit->status,
                    'payment_status' => $permit->payment_status,
                    'issued_at' => $permit->issued_at,
                    'permit_valid_until' => $permit->permit_valid_until,
                    'driver_id' => $permit->driver_id,
                    'fleet_id' => $permit->fleet_id,

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

                    'driver_name' => trim(($permit->first_name_fa ?? '') . ' ' . ($permit->last_name_fa ?? '')),
                    'fleet_plate' => $permit->transit_plate ?: 'ناوگان نامشخص',
                    'countries_text' => $destinations->pluck('country_name')->implode('، '),
                    'destinations' => $destinations,
                ];

                return response()->json([
                    'success' => true,
                    'items' => [$item],
                    'message' => 'پرونده قابل تمدید است.'
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
        $code = trim($code);

        return DB::table('permit_requests as pr')
            ->where('pr.company_id', $companyId)
            ->where('pr.status', 'issued')
            ->where('pr.payment_status', 'settled')
            ->whereNotNull('pr.serial_number')
            ->where('pr.serial_number', '<>', '')
            ->whereNotNull('pr.issued_at')
            ->where(function ($q) use ($code) {
                $q->where('pr.d_code', $code)
                  ->orWhere('pr.serial_number', $code)
                  ->orWhereExists(function ($sub) use ($code) {
                      $sub->select(DB::raw(1))
                          ->from('permit_request_items as pri_lookup')
                          ->whereColumn('pri_lookup.permit_request_id', 'pr.id')
                          ->where('pri_lookup.d_serial_number', $code);
                  });
            })
            ->select('pr.*')
            ->orderByDesc('pr.id')
            ->first();
    }


public function store(Request $request)
    {
        if ($request->filled('receipt_amount')) {
            $request->merge(['receipt_amount' => str_replace(',', '', $request->receipt_amount)]);
        }

        // اگر شماره/کد پرونده مرجع ارسال شده باشد، این درخواست قطعاً تمدید است.
        // این کار باعث می‌شود اگر request_type از فرانت اشتباه یا خالی رسید، تمدید به عنوان new ذخیره نشود.
        if ($request->filled('previous_dozouleh_number')) {
            $request->merge(['request_type' => 'renewal']);
        }

        $validationRules = [
            'tracking_code' => 'required|string',
            'driver_id' => 'required|exists:drivers,id',
            'fleet_id' => 'required|exists:fleets,id',
            'request_type' => 'required|in:new,renewal', 
            'previous_dozouleh_number' => 'required_if:request_type,renewal|nullable|string',
            'cargo_type' => 'required|string',
            'destinations' => 'required|array|min:1',
        ];

        $request->validate($validationRules);

        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        $renewalSource = null;

        if ($request->input('request_type') === 'renewal') {
            $previousNumber = trim((string) $request->previous_dozouleh_number);
            $renewalSource = $this->findRenewalSource($previousNumber, (int) $companyId);

            if (!$renewalSource) {
                return back()
                    ->withErrors(['previous_dozouleh_number' => 'این دوزوله هنوز صادر نهایی نشده است و امکان ثبت درخواست تمدید برای آن وجود ندارد. لطفاً پس از تأیید نهایی و صدور دوزوله، مجدداً اقدام نمایید.'])
                    ->withInput();
            }

            $request->merge([
                'driver_id' => $renewalSource->driver_id,
                'fleet_id'  => $renewalSource->fleet_id,
            ]);
        } else {
            // در متد ذخیره‌سازی فقط ناوگان کنترل می‌شود (راننده کاملاً آزاد شد)
            $activePermitCheck = PermitRequest::where('fleet_id', $request->fleet_id)
                ->whereIn('status', $this->activePermitStatuses())
                ->exists();

            if ($activePermitCheck) {
                return back()->withErrors(['error' => 'این ناوگان در حال حاضر یک درخواست فعال در سیستم دارد.']);
            }
        }

        $totalRequestAmount = 0;
        foreach ($request->destinations as $item) {
            $countryData = Country::findOrFail($item['country_id']);
            $totalRequestAmount += ($countryData->price ?? 0);
        }
        
        $wallet = Wallet::where('company_id', $companyId)->first();
        if (!$wallet || $wallet->balance < $totalRequestAmount) {
            return back()->withErrors(['error' => 'موجودی کیف پول کافی نیست.']);
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
                'payment_status'      => 'reserved',
                'total_amount'        => $totalRequestAmount,

                'request_type'             => $request->input('request_type', 'new'),
                'previous_request_id'      => $request->input('request_type') === 'renewal' ? ($renewalSource->id ?? null) : null,
                'previous_d_code'          => $request->input('request_type') === 'renewal' ? ($renewalSource->d_code ?? null) : null,
                'previous_serial_number'   => $request->input('request_type') === 'renewal' ? ($renewalSource->serial_number ?? null) : null,

                'company_note'        => $request->input('request_type') === 'renewal'
                    ? 'تمدید دوزوله شماره ' . ($renewalSource->serial_number ?? '-') . ' | مرجع: ' . ($renewalSource->d_code ?? $request->previous_dozouleh_number)
                    : 'ثبت درخواست جدید',
            ]);

            foreach ($request->destinations as $item) {
                $countryData = Country::findOrFail($item['country_id']);
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
                    'receipt_file'        => $filePaths['receipt_file'] ?? null,
                    'cmr_file'            => $filePaths['cmr_file'] ?? null,
                    'tir_file'            => $filePaths['tir_file'] ?? null,
                    'declaration_file'    => $filePaths['declaration_file'] ?? null,
                    'price'               => $countryData->price ?? 0,
                    'allocation_status'   => 'pending',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            $wallet->decrement('balance', $totalRequestAmount);
            $wallet->increment('blocked_balance', $totalRequestAmount);

            DB::commit();
            return redirect()->route('dozbalagh.index')->with('success', 'درخواست با موفقیت ثبت شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطای سرور: ' . $e->getMessage()]);
        }
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

            // دژ امنیتی تمدید: اگر حالت درخواست تمدید (renewal) باشد، نباید جلوی ناوگان گرفته شود
            if ($requestType !== 'renewal') {
                $activeFleetPermit = PermitRequest::where('fleet_id', $fleetId)
                    ->whereIn('status', $this->activePermitStatuses())
                    ->first();
                if ($activeFleetPermit) {
                    return response()->json(['success' => false, 'message' => "این ناوگان درخواست فعالی به شماره {$activeFleetPermit->d_code} دارد."], 200);
                }
                if ($driverId) {
                    $activeDriverPermit = PermitRequest::where('driver_id', $driverId)
                        ->whereIn('status', $this->activePermitStatuses())
                        ->first();
                    if ($activeDriverPermit) {
                        return response()->json(['success' => false, 'message' => "راننده درخواست فعالی به شماره {$activeDriverPermit->d_code} دارد."], 200);
                    }
                }
            }

            $allocationCodes = [];
            $usedSerials = PermitRequest::whereNotNull('serial_number')->pluck('serial_number')->toArray();

            foreach ($destinations as $dest) {
                $countryId = $dest['country_id'] ?? null;
                if (!$countryId) continue;

                $batches = DB::table('dozbalagh_batches')->where('country_id', $countryId)->orderBy('id', 'asc')->get();
                $allocatedSerial = 'بدون موجودی سریال';

                foreach ($batches as $b) {
                    for ($s = $b->serial_start; $s <= $b->serial_end; $s++) {
                        if (!in_array($s, $usedSerials)) {
                            $allocatedSerial = $s;
                            break;
                        }
                    }
                    if ($allocatedSerial !== 'بدون موجودی سریال') break;
                }
                $allocationCodes[$countryId] = $allocatedSerial;
            }

            return response()->json([
                'success' => true,
                'message' => 'وضعیت ناوگان و راننده معتبر است.',
                'data' => [
                    'plate_number' => $fleet->transit_plate ?? $fleet->plate_number,
                    'fleet_type'   => $fleet->truck_type ?? $fleet->fleet_type ?? 'نامشخص',
                    'allocation_codes' => $allocationCodes
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

        $allWorldCountries = \App\Models\WorldCountry::orderBy('name_fa', 'asc')->get();
        $drivers = \App\Models\Driver::where('current_company_id', $companyId)->get();
        $fleets = \App\Models\Fleet::where('company_id', $companyId)->get();
        $countries = \App\Models\Country::where('is_active', 1)->get(); 
        $cargoRules = \App\Models\CargoDocumentRule::all()->keyBy('cargo_type');
        
        $wallet = \App\Models\Wallet::where('company_id', $companyId)->first();
        $balance = $wallet ? $wallet->balance : 0;

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
            'cargoRules', 'wallet', 'balance', 'allWorldCountries', 'previousDestinations'
        ));
    }

    public function update(Request $request, $id)
    {
        if ($request->filled('receipt_amount')) {
            $request->merge(['receipt_amount' => str_replace(',', '', $request->receipt_amount)]);
        }

        $request->validate([
            'cargo_type' => 'required|string',
            'destinations' => 'required|array|min:1',
        ]);

        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        $permitRequest = PermitRequest::where('id', $id)
            ->where('company_id', $companyId)
            ->where('status', 'returned')
            ->firstOrFail();

        $filePaths = [];
        if ($request->hasFile('receipt_file')) $filePaths['receipt_file'] = $request->file('receipt_file')->store('permits/receipts', 'public');
        if ($request->hasFile('cmr_file')) $filePaths['cmr_file'] = $request->file('cmr_file')->store('permits/cmr', 'public');
        if ($request->hasFile('tir_file')) $filePaths['tir_file'] = $request->file('tir_file')->store('permits/tir', 'public');
        if ($request->hasFile('declaration_file')) $filePaths['declaration_file'] = $request->file('declaration_file')->store('permits/declarations', 'public');

        DB::beginTransaction();
        try {
            $oldFirstItem = DB::table('permit_request_items')->where('permit_request_id', $id)->first();

            DB::table('permit_request_items')->where('permit_request_id', $id)->delete();

            $totalRequestAmount = 0;
            foreach ($request->destinations as $item) {
                $countryData = Country::findOrFail($item['country_id']);
                $totalRequestAmount += ($countryData->price ?? 0);

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
                    'price'               => $countryData->price ?? 0,
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
            'return_image' => 'required|image|max:5120',
            'courier_name' => 'required|string|max:255',
            'courier_mobile' => 'required|string|max:30',
            'courier_national_code' => 'nullable|string|max:30',
            'courier_vehicle_plate' => 'nullable|string|max:100',
        ]);

        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        $permitRequest = PermitRequest::where('id', $id)
            ->where('company_id', $companyId)
            ->where('status', 'issued')
            ->firstOrFail();

        $file = $request->file('return_image');
        $fileName = ($permitRequest->serial_number ?: $permitRequest->d_code) . '-company-return-' . time() . '.' . $file->getClientOriginalExtension();
        $imagePath = $file->storeAs('permits/company-returns', $fileName, 'public');
        $deliveryCode = (string) random_int(100000, 999999);
        $smsSent = $this->sendCourierDeliveryCode(
            $request->courier_mobile,
            $deliveryCode,
            $permitRequest->serial_number ?: $permitRequest->d_code
        );

        $permitRequest->update([
            'company_return_image' => $imagePath,
            'courier_name' => $request->courier_name,
            'courier_mobile' => $request->courier_mobile,
            'courier_national_code' => $request->courier_national_code,
            'courier_vehicle_plate' => $request->courier_vehicle_plate,
            'courier_delivery_code' => $deliveryCode,
            'courier_code_sent_at' => $smsSent ? now() : null,
            'company_return_submitted_at' => now(),
            'company_note' => 'لاشه توسط شرکت ثبت و برای تحویل به انجمن به پیک سپرده شد.',
        ]);

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

        $permitRequest = PermitRequest::where('id', $id)
            ->where('company_id', $companyId)
            ->where('status', 'issued')
            ->firstOrFail();

        DB::beginTransaction();
        try {
            $updateData = [
                'status' => 'lost',
                'lost_reported_at' => now(),
                'lost_reason' => $request->lost_reason,
                'company_note' => 'مفقودی لاشه توسط شرکت ثبت شد.',
            ];

            if (Schema::hasColumn('permit_requests', 'closed_at')) {
                $updateData['closed_at'] = now();
            }

            $permitRequest->update($updateData);

            if ($permitRequest->driver_id && Schema::hasColumn('drivers', 'is_blocked')) {
                DB::table('drivers')
                    ->where('id', $permitRequest->driver_id)
                    ->orWhere('national_code', $permitRequest->driver_id)
                    ->update(['is_blocked' => false]);
            }

            if ($permitRequest->fleet_id && Schema::hasColumn('fleets', 'is_blocked')) {
                DB::table('fleets')
                    ->where('id', $permitRequest->fleet_id)
                    ->orWhere('smart_card_number', $permitRequest->fleet_id)
                    ->update(['is_blocked' => false]);
            }

            if (!empty($permitRequest->serial_number)) {
                DB::table('dozbalagh_items')
                    ->where('serial_number', $permitRequest->serial_number)
                    ->update([
                        'lifecycle_status' => 'lost',
                        'returned_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'مفقودی لاشه ثبت شد و پرونده از چرخه تردد خارج شد.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت مفقودی: ' . $e->getMessage(),
            ], 500);
        }
    }
}
