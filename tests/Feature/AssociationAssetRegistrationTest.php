<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FleetLoadingType;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AssociationAssetRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_association_can_open_driver_and_fleet_registration_forms(): void
    {
        [$association, $company] = $this->associationAndCompany();
        $this->loadingType();

        $this->actingAs($association)
            ->get(route('admin.drivers.index'))
            ->assertOk()
            ->assertSee('ثبت راننده جدید')
            ->assertSee($company->name_fa);

        $this->actingAs($association)
            ->get(route('admin.fleets.index'))
            ->assertOk()
            ->assertSee('ثبت ناوگان جدید')
            ->assertSee($company->name_fa);
    }

    public function test_association_registration_requires_an_approved_company(): void
    {
        [$association] = $this->associationAndCompany();

        $this->actingAs($association)
            ->postJson(route('admin.drivers.store'), $this->driverPayload(['company_id' => 99999]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->actingAs($association)
            ->postJson(route('admin.fleets.store'), $this->fleetPayload(['company_id' => 99999]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_association_can_register_a_driver_and_fleet_for_an_approved_company(): void
    {
        [$association, $company] = $this->associationAndCompany();
        $loadingType = $this->loadingType();
        SystemSetting::setValue('driver_registration_app_sms_enabled', false);

        $this->actingAs($association)
            ->postJson(route('admin.drivers.store'), $this->driverPayload(['company_id' => $company->id]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('drivers', [
            'national_code' => '0012345678',
            'current_company_id' => $company->id,
            'mobile' => '09121234567',
            'contract_status' => 'bound',
        ]);

        $this->actingAs($association)
            ->postJson(route('admin.fleets.store'), $this->fleetPayload([
                'company_id' => $company->id,
                'loading_type_id' => $loadingType->id,
                'loading_type_code' => $loadingType->rmto_code,
            ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('fleets', [
            'company_id' => $company->id,
            'smart_card_number' => '1768168',
            'transit_plate' => '123-ع-456-11',
            'fleet_loading_type_id' => $loadingType->id,
            'is_active' => true,
        ]);
    }

    private function associationAndCompany(): array
    {
        $associationRole = Role::create(['name' => 'association', 'title_fa' => 'انجمن']);
        $companyRole = Role::create(['name' => 'company', 'title_fa' => 'شرکت']);
        Role::create(['id' => 3, 'name' => 'driver', 'title_fa' => 'راننده']);

        $association = User::create([
            'role_id' => $associationRole->id,
            'username' => 'association-asset-registration',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        $companyUser = User::create([
            'role_id' => $companyRole->id,
            'username' => 'company-asset-registration',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        $company = new Company();
        $company->forceFill([
            'user_id' => $companyUser->id,
            'company_code' => 'COMP-ASSOCIATION-ASSET',
            'name_fa' => 'شرکت آزمون ثبت انجمن',
            'name_en' => 'Association Asset Company',
            'address_fa' => 'تهران',
            'address_en' => 'Tehran',
            'status' => 'approved',
        ])->save();

        return [$association, $company];
    }

    private function loadingType(): FleetLoadingType
    {
        return FleetLoadingType::create([
            'rmto_code' => 'TRAILER-TEST',
            'name_fa' => 'تریلی آزمون',
            'is_active' => true,
            'is_international' => true,
            'association_enabled' => true,
            'is_custom' => false,
            'sort_order' => 1,
        ]);
    }

    private function driverPayload(array $overrides = []): array
    {
        return array_replace([
            'national_id' => '0012345678',
            'mobile' => '09121234567',
            'first_name' => 'راننده',
            'last_name' => 'آزمون',
            'first_name_en' => 'TEST',
            'last_name_en' => 'DRIVER',
            'passport_number' => 'P-TEST-123',
        ], $overrides);
    }

    private function fleetPayload(array $overrides = []): array
    {
        return array_replace([
            'smart_id' => '1768168',
            'loading_type' => 'تریلی آزمون',
            'loading_type_id' => '',
            'loading_type_code' => '',
            'loading_type_source' => 'manual',
            'transit_horse' => 'TR-12345',
            'transit_trailer' => 'TR-98765',
            'transit_plate' => '123-ع-456-11',
        ], $overrides);
    }
}
