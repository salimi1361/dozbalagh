<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\PermitRequest;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

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
        $trackingItem = $this->matchingDozbalaghItem($permit);
        $issuedAt = $permit->issued_at ?? $permit->created_at;
        $validUntil = $permit->permit_valid_until;
        $items = $this->permitItems($permit->id);
        $firstItem = $items[0] ?? null;
        $cmrDate = $permit->cmr_date ?? ($firstItem['cmr_date_raw'] ?? null);
        $tirCarnetDate = $permit->tir_carnet_date ?? ($firstItem['tir_carnet_date_raw'] ?? null);
        $serial = $permit->serial_number ?: ($firstItem['d_serial_number'] ?? null) ?: $permit->d_code;

        $companyName = $permit->company?->name_fa ?: $permit->company?->name;
        $countryName = $permit->country?->name ?: ($firstItem['country_name'] ?? null) ?: $permit->destination;
        $issueDates = $this->formatDatePair($issuedAt);
        $validUntilDates = $this->formatDatePair($validUntil);
        $cmrDates = $this->formatDatePair($cmrDate);
        $tirCarnetDates = $this->formatDatePair($tirCarnetDate);

        return [
            'id' => $permit->id,
            'tracking_item_id' => $trackingItem?->id,
            'serial_number' => $this->toPersianNumbers($serial),
            'raw_serial_number' => $permit->serial_number,
            'd_code' => $this->toPersianNumbers($permit->d_code),
            'status_key' => $this->normalizeStatusKey($permit->status),
            'status_label' => $this->translateStatus($permit->status),
            'issue_date' => $issueDates['jalali'] ?? '---',
            'issue_date_jalali' => $issueDates['jalali'],
            'issue_date_gregorian' => $issueDates['gregorian'],
            'valid_until' => $validUntilDates['jalali'],
            'valid_until_jalali' => $validUntilDates['jalali'],
            'valid_until_gregorian' => $validUntilDates['gregorian'],
            'validity_days' => $permit->validity_days ? $this->toPersianNumbers($permit->validity_days) : null,
            'company_name' => $companyName ?: 'نامشخص',
            'country_name' => $countryName ?: 'نامشخص',
            'origin' => $permit->origin ?: ($firstItem['loading_origin'] ?? null),
            'destination' => $permit->destination ?: ($firstItem['loading_destination'] ?? null),
            'cargo_type' => $permit->cargo_type ?: ($firstItem['operation_type'] ?? null),
            'permit_type' => $permit->permit_type ?: ($firstItem['permit_type'] ?? null),
            'request_type' => $permit->request_type,
            'cits_code' => $permit->cits_code ?: ($firstItem['cits_code'] ?? null),
            'trip_code' => $permit->trip_code ?: ($firstItem['trip_code'] ?? null),
            'receipt_code' => $permit->receipt_code ?: ($firstItem['receipt_code'] ?? null),
            'cmr_date_jalali' => $cmrDates['jalali'],
            'cmr_date_gregorian' => $cmrDates['gregorian'],
            'tir_carnet_number' => $permit->tir_carnet_number ?: ($firstItem['tir_carnet_number'] ?? null),
            'tir_carnet_date_jalali' => $tirCarnetDates['jalali'],
            'tir_carnet_date_gregorian' => $tirCarnetDates['gregorian'],
            'total_amount' => $permit->total_amount ? $this->toPersianNumbers(number_format((float) $permit->total_amount)) : null,
            'payment_status' => $permit->payment_status,
            'company_note' => $permit->company_note,
            'dozoleh_items' => array_map(fn ($item) => $this->formatPermitItem($item), $items),
            'fleet_plate' => $permit->fleet?->transit_plate,
            'fleet_smart_card' => $permit->fleet?->smart_card_number,
            'truck_type' => $permit->fleet?->truck_type,
        ];
    }

    private function permitItems(int $permitId): array
    {
        return DB::table('permit_request_items as pri')
            ->leftJoin('countries as c', 'pri.country_id', '=', 'c.id')
            ->where('pri.permit_request_id', $permitId)
            ->orderBy('pri.id')
            ->select([
                'pri.*',
                'c.name as country_name',
                'pri.cmr_date as cmr_date_raw',
                'pri.tir_carnet_date as tir_carnet_date_raw',
            ])
            ->get()
            ->map(fn ($item) => (array) $item)
            ->all();
    }

    private function formatPermitItem(array $item): array
    {
        $cmrDates = $this->formatDatePair($item['cmr_date_raw'] ?? null);
        $tirDates = $this->formatDatePair($item['tir_carnet_date_raw'] ?? null);

        return [
            'id' => $item['id'] ?? null,
            'country_name' => $item['country_name'] ?? null,
            'permit_type' => $item['permit_type'] ?? null,
            'operation_type' => $item['operation_type'] ?? null,
            'loading_origin' => $item['loading_origin'] ?? null,
            'loading_destination' => $item['loading_destination'] ?? null,
            'cits_code' => $item['cits_code'] ?? null,
            'trip_code' => $item['trip_code'] ?? null,
            'receipt_code' => $item['receipt_code'] ?? null,
            'serial_number' => isset($item['d_serial_number']) ? $this->toPersianNumbers($item['d_serial_number']) : null,
            'allocation_status' => $item['allocation_status'] ?? null,
            'return_status' => $item['return_status'] ?? null,
            'cmr_date_jalali' => $cmrDates['jalali'],
            'cmr_date_gregorian' => $cmrDates['gregorian'],
            'tir_carnet_number' => $item['tir_carnet_number'] ?? null,
            'tir_carnet_date_jalali' => $tirDates['jalali'],
            'tir_carnet_date_gregorian' => $tirDates['gregorian'],
            'price' => isset($item['price']) ? $this->toPersianNumbers(number_format((float) $item['price'])) : null,
            'print_copy_endpoint' => !empty($item['d_serial_number']) && !empty($item['id'])
                ? '/api/v1/driver/permit-items/' . $item['id'] . '/copy'
                : null,
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

    private function formatDatePair($date): array
    {
        if (!$date) {
            return [
                'jalali' => null,
                'gregorian' => null,
            ];
        }

        if (!$date instanceof CarbonInterface) {
            $date = \Carbon\Carbon::parse($date);
        }

        return [
            'jalali' => $this->toPersianNumbers(Jalalian::fromCarbon($date)->format('Y/m/d')),
            'gregorian' => $date->format('Y/m/d'),
        ];
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
