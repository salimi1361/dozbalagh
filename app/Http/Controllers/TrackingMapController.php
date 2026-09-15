<?php

namespace App\Http\Controllers;

use App\Models\DriverEvent;
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
            'tilesEnabled' => (bool) config('tracking.tiles_enabled', false),
            'showMapAttribution' => (bool) config('tracking.online_tiles_enabled', false),
            'outlineUrl' => asset('maps/iran-outline.geojson'),
            'recentDays' => (int) config('tracking.recent_days', 7),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['nullable', 'in:live,recent'],
        ]);

        $mode = $validated['mode'] ?? 'live';
        $user = $request->user();
        $companyId = $user->hasRole('company') ? ($user->company?->id ?? $user->company_id ?? null) : null;
        $onlineSince = now()->subMinutes((int) config('tracking.online_timeout_minutes', 15));
        $recentSince = now()->subDays((int) config('tracking.recent_days', 7));

        $query = DriverLocation::query()
            ->with(['driver.company', 'dozbalaghItem'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [-90, 90])
            ->whereBetween('longitude', [-180, 180])
            ->whereHas('driver', fn ($driver) => $driver->where('is_active', true))
            ->whereHas('dozbalaghItem', fn ($item) => $item
                ->whereIn('lifecycle_status', ['issued', 'consumed'])
                ->whereNull('returned_at'))
            ->whereHas('dozbalaghItem.fleet', fn ($fleet) => $fleet->where('is_active', true))
            ->when($request->integer('item_id'), fn ($q, $id) => $q->where('dozbalagh_item_id', $id))
            ->when($request->filled('from'), fn ($q) => $q->where('recorded_at', '>=', $request->date('from')))
            ->when($user->hasRole('company'), function ($q) use ($companyId) {
                if (! $companyId) {
                    $q->whereRaw('1 = 0');

                    return;
                }

                $q->whereHas('dozbalaghItem', function ($item) use ($companyId) {
                    $item->where('company_id', $companyId);
                });
            });

        $locations = $query->orderByDesc('recorded_at')->limit(5000)->get()->sortBy('recorded_at')->values();
        $events = DriverEvent::query()
            ->whereIn('event_type', ['tracking_started', 'tracking_stopped'])
            ->whereIn('dozbalagh_item_id', $locations->pluck('dozbalagh_item_id')->unique())
            ->orderBy('created_at')
            ->get()
            ->keyBy(fn (DriverEvent $event) => $event->driver_id.':'.$event->dozbalagh_item_id);

        $tracks = $locations->groupBy('dozbalagh_item_id')->map(function ($points, $itemId) use ($events, $onlineSince, $mode) {
            $allPoints = $points;
            $driverId = $points->last()?->driver_id;
            $latestEvent = $events->get($driverId.':'.$itemId);
            $tripIsActive = $latestEvent?->event_type === 'tracking_started';
            $startedAt = $tripIsActive ? $latestEvent?->created_at : null;

            if ($startedAt) {
                $points = $points->filter(fn ($point) =>
                    $point->created_at?->greaterThanOrEqualTo($startedAt)
                    || $point->recorded_at?->greaterThanOrEqualTo($startedAt)
                )->values();
            }

            if ($points->isEmpty()) {
                $points = $allPoints->take(-1)->values();
            }

            $latest = $points->last();
            if (! $latest) {
                return null;
            }

            $driver = $latest->driver;
            $lastSeenAt = $latest->recorded_at;

            return [
                'item_id' => (int) $itemId,
                'driver_id' => $driver?->id,
                'driver_name' => trim(($driver?->first_name_fa ?? '').' '.($driver?->last_name_fa ?? '')) ?: 'راننده نامشخص',
                'company_name' => $driver?->company?->name_fa ?? $driver?->company?->name,
                'serial_number' => $latest->dozbalaghItem?->serial_number,
                'last_seen_at' => $lastSeenAt?->toIso8601String(),
                'is_online' => $tripIsActive
                    && ($lastSeenAt?->greaterThan($onlineSince) ?? false)
                    && ($lastSeenAt?->greaterThanOrEqualTo($startedAt) ?? false),
                'trip_is_active' => $tripIsActive,
                'latest' => $this->point($latest),
                'points' => ($mode === 'recent' ? collect([$latest]) : $points)
                    ->map(fn ($point) => $this->point($point))->values(),
            ];
        })->filter()->filter(function (array $track) use ($mode, $recentSince) {
            if ($mode === 'live') {
                return $track['is_online'];
            }

            return ! $track['is_online']
                && $track['last_seen_at']
                && $recentSince->lessThanOrEqualTo($track['last_seen_at']);
        })->sortByDesc('last_seen_at')->values();

        $drivers = $tracks->groupBy('driver_id')->map(function ($driverTracks) {
            $track = $driverTracks->sortByDesc('last_seen_at')->first();

            return [
                'id' => $track['driver_id'],
                'name' => $track['driver_name'],
                'company_name' => $track['company_name'],
                'has_location' => true,
                'last_seen_at' => $track['last_seen_at'],
                'is_online' => $track['is_online'],
            ];
        })->values();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'mode' => $mode,
            'drivers' => $drivers,
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
