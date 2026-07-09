<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\PermitRequest;

class PermitController extends Controller
{
    public function index()
    {
        $driver = auth()->user();

        $permits = PermitRequest::where(function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);

                if (!empty($driver->fleet_id)) {
                    $query->orWhere('fleet_id', $driver->fleet_id);
                }
            })
            ->with(['company', 'country', 'fleet'])
            ->orderByDesc('id')
            ->get()
            ->map(fn ($permit) => $this->formatPermit($permit));

        return response()->json([
            'status' => 'success',
            'data' => $permits,
        ]);
    }

    public function show($id)
    {
        $driver = auth()->user();

        $permit = PermitRequest::where(function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);

                if (!empty($driver->fleet_id)) {
                    $query->orWhere('fleet_id', $driver->fleet_id);
                }
            })
            ->with(['company', 'country', 'fleet'])
            ->find($id);

        if (!$permit) {
            return response()->json([
                'status' => 'error',
                'message' => 'پروانه مورد نظر یافت نشد یا دسترسی به آن محدود شده است.',
            ], 404);
        }

        $formattedPermit = $this->formatPermit($permit);
        $formattedPermit['events'] = $this->eventsForPermit($permit);

        return response()->json([
            'status' => 'success',
            'data' => $formattedPermit,
        ]);
    }

    private function formatPermit(PermitRequest $permit): array
    {
        $issuedAt = $permit->issued_at ?? $permit->created_at;
        $serial = $permit->serial_number ?: $permit->d_code;

        $companyName = $permit->company?->name_fa ?: $permit->company?->name;
        $countryName = $permit->country?->name ?: $permit->destination;

        return [
            'id' => $permit->id,
            'serial_number' => $this->toPersianNumbers($serial),
            'd_code' => $this->toPersianNumbers($permit->d_code),
            'status_key' => $this->normalizeStatusKey($permit->status),
            'status_label' => $this->translateStatus($permit->status),
            'issue_date' => $issuedAt ? $this->toPersianNumbers($issuedAt->format('Y/m/d')) : '---',
            'valid_until' => $permit->permit_valid_until ? $this->toPersianNumbers($permit->permit_valid_until->format('Y/m/d')) : null,
            'company_name' => $companyName ?: 'نامشخص',
            'country_name' => $countryName ?: 'نامشخص',
            'fleet_plate' => $permit->fleet?->transit_plate,
            'truck_type' => $permit->fleet?->truck_type,
        ];
    }

    private function eventsForPermit(PermitRequest $permit): array
    {
        $item = $this->matchingDozbalaghItem($permit);

        if (!$item) {
            return [];
        }

        return $item->events()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'status_label' => $this->translateStatus($event->event_type),
                'latitude' => $event->latitude,
                'longitude' => $event->longitude,
                'created_at' => $this->toPersianNumbers($event->created_at->format('Y/m/d H:i')),
            ])
            ->all();
    }

    private function matchingDozbalaghItem(PermitRequest $permit)
    {
        if (!$permit->serial_number) {
            return null;
        }

        return \App\Models\DozbalaghItem::where('serial_number', $permit->serial_number)
            ->where(function ($query) use ($permit) {
                $query->where('driver_id', $permit->driver_id)
                    ->orWhere('fleet_id', $permit->fleet_id)
                    ->orWhereNull('driver_id');
            })
            ->first();
    }

    private function toPersianNumbers($string): string
    {
        if ($string === null || $string === '') {
            return '';
        }

        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
            (string) $string
        );
    }

    private function normalizeStatusKey($status): string
    {
        return match ($status) {
            'صادر شده' => 'issued',
            'برگشتی', 'returned', 'collected', 'archived' => 'used',
            default => (string) $status,
        };
    }

    private function translateStatus($status): string
    {
        return match ($status) {
            'pending' => 'در انتظار بررسی',
            'approved' => 'تایید شده',
            'issued', 'صادر شده', 'started_trip' => 'صادر شده / در مسیر',
            'in_transit' => 'در حال ترانزیت',
            'at_border_out' => 'در مرز خروجی',
            'at_destination' => 'تحویل گمرک مقصد',
            'returned', 'collected' => 'عودت شده',
            'archived' => 'بایگانی شده',
            'lost' => 'مفقودی',
            'used' => 'استفاده شده',
            'expired' => 'منقضی شده',
            'rejected' => 'رد شده',
            default => $status ?: 'وضعیت نامشخص',
        };
    }
}
