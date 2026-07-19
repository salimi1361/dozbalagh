<?php

namespace App\Shahbaz\Services;

use App\Models\Company;
use App\Models\SystemSetting;

class ShahbazSectionService
{
    private ?array $stored = null;

    public function definitions(): array { return config('shahbaz_sections', []); }

    public function settings(): array
    {
        if ($this->stored !== null) return $this->stored;
        $value = SystemSetting::getValue('shahbaz_section_settings', '{}');
        $decoded = is_array($value) ? $value : json_decode((string) $value, true);
        return $this->stored = is_array($decoded) ? $decoded : [];
    }

    public function rows(?Company $company = null): array
    {
        $rows = [];
        foreach ($this->definitions() as $key => $definition) {
            $rows[$key] = [
                'key' => $key,
                'label' => $definition['label'],
                'visible' => (bool) data_get($this->settings(), "sections.$key.visible", $definition['default_visible'] ?? false),
                'required' => (bool) data_get($this->settings(), "sections.$key.required", $definition['default_required'] ?? false),
                'company_read_only' => (bool) ($definition['company_read_only'] ?? false),
                'complete' => $company ? $this->isComplete($company, $key) : false,
            ];
        }
        return $rows;
    }

    public function accessibleRows(Company $company): array
    {
        $blocked = false;
        return array_filter(array_map(function (array $row) use ($company, &$blocked) {
            if (! $row['visible']) return null;
            $row['locked'] = $blocked;
            if ($row['required'] && ! $row['complete']) $blocked = true;
            return $row;
        }, $this->rows($company)));
    }

    public function isComplete(Company $company, string $key): bool
    {
        return match ($key) {
            'profile' => app(CompanyEligibilityService::class)->missingFields($company) === [],
            'requests' => \App\Shahbaz\Models\LicenseRequest::where('company_id', $company->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])->exists(),
            'licenses' => filled($company->activity_license_number)
                && filled($company->activity_license_issued_on)
                && filled($company->activity_license_expires_on),
            'personnel', 'board', 'shareholders' => \App\Shahbaz\Models\CompanyPerson::where('company_id', $company->id)
                ->where('relation_type', $key)->where('status', '!=', 'archived')->exists(),
            'fleet' => \App\Models\Fleet::where('company_id', $company->id)->exists(),
            'facilities' => \App\Shahbaz\Models\CompanyFacility::where('company_id', $company->id)->exists(),
            'gazettes' => \App\Shahbaz\Models\OfficialGazette::where('company_id', $company->id)->exists(),
            'registration' => \App\Shahbaz\Models\CompanyRegistration::where('company_id', $company->id)->exists(),
            'branches' => \App\Shahbaz\Models\BranchPermit::where('company_id', $company->id)
                ->where('status', 'active')->whereDate('expires_on', '>=', today())->exists(),
            'manual_status' => filled($company->activity_license_status),
            default => false,
        };
    }

    public function reminders(): array
    {
        return [
            'days' => data_get($this->settings(), 'reminders.days', [60, 30, 15, 0]),
            'sms_enabled' => (bool) data_get($this->settings(), 'reminders.sms_enabled', false),
            'panel_enabled' => (bool) data_get($this->settings(), 'reminders.panel_enabled', true),
        ];
    }

    public function save(array $input): void
    {
        $settings = ['sections' => [], 'reminders' => [
            'days' => array_values(array_unique(array_map('intval', $input['reminder_days'] ?? [60, 30, 15, 0]))),
            'sms_enabled' => (bool) ($input['sms_enabled'] ?? false),
            'panel_enabled' => (bool) ($input['panel_enabled'] ?? false),
        ]];
        foreach ($this->definitions() as $key => $definition) {
            $settings['sections'][$key] = [
                'visible' => (bool) data_get($input, "sections.$key.visible", false),
                'required' => (bool) data_get($input, "sections.$key.required", false),
            ];
        }
        SystemSetting::setValue('shahbaz_section_settings', $settings);
        $this->stored = $settings;
    }
}
