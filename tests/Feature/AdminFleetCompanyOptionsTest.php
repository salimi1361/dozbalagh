<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FleetController;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminFleetCompanyOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fleet_company_options_include_all_registered_company_statuses(): void
    {
        $role = Role::create(['name' => 'company', 'title_fa' => 'شرکت']);
        $user = User::create([
            'role_id' => $role->id,
            'username' => 'fleet-company-options-test',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $approvedCompany = $this->company($user, 'FLEET-OPTIONS-APPROVED', 'شرکت الف', 'approved');
        $pendingCompany = $this->company($user, 'FLEET-OPTIONS-PENDING', 'شرکت ب', 'pending_review');

        $view = app(FleetController::class)->index();
        $companyIds = $view->getData()['companies']->pluck('id');

        $this->assertTrue($companyIds->contains($approvedCompany->id));
        $this->assertTrue($companyIds->contains($pendingCompany->id));
    }

    private function company(User $user, string $code, string $name, string $status): Company
    {
        return Company::forceCreate([
            'user_id' => $user->id,
            'company_code' => $code,
            'name_fa' => $name,
            'name_en' => 'Company '.$code,
            'address_fa' => 'تهران',
            'address_en' => 'Tehran',
            'status' => $status,
        ]);
    }
}
