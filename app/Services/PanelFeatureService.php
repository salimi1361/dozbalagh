<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class PanelFeatureService
{
    private ?array $storedSettings = null;

    public function definitions(): array
    {
        return config('panel_features', []);
    }

    public function enabledForRole(string $role, string $feature): bool
    {
        if (! isset($this->definitions()[$role][$feature])) {
            return false;
        }

        $default = (bool) ($this->definitions()[$role][$feature]['default'] ?? true);

        return (bool) data_get($this->storedSettings(), "$role.$feature", $default);
    }

    public function enabledForRoute(string $role, ?string $routeName): bool
    {
        if (! $routeName || ! isset($this->definitions()[$role])) {
            return true;
        }

        foreach ($this->definitions()[$role] as $feature => $definition) {
            foreach ($definition['routes'] as $pattern) {
                if (Str::is($pattern, $routeName)) {
                    return $this->enabledForRole($role, $feature);
                }
            }
        }

        return true;
    }

    public function save(array $submitted): void
    {
        $settings = [];

        foreach ($this->definitions() as $role => $features) {
            foreach (array_keys($features) as $feature) {
                $settings[$role][$feature] = (bool) data_get($submitted, "$role.$feature", false);
            }
        }

        SystemSetting::setValue('panel_features', $settings);
        $this->storedSettings = $settings;
    }

    public function landingRoute(string $role): ?string
    {
        $routes = [
            'association' => [
                'dashboard' => 'association.dashboard',
                'requests' => 'association.pending.index',
                'issuance' => 'association.approved.index',
                'transit' => 'association.transit.index',
                'archive' => 'association.archive.index',
                'reports' => 'association.reports.index',
                'financial' => 'association.issued-financial.index',
                'tracking' => 'admin.tracking.index',
                'crm' => 'admin.association_crm.index',
                'print_layouts' => 'association.print-layouts.index',
                'shahbaz_company_review' => 'association.shahbaz.companies.index',
                'cmr_documents' => 'admin.cmr.index',
                'cmr_issuance' => 'admin.cmr.create',
                'cmr_master_data' => 'admin.cmr.master-data.index',
                'cmr_company_settings' => 'admin.cmr.company-settings.index',
                'cmr_financial' => 'admin.cmr.settings',
                'cmr_reports' => 'admin.cmr.reports.index',
            ],
            'company' => [
                'dashboard' => 'dashboard',
                'requests' => 'dozbalagh.index',
                'issued' => 'company.dozbalagh.issued',
                'drivers' => 'web.company.driver.index',
                'tracking' => 'company.tracking.index',
                'driver_messages' => 'company.driver_messages.index',
                'association_crm' => 'company.association_crm.index',
                'fleets' => 'web.company.fleet.index',
                'reports' => 'report.index',
                'wallet' => 'company.wallet.index',
                'profile' => 'company.profile.edit',
                'shahbaz_overview' => 'company.shahbaz.dossier.show',
                'cmr_documents' => 'admin.cmr.index',
                'cmr_issuance' => 'admin.cmr.create',
                'cmr_master_data' => 'admin.cmr.master-data.index',
                'cmr_company_settings' => 'admin.cmr.company-settings.index',
                'cmr_financial' => 'admin.cmr.settings',
                'cmr_reports' => 'company.cmr-reports.index',
            ],
        ];

        foreach ($routes[$role] ?? [] as $feature => $route) {
            if ($this->enabledForRole($role, $feature)) {
                return $route;
            }
        }

        return null;
    }

    private function storedSettings(): array
    {
        if ($this->storedSettings !== null) {
            return $this->storedSettings;
        }

        try {
            if (! Schema::hasTable('system_settings')) {
                return $this->storedSettings = [];
            }

            $value = SystemSetting::getValue('panel_features', '{}');
            $decoded = is_array($value) ? $value : json_decode((string) $value, true);

            return $this->storedSettings = is_array($decoded) ? $decoded : [];
        } catch (Throwable) {
            return $this->storedSettings = [];
        }
    }
}
