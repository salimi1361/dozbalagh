<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\MobileAppInstallation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAppInstallationController extends Controller
{
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
        ]);

        $now = now();
        $installation = MobileAppInstallation::firstOrNew([
            'driver_id' => $request->user()->getKey(),
            'device_uuid' => $data['device_uuid'],
        ]);

        if ($installation->exists && $installation->revoked_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'دسترسی این دستگاه لغو شده است. برای فعال‌سازی مجدد با شرکت یا انجمن تماس بگیرید.',
            ], 409);
        }

        $hasAnotherActiveDevice = MobileAppInstallation::query()
            ->where('driver_id', $request->user()->getKey())
            ->whereNull('revoked_at')
            ->when($installation->exists, fn ($query) => $query->where('id', '!=', $installation->getKey()))
            ->exists();

        if ($hasAnotherActiveDevice) {
            return response()->json([
                'status' => 'error',
                'message' => 'یک گوشی دیگر برای این راننده فعال است. ابتدا دستگاه قبلی باید از پنل مدیریت ریست شود.',
            ], 409);
        }

        if (! $installation->exists) {
            $installation->installed_at = $now;
        }

        $installation->fill($data + [
            'last_ip' => $request->ip(),
            'last_seen_at' => $now,
        ])->save();

        return response()->json([
            'status' => 'success',
            'installation_id' => $installation->id,
        ]);
    }
}
