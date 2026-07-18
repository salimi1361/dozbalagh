<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Shahbaz\Services\ShahbazSectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShahbazSectionSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_choose_visible_and_required_company_sections(): void
    {
        $admin = $this->user('admin', 'admin-sections');
        $this->actingAs($admin)->put(route('admin.shahbaz.settings.update'), [
            'sections' => ['profile' => ['visible' => 1, 'required' => 1], 'personnel' => ['visible' => 1]],
            'reminder_days' => [60, 30, 15, 0], 'panel_enabled' => 1, 'sms_enabled' => 0,
        ])->assertSessionHasNoErrors();

        $rows = app(ShahbazSectionService::class)->rows();
        $this->assertTrue($rows['profile']['visible']);
        $this->assertTrue($rows['profile']['required']);
        $this->assertTrue($rows['personnel']['visible']);
        $this->assertFalse($rows['board']['visible']);
    }

    public function test_required_incomplete_step_locks_later_company_steps(): void
    {
        $companyUser = $this->user('company', 'company-sections');
        $company = Company::create(['user_id' => $companyUser->id, 'company_code' => 'SEC-1', 'name_fa' => 'شرکت تست', 'name_en' => 'Test', 'address_fa' => 'تهران', 'address_en' => 'Tehran']);
        $rows = array_values(app(ShahbazSectionService::class)->accessibleRows($company));
        $this->assertSame('profile', $rows[0]['key']);
        $this->assertFalse($rows[0]['locked']);
        $this->assertTrue($rows[1]['locked']);
    }

    private function user(string $role, string $username): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role], ['title_fa' => $role]);
        return User::create(['role_id' => $roleModel->id, 'username' => $username, 'password' => Hash::make('secret'), 'status' => 'active']);
    }
}
