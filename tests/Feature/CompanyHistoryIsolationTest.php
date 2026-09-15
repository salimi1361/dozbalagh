<?php

namespace Tests\Feature;

use App\CMR\Models\CmrDocument;
use App\CMR\Http\Controllers\Admin\CmrController;
use App\Http\Controllers\Company\CmrReportController;
use App\Http\Controllers\Company\DriverController as CompanyDriverController;
use App\Http\Controllers\Company\FleetController as CompanyFleetController;
use App\Http\Controllers\Company\ReportController;
use App\Models\Company;
use App\Models\DozbalaghBatch;
use App\Models\DozbalaghItem;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\PermitRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CompanyDriverMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyHistoryIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_driver_and_fleet_overviews_only_count_current_company_records(): void
    {
        [$companyA, $companyAUser] = $this->companyAccount('A');
        [$companyB, $companyBUser] = $this->companyAccount('B');
        $driver = $this->driver($companyB->id, '0012345601', '09157277011');
        $fleet = Fleet::create([
            'company_id' => $companyB->id,
            'smart_card_number' => 'SMART-HISTORY-1',
            'transit_plate' => '12-A-345-67',
            'truck_type' => 'tractor',
            'is_active' => true,
        ]);
        $batch = DozbalaghBatch::create([
            'country_name' => 'Turkey',
            'serial_start' => 1001,
            'serial_end' => 1003,
            'total_quantity' => 3,
            'status' => 'active',
        ]);

        DozbalaghItem::create([
            'batch_id' => $batch->id,
            'serial_number' => 1001,
            'company_id' => $companyA->id,
            'driver_id' => $driver->id,
            'fleet_id' => $fleet->id,
            'lifecycle_status' => 'issued',
        ]);
        DozbalaghItem::create([
            'batch_id' => $batch->id,
            'serial_number' => 1002,
            'company_id' => $companyB->id,
            'driver_id' => $driver->id,
            'fleet_id' => $fleet->id,
            'lifecycle_status' => 'issued',
        ]);

        $driver->notify(new CompanyDriverMessageNotification(
            companyId: $companyA->id,
            companyName: 'Company A',
            title: 'Old company message',
            message: 'Old message',
        ));
        $driver->notify(new CompanyDriverMessageNotification(
            companyId: $companyB->id,
            companyName: 'Company B',
            title: 'Current company message',
            message: 'Current message',
        ));

        $this->actingAs($companyBUser);
        $driverView = app(CompanyDriverController::class)->index();
        $driverRow = collect($driverView->getData()['drivers'])->firstWhere('id', $driver->id);

        $this->assertNotNull($driverRow);
        $this->assertSame(1, (int) $driverRow->active_dozbalaghs);
        $this->assertSame(1, (int) $driverRow->unread_company_messages);

        $fleetView = app(CompanyFleetController::class)->index();
        $fleetRow = collect($fleetView->getData()['fleets'])->firstWhere('id', $fleet->id);

        $this->assertNotNull($fleetRow);
        $this->assertSame(1, (int) $fleetRow->active_dozbalagh_count);
    }

    public function test_company_permit_and_cmr_reports_only_return_current_company_records(): void
    {
        [$companyA, $companyAUser] = $this->companyAccount('REPORT-A');
        [$companyB, $companyBUser] = $this->companyAccount('REPORT-B');
        $driver = $this->driver($companyB->id, '0012345602', '09157277012');
        $fleet = Fleet::create([
            'company_id' => $companyB->id,
            'smart_card_number' => 'SMART-REPORT-1',
            'transit_plate' => '12-B-456-78',
            'truck_type' => 'tractor',
            'is_active' => true,
        ]);

        $previousPermit = PermitRequest::create([
            'd_code' => 'D-HISTORY-A',
            'company_id' => $companyA->id,
            'driver_id' => $driver->id,
            'fleet_id' => $fleet->id,
            'status' => 'issued',
            'request_type' => 'new',
            'issued_at' => now()->subDay(),
        ]);
        $currentPermit = PermitRequest::create([
            'd_code' => 'D-HISTORY-B',
            'company_id' => $companyB->id,
            'driver_id' => $driver->id,
            'fleet_id' => $fleet->id,
            'status' => 'issued',
            'request_type' => 'new',
            'issued_at' => now(),
        ]);
        $previousCmr = $this->cmr($companyA, $driver, $fleet, 'CMR-HISTORY-A', now()->subDay());
        $currentCmr = $this->cmr($companyB, $driver, $fleet, 'CMR-HISTORY-B', now());

        $this->actingAs($companyBUser);
        $permitView = app(ReportController::class)->index(Request::create('/web/company/report/index', 'GET'));
        $permitIds = $permitView->getData()['permits']->getCollection()->pluck('id')->all();
        $this->assertSame([$currentPermit->id], $permitIds);

        $cmrView = app(CmrReportController::class)->index(Request::create('/web/company/cmr-reports', 'GET'));
        $cmrIds = $cmrView->getData()['documents']->getCollection()->pluck('id')->all();
        $this->assertSame([$currentCmr->id], $cmrIds);
    }

    public function test_company_cannot_create_a_cmr_draft_for_another_company_by_tampering_with_company_id(): void
    {
        [$companyA] = $this->companyAccount('WRITE-A');
        [$companyB, $companyBUser] = $this->companyAccount('WRITE-B');
        $driver = $this->driver($companyB->id, '0012345603', '09157277013');
        $fleet = Fleet::create([
            'company_id' => $companyB->id,
            'smart_card_number' => 'SMART-WRITE-1',
            'transit_plate' => '12-E-333-33',
            'truck_type' => 'tractor',
            'is_active' => true,
        ]);

        $request = Request::create('/admin/cmr', 'POST', [
            'company_id' => $companyA->id,
            'driver_id' => $driver->id,
            'fleet_id' => $fleet->id,
            'language' => 'en',
            'transport_type' => 'road',
            'consignor_name' => 'Sender',
            'consignee_name' => 'Receiver',
            'carrier_name' => 'Carrier',
            'taking_over_place' => 'Mashhad',
            'delivery_place' => 'Istanbul',
            'goods' => [['description' => 'Cargo']],
        ]);

        $this->actingAs($companyBUser);
        app(CmrController::class)->store($request);

        $this->assertDatabaseHas('cmr_documents', [
            'company_id' => $companyB->id,
            'driver_id' => $driver->id,
            'fleet_id' => $fleet->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseMissing('cmr_documents', [
            'company_id' => $companyA->id,
            'driver_id' => $driver->id,
            'fleet_id' => $fleet->id,
        ]);
    }

    private function companyAccount(string $suffix): array
    {
        $role = Role::firstOrCreate(['name' => 'company'], ['title_fa' => 'Company']);
        $user = User::create([
            'role_id' => $role->id,
            'username' => 'company-history-' . strtolower($suffix),
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);
        $company = Company::create([
            'user_id' => $user->id,
            'company_code' => 'COMP-HISTORY-' . $suffix,
            'name_fa' => 'شرکت تاریخچه ' . $suffix,
            'name_en' => 'History Company ' . $suffix,
            'address_fa' => 'مشهد',
            'address_en' => 'Mashhad',
            'status' => 'approved',
        ]);

        return [$company, $user];
    }

    private function driver(int $companyId, string $nationalCode, string $mobile): Driver
    {
        return Driver::create([
            'current_company_id' => $companyId,
            'national_code' => $nationalCode,
            'passport_number' => 'P-' . $nationalCode,
            'first_name_fa' => 'راننده',
            'last_name_fa' => 'تاریخچه',
            'first_name_en' => 'History',
            'last_name_en' => 'Driver',
            'mobile' => $mobile,
            'contract_status' => 'bound',
            'is_active' => true,
        ]);
    }

    private function cmr(Company $company, Driver $driver, Fleet $fleet, string $number, $issuedAt): CmrDocument
    {
        return CmrDocument::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'fleet_id' => $fleet->id,
            'status' => 'issued',
            'version' => 1,
            'integrity_hash' => str_repeat('b', 64),
            'number' => $number,
            'consignor_name' => 'Sender',
            'consignee_name' => 'Receiver',
            'carrier_name' => 'Carrier',
            'taking_over_place' => 'Mashhad',
            'delivery_place' => 'Istanbul',
            'issued_at' => $issuedAt,
        ]);
    }
}
