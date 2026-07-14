<?php

namespace App\Http\Controllers;

use App\Models\DriverLocation;
use App\Models\Driver;
use App\Models\DriverEvent;
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
            'tilesEnabled' => (bool) config('tracking.tiles_enabled', false),
            'showMapAttribution' => (bool) config('tracking.online_tiles_enabled', false),
            'outlineUrl' => asset('maps/iran-outline.geojson'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->hasRole('company') ? ($user->company?->id ?? $user->company_id ?? null) : null;
        $query = DriverLocation::query()
            ->with(['driver.company', 'dozbalaghItem'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [-90, 90])
            ->whereBetween('longitude', [-180, 180])
            ->whereHas('dozbalaghItem', fn ($item) => $item
                ->whereIn('lifecycle_status', ['issued', 'consumed'])
                ->whereNull('returned_at'))
            ->when($request->integer('item_id'), fn ($q, $id) => $q->where('dozbalagh_item_id', $id))
            ->when($request->filled('from'), fn ($q) => $q->where('recorded_at', '>=', $request->date('from')))
            ->when($user->hasRole('company'), function ($q) use ($companyId) {
                $q->whereHas('driver', fn ($driver) => $driver->where('current_company_id', $companyId));
            });

        $locations = $query->orderByDesc('recorded_at')->limit(5000)->get()->sortBy('recorded_at')->values();
        $latestStarts = DriverEvent::query()
            ->where('event_type', 'tracking_started')
            ->whereIn('dozbalagh_item_id', $locations->pluck('dozbalagh_item_id')->unique())
            ->orderBy('created_at')
            ->get()
            ->keyBy(fn (DriverEvent $event) => $event->driver_id.':'.$event->dozbalagh_item_id);

        $tracks = $locations->groupBy('dozbalagh_item_id')->map(function ($points, $itemId) use ($latestStarts) {
            $driverId = $points->last()?->driver_id;
            $startedAt = $latestStarts->get($driverId.':'.$itemId)?->created_at;
            if ($startedAt) {
                $points = $points->filter(fn ($point) => $point->recorded_at?->greaterThanOrEqualTo($startedAt))->values();
            }

            $latest = $points->last();
            if (! $latest || ! $latest->recorded_at?->greaterThan(now()->subMinutes(5))) {
                return null;
            }

            $driver = $latest->driver;

            return [
                'item_id' => (int) $itemId,
                'driver_id' => $driver?->id,
                'driver_name' => trim(($driver?->first_name_fa ?? '').' '.($driver?->last_name_fa ?? '')) ?: 'راننده نامشخص',
                'company_name' => $driver?->company?->name_fa ?? $driver?->company?->name,
                'serial_number' => $latest->dozbalaghItem?->serial_number,
                'last_seen_at' => $latest->recorded_at?->toIso8601String(),
                'is_online' => true,
                'latest' => $this->point($latest),
                'points' => $points->map(fn ($point) => $this->point($point))->values(),
            ];
        })->filter()->values();

        $latestLocationsByDriver = $locations->groupBy('driver_id')->map(fn ($points) => $points->last());
        $drivers = Driver::query()
            ->with('company')
            ->whereIn('id', $tracks->pluck('driver_id')->filter()->unique())
            ->orderBy('last_name_fa')
            ->get();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'drivers' => $drivers->map(function (Driver $driver) use ($latestLocationsByDriver) {
                $latest = $latestLocationsByDriver->get($driver->id);

                return [
                    'id' => $driver->id,
                    'name' => trim(($driver->first_name_fa ?? '').' '.($driver->last_name_fa ?? '')) ?: 'راننده نامشخص',
                    'company_name' => $driver->company?->name_fa ?? $driver->company?->name,
                    'has_location' => (bool) $latest,
                    'last_seen_at' => $latest?->recorded_at?->toIso8601String(),
                    'is_online' => $latest?->recorded_at?->greaterThan(now()->subMinutes(5)) ?? false,
                ];
            })->values(),
            'tracks' => $tracks,
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
