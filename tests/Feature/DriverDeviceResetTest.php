<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Driver;
use App\Models\MobileAppInstallation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DriverDeviceResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_association_can_revoke_any_driver_device_and_tokens(): void
    {
        $association = $this->user('association', 'association-reset');
        $driver = $this->driver();
        $driver->createToken('old-phone');
        $installation = $this->installation($driver);

        $this->actingAs($association)
            ->delete(route('driver-device-reset.destroy', $driver))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($installation->fresh()->revoked_at);
        $this->assertSame($association->id, $installation->fresh()->revoked_by_user_id);
        $this->assertCount(0, $driver->fresh()->tokens);
    }

    public function test_company_can_only_reset_its_own_driver(): void
    {
        $companyUser = $this->user('company', 'company-reset');
        $company = Company::create([
            'user_id' => $companyUser->id,
            'company_code' => 'COMP-RESET',
            'name_fa' => 'شرکت آزمایشی',
            'name_en' => 'Test Company',
            'address_fa' => 'مشهد',
            'address_en' => 'Mashhad',
        ]);
        $ownDriver = $this->driver($company->id, '09120000001');
        $otherDriver = $this->driver(null, '09120000002');
        $ownInstallation = $this->installation($ownDriver, 'own-device');
        $otherInstallation = $this->installation($otherDriver, 'other-device');

        $this->actingAs($companyUser)
            ->delete(route('driver-device-reset.destroy', $otherDriver))
            ->assertForbidden();

        $this->actingAs($companyUser)
            ->delete(route('driver-device-reset.destroy', $ownDriver))
            ->assertRedirect();

        $this->assertNull($otherInstallation->fresh()->revoked_at);
        $this->assertNotNull($ownInstallation->fresh()->revoked_at);
    }

    private function user(string $role, string $username): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role], ['title_fa' => $role]);

        return User::create([
            'role_id' => $roleModel->id,
            'username' => $username,
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);
    }

    private function driver(?int $companyId = null, string $mobile = '09120000000'): Driver
    {
        $user = $this->user('driver', 'driver-'.$mobile);

        return Driver::create([
            'user_id' => $user->id,
            'current_company_id' => $companyId,
            'national_code' => substr($mobile, 1),
            'passport_number' => 'P'.substr($mobile, 4),
            'first_name_fa' => 'راننده',
            'last_name_fa' => 'آزمایشی',
            'first_name_en' => 'Test',
            'last_name_en' => 'Driver',
            'mobile' => $mobile,
        ]);
    }

    private function installation(Driver $driver, string $uuid = 'device-reset-test'): MobileAppInstallation
    {
        return MobileAppInstallation::create([
            'driver_id' => $driver->id,
            'device_uuid' => $uuid,
            'platform' => 'android',
            'installed_at' => now(),
            'last_seen_at' => now(),
        ]);
    }
}
