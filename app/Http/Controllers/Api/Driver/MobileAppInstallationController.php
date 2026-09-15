<?php

namespace App\Http\Controllers\Api\Driver;

use App\Exceptions\DriverDeviceBindingException;
use App\Http\Controllers\Controller;
use App\Services\DriverDeviceBindingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAppInstallationController extends Controller
{
    public function __construct(
        private readonly DriverDeviceBindingService $deviceBinding,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_uuid' => ['required', 'string', 'max:191'],
            'platform' => ['required', 'in:android,ios'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'os_version' => ['nullable', 'string', 'max:255'],
            'sdk_version' => ['nullable', 'integer', 'min:1', 'max:999'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'app_build' => ['nullable', 'string', 'max:50'],
            'app_identifier' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:20'],
            'fcm_token' => ['nullable', 'string', 'min:20', 'max:4096'],
            'notifications_enabled' => ['sometimes', 'boolean'],
        ]);

        try {
            $installation = $this->deviceBinding->bind(
                $request->user(),
                $data,
                $request->ip(),
            );
        } catch (DriverDeviceBindingException $exception) {
            $isRevoked = $exception->reason === DriverDeviceBindingException::REVOKED_DEVICE;

            return response()->json([
                'status' => 'error',
                'error_code' => $isRevoked ? 'DEVICE_ACCESS_REVOKED' : 'DEVICE_ALREADY_BOUND',
                'message' => $isRevoked
                    ? 'دسترسی این دستگاه لغو شده است. برای فعال‌سازی مجدد با شرکت یا انجمن تماس بگیرید.'
                    : 'یک گوشی دیگر برای این راننده فعال است. ابتدا دستگاه قبلی باید از پنل مدیریت ریست شود.',
            ], 409);
        }

        return response()->json([
            'status' => 'success',
            'installation_id' => $installation->id,
            'notifications_enabled' => $installation->notifications_enabled,
        ]);
    }
}
