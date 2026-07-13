<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PanelFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_landing_route_uses_first_enabled_feature(): void
    {
        SystemSetting::setValue('panel_features', [
            'association' => [
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
}
