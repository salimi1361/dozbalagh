<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyAccountRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_company_account_with_company_role(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['title_fa' => 'ادمین کل']);
        $companyRole = Role::firstOrCreate(['name' => 'company'], ['title_fa' => 'شرکت']);
        $admin = User::create([
            'role_id' => $adminRole->id,
            'username' => 'admin-test',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.companies.store'), $this->companyPayload([
            'national_id' => '14001234567',
        ]))->assertSessionHasNoErrors();

        $companyUser = User::where('username', '14001234567')->firstOrFail();

        $this->assertSame($companyRole->id, $companyUser->role_id);

        $this->post(route('logout'));
        $this->post(route('login.post'), [
            'username' => '14001234567',
            'password' => '12345678',
        ])->assertRedirect(route('company.profile.edit'));
    }

    public function test_existing_admin_account_cannot_be_reused_as_a_company_account(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['title_fa' => 'ادمین کل']);
        $admin = User::create([
            'role_id' => $adminRole->id,
            'username' => '14007654321',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        $this->actingAs($admin)->from(route('admin.companies.create'))->post(route('admin.companies.store'), $this->companyPayload([
            'national_id' => '14007654321',
        ]))->assertRedirect(route('admin.companies.create'))
            ->assertSessionHasErrors('national_id');

        $this->assertDatabaseMissing('companies', ['national_id' => '14007654321']);
        $this->assertSame($adminRole->id, $admin->fresh()->role_id);
    }

    public function test_login_repairs_an_old_company_account_with_an_incorrect_admin_role(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['title_fa' => 'ادمین کل']);
        Role::firstOrCreate(['name' => 'company'], ['title_fa' => 'شرکت']);
        $admin = User::create([
            'role_id' => $adminRole->id,
            'username' => 'admin-creator',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.companies.store'), $this->companyPayload([
            'national_id' => '14009876543',
        ]))->assertSessionHasNoErrors();

        $companyUser = User::where('username', '14009876543')->firstOrFail();
        $companyUser->update(['role_id' => $adminRole->id]);

        $this->post(route('logout'));
        $this->post(route('login.post'), [
            'username' => '14009876543',
            'password' => '12345678',
        ])->assertRedirect(route('company.profile.edit'));

        $this->assertSame('company', $companyUser->fresh()->role->name);
    }

    private function companyPayload(array $overrides = []): array
    {
        return array_replace([
            'national_id' => '14001234567',
            'name' => 'شرکت حمل و نقل بین المللی آزمایشی',
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
            'is_association_member' => '1',
        ], $overrides);
    }
}
