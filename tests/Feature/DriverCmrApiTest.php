<?php

namespace Tests\Feature;

use App\CMR\Models\CmrCompanySetting;
use App\CMR\Models\CmrDocument;
use App\CMR\Services\CmrDriverNotificationService;
use App\Jobs\SendCmrDriverPush;
use App\Models\Company;
use App\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverCmrApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_complete_simple_lifecycle_and_stop_tracking_after_delivery(): void
    {
        [$driver, $document] = $this->issuedDocument();
        Sanctum::actingAs($driver);

        $this->postJson("/api/v1/driver/cmr/{$document->id}/accept", [
            'confirmed' => true,
            'document_version' => 1,
            'document_hash' => str_repeat('a', 64),
        ])->assertOk()->assertJsonPath('status', 'accepted');

        $this->postJson("/api/v1/driver/cmr/{$document->id}/start")
            ->assertOk()->assertJsonPath('status', 'in_transit');

        $this->postJson("/api/v1/driver/cmr/{$document->id}/deliver", [
            'confirmed_by_consignee' => true,
            'consignee_signer_name' => 'Test Receiver',
            'document_version' => 1,
            'document_hash' => str_repeat('a', 64),
        ])->assertOk()->assertJsonPath('status', 'delivered');

        $this->postJson("/api/v1/driver/cmr/{$document->id}/tracking/event/log", [
            'event_type' => 'tracking_stopped',
        ])->assertOk();

        $this->assertDatabaseHas('driver_events', [
            'driver_id' => $driver->id,
            'cmr_document_id' => $document->id,
            'event_type' => 'tracking_stopped',
        ]);
    }

    public function test_evidence_handover_rejects_stale_document_and_invalid_stage_outcome(): void
    {
        [$driver, $document, $company] = $this->issuedDocument();
        Sanctum::actingAs($driver);
        CmrCompanySetting::forCompany($company->id)->update([
            'origin_evidence_enabled' => true,
            'allow_delivery_exceptions' => true,
        ]);

        $base = [
            'client_uuid' => (string) Str::uuid(),
            'stage' => 'origin',
            'outcome' => 'accepted',
            'document_version' => 2,
            'document_hash' => str_repeat('b', 64),
            'confirmed' => true,
        ];

        $this->postJson("/api/v1/driver/cmr/{$document->id}/handover", $base)
            ->assertConflict();

        $this->postJson("/api/v1/driver/cmr/{$document->id}/handover", [
            ...$base,
            'client_uuid' => (string) Str::uuid(),
            'outcome' => 'delivered',
            'document_version' => 1,
            'document_hash' => str_repeat('a', 64),
        ])->assertUnprocessable();

        $this->postJson("/api/v1/driver/cmr/{$document->id}/handover", [
            ...$base,
            'client_uuid' => (string) Str::uuid(),
            'document_version' => 1,
            'document_hash' => str_repeat('a', 64),
        ])->assertCreated();

        $this->assertSame('accepted', $document->fresh()->status);
        $this->assertDatabaseHas('cmr_handover_records', [
            'cmr_document_id' => $document->id,
            'stage' => 'origin',
            'outcome' => 'accepted',
        ]);
    }

    public function test_issued_cmr_dispatches_driver_push_job(): void
    {
        Queue::fake();
        [$driver, $document] = $this->issuedDocument();
        $driver->update(['mobile' => null]);

        app(CmrDriverNotificationService::class)->sendIssued($document->id);

        Queue::assertPushed(
            SendCmrDriverPush::class,
            fn (SendCmrDriverPush $job) => $job->documentId === $document->id,
        );
    }

    private function issuedDocument(): array
    {
        $company = Company::create([
            'company_code' => 'CMR-DRIVER-TEST',
            'name_fa' => 'شرکت آزمایشی CMR',
            'name_en' => 'CMR Test Carrier',
            'address_fa' => 'مشهد',
            'address_en' => 'Mashhad',
        ]);
        $driver = Driver::create([
            'current_company_id' => $company->id,
            'national_code' => '0012345678',
            'first_name_fa' => 'راننده',
            'last_name_fa' => 'آزمایشی',
            'first_name_en' => 'Test',
            'last_name_en' => 'Driver',
            'mobile' => '09120000000',
        ]);
        $document = CmrDocument::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => 'issued',
            'version' => 1,
            'integrity_hash' => str_repeat('a', 64),
            'number' => 'CMR-TEST-001',
            'company_serial' => 'SERIAL-001',
            'consignor_name' => 'Test Sender',
            'consignee_name' => 'Test Receiver',
            'carrier_name' => 'CMR Test Carrier',
            'taking_over_place' => 'Mashhad',
            'delivery_place' => 'Istanbul',
            'issued_at' => now(),
        ]);

        return [$driver, $document, $company];
    }
}
