<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\MobileAppInstallation;
use App\Models\PermitRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RecognizedDeviceAuthController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'device_uuid' => ['required', 'string', 'max:191'],
            'app_identifier' => ['nullable', 'string', 'max:255'],
        ]);

        $installation = $this->recognizedInstallation($data['mobile'], $data['device_uuid']);

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

        $installation = $this->recognizedInstallation($data['mobile'], $data['device_uuid']);
        if (! $installation || ! $installation->device_pin_hash || ! Hash::check($data['pin'], $installation->device_pin_hash)) {
            return response()->json([
                'status' => 'error',
                'message' => 'رمز ورود صحیح نیست. در صورت فراموشی، ورود با کد یک‌بارمصرف را انتخاب کنید.',
            ], 422);
        }

        $driver = Driver::with('company')->findOrFail($installation->driver_id);
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

    private function recognizedInstallation(string $mobile, string $deviceUuid): ?MobileAppInstallation
    {
        return MobileAppInstallation::query()
            ->where('device_uuid', $deviceUuid)
            ->whereNull('revoked_at')
            ->whereHas('driver', fn ($query) => $query->where('mobile', $mobile))
            ->first();
    }

    private function sessionResponse(Driver $driver): JsonResponse
    {
        $latestPermitFleet = PermitRequest::with('fleet')
            ->where('driver_id', $driver->id)
            ->whereHas('fleet')
            ->latest('id')
            ->first()?->fleet;
        $company = $driver->company;

        return response()->json([
            'status' => 'success',
            'message' => 'ورود با رمز دستگاه با موفقیت انجام شد.',
            'token' => $driver->createToken('driver_app_token')->plainTextToken,
            'driver' => [
                'id' => $driver->id,
                'name' => trim(($driver->first_name_fa ?? '').' '.($driver->last_name_fa ?? '')) ?: ($driver->name ?? 'راننده سامانه'),
                'national_code' => $driver->national_code,
                'mobile' => $driver->mobile,
                'company_name' => $company ? ($company->name_fa ?: $company->name) : 'شرکت حمل‌ونقل',
                'company_manager' => $company ? ($company->ceo_name ?: 'ثبت نشده') : 'ثبت نشده',
                'company_address' => $company ? ($company->address_fa ?: $company->address_en ?: 'ثبت نشده') : 'ثبت نشده',
                'company_phone' => $company?->phone,
                'company_ceo_mobile' => $company?->ceo_mobile,
                'truck_plate' => $latestPermitFleet?->transit_plate ?? $driver->truck_plate ?? $driver->plate_number ?? null,
                'truck_smart_card' => $latestPermitFleet?->smart_card_number ?? $driver->truck_smart_id ?? $driver->smart_card_number ?? null,
                'truck_type' => $latestPermitFleet?->truck_type ?? $driver->truck_type ?? null,
                'fleet_source' => $latestPermitFleet ? 'dozoleh' : 'driver',
            ],
        ]);
    }
}
