<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\DozbalaghItem;
use App\Models\DriverEvent;
use App\Models\PermitRequest;
use App\Notifications\DriverEventNotification;
use Illuminate\Http\Request;

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
        ]);

        $driver = auth()->user();
        $dozbalaghItem = $this->resolveDozbalaghItem((int) $request->dozbalagh_item_id, $driver);

        if (!$dozbalaghItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'دوزبلاغ مرتبط با این راننده یا ناوگان یافت نشد.',
            ], 404);
        }

        $driver->locations()->create([
            'dozbalagh_item_id' => $dozbalaghItem->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'recorded_at' => now(),
        ]);

        return response()->json(['status' => 'success']);
    }

    private function resolveDozbalaghItem(int $id, $driver): ?DozbalaghItem
    {
        $item = DozbalaghItem::where('id', $id)
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
            ->where(function ($query) use ($permit, $driver) {
                $query->where('driver_id', $driver->id)
                    ->orWhere('fleet_id', $permit->fleet_id)
                    ->orWhereNull('driver_id');
            })
            ->first();
    }
}
