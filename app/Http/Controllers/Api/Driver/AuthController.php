<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\PermitRequest;
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

        $this->sendOtpNotification($driver, $otpCode, $request->getHost());

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
        $latestPermitFleet = PermitRequest::with('fleet')
            ->where('driver_id', $driver->id)
            ->whereHas('fleet')
            ->orderByDesc('id')
            ->first()?->fleet;

        // ایجاد توکن امنیتی Sanctum
        $token = $driver->createToken('driver_app_token')->plainTextToken;

        // استخراج فیلدهای واقعی شرکت از رابطه
        $companyName = $driver->company
            ? ($driver->company->name_fa ?: $driver->company->name)
            : 'شرکت حمل و نقل بین‌المللی خراسان';
        $companyManager = $driver->company
            ? ($driver->company->ceo_name ?: 'ثبت نشده')
            : 'ثبت نشده';
        $companyAddress = $driver->company
            ? ($driver->company->address_fa ?: $driver->company->address_en ?: 'ثبت نشده')
            : 'ثبت نشده';

        /*
        |--------------------------------------------------------------------------
        | بخش ناوگان و کامیون (پلاک و کارت هوشمند)
        |--------------------------------------------------------------------------
        | از آنجا که در مدل Driver رابطه‌ای برای کانتینر یا کامیون تعریف نشده است،
        | اگر این فیلدها بعداً به جدول drivers یا از طریق رابطه اضافه شدند،
        | می‌توانید مقادیر سمت راست را به $driver->truck_plate یا رابطه متصل کنید.
        */
        $truckPlate = $latestPermitFleet?->transit_plate ?? $driver->truck_plate ?? $driver->plate_number ?? null;
        $truckSmartCard = $latestPermitFleet?->smart_card_number ?? $driver->truck_smart_id ?? $driver->smart_card_number ?? null;
        $truckType = $latestPermitFleet?->truck_type ?? $driver->truck_type ?? null;

        return response()->json([
            'status' => 'success',
            'message' => 'ورود با موفقیت انجام شد.',
            'token' => $token,
            'driver' => [
                'id' => $driver->id,
                'name' => ($driver->first_name_fa || $driver->last_name_fa) ? ($driver->first_name_fa . ' ' . $driver->last_name_fa) : ($driver->name ?? 'راننده سیستم'),
                'national_code' => $driver->national_code,
                'mobile' => $driver->mobile,
                
                // اطلاعات داینامیک شرکت
                'company_name' => $companyName,
                'company_manager' => $companyManager,
                'company_address' => $companyAddress,
                'company_phone' => $driver->company?->phone,
                'company_ceo_mobile' => $driver->company?->ceo_mobile,
                
                // اطلاعات ناوگان کامیون
                'truck_plate' => $truckPlate,
                'truck_smart_card' => $truckSmartCard,
                'truck_type' => $truckType,
                'fleet_source' => $latestPermitFleet ? 'dozoleh' : 'driver',
            ]
        ]);
    }

    private function sendOtpNotification($driver, $code, ?string $host = null)
    {
        $webOtpHost = $this->webOtpHost($host);
        $webOtpLine = $webOtpHost ? "\n\n@{$webOtpHost} #{$code}" : '';
        $message = "سامانه هوشمند دوزوله\n\nکد تایید ورود شما:\n{$code}\n\nاین کد تا ۳ دقیقه معتبر است.{$webOtpLine}";
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

    private function webOtpHost(?string $requestHost): ?string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: $requestHost;
        $host = preg_replace('/:\d+$/', '', (string) $host);

        if (!$host || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return null;
        }

        return $host;
    }
}
