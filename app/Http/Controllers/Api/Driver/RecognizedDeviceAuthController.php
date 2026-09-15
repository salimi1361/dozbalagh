<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\MobileAppInstallation;
use App\Services\DriverIdentityService;
use App\Services\DriverProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RecognizedDeviceAuthController extends Controller
{
    public function __construct(
        private readonly DriverIdentityService $driverIdentity,
        private readonly DriverProfileService $driverProfile,
    ) {}

    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'device_uuid' => ['required', 'string', 'max:191'],
            'app_identifier' => ['nullable', 'string', 'max:255'],
        ]);

        $driver = $this->driverIdentity->resolveActiveByMobile($data['mobile']);
        if (! $driver) {
            return response()->json(['status' => 'error', 'message' => 'راننده‌ای با این شماره موبایل در سامانه یافت نشد.'], 404);
        }
        if (! $driver->isActive()) {
            return response()->json(['status' => 'error', 'message' => 'دسترسی این راننده توسط انجمن غیرفعال شده است.'], 403);
        }

        $activeInstallation = $this->activeInstallation($driver->id);
        if ($activeInstallation && (string) $activeInstallation->device_uuid !== $data['device_uuid']) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'DEVICE_ALREADY_BOUND',
                'message' => 'این حساب قبلاً روی گوشی دیگری فعال شده است. لطفاً با گوشی اصلی وارد شوید.',
            ], 409);
        }

        $installation = $this->recognizedInstallation($driver->id, $data['device_uuid']);

        return response()->json([
            'status' => 'success',
            'recognized' => $installation !== null && filled($installation->device_pin_hash),
            'login_method' => $installation !== null && filled($installation->device_pin_hash)
                ? 'device_pin'
                : 'otp',
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'device_uuid' => ['required', 'string', 'max:191'],
            'pin' => ['required', 'string', 'regex:/^[0-9]{4,6}$/'],
        ]);

        $driver = $this->driverIdentity->resolveActiveByMobile($data['mobile']);
        if (! $driver) {
            return response()->json(['status' => 'error', 'message' => 'راننده‌ای با این شماره موبایل در سامانه یافت نشد.'], 404);
        }
        if (! $driver->isActive()) {
            return response()->json(['status' => 'error', 'message' => 'دسترسی این راننده توسط انجمن غیرفعال شده است.'], 403);
        }

        $activeInstallation = $this->activeInstallation($driver->id);
        if ($activeInstallation && (string) $activeInstallation->device_uuid !== $data['device_uuid']) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'DEVICE_ALREADY_BOUND',
                'message' => 'این حساب قبلاً روی گوشی دیگری فعال شده است. لطفاً با گوشی اصلی وارد شوید.',
            ], 409);
        }

        $installation = $this->recognizedInstallation($driver->id, $data['device_uuid']);
        if (! $installation || ! $installation->device_pin_hash || ! Hash::check($data['pin'], $installation->device_pin_hash)) {
            return response()->json([
                'status' => 'error',
                'message' => 'رمز ورود صحیح نیست. در صورت فراموشی، ورود با کد یک‌بارمصرف را انتخاب کنید.',
            ], 422);
        }

        $installation->forceFill([
            'last_ip' => $request->ip(),
            'last_seen_at' => now(),
        ])->save();

        return $this->sessionResponse($driver);
    }

    public function setPin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_uuid' => ['required', 'string', 'max:191'],
            'pin' => ['required', 'string', 'regex:/^[0-9]{4,6}$/'],
        ]);

        $installation = MobileAppInstallation::query()
            ->where('driver_id', $request->user()->getKey())
            ->where('device_uuid', $data['device_uuid'])
            ->whereNull('revoked_at')
            ->first();

        if (! $installation) {
            return response()->json([
                'status' => 'error',
                'message' => 'این دستگاه هنوز برای راننده ثبت نشده است.',
            ], 409);
        }

        $installation->forceFill([
            'device_pin_hash' => Hash::make($data['pin']),
            'device_pin_updated_at' => now(),
            'last_ip' => $request->ip(),
            'last_seen_at' => now(),
        ])->save();

        return response()->json(['status' => 'success']);
    }

    private function recognizedInstallation(int $driverId, string $deviceUuid): ?MobileAppInstallation
    {
        return MobileAppInstallation::query()
            ->where('driver_id', $driverId)
            ->where('device_uuid', $deviceUuid)
            ->whereNull('revoked_at')
            ->first();
    }

    private function activeInstallation(int $driverId): ?MobileAppInstallation
    {
        return MobileAppInstallation::query()
            ->where('driver_id', $driverId)
            ->whereNull('revoked_at')
            ->latest('last_seen_at')
            ->first();
    }

    private function sessionResponse(Driver $driver): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'ورود با رمز دستگاه با موفقیت انجام شد.',
            'token' => $driver->createToken('driver_app_token')->plainTextToken,
            'driver' => $this->driverProfile->data($driver),
        ]);
    }
}
