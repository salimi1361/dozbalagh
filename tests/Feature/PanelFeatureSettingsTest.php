<?php

namespace Tests\Feature;

use App\CMR\Models\CmrDocument;
use App\Models\Company;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PanelFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PanelFeatureSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_panel_features(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->put(route('admin.settings.panel-features.update'), [
            'features' => [
                'association' => ['requests' => '1'],
                'company' => ['dashboard' => '1'],
            ],
        ])->assertRedirect();

        $stored = json_decode(SystemSetting::getValue('panel_features'), true);

        $this->assertTrue($stored['association']['requests']);
        $this->assertFalse($stored['association']['reports']);
        $this->assertTrue($stored['company']['dashboard']);
        $this->assertFalse($stored['company']['wallet']);
        $this->assertFalse($stored['association']['shahbaz_company_review']);
        $this->assertFalse($stored['association']['cmr_documents']);
        $this->assertFalse($stored['company']['shahbaz_overview']);
        $this->assertFalse($stored['company']['cmr_reports']);
    }

    public function test_shahbaz_and_cmr_features_are_disabled_by_default(): void
    {
        $features = app(PanelFeatureService::class);

        $this->assertFalse($features->enabledForRole('association', 'shahbaz_company_review'));
        $this->assertFalse($features->enabledForRole('association', 'cmr_documents'));
        $this->assertFalse($features->enabledForRole('association', 'cmr_issuance'));
        $this->assertFalse($features->enabledForRole('company', 'shahbaz_overview'));
        $this->assertFalse($features->enabledForRole('company', 'shahbaz_profile'));
        $this->assertFalse($features->enabledForRole('company', 'cmr_documents'));
        $this->assertFalse($features->enabledForRole('company', 'cmr_issuance'));
        $this->assertFalse($features->enabledForRole('company', 'cmr_master_data'));
        $this->assertFalse($features->enabledForRole('company', 'cmr_company_settings'));
        $this->assertFalse($features->enabledForRole('company', 'cmr_financial'));
        $this->assertFalse($features->enabledForRole('company', 'cmr_reports'));
    }

    public function test_admin_settings_page_groups_shahbaz_and_cmr_items_for_both_panels(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.settings.panel-features.index'))
            ->assertOk()
            ->assertSee('شهباز')
            ->assertSee('CMR')
            ->assertSee('features[association][shahbaz_company_review]', false)
            ->assertSee('features[association][cmr_documents]', false)
            ->assertSee('features[company][shahbaz_overview]', false)
            ->assertSee('features[company][cmr_documents]', false)
            ->assertSee('features[company][cmr_issuance]', false)
            ->assertSee('features[company][cmr_master_data]', false)
            ->assertSee('features[company][cmr_company_settings]', false)
            ->assertSee('features[company][cmr_financial]', false)
            ->assertSee('features[company][cmr_reports]', false);
    }

    public function test_disabled_feature_blocks_direct_access_for_association(): void
    {
        $association = $this->userWithRole('association');
        SystemSetting::setValue('panel_features', [
            'association' => ['reports' => false],
        ]);

        $this->actingAs($association)
            ->get(route('association.reports.index'))
            ->assertForbidden();
    }

    public function test_disabled_feature_blocks_direct_access_for_company(): void
    {
        $company = $this->userWithRole('company');
        SystemSetting::setValue('panel_features', [
            'company' => ['dashboard' => false],
        ]);

        $this->actingAs($company)->get(route('dashboard'))->assertForbidden();
    }

    public function test_disabled_shahbaz_and_cmr_routes_block_direct_access(): void
    {
        $association = $this->userWithRole('association');
        SystemSetting::setValue('panel_features', [
            'association' => [
                'shahbaz_company_review' => false,
                'cmr_documents' => false,
            ],
        ]);

        $this->actingAs($association)
            ->get(route('association.shahbaz.companies.index'))
            ->assertForbidden();
        $this->actingAs($association)
            ->get(route('admin.cmr.index'))
            ->assertForbidden();

        $company = $this->userWithRole('company');
        SystemSetting::setValue('panel_features', [
            'company' => [
                'shahbaz_overview' => false,
                'cmr_reports' => false,
            ],
        ]);

        $this->actingAs($company)
            ->get(route('company.shahbaz.dossier.show'))
            ->assertForbidden();
        $this->actingAs($company)
            ->get(route('company.cmr-reports.index'))
            ->assertForbidden();
    }

    public function test_shahbaz_items_are_checked_independently(): void
    {
        SystemSetting::setValue('panel_features', [
            'company' => [
                'shahbaz_requests' => true,
                'shahbaz_profile' => false,
            ],
        ]);

        $features = app(PanelFeatureService::class);

        $this->assertTrue($features->enabledForRoute('company', 'company.shahbaz.requests.index'));
        $this->assertFalse($features->enabledForRoute('company', 'company.shahbaz.profile.edit'));
    }

    public function test_company_cmr_documents_are_scoped_to_its_own_company(): void
    {
        $role = Role::create(['name' => 'company', 'title_fa' => 'company']);
        $firstUser = User::create(['role_id' => $role->id, 'username' => 'company-one', 'password' => Hash::make('secret'), 'status' => 'active']);
        $secondUser = User::create(['role_id' => $role->id, 'username' => 'company-two', 'password' => Hash::make('secret'), 'status' => 'active']);
        $firstCompany = $this->companyForUser($firstUser, 'CMP-ONE');
        $secondCompany = $this->companyForUser($secondUser, 'CMP-TWO');
        $ownDocument = $this->cmrDocument($firstCompany, 'OWN-CMR');
        $otherDocument = $this->cmrDocument($secondCompany, 'OTHER-CMR');

        SystemSetting::setValue('panel_features', [
            'company' => ['cmr_documents' => true],
        ]);

        $this->actingAs($firstUser)
            ->get(route('admin.cmr.index'))
            ->assertOk()
            ->assertSee($ownDocument->number)
            ->assertDontSee($otherDocument->number);

        $this->actingAs($firstUser)
            ->get(route('admin.cmr.show', $otherDocument))
            ->assertNotFound();
    }

    public function test_company_can_view_but_cannot_change_global_cmr_tariff(): void
    {
        $companyUser = $this->userWithRole('company');
        $this->companyForUser($companyUser, 'CMP-FIN');
        SystemSetting::setValue('panel_features', [
            'company' => ['cmr_financial' => true],
        ]);

        $this->actingAs($companyUser)
            ->get(route('admin.cmr.settings'))
            ->assertOk()
            ->assertSee('تغییر تعرفه سراسری فقط در اختیار مدیر کل سامانه است.');

        $this->actingAs($companyUser)
            ->put(route('admin.cmr.settings.update'), ['issuance_fee' => 1])
            ->assertForbidden();
    }

    public function test_landing_route_uses_first_enabled_feature(): void
    {
        SystemSetting::setValue('panel_features', [
            'association' => [
                'dashboard' => false,
                'requests' => false,
                'issuance' => false,
                'transit' => true,
            ],
        ]);

        $this->assertSame(
            'association.transit.index',
            app(PanelFeatureService::class)->landingRoute('association'),
        );
    }

    public function test_admin_can_delegate_an_admin_module_to_association(): void
    {
        $association = $this->userWithRole('association');
        SystemSetting::setValue('panel_features', [
            'association' => ['companies' => true],
        ]);

        $this->actingAs($association)->get(route('admin.companies.index'))->assertOk();
    }

    public function test_association_cannot_manage_panel_settings(): void
    {
        $association = $this->userWithRole('association');

        $this->actingAs($association)
            ->get(route('admin.settings.panel-features.index'))
            ->assertForbidden();
    }

    public function test_driver_device_reset_is_available_for_association_and_company_by_default(): void
    {
        $features = app(PanelFeatureService::class);

        $this->assertTrue($features->enabledForRole('association', 'driver_device_reset'));
        $this->assertTrue($features->enabledForRole('company', 'driver_device_reset'));
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::create(['name' => $roleName, 'title_fa' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'username' => $roleName,
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);
    }

    private function companyForUser(User $user, string $code): Company
    {
        return Company::create([
            'user_id' => $user->id,
            'company_code' => $code,
            'name_fa' => 'شرکت '.$code,
            'name_en' => 'Company '.$code,
            'national_id' => 'NI-'.$code,
        ]);
    }

    private function cmrDocument(Company $company, string $number): CmrDocument
    {
        return CmrDocument::create([
            'uuid' => (string) Str::uuid(),
            'number' => $number,
            'company_id' => $company->id,
            'status' => 'draft',
            'consignor_name' => 'Sender',
            'consignee_name' => 'Receiver',
            'carrier_name' => 'Carrier',
            'taking_over_place' => 'Tehran',
            'delivery_place' => 'Berlin',
        ]);
    }
}
