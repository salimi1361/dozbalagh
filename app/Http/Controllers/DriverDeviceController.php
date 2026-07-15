<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\MobileAppInstallation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DriverDeviceController extends Controller
{
    public function index(Request $request): View
    {
        $query = MobileAppInstallation::query()
            ->with('driver.company')
            ->latest('last_seen_at');

        if ($request->user()->hasRole('company')) {
            $companyId = $request->user()->company?->id;
            abort_unless($companyId, 403);
            $query->whereHas('driver', fn ($driverQuery) => $driverQuery->where('current_company_id', $companyId));
        }

        $installations = $query->paginate(30);

        return view('driver_devices.index', compact('installations'));
    }

    public function destroy(Request $request, Driver $driver): RedirectResponse
    {
        if ($request->user()->hasRole('company')) {
            $companyId = $request->user()->company?->id;
            abort_unless($companyId && (int) $driver->current_company_id === (int) $companyId, 403);
        }

        $activeDevices = $driver->appInstallations()->whereNull('revoked_at')->count();
        if ($activeDevices === 0) {
            return back()->with('error', 'دستگاه فعالی برای این راننده ثبت نشده است.');
        }

        DB::transaction(function () use ($driver, $request): void {
            $driver->appInstallations()->whereNull('revoked_at')->update([
                'revoked_at' => now(),
                'revoked_by_user_id' => $request->user()->getKey(),
                'updated_at' => now(),
            ]);
            $driver->tokens()->delete();
        });

        return back()->with('success', 'دستگاه قبلی راننده از مدار خارج شد. راننده اکنون می‌تواند با گوشی جدید وارد شود.');
    }
}
