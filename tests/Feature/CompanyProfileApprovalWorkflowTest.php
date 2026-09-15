<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Shahbaz\Models\DossierReview;
use App\Shahbaz\Services\CompanyEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyProfileApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_requires_profile_submission_and_association_approval(): void
    {
        [$companyUser, $company] = $this->companyAccount();

        $this->post(route('login.post'), [
            'username' => $companyUser->username,
            'password' => '12345678',
        ])->assertRedirect(route('company.profile.edit'));

        $this->actingAs($companyUser)->put(route('company.profile.update'), $this->completeProfile())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => 'pending_review',
            'city' => 'مشهد',
        ]);
        $this->assertNotNull($company->fresh()->profile_submitted_at);
        $this->actingAs($companyUser)->get(route('dashboard'))->assertRedirect(route('company.profile.edit'));

        $association = $this->userWithRole('association', 'association-reviewer');
        $this->actingAs($association)->get(route('admin.companies.review', $company->id))->assertOk();
        $this->actingAs($association)->put(route('admin.companies.approve', $company->id))->assertRedirect();

        $this->assertSame('approved', $company->fresh()->status);
        $this->assertSame('verified', $company->fresh()->shahbaz_verification_status);
        $this->actingAs($companyUser)->get(route('dashboard'))->assertOk();
    }

    public function test_association_can_return_profile_for_correction(): void
    {
        [$companyUser, $company] = $this->companyAccount('pending_review');
        $association = $this->userWithRole('association', 'association-correction');

        $this->actingAs($association)->put(route('admin.companies.request-correction', $company->id), [
            'profile_review_note' => 'آدرس لاتین و تلفن شرکت اصلاح شود.',
        ])->assertRedirect(route('admin.companies.index'));

        $company->refresh();
        $this->assertSame('correction_required', $company->status);
        $this->assertSame('آدرس لاتین و تلفن شرکت اصلاح شود.', $company->profile_review_note);
        $this->actingAs($companyUser)->get(route('company.profile.edit'))
            ->assertOk()
            ->assertSee('آدرس لاتین و تلفن شرکت اصلاح شود.');
    }

    public function test_final_shahbaz_approval_reopens_company_operational_menus(): void
    {
        [$companyUser, $company] = $this->companyAccount();
        $company->forceFill($this->completeProfile() + [
            'status' => 'shahbaz_review',
            'shahbaz_verification_status' => 'pending_association_review',
        ])->save();

        $association = $this->userWithRole('association', 'association-shahbaz-final');
        SystemSetting::setValue('panel_features', [
            'association' => ['shahbaz_company_review' => true],
        ]);
        DossierReview::create([
            'company_id' => $company->id,
            'section_key' => 'profile',
            'entity_type' => 'company',
            'entity_id' => $company->id,
            'status' => 'approved',
            'entity_snapshot' => $company->fresh()->toArray(),
            'reviewed_by_user_id' => $association->id,
            'reviewed_at' => now()->addSecond(),
        ]);

        $this->actingAs($association)->post(route('association.shahbaz.companies.review', $company), [
            'result' => 'verified',
            'description' => 'پرونده کامل است و دسترسی نهایی تأیید شد.',
            'activity_license_number' => 'LIC-TEST-1001',
            'activity_license_issued_on_jalali' => '1405/05/13',
            'activity_license_expires_on_jalali' => '1406/05/13',
        ])->assertRedirect(route('association.shahbaz.companies.index'));

        $company->refresh();
        $this->assertSame('approved', $company->status);
        $this->assertSame('verified', $company->shahbaz_verification_status);
        $this->assertTrue(app(CompanyEligibilityService::class)->canOperate($company));
        $this->actingAs($companyUser)->get(route('dashboard'))->assertOk();
    }

    private function companyAccount(string $status = 'profile_incomplete'): array
    {
        $user = $this->userWithRole('company', '14001234567');
        $company = new Company();
        $company->forceFill([
            'user_id' => $user->id,
            'company_code' => 'C-TEST-PROFILE',
            'name' => 'شرکت حمل و نقل بین المللی آزمایش',
            'name_fa' => 'شرکت حمل و نقل بین المللی آزمایش',
            'name_en' => 'Test International Transport Company',
            'national_id' => '14001234567',
            'registration_number' => '12345',
            'ceo_name' => 'مدیر آزمایشی',
            'ceo_mobile' => '09121234567',
            'phone' => '000',
            'address_fa' => 'ثبت نشده',
            'address_en' => 'N/A',
            'status' => $status,
            'is_association_member' => true,
        ])->save();

        $company = Company::where('company_code', 'C-TEST-PROFILE')->firstOrFail();

        return [$user, $company];
    }

    private function userWithRole(string $roleName, string $username): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['title_fa' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'username' => $username,
            'password' => Hash::make('12345678'),
            'status' => 'active',
            'is_manual' => true,
        ]);
    }

    private function completeProfile(): array
    {
        return [
            'name_fa' => 'شرکت حمل و نقل بین المللی آزمایش',
            'name_en' => 'Test International Transport Company',
            'registration_number' => '12345',
            'ceo_name' => 'مدیر آزمایشی',
            'ceo_national_code' => '0921234567',
            'ceo_mobile' => '09121234567',
            'phone' => '05137654321',
            'postal_code' => '9187654321',
            'province' => 'خراسان رضوی',
            'city' => 'مشهد',
            'activity_type' => 'حمل‌ونقل بین‌المللی کالا',
            'address_fa' => 'مشهد، بلوار سجاد، پلاک ۱۰',
            'address_en' => 'No. 10, Sajjad Blvd, Mashhad',
        ];
    }
}
