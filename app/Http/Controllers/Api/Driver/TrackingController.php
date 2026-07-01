<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\DozbalaghItem;
use App\Models\DriverEvent;
use Illuminate\Http\Request;
use App\Notifications\DriverEventNotification;

class TrackingController extends Controller
{
    // ۱. ثبت رویداد دستی راننده (مثل اعلام رسیدن به مقصد)
    public function logEvent(Request $request)
    {
        $request->validate([
            'dozbalagh_item_id' => 'required|exists:dozbalagh_items,id',
            'event_type' => 'required|string', // e.g., 'at_destination'
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $driver = auth()->user(); // راننده‌ای که لاگین کرده (از طریق Sanctum)

        // ثبت رویداد در دیتابیس
        $event = DriverEvent::create([
            'driver_id' => $driver->id,
            'dozbalagh_item_id' => $request->dozbalagh_item_id,
            'event_type' => $request->event_type,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // پیدا کردن شرکت حمل‌ونقلی که این دوزبلاغ متعلق به اوست
        $dozbalaghItem = DozbalaghItem::with('permitRequest.company')->find($request->dozbalagh_item_id);
        $company = $dozbalaghItem->permitRequest->company ?? null;

        // ارسال نوتیفیکیشن فوری به پنل شرکت (Filament) یا بازوی بله شرکت
        if ($company) {
            $company->notify(new DriverEventNotification($driver, $event, $dozbalaghItem));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'رویداد با موفقیت ثبت و به شرکت اطلاع داده شد.'
        ]);
    }

    // ۲. دریافت لوکیشن زنده در بک‌گراند (Sync Location)
    public function syncLocation(Request $request)
    {
        $request->validate([
            'dozbalagh_item_id' => 'required|exists:dozbalagh_items,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        auth()->user()->locations()->create([
            'dozbalagh_item_id' => $request->dozbalagh_item_id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'recorded_at' => now(),
        ]);

        return response()->json(['status' => 'success']);
    }
}