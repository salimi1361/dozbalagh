<?php

namespace App\Services;

use App\Models\PermitPrintLayout;
use Hekmatinasser\Verta\Verta;
use Illuminate\Support\Facades\DB;

class PermitPrintService
{
    public function contextForItem(int $itemId): array
    {
        $item = DB::table('permit_request_items')->where('id', $itemId)->firstOrFail();
        $permit = DB::table('permit_requests')->where('id', $item->permit_request_id)->firstOrFail();
        $country = DB::table('countries')->where('id', $item->country_id)->first();
        $company = DB::table('companies')->where('id', $permit->company_id)->first();
        $driver = DB::table('drivers')->where('id', $permit->driver_id)->orWhere('national_code', $permit->driver_id)->first();
        $fleet = DB::table('fleets')->where('id', $permit->fleet_id)->orWhere('smart_card_number', $permit->fleet_id)->first();

        $layout = PermitPrintLayout::with(['fields', 'masks'])
            ->where('country_id', $item->country_id)->where('is_active', true)->orderByDesc('version')->get()
            ->first(fn (PermitPrintLayout $candidate) => $this->normalizePermitType($candidate->permit_type) === $this->normalizePermitType($item->permit_type));

        return compact('item', 'permit', 'country', 'company', 'driver', 'fleet', 'layout') + ['values' => [
            'serial_number' => $item->d_serial_number ?: $permit->serial_number,
            'tracking_code' => $permit->d_code,
            'company_name' => $company->name_fa ?? $company->name ?? '',
            'company_name_en' => $company->name_en ?? '',
            'company_address' => $company->address_fa ?? '',
            'company_address_en' => $company->address_en ?? '',
            'driver_name' => trim(($driver->first_name_fa ?? '') . ' ' . ($driver->last_name_fa ?? '')),
            'driver_passport' => $driver->passport_number ?? '',
            'vehicle_plate' => $fleet->transit_plate ?? '',
            'trailer_plate' => $item->trailer_plate ?? '',
            'loading_origin' => $item->loading_origin ?? '',
            'loading_destination' => $item->loading_destination ?? '',
            'permit_type' => $item->permit_type ?? '',
            'operation_type' => $item->operation_type ?? '',
            'issued_date' => $this->jalali($item->issued_at ?: $permit->issued_at),
            'valid_until' => $this->jalali($item->permit_valid_until ?: $permit->permit_valid_until),
            'country_name' => $country->name ?? '',
            'cits_code' => $item->cits_code ?? '',
            'trip_code' => $item->trip_code ?? '',
        ]];
    }

    private function jalali($date): string
    {
        if (!$date) return '';
        try { return Verta::instance($date)->format('Y/m/d'); } catch (\Throwable) { return (string)$date; }
    }

    private function normalizePermitType($value): string
    {
        $value = mb_strtolower(trim((string)$value));
        $value = str_replace(['-', ' ', '‌'], '_', $value);
        $value = preg_replace('/_+/', '_', $value) ?: $value;
        return [
            'دوجانبه' => 'bilateral', 'دو_جانبه' => 'bilateral',
            'ترانزیت' => 'transit',
            'دوجانبه_ترانزیت' => 'bilateral_transit', 'دو_جانبه_ترانزیت' => 'bilateral_transit',
            'ترانزیت_ثالث' => 'third_country_transit',
            'ثالث' => 'third_country', 'کشور_ثالث' => 'third_country',
        ][$value] ?? $value;
    }
}
