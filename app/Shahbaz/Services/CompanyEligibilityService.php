<?php

namespace App\Shahbaz\Services;

use App\Models\Company;
use App\Models\SystemSetting;

class CompanyEligibilityService
{
    public const GATE_DISABLED = 'disabled';
    public const GATE_PROFILE = 'profile';
    public const GATE_FULL = 'full_shahbaz';
    public const GATE_MODES = [self::GATE_DISABLED, self::GATE_FULL];

    public const REQUIRED_FIELDS = [
        'name_fa', 'name_en', 'national_id', 'registration_number', 'ceo_name',
        'ceo_national_code', 'ceo_mobile', 'phone', 'address_fa', 'address_en',
        'postal_code', 'province', 'city', 'activity_type',
    ];

    public function missingFields(Company $company): array
    {
        return array_values(array_filter(self::REQUIRED_FIELDS, function (string $field) use ($company): bool {
            $value = trim((string) ($company->{$field} ?? ''));

            // Imported companies used these placeholders for unknown values.
            // They must not count as a completed profile.
            return $value === '' || in_array(mb_strtolower($value), [
                '000', 'ثبت نشده', 'ثبت نشده است', 'n/a', 'na', '-', '—', 'نامشخص', 'نامعلوم',
            ], true);
        }));
    }

    public function incompleteRequiredSections(Company $company): array
    {
        return app(ShahbazSectionService::class)->incompleteRequiredRows($company);
    }

    public function canOperate(Company $company): bool
    {
        return match ($this->gateMode()) {
            self::GATE_DISABLED => true,
            self::GATE_FULL => $this->missingFields($company) === []
                && $this->incompleteRequiredSections($company) === []
                && $company->shahbaz_verification_status === 'verified',
        };
    }

    public function blockingReasons(Company $company): array
    {
        if ($this->gateMode() === self::GATE_DISABLED) return [];

        $reasons = [];
        if ($this->missingFields($company) !== []) $reasons[] = 'اطلاعات پایه الزامی شرکت کامل نشده است.';
        foreach ($this->incompleteRequiredSections($company) as $section) {
            if ($section['key'] === 'profile' && $this->missingFields($company) !== []) continue;
            $reasons[] = 'بخش «'.$section['label'].'» هنوز تکمیل نشده است.';
        }
        if ($company->shahbaz_verification_status !== 'verified') $reasons[] = 'اطلاعات شرکت هنوز توسط انجمن و کنترل دستی شحباز تأیید نشده است.';
        return array_values(array_unique($reasons));
    }

    public function onboardingRoute(Company $company): string
    {
        return $this->missingFields($company) !== []
            || collect($this->incompleteRequiredSections($company))->contains('key', 'profile')
            ? 'company.shahbaz.profile.edit'
            : 'company.shahbaz.dossier.show';
    }

    public function gateMode(): string
    {
        $mode = (string) SystemSetting::getValue('shahbaz_dozbalagh_gate_mode', self::GATE_FULL);
        return $mode === self::GATE_DISABLED ? self::GATE_DISABLED : self::GATE_FULL;
    }

    public function setGateMode(string $mode): void
    {
        abort_unless(in_array($mode, self::GATE_MODES, true), 422);
        SystemSetting::setValue('shahbaz_dozbalagh_gate_mode', $mode);
    }
}
