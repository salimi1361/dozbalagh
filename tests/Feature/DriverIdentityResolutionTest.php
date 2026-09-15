<?php

namespace Tests\Feature;

use App\CMR\Models\CmrDocument;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\PermitRequest;
use App\Services\DriverIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverIdentityResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_identity_resolves_a_legacy_country_code_mobile_format(): void
    {
        $company = $this->company('COMPANY-CANONICAL');
        $driver = $this->driver('Canonical', 'Driver', $company->id, 1);
        DB::table('drivers')->where('id', $driver->id)->update(['mobile' => '0098 915 727 7001']);

        $selected = app(DriverIdentityService::class)->resolveActiveByMobile('09157277001');

        $this->assertNotNull($selected);
        $this->assertSame($driver->id, $selected->id);
    }

    public function test_profile_returns_the_authenticated_driver_when_mobile_is_unique(): void
    {
        $driver = $this->driver('Current', 'Driver', null, 1);

        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/driver/auth/profile')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('driver.id', $driver->id);
    }

    public function test_driver_mobile_is_unique_at_the_database_boundary(): void
    {
        $this->driver('First', 'Driver', null, 1);

        $this->expectException(QueryException::class);
        $this->driver('Second', 'Driver', null, 2);
    }

    public function test_driver_cmr_list_keeps_documents_from_previous_and_current_companies(): void
    {
        $previousCompany = $this->company('COMPANY-PREVIOUS');
        $currentCompany = $this->company('COMPANY-CURRENT');
        $driver = $this->driver('Multi', 'Company', $currentCompany->id, 1);

        $previous = $this->cmr($previousCompany, $driver, now());
        $current = $this->cmr($currentCompany, $driver, now()->addSecond());

        Sanctum::actingAs($driver);

        $response = $this->getJson('/api/v1/driver/cmr');
        $response->assertOk();

        $documents = collect($response->json('data'));
        $this->assertSame([$current->id, $previous->id], $documents->pluck('id')->all());
        $this->assertSame(
            [$currentCompany->name_fa, $previousCompany->name_fa],
            $documents->pluck('company_name')->all(),
        );
    }

    public function test_driver_permit_and_cmr_history_survives_a_company_change_and_fleet_release(): void
    {
        $previousCompany = $this->company('COMPANY-PREVIOUS-FLEET');
        $currentCompany = $this->company('COMPANY-CURRENT-FLEET');
        $driver = $this->driver('Multi', 'Company', $currentCompany->id, 3);
        $previousFleet = Fleet::create([
            'company_id' => $previousCompany->id,
            'smart_card_number' => 'SMART-PREVIOUS-FLEET',
            'transit_plate' => '12-C-111-11',
            'truck_type' => 'tractor',
            'is_active' => false,
        ]);
        $currentFleet = Fleet::create([
            'company_id' => $currentCompany->id,
            'smart_card_number' => 'SMART-CURRENT-FLEET',
            'transit_plate' => '12-D-222-22',
            'truck_type' => 'tractor',
            'is_active' => true,
        ]);

        $previousPermit = PermitRequest::create([
            'd_code' => 'D-DRIVER-HISTORY-A',
            'company_id' => $previousCompany->id,
            'driver_id' => $driver->id,
            'fleet_id' => $previousFleet->id,
            'status' => 'issued',
            'request_type' => 'new',
            'issued_at' => now()->subDay(),
        ]);
        $currentPermit = PermitRequest::create([
            'd_code' => 'D-DRIVER-HISTORY-B',
            'company_id' => $currentCompany->id,
            'driver_id' => $driver->id,
            'fleet_id' => $currentFleet->id,
            'status' => 'issued',
            'request_type' => 'new',
            'issued_at' => now(),
        ]);
        $previousCmr = $this->cmr($previousCompany, $driver, now()->subDay());
        $previousCmr->update(['fleet_id' => $previousFleet->id]);
        $currentCmr = $this->cmr($currentCompany, $driver, now());
        $currentCmr->update(['fleet_id' => $currentFleet->id]);

        Sanctum::actingAs($driver);

        $permitIds = collect($this->getJson('/api/v1/driver/permits')->json('data'))
            ->pluck('id')
            ->all();
        $this->assertSame([$currentPermit->id, $previousPermit->id], $permitIds);

        $cmrIds = collect($this->getJson('/api/v1/driver/cmr')->json('data'))
            ->pluck('id')
            ->all();
        $this->assertSame([$currentCmr->id, $previousCmr->id], $cmrIds);

        $this->getJson('/api/v1/driver/cmr/' . $previousCmr->id)
            ->assertOk()
            ->assertJsonPath('data.id', $previousCmr->id);
    }

    private function driver(string $firstName, string $lastName, ?int $companyId, int $suffix): Driver
    {
        return Driver::create([
            'current_company_id' => $companyId,
            'national_code' => '00123456'.str_pad((string) $suffix, 2, '0', STR_PAD_LEFT),
            'passport_number' => 'P00123456'.$suffix,
            'first_name_fa' => $firstName,
            'last_name_fa' => $lastName,
            'first_name_en' => 'Test',
            'last_name_en' => 'Driver',
            'mobile' => '09157277001',
            'is_active' => true,
        ]);
    }

    private function company(string $code): Company
    {
        return Company::create([
            'company_code' => $code,
            'name_fa' => 'شرکت '.$code,
            'name_en' => 'Company '.$code,
            'address_fa' => 'مشهد',
            'address_en' => 'Mashhad',
        ]);
    }

    private function cmr(Company $company, Driver $driver, $issuedAt): CmrDocument
    {
        return CmrDocument::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => 'issued',
            'version' => 1,
            'integrity_hash' => str_repeat('a', 64),
            'number' => 'CMR-TEST-'.$company->id,
            'company_serial' => 'SERIAL-'.$company->id,
            'consignor_name' => 'Test Sender',
            'consignee_name' => 'Test Receiver',
            'carrier_name' => 'Test Carrier',
            'taking_over_place' => 'Mashhad',
            'delivery_place' => 'Istanbul',
            'issued_at' => $issuedAt,
        ]);
    }
}
