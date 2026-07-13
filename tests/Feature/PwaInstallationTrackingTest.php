<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PwaInstallationTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_installation_is_recorded_per_device(): void
    {
        $company = $this->userWithRole('company');

        $payload = [
            'device_uuid' => 'device-test-1',
            'event' => 'installed',
            'platform' => 'Android',
            'browser' => 'Chrome',
            'device_type' => 'mobile',
        ];

        $this->actingAs($company)->postJson(route('pwa.installations.store'), $payload)->assertOk();
        $this->actingAs($company)->postJson(route('pwa.installations.store'), [...$payload, 'event' => 'standalone'])->assertOk();

        $this->assertDatabaseCount('pwa_installations', 1);
        $this->assertDatabaseHas('pwa_installations', [
            'actor_type' => 'user',
            'actor_id' => $company->id,
            'device_uuid' => 'device-test-1',
            'role' => 'company',
            'is_installed' => true,
            'is_standalone' => true,
        ]);
    }

    public function test_only_admin_can_view_installation_report(): void
    {
        $admin = $this->userWithRole('admin');
        $association = $this->userWithRole('association');

        $this->actingAs($admin)->get(route('admin.reports.pwa-installations.index'))->assertOk();
        $this->actingAs($association)->get(route('admin.reports.pwa-installations.index'))->assertForbidden();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['title_fa' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'username' => $roleName.'-'.uniqid(),
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);
    }
}
