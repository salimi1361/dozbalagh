<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\MobileAppInstallation;
use App\Models\PwaInstallation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Morilog\Jalali\Jalalian;

class PwaInstallationReportController extends Controller
{
    public function index(Request $request): View
    {
        $section = $request->string('section')->value() === 'web' ? 'web' : 'app';

        if ($section === 'app') {
            return $this->mobileAppReport($request, $section);
        }

        $query = PwaInstallation::query()->latest('last_seen_at');
        $query->when($request->filled('role'), fn ($q) => $q->where('role', $request->role));
        $query->when($request->filled('device_type'), fn ($q) => $q->where('device_type', $request->device_type));
        $query->when($request->status === 'installed', fn ($q) => $q->where('is_installed', true));
        $query->when($request->status === 'browser', fn ($q) => $q->where('is_installed', false));
        $query->when($request->status === 'active', fn ($q) => $q->whereNull('revoked_at')->where('last_seen_at', '>=', now()->subDays(30)));
        $query->when($request->status === 'inactive', fn ($q) => $q->whereNull('revoked_at')->where('last_seen_at', '<', now()->subDays(30)));
        $query->when($request->status === 'revoked', fn ($q) => $q->whereNotNull('revoked_at'));

        $installations = $query->paginate(30)->withQueryString();
        $userIds = $installations->where('actor_type', 'user')->pluck('actor_id');
        $driverIds = $installations->where('actor_type', 'driver')->pluck('actor_id');
        $users = User::with('company')->whereIn('id', $userIds)->get()->keyBy('id');
        $drivers = Driver::whereIn('id', $driverIds)->get()->keyBy('id');

        $jalaliNow = Jalalian::now();
        $jalaliMonthStart = (new Jalalian($jalaliNow->getYear(), $jalaliNow->getMonth(), 1))
            ->toCarbon()
            ->startOfDay();

        $stats = [
            'devices' => PwaInstallation::where('is_installed', true)->count(),
            'users' => PwaInstallation::where('is_installed', true)->select('actor_type', 'actor_id')->distinct()->get()->count(),
            'month' => PwaInstallation::where('installed_at', '>=', $jalaliMonthStart)->count(),
            'active' => PwaInstallation::where('is_installed', true)->where('last_seen_at', '>=', now()->subDays(30))->count(),
        ];

        return view('admin.reports.pwa_installations', compact('section', 'installations', 'users', 'drivers', 'stats'));
    }

    private function mobileAppReport(Request $request, string $section): View
    {
        $query = MobileAppInstallation::query()
            ->with('driver.company')
            ->latest('last_seen_at');

        $query->when($request->filled('platform'), fn ($q) => $q->where('platform', $request->platform));
        $query->when($request->status === 'active', fn ($q) => $q->where('last_seen_at', '>=', now()->subDays(30)));
        $query->when($request->status === 'inactive', fn ($q) => $q->where('last_seen_at', '<', now()->subDays(30)));

        $installations = $query->paginate(30)->withQueryString();
        $users = collect();
        $drivers = collect();

        $jalaliNow = Jalalian::now();
        $jalaliMonthStart = (new Jalalian($jalaliNow->getYear(), $jalaliNow->getMonth(), 1))
            ->toCarbon()
            ->startOfDay();

        $stats = [
            'devices' => MobileAppInstallation::whereNull('revoked_at')->distinct()->count('device_uuid'),
            'users' => MobileAppInstallation::whereNull('revoked_at')->distinct()->count('driver_id'),
            'month' => MobileAppInstallation::whereNull('revoked_at')->where('installed_at', '>=', $jalaliMonthStart)->count(),
            'active' => MobileAppInstallation::whereNull('revoked_at')->where('last_seen_at', '>=', now()->subDays(30))->count(),
        ];

        return view('admin.reports.pwa_installations', compact('section', 'installations', 'users', 'drivers', 'stats'));
    }
}
