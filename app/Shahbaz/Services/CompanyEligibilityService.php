<?php

namespace App\Shahbaz\Services;

use App\Models\Company;

class CompanyEligibilityService
{
    public const REQUIRED_FIELDS = [
        'name_fa', 'name_en', 'national_id', 'registration_number', 'ceo_name',
        'ceo_national_code', 'ceo_mobile', 'phone', 'address_fa', 'address_en',
        'postal_code', 'province', 'city', 'activity_type',
    ];

    public function missingFields(Company $company): array
    {
        return array_values(array_filter(self::REQUIRED_FIELDS, fn (string $field) => blank($company->{$field})));
    }

    public function canOperate(Company $company): bool
    {
        return $this->missingFields($company) === []
            && $company->shahbaz_verification_status === 'verified'
            && $company->activity_license_status === 'active'
            && filled($company->activity_license_number)
            && !$company->activity_license_expires_on->isBefore(today());
    }

    public function blockingReasons(Company $company): array
    {
        $reasons = [];
        if ($this->missingFields($company) !== []) $reasons[] = 'اطلاعات الزامی شرکت کامل نشده است.';
        if ($company->shahbaz_verification_status !== 'verified') $reasons[] = 'اطلاعات شرکت هنوز توسط انجمن و کنترل دستی شحباز تأیید نشده است.';
        if ($company->activity_license_status !== 'active') $reasons[] = 'پروانه فعالیت شرکت فعال نیست.';
        if (!$company->activity_license_expires_on || $company->activity_license_expires_on->isBefore(today())) $reasons[] = 'پروانه فعالیت معتبر نیست یا تاریخ اعتبار آن پایان یافته است.';
        return array_values(array_unique($reasons));
    }
}
