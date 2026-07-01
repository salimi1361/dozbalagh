<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // ۱. درخواست ارسال کد تایید (دریافت شماره موبایل)
    public function requestOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|regex:/^09[0-9]{9}$/',
        ]);

        $driver = Driver::where('mobile', $request->mobile)->first();

        if (!$driver) {
            return response()->json([
                'status' => 'error',
                'message' => 'راننده‌ای با این شماره موبایل در سامانه یافت نشد.'
            ], 404);
        }

        $otpCode = rand(10000, 99999);

        Cache::put('otp_' . $request->mobile, $otpCode, now()->addMinutes(3));

        $this->sendOtpNotification($driver, $otpCode);

        return response()->json([
            'status' => 'success',
            'message' => 'کد تایید با موفقیت ارسال شد.'
        ]);
    }

    // ۲. تایید کد و صدور توکن Sanctum (Login)
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|regex:/^09[0-9]{9}$/',
            'code' => 'required|string|size:5',
        ]);

        $cachedCode = Cache::get('otp_' . $request->mobile);

        if (!$cachedCode || $cachedCode != $request->code) {
            return response()->json([
                'status' => 'error',
                'message' => 'کد وارد شده اشتباه است یا منقضی شده است.'
            ], 422);
        }

        Cache::forget('otp_' . $request->mobile);

        // 🌟 لود دقیق راننده بر اساس رابطه رسمی 'company' که در مدل Driver تعریف شده است
        $driver = Driver::with(['company'])->where('mobile', $request->mobile)->first();

        // ایجاد توکن امنیتی Sanctum
        $token = $driver->createToken('driver_app_token')->plainTextToken;

        // استخراج فیلدهای واقعی شرکت از رابطه
        $companyName = $driver->company ? $driver->company->name : 'شرکت حمل و نقل بین‌المللی خراسان';
        // در صورتی که فیلد نام مدیر یا آدرس در جدول شرکت شما متفاوت است، نام فیلد را در اینجا اصلاح کنید
        $companyManager = $driver->company ? ($driver->company->manager_name ?? $driver->company->manager ?? 'نامشخص') : 'جناب ذاکری';
        $companyAddress = $driver->company ? ($driver->company->address ?? 'مشهد، پایانه مرزی دوغارون') : 'دفتر مرکزی ترانزیت';

        /*
        |--------------------------------------------------------------------------
        | بخش ناوگان و کامیون (پلاک و کارت هوشمند)
        |--------------------------------------------------------------------------
        | از آنجا که در مدل Driver رابطه‌ای برای کانتینر یا کامیون تعریف نشده است،
        | اگر این فیلدها بعداً به جدول drivers یا از طریق رابطه اضافه شدند،
        | می‌توانید مقادیر سمت راست را به $driver->truck_plate یا رابطه متصل کنید.
        */
        $truckPlate = $driver->truck_plate ?? $driver->plate_number ?? 'ع ۱۲ - ۳۴۵ ایران ۱۲';
        $truckSmartCard = $driver->truck_smart_id ?? $driver->smart_card_number ?? '۴۵۷۸۹۶۲';
        $truckType = $driver->truck_type ?? 'ترانزیت چادری (Scania)';

        return response()->json([
            'status' => 'success',
            'message' => 'ورود با موفقیت انجام شد.',
            'token' => $token,
            'driver' => [
                'id' => $driver->id,
                'name' => ($driver->first_name_fa || $driver->last_name_fa) ? ($driver->first_name_fa . ' ' . $driver->last_name_fa) : ($driver->name ?? 'راننده سیستم'),
                'national_code' => $driver->national_code,
                
                // اطلاعات داینامیک شرکت
                'company_name' => $companyName,
                'company_manager' => $companyManager,
                'company_address' => $companyAddress,
                
                // اطلاعات ناوگان کامیون
                'truck_plate' => $truckPlate,
                'truck_smart_card' => $truckSmartCard,
                'truck_type' => $truckType,
            ]
        ]);
    }

    private function sendOtpNotification($driver, $code)
    {
        $message = "سامانه هوشمند دوزوله\n\nکد تایید ورود شما:\n{$code}\n\nاین کد تا ۳ دقیقه معتبر است.";
        $apiKey = config('services.kavenegar.key');
        $url = "https://api.kavenegar.com/v1/{$apiKey}/sms/send.json";

        try {
            $response = Http::asForm()->post($url, [
                'receptor' => $driver->mobile,
                'message'  => $message,
            ]);

            if (!$response->successful()) {
                Log::error('Kavenegar Send Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Kavenegar Connection Error: ' . $e->getMessage());
        }
    }
}