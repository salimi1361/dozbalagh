<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Shahbaz\Models\LicenseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShahbazLicenseRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_only_create_and_edit_its_own_draft_request(): void
    {
        [$user, $company] = $this->company('request-company-one', 'REQ-1');
        [$otherUser, $otherCompany] = $this->company('request-company-two', 'REQ-2');

        $this->actingAs($user)->post(route('company.shahbaz.requests.store'), [
            'request_type' => 'initial',
            'activity_scope' => 'domestic',
            'activity_type' => 'حمل‌ونقل کالا',
        ])->assertSessionHasNoErrors();

        $item = LicenseRequest::where('company_id', $company->id)->firstOrFail();
        $this->assertSame('draft', $item->status);
        $this->assertCount(1, $item->histories);

        $otherItem = LicenseRequest::create([
            'company_id' => $otherCompany->id, 'tracking_code' => 'SH-OTHER',
            'request_type' => 'initial', 'activity_scope' => 'domestic',
            'activity_type' => 'کالا', 'status' => 'draft', 'created_by_user_id' => $otherUser->id,
        ]);
        $this->actingAs($user)->get(route('company.shahbaz.requests.edit', $otherItem))->assertNotFound();
    }

    public function test_renewal_requires_an_existing_license(): void
    {
        [$user] = $this->company('request-renewal', 'REQ-3');

        $this->actingAs($user)->post(route('company.shahbaz.requests.store'), [
            'request_type' => 'renewal', 'activity_scope' => 'domestic', 'activity_type' => 'کالا',
        ])->assertStatus(422);
    }

    private function company(string $username, string $code): array
    {
        $role = Role::firstOrCreate(['name' => 'company'], ['title_fa' => 'شرکت']);
        $user = User::create(['role_id' => $role->id, 'username' => $username, 'password' => Hash::make('secret'), 'status' => 'active']);
        $company = Company::create([
            'user_id' => $user->id, 'company_code' => $code, 'name_fa' => 'شرکت تست',
            'name_en' => 'Test', 'address_fa' => 'مشهد', 'address_en' => 'Mashhad',
            'shahbaz_verification_status' => 'profile_incomplete',
        ]);
        return [$user, $company];
    }
}
