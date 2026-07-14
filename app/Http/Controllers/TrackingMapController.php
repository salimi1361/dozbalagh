<?php

namespace App\Http\Controllers;

use App\Models\DriverLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingMapController extends Controller
{
    public function index(Request $request): View
    {
        $companyScope = $request->user()->hasRole('company');

        return view('tracking.index', [
            'dataUrl' => $companyScope ? route('company.tracking.data') : route('admin.tracking.data'),
            'scopeLabel' => $companyScope ? 'ناوگان شرکت' : 'تمام رانندگان سامانه',
            'layout' => $companyScope ? 'layouts.app' : 'layouts.admin',
            'localTileUrl' => url((string) config('tracking.tile_url', '/maps/offline-grid.svg')),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = DriverLocation::query()
            ->with(['driver.company', 'dozbalaghItem'])
            ->when($request->integer('item_id'), fn ($q, $id) => $q->where('dozbalagh_item_id', $id))
            ->when($request->filled('from'), fn ($q) => $q->where('recorded_at', '>=', $request->date('from')))
            ->when($user->hasRole('company'), function ($q) use ($user) {
                $companyId = $user->company?->id ?? $user->company_id ?? null;
                $q->whereHas('driver', fn ($driver) => $driver->where('current_company_id', $companyId));
            });

        $locations = $query->orderByDesc('recorded_at')->limit(5000)->get()->sortBy('recorded_at')->values();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'tracks' => $locations->groupBy('dozbalagh_item_id')->map(function ($points, $itemId) {
                $latest = $points->last();
                $driver = $latest->driver;

                return [
                    'item_id' => (int) $itemId,
                    'driver_id' => $driver?->id,
                    'driver_name' => trim(($driver?->first_name_fa ?? '').' '.($driver?->last_name_fa ?? '')) ?: 'راننده نامشخص',
                    'company_name' => $driver?->company?->name_fa ?? $driver?->company?->name,
                    'serial_number' => $latest->dozbalaghItem?->serial_number,
                    'last_seen_at' => $latest->recorded_at?->toIso8601String(),
                    'is_online' => $latest->recorded_at?->greaterThan(now()->subMinutes(5)) ?? false,
                    'latest' => $this->point($latest),
                    'points' => $points->map(fn ($point) => $this->point($point))->values(),
                ];
            })->values(),
        ]);
    }

    private function point(DriverLocation $location): array
    {
        return [
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'accuracy' => $location->accuracy,
            'speed' => $location->speed,
            'heading' => $location->heading,
            'recorded_at' => $location->recorded_at?->toIso8601String(),
        ];
    }
}
