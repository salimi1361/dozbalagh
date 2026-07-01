<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Throwable;

class DriverController extends Controller
{
    // ۱. لیست رانندگان شرکت به همراه تعداد دوزبلاغ‌های فعال
    public function index()
    {
        // 🟢 خواندن امن آیدی شرکت از کاربری که لاگین کرده است
        $companyId = auth()->user()->company->id;
        
        $drivers = DB::table('drivers')
                     ->select('drivers.*')
                     ->addSelect([
                         'active_dozbalaghs' => DB::table('permit_requests') 
                                                 ->whereColumn('permit_requests.driver_id', 'drivers.id') 
                                                 ->where('permit_requests.status', 'صادر شده') 
                                                 ->selectRaw('count(*)')
                     ])
                     ->where('current_company_id', $companyId) // فیلتر کردن دقیق بر اساس شرکت فعلی
                     ->orderBy('id', 'desc')
                     ->get();

        return view('company.driver.index', compact('drivers'));
    }

    // ۲. استعلام زنده راننده با قابلیت رمزگشایی خطای ۴۰۰ بارگرام
    public function inquireApi(Request $request)
    {
        try {
            $nationalId = $request->national_code ?? $request->national_id; 
            $mobileNumber = $request->mobile ?? $request->phone ?? $request->mobile_number;

            if (empty($nationalId)) {
                return response()->json(['success' => false, 'message' => 'وارد کردن کد ملی برای استعلام الزامی است.'], 400);
            }

            // گام اول: بررسی استعلام آفلاین از دیتابیس خودمان
            $localDriver = DB::table('drivers')->where('national_code', $nationalId)->first();
            
            if ($localDriver && !empty($localDriver->first_name_fa) && !empty($localDriver->last_name_fa)) {
                $mockApiData = [
                    'driver' => [
                        'NAME' => $localDriver->first_name_fa,
                        'FAMILY' => $localDriver->last_name_fa
                    ]
                ];
                
                return response()->json([
                    'success' => true,
                    'data'    => $mockApiData,
                    'message' => 'اطلاعات راننده به صورت آفلاین از بانک اطلاعاتی سیستم بازیابی شد.'
                ]);
            }

            // توکن اختصاصی بارگرام
            $token = "eyJpdiI6IkhrVE8vVllaWUtHNE9Pb08xLzRSQ1E9PSIsInZhbHVlIjoiSEZSeC9URTVuc01Uc0FYKzBwWWIxTEhTc21ka0tENXFvR2ZYOUJFaFhkZVBHY0RyZ2ZSWkk2OGFOa1V2Y0lUM3NUV3dobmhwdFQzUlRLY2xLRTVnYlpHVE0wRGVuaGc4UzAxOUczQ2x2d0E9IiwibWFjIjoiZGRjMDYzMDBhZDVhY2M5ZmU0NDJmNmM0NDRhN2YxYTBlYjlhMzIyYmM1MWQyMDAzODY1OTBjYTg2OGIzN2VmMCIsInRhZyI6IiJ9";

            // ارسال درخواست آنلاین به بارگرام
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->post('https://api.bargram.ir/api/v2/ebl/driver_inquiry_mn', [
                    'national_id'   => $nationalId,
                    'mobile_number' => $mobileNumber ?? '',
                    'token'         => $token
                ]);

            if ($response->successful()) {
                $apiData = $response->json();
                
                if (isset($apiData['data']) && is_string($apiData['data'])) {
                    $apiData['data'] = json_decode($apiData['data'], true);
                }
                
                return response()->json([
                    'success' => true,
                    'data'    => $apiData,
                    'message' => 'اطلاعات با موفقیت از سازمان راهداری استعلام شد.'
                ]);
            }

            // 🛠️ فیکس ریشه‌ای خطای 400: خواندن دلیل واقعی از بدنه پاسخ سرور بارگرام
            $apiError = $response->json();
            $errorMessage = $apiError['message'] ?? $apiError['error'] ?? $apiError['data']['message'] ?? 'پارامترهای ارسالی (کد ملی، موبایل یا توکن) از نظر بارگرام معتبر نیست.';

            return response()->json([
                'success' => false,
                'message' => 'پاسخ بارگرام: ' . $errorMessage
            ], 400);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارتباط با سرور استعلام بارگرام: ' . $e->getMessage()
            ], 500);
        }
    }

    // ۳. ثبت و ذخیره هوشمند (نسخه سازگار با AJAX)
    public function store(Request $request)
    {
        try {
            // 🟢 گرفتن آیدی شرکت به صورت کاملاً امن
            $companyId = auth()->user()->company->id ?? auth()->user()->company_id;
            $nationalCode = $request->national_code ?? $request->national_id;
            $mobile = $request->mobile ?? $request->phone;

            if (empty($nationalCode)) {
                return response()->json(['success' => false, 'message' => 'وارد کردن کد ملی الزامی است.'], 400);
            }

            $isEdit = $request->input('is_edit', 0);
            if (!$isEdit) {
                $existingActive = DB::table('drivers')
                                    ->where('national_code', $nationalCode)
                                    ->where('current_company_id', $companyId)
                                    ->first();
                                    
                if ($existingActive) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'خطا: راننده‌ای با این کد ملی در حال حاضر در کارتابل شما فعال است.'
                    ], 400);
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // 🟢 [بخش جدید]: بررسی اینکه آیا این راننده از قبل حساب کاربری دارد یا نه
            $user = DB::table('users')->where('username', $nationalCode)->first();
            
            if ($user) {
                $userId = $user->id;
            } else {
                // اگر حساب نداشت، یکی برایش می‌سازیم
                // توجه: اگر در جدول roles نقشی با شناسه 3 (راننده) نداری، ممکن است اینجا ارور بدهد
                // در این صورت باید یک نقش با id=3 در دیتابیس (جدول roles) بسازی
                $userId = DB::table('users')->insertGetId([
                    'role_id'   => 3, 
                    'username'  => $nationalCode,
                    'password'  => Hash::make($mobile ?? $nationalCode),
                    'mobile'    => $mobile,
                    'status'    => 'active',
                    'is_manual' => 1,
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]);
            }

            DB::table('drivers')->updateOrInsert(
                ['national_code' => $nationalCode],
                [
                    'user_id'            => $userId, // 🟢 اضافه شدن کلید کاربری به دیتابیس
                    'current_company_id' => $companyId,
                    'mobile'             => $mobile,
                    'first_name_fa'      => $request->first_name_fa ?? $request->first_name,
                    'last_name_fa'       => $request->last_name_fa ?? $request->last_name,
                    'first_name_en'      => $request->first_name_en ?? $request->en_first_name,
                    'last_name_en'       => $request->last_name_en ?? $request->en_last_name,
                    'passport_number'    => $request->passport_number,
                    'contract_status'    => 'free',
                    'updated_at'         => now(),
                    'created_at'         => DB::raw('COALESCE(created_at, NOW())')
                ]
            );

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // 🟢 بازگشت خروجی JSON برای جاوااسکریپت (SweetAlert)
            return response()->json([
                'success' => true, 
                'message' => 'اطلاعات راننده با موفقیت در سیستم ثبت و به کارتابل شما اضافه شد.'
            ]);

        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return response()->json(['success' => false, 'message' => 'خطا در ثبت دیتابیس: ' . $e->getMessage()], 500);
        }
    }

    // ۴. حذف راننده از شرکت
    public function destroy(Request $request)
    {
        try {
            $nationalCode = $request->national_code ?? $request->national_id;

            $driver = DB::table('drivers')->where('national_code', $nationalCode)->first();
            if (!$driver) {
                return response()->json(['success' => false, 'message' => 'راننده یافت نشد.'], 404);
            }

            $activeCount = DB::table('permit_requests')
                             ->where('driver_id', $driver->id)
                             ->where('status', 'صادر شده')
                             ->count();

            if ($activeCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "❌ خطای سرور: این راننده دارای {$activeCount} پروانه دوزبلاغ فعال است و امکان حذف او وجود ندارد."
                ], 400);
            }

            DB::table('drivers')
                ->where('national_code', $nationalCode)
                ->update(['current_company_id' => null]);

            return response()->json(['success' => true, 'message' => 'راننده با موفقیت از کارتابل شرکت شما آزاد شد.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}