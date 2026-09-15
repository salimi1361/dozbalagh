<?php

namespace App\Http\Controllers\Api\Driver;

use App\Exceptions\DriverDeviceBindingException;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Services\DriverDeviceBindingService;
use App\Services\DriverIdentityService;
use App\Services\DriverProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private readonly DriverIdentityService $driverIdentity,
        private readonly DriverDeviceBindingService $deviceBinding,
        private readonly DriverProfileService $driverProfile,
    ) {}

    // ۱. درخواست ارسال کد تایید (دریافت شماره موبایل)
    public function requestOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|regex:/^09[0-9]{9}$/',
        ]);

        $driver = $this->driverIdentity->resolveActiveByMobile($request->mobile);

        if (!$driver) {
            return response()->json([
                'status' => 'error',
                'message' => 'راننده‌ای با این شماره موبایل در سامانه یافت نشد.'
            ], 404);
        }

        if (! $driver->isActive()) {
            return response()->json([
                'status' => 'error',
                'message' => 'دسترسی این راننده توسط انجمن غیرفعال شده است. برای پیگیری با انجمن تماس بگیرید.',
            ], 403);
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
            'device_uuid' => 'required|string|max:191',
            'platform' => 'required|in:android,ios',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:255',
            'sdk_version' => 'nullable|integer|min:1|max:999',
            'app_version' => 'nullable|string|max:50',
            'app_build' => 'nullable|string|max:50',
            'app_identifier' => 'nullable|string|max:255',
            'locale' => 'nullable|string|max:20',
        ]);

        $cachedCode = Cache::get('otp_' . $request->mobile);

        if (!$cachedCode || $cachedCode != $request->code) {
            return response()->json([
                'status' => 'error',
                'message' => 'کد وارد شده اشتباه است یا منقضی شده است.'
            ], 422);
        }

        Cache::forget('otp_' . $request->mobile);

        // راننده باید فقط از روی شماره موبایل یکتا انتخاب شود؛ انتخاب اولین رکورد
        // در صورت وجود شماره تکراری می‌تواند نشست راننده دیگری را ایجاد کند.
        $driver = $this->driverIdentity->resolveActiveByMobile($request->mobile);
        if (! $driver || ! $driver->isActive()) {
            return response()->json([
                'status' => 'error',
                'message' => 'دسترسی این راننده توسط انجمن غیرفعال شده است. برای پیگیری با انجمن تماس بگیرید.',
            ], 403);
        }

        try {
            $this->deviceBinding->bind(
                $driver,
                $request->only([
                    'device_uuid',
                    'platform',
                    'manufacturer',
                    'model',
                    'device_name',
                    'os_version',
                    'sdk_version',
                    'app_version',
                    'app_build',
                    'app_identifier',
                    'locale',
                ]),
                $request->ip(),
                allowRevokedRebind: true,
            );
        } catch (DriverDeviceBindingException $exception) {
            $isRevoked = $exception->reason === DriverDeviceBindingException::REVOKED_DEVICE;

            return response()->json([
                'status' => 'error',
                'error_code' => $isRevoked ? 'DEVICE_ACCESS_REVOKED' : 'DEVICE_ALREADY_BOUND',
                'message' => $isRevoked
                    ? 'دسترسی این گوشی قبلاً لغو شده است. لطفاً با انجمن تماس بگیرید.'
                    : 'این حساب قبلاً روی گوشی دیگری فعال شده است. لطفاً با گوشی اصلی وارد شوید.',
            ], 409);
        }

        // ایجاد توکن امنیتی Sanctum
        $token = $driver->createToken('driver_app_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'ورود با موفقیت انجام شد.',
            'token' => $token,
            'driver' => $this->driverData($driver),
        ]);
    }

    public function profile()
    {
        $authenticatedDriver = Driver::with('company')->findOrFail(auth()->id());
        $driver = $this->driverIdentity->canonicalFor($authenticatedDriver);

        if ($driver && (int) $driver->id !== (int) $authenticatedDriver->id) {
            // An old token may still point to a duplicate row. Force a fresh
            // mobile login instead of exposing the stale identity.
            $authenticatedDriver->tokens()->delete();

            return response()->json([
                'status' => 'error',
                'message' => 'شناسه راننده تغییر کرده است؛ لطفاً دوباره وارد شوید.',
            ], 409);
        }

        return response()->json([
            'status' => 'success',
            'driver' => $this->driverData($driver),
        ]);
    }

    private function driverData(Driver $driver): array
    {
        return $this->driverProfile->data($driver);
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
