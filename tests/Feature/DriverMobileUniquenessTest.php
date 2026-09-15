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

class DriverMobileUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_registration_rejects_a_mobile_owned_by_another_driver(): void
    {
        [$companyUser] = $this->companyAccount();
        $existingDriver = $this->driver('09157277001', false);

        $this->actingAs($companyUser)
            ->postJson(route('web.company.driver.store'), $this->driverPayload([
                'national_id' => '0012345602',
                'mobile' => '۰۹۱۵۷۲۷۷۰۰۱',
            ]))
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'این شماره موبایل قبلاً برای راننده دیگری ثبت شده است؛ ابتدا شماره رکورد قبلی را اصلاح کنید.');

        $this->assertDatabaseMissing('drivers', ['national_code' => '0012345602']);
        $this->assertDatabaseHas('drivers', [
            'id' => $existingDriver->id,
            'current_company_id' => null,
        ]);
    }

    private function companyAccount(): array
    {
        $companyRole = Role::firstOrCreate(['name' => 'company'], ['title_fa' => 'شرکت']);
        $user = User::create([
            'role_id' => $companyRole->id,
            'username' => 'company-driver-mobile-test',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        $company = new Company();
        $company->forceFill([
            'user_id' => $user->id,
            'company_code' => 'COMP-DRIVER-MOBILE-TEST',
            'name_fa' => 'شرکت آزمون راننده',
            'name_en' => 'Driver Mobile Test Company',
            'address_fa' => 'تهران',
            'address_en' => 'Tehran',
            'status' => 'approved',
        ])->save();

        SystemSetting::setValue('shahbaz_dozbalagh_gate_mode', 'disabled');

        return [$user, $company];
    }

    private function driver(string $mobile, bool $active): Driver
    {
        $driverRole = Role::firstOrCreate(['name' => 'driver'], ['title_fa' => 'راننده']);
        $user = User::create([
            'role_id' => $driverRole->id,
            'username' => 'driver-' . $mobile,
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        return Driver::create([
            'user_id' => $user->id,
            'national_code' => '0012345601',
            'passport_number' => 'P-1',
            'first_name_fa' => 'راننده',
            'last_name_fa' => 'قبلی',
            'first_name_en' => 'Previous',
            'last_name_en' => 'Driver',
            'mobile' => $mobile,
            'is_active' => $active,
        ]);
    }

    private function driverPayload(array $overrides = []): array
    {
        return array_replace([
            'national_id' => '0012345602',
            'mobile' => '09157277001',
            'first_name' => 'راننده',
            'last_name' => 'جدید',
            'first_name_en' => 'New',
            'last_name_en' => 'Driver',
            'passport_number' => 'P-2',
        ], $overrides);
    }
}
