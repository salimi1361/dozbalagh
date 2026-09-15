<?php

namespace App\Services;

use App\CMR\Models\CmrDocument;
use App\Models\Driver;
use App\Models\PermitRequest;

class DriverProfileService
{
    public function data(Driver $driver): array
    {
        $driver->loadMissing('company');

        $latestPermit = PermitRequest::with(['fleet', 'company'])
            ->where('driver_id', $driver->id)
            ->orderByDesc('id')
            ->first();
        $latestCmr = CmrDocument::with(['fleet', 'company'])
            ->where('driver_id', $driver->id)
            ->where('status', '!=', 'draft')
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->first();

        $company = $driver->company ?: $latestPermit?->company ?: $latestCmr?->company;
        $companyName = trim((string) ($company?->name_fa ?: $company?->name_en));
        $driverName = trim(($driver->first_name_fa ?? '').' '.($driver->last_name_fa ?? ''));
        $driverName = $driverName ?: trim((string) data_get($driver, 'name'));

        $fleet = $latestPermit?->fleet ?: $latestCmr?->fleet;
        $companyData = $company ? [
            'id' => $company->id,
            'name_fa' => $company->name_fa,
            'name_en' => $company->name_en,
            'ceo_name' => $company->ceo_name,
            'phone' => $company->phone,
            'ceo_mobile' => $company->ceo_mobile,
            'address_fa' => $company->address_fa,
            'address_en' => $company->address_en,
        ] : null;

        return [
            'id' => $driver->id,
            'first_name_fa' => $driver->first_name_fa,
            'last_name_fa' => $driver->last_name_fa,
            'first_name_en' => $driver->first_name_en,
            'last_name_en' => $driver->last_name_en,
            'name' => $driverName ?: 'راننده سامانه',
            'national_code' => $driver->national_code,
            'mobile' => $driver->mobile,
            'current_company_id' => $driver->current_company_id,
            'company_id' => $company?->id,
            'company_name' => $companyName ?: 'شرکت ثبت نشده',
            'company_manager' => trim((string) ($company?->ceo_name)) ?: 'ثبت نشده',
            'company_address' => trim((string) ($company?->address_fa ?: $company?->address_en)) ?: 'ثبت نشده',
            'company_phone' => $company?->phone,
            'company_ceo_mobile' => $company?->ceo_mobile,
            'company' => $companyData,
            'truck_plate' => $fleet?->transit_plate
                ?? $driver->truck_plate
                ?? $driver->plate_number,
            'truck_smart_card' => $fleet?->smart_card_number
                ?? $driver->truck_smart_id
                ?? $driver->smart_card_number,
            'truck_type' => $fleet?->truck_type ?? $driver->truck_type,
            'fleet_source' => $fleet ? ($latestPermit?->fleet ? 'dozoleh' : 'cmr') : 'driver',
        ];
    }
}
