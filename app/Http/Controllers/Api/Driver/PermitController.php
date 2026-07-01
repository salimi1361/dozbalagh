<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DozbalaghItem;

class PermitController extends Controller
{
    // ۱. لیست تمام دوزبلاغ‌های مختص به راننده لاگین شده
    public function index()
    {
        $driver = auth()->user(); // راننده احراز هویت شده با Sanctum

        $permits = DozbalaghItem::where('driver_id', $driver->id)
            ->with(['permitRequest.company', 'country'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                // تبدیل خروجی برای نمایش بهینه‌تر و خوانا در اپلیکیشن راننده
                return [
                    'id' => $item->id,
                    'serial_number' => $this->toPersianNumbers($item->serial_number),
                    'status_key' => $item->status,
                    'status_label' => $this->translateStatus($item->status),
                    'issue_date' => $this->toPersianNumbers($item->created_at->format('Y/m/d')),
                    'company_name' => $item->permitRequest->company->name ?? 'نامشخص',
                    'country_name' => $item->country->name ?? 'نامشخص',
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $permits
        ]);
    }

    // ۲. دریافت جزئیات دقیق و لایه‌های اداری یک دوزبلاغ خاص
    public function show($id)
    {
        $driver = auth()->user();

        $permit = DozbalaghItem::where('driver_id', $driver->id)
            ->with(['permitRequest.company', 'country', 'events'])
            ->find($id);

        if (!$permit) {
            return response()->json([
                'status' => 'error',
                'message' => 'پروانه مورد نظر یافت نشد یا دسترسی به آن محدود شده است.'
            ], 404);
        }

        // تبدیل داده‌های دوزبلاغ برای صفحه جزئیات راننده
        $formattedPermit = [
            'id' => $permit->id,
            'serial_number' => $this->toPersianNumbers($permit->serial_number),
            'status_key' => $permit->status,
            'status_label' => $this->translateStatus($permit->status),
            'issue_date' => $this->toPersianNumbers($permit->created_at->format('Y/m/d')),
            'company_name' => $permit->permitRequest->company->name ?? 'نامشخص',
            'country_name' => $permit->country->name ?? 'نامشخص',
            'events' => $permit->events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'status_label' => $this->translateStatus($event->event_type),
                    'latitude' => $event->latitude,
                    'longitude' => $event->longitude,
                    'created_at' => $this->toPersianNumbers($event->created_at->format('Y/m/d H:i')),
                ];
            })
        ];

        return response()->json([
            'status' => 'success',
            'data' => $formattedPermit
        ]);
    }

    // ==========================================
    // متدهای کمکی برای شکیل‌سازی خروجی راننده
    // ==========================================

    private function toPersianNumbers($string)
    {
        if (!$string) return '';
        $farsiDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $latinDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($latinDigits, $farsiDigits, $string);
    }

    private function translateStatus($status)
    {
        return match ($status) {
            'issued', 'started_trip' => 'صادر شده / در مسیر',
            'used' => 'استفاده شده',
            'expired' => 'منقضی شده',
            'at_border_out' => 'در مرز خروجی',
            'in_transit' => 'در حال ترانزیت',
            'at_destination' => 'تحویل گمرک مقصد',
            default => 'وضعیت نامشخص',
        };
    }
}