<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CountryPermitStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_enable_selected_permits_for_a_country(): void
    {
        $country = $this->country();

        $this->actingAs($this->admin())->put(
            route('admin.countries.permit-statuses.update', $country),
            ['permit_types' => ['bilateral', 'third_country']],
        )->assertRedirect();

        $this->assertSame(
            ['bilateral', 'third_country'],
            $country->fresh()->allowed_permit_types,
        );
    }

    public function test_admin_can_disable_all_permits_for_a_country(): void
    {
        $country = $this->country();

        $this->actingAs($this->admin())->put(
            route('admin.countries.permit-statuses.update', $country),
        )->assertRedirect();

        $this->assertSame([], $country->fresh()->allowed_permit_types);
    }

    private function admin(): User
    {
        $role = Role::create(['name' => 'admin', 'title_fa' => 'ادمین']);

        return User::create([
            'role_id' => $role->id,
            'username' => 'country-permit-admin',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);
    }

    private function country(): Country
    {
        return Country::create([
            'name' => 'کشور آزمایشی',
            'code' => 'TT',
            'price' => 0,
            'validity_days' => 30,
            'is_active' => true,
            'allowed_permit_types' => ['bilateral', 'transit'],
        ]);
    }
}
