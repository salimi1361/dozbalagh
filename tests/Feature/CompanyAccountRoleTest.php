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

        $this->actingAs($admin)->post(route('admin.companies.store'), [
            'national_id' => '14001234567',
            'name' => 'شرکت آزمایشی',
            'ceo_mobile' => '09121234567',
        ])->assertSessionHasNoErrors();

        $companyUser = User::where('username', '14001234567')->firstOrFail();

        $this->assertSame($companyRole->id, $companyUser->role_id);

        $this->post(route('logout'));
        $this->post(route('login.post'), [
            'username' => '14001234567',
            'password' => '12345678',
        ])->assertRedirect(route('dashboard'));
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

        $this->actingAs($admin)->from(route('admin.companies.create'))->post(route('admin.companies.store'), [
            'national_id' => '14007654321',
            'name' => 'شرکت تکراری',
            'ceo_mobile' => '09121111111',
        ])->assertRedirect(route('admin.companies.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('companies', ['national_id' => '14007654321']);
        $this->assertSame($adminRole->id, $admin->fresh()->role_id);
    }
}
