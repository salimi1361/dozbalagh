<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DriverCompanyOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_cannot_be_registered_by_another_company_until_released(): void
    {
        [$companyAUser, $companyA] = $this->companyAccount('A');
        [$companyBUser, $companyB] = $this->companyAccount('B');
        $driver = $this->driver($companyA->id);

        $payload = [
            'national_id' => $driver->national_code,
            'mobile' => $driver->mobile,
            'first_name' => 'First',
            'last_name' => 'Driver',
            'first_name_en' => 'First',
            'last_name_en' => 'Driver',
            'passport_number' => 'P-OWNERSHIP-1',
        ];

        $this->actingAs($companyBUser)
            ->postJson(route('web.company.driver.inquire'), [
                'national_id' => $driver->national_code,
                'mobile_number' => $driver->mobile,
            ])
            ->assertStatus(409);

        $this->actingAs($companyBUser)
            ->postJson(route('web.company.driver.store'), $payload)
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'current_company_id' => $companyA->id,
            'contract_status' => 'bound',
        ]);

        $this->actingAs($companyAUser)
            ->postJson(route('web.company.driver.delete'), [
                'national_code' => $driver->national_code,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'current_company_id' => null,
            'contract_status' => 'free',
        ]);

        $this->actingAs($companyBUser)
            ->postJson(route('web.company.driver.store'), $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'current_company_id' => $companyB->id,
            'contract_status' => 'bound',
        ]);
    }

    private function companyAccount(string $suffix): array
    {
        $companyRole = Role::firstOrCreate(['name' => 'company'], ['title_fa' => 'شرکت']);
        $user = User::create([
            'role_id' => $companyRole->id,
            'username' => 'company-driver-ownership-' . strtolower($suffix),
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        $company = new Company();
        $company->forceFill([
            'user_id' => $user->id,
            'company_code' => 'COMP-DRIVER-OWNERSHIP-' . $suffix,
            'name_fa' => 'شرکت آزمون مالکیت راننده ' . $suffix,
            'name_en' => 'Driver Ownership Company ' . $suffix,
            'address_fa' => 'تهران',
            'address_en' => 'Tehran',
            'status' => 'approved',
        ])->save();

        SystemSetting::setValue('shahbaz_dozbalagh_gate_mode', 'disabled');
        SystemSetting::setValue('driver_registration_app_sms_enabled', false);

        return [$user, $company];
    }

    private function driver(int $companyId): Driver
    {
        $driverRole = Role::firstOrCreate(['name' => 'driver'], ['title_fa' => 'راننده']);
        $user = User::create([
            'role_id' => $driverRole->id,
            'username' => '0012345699',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        return Driver::create([
            'user_id' => $user->id,
            'current_company_id' => $companyId,
            'national_code' => '0012345699',
            'passport_number' => 'P-OWNERSHIP-0',
            'first_name_fa' => 'راننده',
            'last_name_fa' => 'آزمون',
            'first_name_en' => 'Test',
            'last_name_en' => 'Driver',
            'mobile' => '09157277009',
            'contract_status' => 'bound',
            'is_active' => true,
        ]);
    }
}
