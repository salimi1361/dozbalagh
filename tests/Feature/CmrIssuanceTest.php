<?php

namespace Tests\Feature;

use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrSetting;
use App\CMR\Services\CmrIssuanceService;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CmrIssuanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issuance_deducts_fee_once_and_stores_immutable_version(): void
    {
        $role = Role::create(['name' => 'admin', 'title_fa' => 'مدیر']);
        $user = User::create(['role_id' => $role->id, 'username' => 'cmr-admin-1', 'password' => Hash::make('secret'), 'status' => 'active']);
        $company = $this->company($user, '001');
        $wallet = Wallet::updateOrCreate(['company_id' => $company->id], ['balance' => 500000, 'blocked_balance' => 0]);
        CmrSetting::create(['issuance_fee' => 100000, 'currency' => 'IRR', 'billing_enabled' => true]);
        $document = $this->draft($company);
        $document->goods()->create(['line_number' => 1, 'description' => 'Test cargo']);

        app(CmrIssuanceService::class)->issue($document, $user->id);

        $this->assertSame('issued', $document->fresh()->status);
        $this->assertSame('400000.00', $wallet->fresh()->balance);
        $this->assertDatabaseCount('cmr_wallet_entries', 1);
        $this->assertDatabaseCount('cmr_versions', 1);
        try {
            app(CmrIssuanceService::class)->issue($document, $user->id);
            $this->fail('Expected duplicate issuance exception.');
        } catch (\RuntimeException) {
            // The second request must not create another charge.
        }
        $this->assertSame('400000.00', $wallet->fresh()->balance);
        $this->assertDatabaseCount('cmr_wallet_entries', 1);
    }

    public function test_insufficient_balance_keeps_document_as_draft(): void
    {
        $role = Role::create(['name' => 'admin', 'title_fa' => 'مدیر']);
        $user = User::create(['role_id' => $role->id, 'username' => 'cmr-admin-2', 'password' => Hash::make('secret'), 'status' => 'active']);
        $company = $this->company($user, '002');
        Wallet::updateOrCreate(['company_id' => $company->id], ['balance' => 10, 'blocked_balance' => 0]);
        CmrSetting::create(['issuance_fee' => 100000, 'currency' => 'IRR', 'billing_enabled' => true]);
        $document = $this->draft($company);

        try {
            app(CmrIssuanceService::class)->issue($document, $user->id);
            $this->fail('Expected insufficient balance exception.');
        } catch (\RuntimeException) {
            $this->assertSame('draft', $document->fresh()->status);
            $this->assertDatabaseCount('cmr_wallet_entries', 0);
        }
    }

    private function draft(Company $company): CmrDocument
    {
        return CmrDocument::create([
            'uuid' => (string) Str::uuid(), 'company_id' => $company->id, 'status' => 'draft',
            'consignor_name' => 'Sender', 'consignee_name' => 'Receiver', 'carrier_name' => 'Carrier',
            'taking_over_place' => 'Tehran', 'delivery_place' => 'Berlin',
        ]);
    }

    private function company(User $user, string $code): Company
    {
        return Company::create([
            'user_id' => $user->id,
            'company_code' => $code,
            'name_fa' => 'شرکت آزمایشی',
            'name_en' => 'Test Carrier',
            'address_fa' => 'تهران',
            'address_en' => 'Tehran',
            'national_id' => '14000000'.$code,
            'registration_number' => 'REG-'.$code,
            'ceo_name' => 'مدیر آزمایشی',
            'ceo_national_code' => '0012345'.$code,
            'ceo_mobile' => '09120000'.$code,
            'phone' => '0210000'.$code,
            'postal_code' => '1234567'.$code,
            'province' => 'تهران',
            'city' => 'تهران',
            'activity_type' => 'حمل‌ونقل بین‌المللی',
            'shahbaz_verification_status' => 'verified',
            'activity_license_number' => 'LIC-'.$code,
            'activity_license_issued_on' => now()->subDay(),
            'activity_license_expires_on' => now()->addYear(),
            'activity_license_status' => 'active',
        ]);
    }

    public function test_unverified_company_cannot_issue_cmr(): void
    {
        $role = Role::create(['name' => 'admin', 'title_fa' => 'مدیر']);
        $user = User::create(['role_id' => $role->id, 'username' => 'cmr-admin-blocked', 'password' => Hash::make('secret'), 'status' => 'active']);
        $company = $this->company($user, '003');
        $company->update(['shahbaz_verification_status' => 'pending_association_review']);
        $document = $this->draft($company);

        $this->expectException(\RuntimeException::class);
        app(CmrIssuanceService::class)->issue($document, $user->id);
    }
}
