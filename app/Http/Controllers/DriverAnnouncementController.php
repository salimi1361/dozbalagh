<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Driver;
use App\Models\DriverAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DriverAnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->user()->role?->name;
        $announcements = DriverAnnouncement::query()
            ->withCount([
                'receipts',
                'receipts as seen_count' => fn ($query) => $query->whereNotNull('seen_at'),
                'receipts as acknowledged_count' => fn ($query) => $query->whereNotNull('acknowledged_at'),
            ])
            ->when($role !== 'admin', fn ($query) => $query->where('created_by_user_id', $request->user()->id))
            ->latest()
            ->paginate(20);

        $drivers = $this->allowedDrivers($request)->orderBy('first_name_fa')->get();
        $companies = $role === 'company' ? collect() : Company::query()->orderBy('name_fa')->get();

        return view('driver_announcements.index', compact('announcements', 'drivers', 'companies', 'role'));
    }

    public function store(Request $request): RedirectResponse
    {
        $role = $request->user()->role?->name;
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:normal,important,urgent'],
            'display_mode' => ['required', 'in:normal,important,mandatory,emergency'],
            'audience_type' => ['required', 'in:all,company,selected'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'driver_ids' => ['nullable', 'array'],
            'driver_ids.*' => ['integer', 'exists:drivers,id'],
            'acknowledgement_text' => ['nullable', 'string', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        if ($role === 'company' && $validated['audience_type'] === 'company') {
            $validated['audience_type'] = 'all';
        }

        if ($validated['audience_type'] === 'company' && empty($validated['company_id'])) {
            return back()->withInput()->with('error', 'برای ارسال شرکتی، انتخاب شرکت الزامی است.');
        }

        if ($validated['audience_type'] === 'selected' && empty($validated['driver_ids'])) {
            return back()->withInput()->with('error', 'حداقل یک راننده را انتخاب کنید.');
        }

        $drivers = $this->allowedDrivers($request);
        if ($validated['audience_type'] === 'company') {
            $drivers->where('current_company_id', $validated['company_id'] ?? 0);
        } elseif ($validated['audience_type'] === 'selected') {
            $drivers->whereIn('id', $validated['driver_ids'] ?? []);
        }
        $driverIds = $drivers->pluck('id');

        if ($driverIds->isEmpty()) {
            return back()->withInput()->with('error', 'هیچ راننده‌ای در محدوده انتخاب‌شده وجود ندارد.');
        }

        DB::transaction(function () use ($request, $validated, $role, $driverIds): void {
            $announcement = DriverAnnouncement::create([
                'created_by_user_id' => $request->user()->id,
                'company_id' => $role === 'company' ? $request->user()->company?->id : ($validated['company_id'] ?? null),
                'source_role' => $role,
                'title' => $validated['title'],
                'message' => $validated['message'],
                'priority' => $validated['priority'],
                'display_mode' => $validated['display_mode'],
                'audience_type' => $validated['audience_type'],
                'show_once' => $request->boolean('show_once'),
                'requires_acknowledgement' => $request->boolean('requires_acknowledgement') || in_array($validated['display_mode'], ['mandatory', 'emergency'], true),
                'acknowledgement_text' => $validated['acknowledgement_text'] ?: 'مطالعه کردم',
                'starts_at' => $validated['starts_at'] ?? now(),
                'ends_at' => $validated['ends_at'] ?? null,
                'is_active' => true,
            ]);

            $now = now();
            $announcement->receipts()->insert($driverIds->map(fn ($driverId) => [
                'announcement_id' => $announcement->id,
                'driver_id' => $driverId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });

        return back()->with('success', 'اطلاعیه برای '.number_format($driverIds->count()).' راننده منتشر شد.');
    }

    public function toggle(Request $request, DriverAnnouncement $announcement): RedirectResponse
    {
        $this->authorizeAnnouncement($request, $announcement);
        $announcement->update(['is_active' => ! $announcement->is_active]);

        return back()->with('success', 'وضعیت اطلاعیه تغییر کرد.');
    }

    private function allowedDrivers(Request $request)
    {
        $query = Driver::query();
        if ($request->user()->hasRole('company')) {
            $companyId = $request->user()->company?->id;
            abort_unless($companyId, 403);
            $query->where('current_company_id', $companyId);
        }

        return $query;
    }

    private function authorizeAnnouncement(Request $request, DriverAnnouncement $announcement): void
    {
        abort_unless($request->user()->hasRole('admin') || $announcement->created_by_user_id === $request->user()->id, 403);
    }
}
