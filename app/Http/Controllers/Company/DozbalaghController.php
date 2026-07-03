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
use Illuminate\Support\Str;
use Carbon\Carbon;

class DozbalaghController extends Controller
{
    private function generateDCode()
    {
        $datePrefix = \Hekmatinasser\Verta\Verta::now()->format('Ymd');
        $lastRequest = PermitRequest::where('d_code', 'like', 'D' . $datePrefix . '%')
                                    ->orderBy('id', 'desc')
                                    ->first();
        $nextSequence = $lastRequest ? intval(substr($lastRequest->d_code, -3)) + 1 : 1;
        return 'D' . $datePrefix . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        if (!$companyId) {
            return redirect()->route('dashboard')->with('error', 'حساب کاربری شما به شرکتی متصل نیست.');
        }

        $query = PermitRequest::where('company_id', $companyId)->with(['driver', 'fleet']);

        // ۱. 🔍 فیلتر جستجوی متنی (پلاک، راننده، کدرهگیری)
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

        // ۲. 🎛️ فیلتر وضعیت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->orderByRaw("FIELD(status, 'pending', 'approved', 'issued', 'rejected', 'returned', 'renewed', 'lost') ASC");
        }

        $query->orderBy('id', 'desc');
        $requests = $query->paginate(10)->withQueryString();

        // 🟢 محاسبه زنده تعداد اصلاحیه‌ها بر اساس منطق دیتابیس شما (returned = نیاز به اصلاح)
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
                'allWorldCountries' => $allWorldCountries
            ])->with('error', 'حساب کاربری شما به هیچ شرکتی متصل نیست.');
        }

        // استخراج دقیق تعداد دوزبلاغ‌های فعال و کد آن‌ها برای "رانندگان"
        $drivers = \App\Models\Driver::where('current_company_id', $companyId)->get();
        foreach ($drivers as $driver) {
            $activeDriverPermit = \App\Models\PermitRequest::where('driver_id', $driver->id)
                ->whereIn('status', ['pending', 'approved', 'issued', 'صادر شده'])
                ->orderBy('id', 'desc')
                ->first();
            $driver->active_dozbalaghs = $activeDriverPermit ? 1 : 0;
            $driver->last_active_number = $activeDriverPermit ? $activeDriverPermit->d_code : '';
        }

        // استخراج دقیق تعداد دوزبلاغ‌های فعال و کد آن‌ها برای "ناوگان"
        $fleets = \App\Models\Fleet::where('company_id', $companyId)->get();
        foreach ($fleets as $fleet) {
            $activeFleetPermit = \App\Models\PermitRequest::where('fleet_id', $fleet->id)
                ->whereIn('status', ['pending', 'approved', 'issued', 'صادر شده'])
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

    /**
     * 🔄 لیست دوزبلاغ‌های باز و قابل تمدید شرکت (هوشمند بر اساس راننده یا ناوگان)
     */
    public function renewableList(Request $request)
    {
        try {
            $user = auth()->user();
            $companyId = optional($user->company)->id ?? $user->company_id ?? null;

            if (!$companyId) {
                return response()->json(['success' => false, 'items' => [], 'message' => 'شرکت یافت نشد'], 200);
            }

            $fleetId = $request->input('fleet_id');
            $driverId = $request->input('driver_id');

            if (empty($fleetId) && empty($driverId)) {
                return response()->json(['success' => true, 'items' => [], 'message' => 'ابتدا راننده یا ناوگان را انتخاب کنید.'], 200);
            }

            $query = DB::table('permit_requests as pr')
                ->leftJoin('drivers as d', 'pr.driver_id', '=', 'd.id')
                ->leftJoin('fleets as f', 'pr.fleet_id', '=', 'f.id')
                ->where('pr.company_id', $companyId);

            // 🟢 جلوگیری از تمدید مجدد پرونده‌هایی که لاشه شده‌اند یا خود در وضعیت اصلاحیه (returned) هستند
            $query->whereNotIn('pr.status', [
                'rejected', 
                'returned', // همان وضعیت نیاز به اصلاح جاری
                'تحویل داده شده/لاشه', 
                'lost', 
                'مفقودی', 
                'renewed', 
                'تمدیدی',
                'returned_for_review'
            ]);

            if (!empty($fleetId) && !empty($driverId)) {
                $query->where('pr.fleet_id', $fleetId)->where('pr.driver_id', $driverId);
            } elseif (!empty($fleetId)) {
                $query->where('pr.fleet_id', $fleetId);
            } elseif (!empty($driverId)) {
                $query->where('pr.driver_id', $driverId);
            }

            $rows = $query->select([
                'pr.id as permit_request_id',
                'pr.d_code',
                'pr.serial_number',
                'pr.status',
                'pr.created_at',
                'd.first_name_fa',
                'd.last_name_fa',
                'f.transit_plate',
            ])->orderByDesc('pr.id')->limit(30)->get();

            $items = $rows->map(function ($row) {
                $destinations = DB::table('permit_request_items as pri')
                    ->leftJoin('countries as c', 'pri.country_id', '=', 'c.id')
                    ->where('pri.permit_request_id', $row->permit_request_id)
                    ->select('pri.country_id', 'c.name as country_name', 'pri.permit_type', 'pri.operation_type')
                    ->get();

                $driverName = trim(($row->first_name_fa ?? '') . ' ' . ($row->last_name_fa ?? ''));
                $countryNames = $destinations->pluck('country_name')->implode('، ');
                $jalaliDate = $row->created_at ? \Hekmatinasser\Verta\Verta::instance($row->created_at)->format('Y/m/d') : '---';

                return [
                    'id' => $row->permit_request_id,
                    'd_code' => $row->d_code,
                    'serial_number' => $row->serial_number ?: 'بدون سریال',
                    'status' => $row->status,
                    'created_at' => $jalaliDate,
                    'driver' => $driverName ?: 'راننده نامشخص',
                    'fleet' => $row->transit_plate ?: 'ناوگان نامشخص',
                    'countries_text' => $countryNames ?: 'بدون مقصد',
                    'destinations' => $destinations
                ];
            })->values();

            return response()->json([
                'success' => true,
                'items' => $items,
                'message' => $items->count() ? 'دریافت شد' : 'موردی یافت نشد'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'items' => []], 200);
        }
    }

    public function store(Request $request)
    {
        if ($request->filled('receipt_amount')) {
            $request->merge(['receipt_amount' => str_replace(',', '', $request->receipt_amount)]);
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

        if ($request->input('request_type') === 'renewal') {
            $previousNumber = $request->previous_dozouleh_number;

            $previousPermitExists = DB::table('permit_request_items as pri')
                ->join('permit_requests as pr', 'pri.permit_request_id', '=', 'pr.id')
                ->where('pr.company_id', $companyId)
                ->where(function ($q) use ($previousNumber) {
                    $q->where('pri.d_serial_number', $previousNumber)
                      ->orWhere('pr.d_code', $previousNumber)
                      ->orWhere('pr.serial_number', $previousNumber);
                })
                ->exists();

            if (!$previousPermitExists) {
                return back()->withErrors(['previous_dozouleh_number' => 'دوزبلاغ مرجع انتخاب‌شده معتبر نیست یا متعلق به شرکت شما نیست.'])->withInput();
            }
        }

        if ($request->input('request_type') !== 'renewal') {
            $activePermitCheck = PermitRequest::where('fleet_id', $request->fleet_id)
                ->whereIn('status', ['pending', 'approved', 'issued'])
                ->exists();

            if ($activePermitCheck) {
                return back()->withErrors(['error' => 'این ناوگان در حال حاضر یک درخواست فعال، تایید شده یا صادر شده در سیستم دارد.']);
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

        DB::beginTransaction();
        try {
            $permitRequest = PermitRequest::create([
                'd_code'         => $this->generateDCode(),
                'company_id'     => $companyId,
                'driver_id'      => $request->driver_id,
                'fleet_id'       => $request->fleet_id,
                'status'         => 'pending',
                'payment_status' => 'reserved',
                'total_amount'   => $totalRequestAmount,
                'company_note'   => $request->input('request_type') === 'renewal' ? 'تمدید مجوز شماره: ' . $request->previous_dozouleh_number : 'ثبت درخواست جدید',
            ]);

            foreach ($request->destinations as $item) {
                $countryData = Country::findOrFail($item['country_id']);
                DB::table('permit_request_items')->insert([
                    'permit_request_id'   => $permitRequest->id,
                    'country_id'          => $item['country_id'],
                    'permit_type'         => $item['permit_type'],
                    'operation_type'      => $request->cargo_type ?? '', 
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
        $requestType = $request->input('request_type'); 
        $destinations = $request->input('destinations', []);

        if (!$fleetId) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه ناوگان ارسال نشده است.'
            ], 200);
        }

        try {
            $fleet = Fleet::find($fleetId);
            
            if (!$fleet) {
                return response()->json([
                    'success' => false,
                    'message' => 'ناوگان مورد نظر در دیتابیس یافت نشد.'
                ], 200);
            }

            if ($requestType !== 'renewal') {
                $activeFleetPermit = PermitRequest::where('fleet_id', $fleetId)
                    ->whereIn('status', ['pending', 'approved', 'issued'])
                    ->first();

                if ($activeFleetPermit) {
                    return response()->json([
                        'success' => false,
                        'message' => "این ناوگان یک درخواست فعال یا پروانه صادر شده به شماره رهگیری {$activeFleetPermit->d_code} دارد و امکان ثبت درخواست جدید برای آن وجود ندارد."
                    ], 200);
                }

                if ($driverId) {
                    $activeDriverPermit = PermitRequest::where('driver_id', $driverId)
                        ->whereIn('status', ['pending', 'approved', 'issued'])
                        ->first();

                    if ($activeDriverPermit) {
                        return response()->json([
                            'success' => false,
                            'message' => "راننده انتخاب شده یک درخواست فعال یا پروانه صادر شده به شماره رهگیری {$activeDriverPermit->d_code} دارد."
                        ], 200);
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
            return response()->json([
                'success' => false,
                'message' => 'خطای داخلی سرور: ' . $e->getMessage()
            ], 200);
        }
    }
	/**
     * 🛠️ نمایش فرم ویرایش و اصلاح پروانه‌های برگشت‌خورده
     */
    public function edit($id)
    {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        // واکشی پرونده با شرط اینکه متعلق به همین شرکت باشد و وضعیت آن returned (نیاز به اصلاح) باشد
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

        // واکشی مقاصد قبلی ثبت شده برای این درخواست جهت Auto-fill شدن در فرم
        $previousDestinations = DB::table('permit_request_items')
            ->where('permit_request_id', $id)
            ->get()
            ->map(function($item) {
                return [
                    'country_id' => $item->country_id,
                    'permit_type' => $item->permit_type,
                ];
            });

        // باز کردن همان فرم ویزارد create اما در حالت ویرایش (با پاس دادن اطلاعات قبلی)
        return view('company.dozbalagh.create', compact(
            'permitRequest', 'drivers', 'fleets', 'countries', 
            'cargoRules', 'wallet', 'balance', 'allWorldCountries', 'previousDestinations'
        ));
    }

    /**
     * 💾 ذخیره و ارسال مجدد پرونده اصلاح‌شده به کارتابل ادمین
     */
    public function updateRequest(Request $request, $id)
    {
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

        DB::beginTransaction();
        try {
            // ۱. پاک کردن مقاصد قبلی جهت ثبت مقاصد جدید اصلاح شده
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
                    'price'               => $countryData->price ?? 0,
                    'allocation_status'   => 'pending',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            // موجودی قبلاً کسر و بلوکه شده است، اگر مبالغ مقاصد جدید تغییر کرده تفاضل را اعمال می‌کنیم
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

            // ۲. تغییر مجدد وضعیت به pending جهت بازگشت به کارتابل بررسی ادمین
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
}