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

        return view('company.dozbalagh.index', compact('requests'));
    }

    public function create()
    {
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;
        $allWorldCountries = WorldCountry::orderBy('name_fa', 'asc')->get();

        if (empty($companyId) || $companyId == 0) {
            return view('company.dozbalagh.create', [
                'drivers' => collect(), 
                'fleets' => collect(), 
                'countries' => Country::where('is_active', 1)->get(), 
                'cargoRules' => collect(),
                'balance' => 0,
                'wallet' => null,
                'newDCode' => '',
                'allWorldCountries' => $allWorldCountries
            ])->with('error', 'حساب کاربری شما به هیچ شرکتی متصل نیست.');
        }

        $drivers = Driver::where('current_company_id', $companyId)->get();
        $fleets = Fleet::where('company_id', $companyId)->get();
        $countries = Country::where('is_active', 1)->get(); 
        $cargoRules = CargoDocumentRule::all()->keyBy('cargo_type');
        
        $wallet = Wallet::firstOrCreate(
            ['company_id' => $companyId],
            ['balance' => 50000000, 'blocked_balance' => 0] 
        );
        
        $newDCode = $this->generateDCode(); 
        $balance = $wallet->balance;

        return view('company.dozbalagh.create', compact('drivers', 'fleets', 'countries', 'cargoRules', 'wallet', 'newDCode', 'balance', 'allWorldCountries'));
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
            'cargo_type' => 'required|string',
            'destinations' => 'required|array|min:1',
        ];

        $request->validate($validationRules);

        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        // 🛑 لایه امنیتی هاردکد بک‌اند برای جلوگیری از ثبت دوزبلاغ همزمان (به جز درخواست‌های تمدید)
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

    /**
     * 🟢 متد استعلام ناوگان و راننده - اصلاح شده و کاملاً یکپارچه برای نمایش کدهای انبار هر کشور
     */
    public function checkFleetStatus(Request $request)
    {
        $fleetId = $request->input('fleet_id') ?? $request->input('id');
        $driverId = $request->input('driver_id');
        $destinations = $request->input('destinations', []); // دریافت داینامیک کشورهای انتخاب شده

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

            // ۱. بررسی دوزبلاغ فعال ناوگان
            $activeFleetPermit = PermitRequest::where('fleet_id', $fleetId)
                ->whereIn('status', ['pending', 'approved', 'issued'])
                ->first();

            if ($activeFleetPermit) {
                return response()->json([
                    'success' => false,
                    'message' => "این ناوگان یک درخواست فعال یا پروانه صادر شده به شماره رهگیری {$activeFleetPermit->d_code} دارد و امکان ثبت درخواست جدید برای آن وجود ندارد."
                ], 200);
            }

            // ۲. بررسی دوزبلاغ فعال راننده
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

            // ۳. ⚡ محاسبه و یافتن اولین سریال آزاد انبار پارت‌ها به تفکیک کشورهای انتخابی (مثل ترکیه)
            $allocationCodes = [];
            $usedSerials = PermitRequest::whereNotNull('serial_number')->pluck('serial_number')->toArray();

            foreach ($destinations as $dest) {
                $countryId = $dest['country_id'] ?? null;
                if (!$countryId) continue;

                // واکشی پارت‌های انبار مرکزی بر اساس کدهایی که فرستادی
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
                    'allocation_codes' => $allocationCodes // ارسال کدهای اختصاصی انبار هر کشور
                ]
            ]);

        } catch (\Throwable $e) { 
            return response()->json([
                'success' => false,
                'message' => 'خطای داخلی سرور: ' . $e->getMessage()
            ], 200);
        }
    }
}