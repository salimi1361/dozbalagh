<?php

namespace App\Shahbaz\Services;

use App\Models\Company;
use App\Models\SystemSetting;

class CompanyEligibilityService
{
    public const GATE_DISABLED = 'disabled';
    public const GATE_PROFILE = 'profile';
    public const GATE_FULL = 'full_shahbaz';
    public const GATE_MODES = [self::GATE_DISABLED, self::GATE_PROFILE, self::GATE_FULL];

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
        return match ($this->gateMode()) {
            self::GATE_DISABLED => true,
            self::GATE_PROFILE => $this->missingFields($company) === [],
            self::GATE_FULL => $this->missingFields($company) === []
                && $company->shahbaz_verification_status === 'verified'
                && $company->activity_license_status === 'active'
                && filled($company->activity_license_number)
                && $company->activity_license_expires_on
                && ! $company->activity_license_expires_on->isBefore(today()),
        };
    }

    public function blockingReasons(Company $company): array
    {
        if ($this->gateMode() === self::GATE_DISABLED) return [];

        $reasons = [];
        if ($this->missingFields($company) !== []) $reasons[] = 'اطلاعات الزامی شرکت کامل نشده است.';
        if ($this->gateMode() === self::GATE_PROFILE) return $reasons;

        if ($company->shahbaz_verification_status !== 'verified') $reasons[] = 'اطلاعات شرکت هنوز توسط انجمن و کنترل دستی شحباز تأیید نشده است.';
        if ($company->activity_license_status !== 'active') $reasons[] = 'پروانه فعالیت شرکت فعال نیست.';
        if (!$company->activity_license_expires_on || $company->activity_license_expires_on->isBefore(today())) $reasons[] = 'پروانه فعالیت معتبر نیست یا تاریخ اعتبار آن پایان یافته است.';
        return array_values(array_unique($reasons));
    }

    public function gateMode(): string
    {
        $mode = (string) SystemSetting::getValue('shahbaz_dozbalagh_gate_mode', self::GATE_PROFILE);
        return in_array($mode, self::GATE_MODES, true) ? $mode : self::GATE_PROFILE;
    }

    public function setGateMode(string $mode): void
    {
        abort_unless(in_array($mode, self::GATE_MODES, true), 422);
        SystemSetting::setValue('shahbaz_dozbalagh_gate_mode', $mode);
    }
}
