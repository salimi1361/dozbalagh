<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class FleetController extends Controller 
{
    public function index() {
        // 🟢 خواندن قطعی و امن آیدی شرکت
        $user = auth()->user();
        $companyId = optional($user->company)->id ?? $user->company_id ?? null;

        if (!$companyId) {
            return view('company.fleet.index', ['fleets' => []])->with('error', 'شما به هیچ شرکتی متصل نیستید.');
        }

        $fleets = DB::table('fleets')
                    ->where('company_id', $companyId)
                    ->orderBy('id', 'desc')
                    ->get();
        
        foreach ($fleets as $fleet) {
            // 🟢 [جدید] تزریق فیلدهای ترانزیت فرضی برای جلوگیری از خطای وب‌پیج
            if (!isset($fleet->transit_horse)) {
                $fleet->transit_horse = ''; 
            }
            if (!isset($fleet->transit_trailer)) {
                $fleet->transit_trailer = '';
            }

            try {
                // شمارش تعداد دقیق دوزبلاغ‌های فعال برای این پلاک
                $fleet->active_dozbalagh_count = DB::table('permit_requests') 
                    ->where('fleet_id', $fleet->id)
                    ->where('status', 'صادر شده')
                    ->count();
            } catch (\Exception $e) {
                $fleet->active_dozbalagh_count = 0;
            }
        }

        return view('company.fleet.index', compact('fleets'));
    }

    public function inquireApi(Request $request) {
        try {
            $smartId = $request->input('smart_id');
            if (empty($smartId)) return response()->json(['success' => false, 'message' => 'کارت هوشمند الزامی است.'], 400);

            $local = DB::table('fleets')->where('smart_card_number', $smartId)->first();
            if ($local) return response()->json(['success' => true, 'data' => $local]);

            $token = "eyJpdiI6IkhrVE8vVllaWUtHNE9Pb08xLzRSQ1E9PSIsInZhbHVlIjoiSEZSeC9URTVuc01Uc0FYKzBwWWIxTEhTc21ka0tENXFvR2ZYOUJFaFhkZVBHY0RyZ2ZSWkk2OGFOa1V2Y0lUM3NUV3dobmhwdFQzUlRLY2xLRTVnYlpHVE0wRGVuaGc4UzAxOUczQ2x2d0E9IiwibWFjIjoiZGRjMDYzMDBhZDVhY2M5ZmU0NDJmNmM0NDRhN2YxYTBlYjlhMzIyYmM1MWQyMDAzODY1OTBjYTg2OGIzN2VmMCIsInRhZyI6IiJ9";

            $response = Http::withoutVerifying()
                ->timeout(30)
                ->post('https://api.bargram.ir/api/v2/ebl/car_inquiry', [
                    'smart_id' => $smartId,
                    'token' => $token
                ]);

            if ($response->successful()) {
                return response()->json(['success' => true, 'data' => $response->json()]);
            }
            
            return response()->json([
                'success' => false, 
                'message' => 'پاسخ راهداری (کد ' . $response->status() . '): ' . $response->body()
            ]);

        } catch (Throwable $e) {
            $err = $e->getMessage();
            if (str_contains($err, 'cURL error 28')) {
                return response()->json(['success' => false, 'message' => 'سرور راهداری شلوغ است (تایم‌اوت). دستی وارد کنید.']);
            }
            return response()->json(['success' => false, 'message' => 'خطای شبکه/سیستمی: ' . $err]);
        }
    }

    public function store(Request $request) {
        try {
            $user = auth()->user();
            $companyId = optional($user->company)->id ?? $user->company_id ?? null;
            
            if (!$companyId) {
                return response()->json(['success' => false, 'message' => 'حساب کاربری شما به هیچ شرکتی متصل نیست.'], 400);
            }

            $transitPlate = $request->transit_plate;
            
            if (empty($transitPlate)) {
                return response()->json(['success' => false, 'message' => 'پلاک ترانزیت الزامی است.'], 400);
            }

            // بررسی جلوگیری از تداخل (اگر ناوگان دست شرکت دیگری باشد)
            $isEdit = $request->input('is_edit', 0);
            if (!$isEdit) {
                $existingFleet = DB::table('fleets')->where('transit_plate', $transitPlate)->first();
                if ($existingFleet && $existingFleet->company_id != null && $existingFleet->company_id != $companyId) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'خطا: این ناوگان در حال حاضر در کارتابل یک شرکت دیگر فعال است.'
                    ], 400);
                }
            }

            // 🟢 ثبت قطعی و کامل اطلاعات فرم به همراه شماره ترانزیت اسب و یدک
            DB::table('fleets')->updateOrInsert(
                ['transit_plate' => $transitPlate], 
                [
                    'company_id'        => $companyId,
                    'smart_card_number' => $request->smart_id,
                    'truck_type'        => $request->loading_type,
                    'transit_horse'     => $request->transit_horse, // 🟢 ذخیره مستقیم
                    'transit_trailer'   => $request->transit_trailer, // 🟢 ذخیره مستقیم
                    'updated_at'        => now(),
                    'created_at'        => DB::raw('COALESCE(created_at, NOW())')
                ]
            );
            
            return response()->json(['success' => true, 'message' => 'اطلاعات ناوگان با موفقیت در کارتابل شرکت شما ذخیره شد.']);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false, 
                'message' => 'خطای سرور: ' . $e->getMessage() . ' در فایل ' . $e->getFile() . ' خط ' . $e->getLine()
            ], 500);
        }
    }

    public function release(Request $request) {
        try {
            $activeCount = 0;
            
            try {
                $activeCount = DB::table('permit_requests') 
                    ->where('fleet_id', function($query) use ($request) {
                        $query->select('id')->from('fleets')->where('transit_plate', $request->transit_plate)->limit(1);
                    })
                    ->where('status', 'صادر شده')
                    ->count();
            } catch (\Exception $e) {}

            if ($activeCount > 0) {
                return response()->json(['success' => false, 'message' => "این ناوگان در حال حاضر {$activeCount} دوزبلاغ فعال دارد و آزادسازی آن غیرمجاز است."], 403);
            }

            DB::table('fleets')->where('transit_plate', $request->transit_plate)->update(['company_id' => null]);
            return response()->json(['success' => true, 'message' => 'ناوگان از شرکت شما آزاد شد و در سامانه مرکزی باقی ماند.']);
            
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'خطای سرور: ' . $e->getMessage()], 500);
        }
    }
}