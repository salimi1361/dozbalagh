<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\DozbalaghItem;
use App\Models\DriverEvent;
use App\Models\PermitRequest;
use App\Notifications\DriverEventNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrackingController extends Controller
{
    public function logEvent(Request $request)
    {
        $request->validate([
            'dozbalagh_item_id' => 'required|integer',
            'event_type' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $driver = auth()->user();
        $dozbalaghItem = $this->resolveDozbalaghItem((int) $request->dozbalagh_item_id, $driver);

        if (!$dozbalaghItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'دوزبلاغ مرتبط با این راننده یا ناوگان یافت نشد.',
            ], 404);
        }

        $event = DriverEvent::create([
            'driver_id' => $driver->id,
            'dozbalagh_item_id' => $dozbalaghItem->id,
            'event_type' => $request->event_type,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        $company = $dozbalaghItem->permitRequest->company ?? $dozbalaghItem->company;

        if ($company) {
            $company->notify(new DriverEventNotification($driver, $event, $dozbalaghItem));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'رویداد با موفقیت ثبت شد.',
        ]);
    }

    public function syncLocation(Request $request)
    {
        $request->validate([
            'dozbalagh_item_id' => 'required|integer',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'client_uuid' => 'nullable|uuid',
            'accuracy' => 'nullable|numeric|min:0|max:10000',
            'altitude' => 'nullable|numeric|min:-1000|max:20000',
            'speed' => 'nullable|numeric|min:0|max:150',
            'heading' => 'nullable|numeric|min:0|max:360',
            'recorded_at' => 'nullable|date',
        ]);

        $driver = auth()->user();
        $dozbalaghItem = $this->resolveDozbalaghItem((int) $request->dozbalagh_item_id, $driver);

        if (!$dozbalaghItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'دوزبلاغ مرتبط با این راننده یا ناوگان یافت نشد.',
            ], 404);
        }

        $driver->locations()->updateOrCreate(['client_uuid' => $request->client_uuid ?: (string) Str::uuid()], [
            'dozbalagh_item_id' => $dozbalaghItem->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'altitude' => $request->altitude,
            'speed' => $request->speed,
            'heading' => $request->heading,
            'recorded_at' => $request->date('recorded_at') ?? now(),
        ]);

        return response()->json(['status' => 'success', 'accepted' => 1]);
    }

    public function syncLocations(Request $request)
    {
        $validated = $request->validate([
            'points' => 'required|array|min:1|max:100',
            'points.*.client_uuid' => 'required|uuid',
            'points.*.dozbalagh_item_id' => 'required|integer',
            'points.*.latitude' => 'required|numeric|between:-90,90',
            'points.*.longitude' => 'required|numeric|between:-180,180',
            'points.*.accuracy' => 'nullable|numeric|min:0|max:10000',
            'points.*.altitude' => 'nullable|numeric|min:-1000|max:20000',
            'points.*.speed' => 'nullable|numeric|min:0|max:150',
            'points.*.heading' => 'nullable|numeric|min:0|max:360',
            'points.*.recorded_at' => 'required|date',
        ]);

        $driver = $request->user();
        $accepted = 0;
        $rejected = [];

        DB::transaction(function () use ($validated, $driver, &$accepted, &$rejected) {
            foreach ($validated['points'] as $point) {
                $item = $this->resolveDozbalaghItem((int) $point['dozbalagh_item_id'], $driver);
                if (! $item) {
                    $rejected[] = $point['client_uuid'];
                    continue;
                }

                $driver->locations()->updateOrCreate(
                    ['client_uuid' => $point['client_uuid']],
                    [
                        'dozbalagh_item_id' => $item->id,
                        'latitude' => $point['latitude'],
                        'longitude' => $point['longitude'],
                        'accuracy' => $point['accuracy'] ?? null,
                        'altitude' => $point['altitude'] ?? null,
                        'speed' => $point['speed'] ?? null,
                        'heading' => $point['heading'] ?? null,
                        'recorded_at' => $point['recorded_at'],
                    ],
                );
                $accepted++;
            }
        });

        return response()->json(['status' => 'success', 'accepted' => $accepted, 'rejected' => $rejected]);
    }

    private function resolveDozbalaghItem(int $id, $driver): ?DozbalaghItem
    {
        $item = DozbalaghItem::where('id', $id)
            ->where('lifecycle_status', 'issued')
            ->whereNull('returned_at')
            ->where(function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);

                if (!empty($driver->fleet_id)) {
                    $query->orWhere('fleet_id', $driver->fleet_id);
                }
            })
            ->first();

        if ($item) {
            return $item;
        }

        $permit = PermitRequest::where('id', $id)
            ->where(function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);

                if (!empty($driver->fleet_id)) {
                    $query->orWhere('fleet_id', $driver->fleet_id);
                }
            })
            ->first();

        if (!$permit || !$permit->serial_number) {
            return null;
        }

        return DozbalaghItem::where('serial_number', $permit->serial_number)
            ->where('lifecycle_status', 'issued')
            ->whereNull('returned_at')
            ->where(function ($query) use ($permit, $driver) {
                $query->where('driver_id', $driver->id)
                    ->orWhere('fleet_id', $permit->fleet_id)
                    ->orWhereNull('driver_id');
            })
            ->first();
    }
}
