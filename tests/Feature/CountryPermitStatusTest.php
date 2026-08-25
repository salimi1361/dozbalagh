<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
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

        $freshCountry = $country->fresh();
        $this->assertSame(
            ['bilateral', 'third_country'],
            $freshCountry->requestable_permit_types,
        );
        $this->assertSame(['bilateral', 'transit', 'third_country'], $freshCountry->allowed_permit_types);
    }

    public function test_admin_can_disable_all_permits_for_a_country(): void
    {
        $country = $this->country();

        $this->actingAs($this->admin())->put(
            route('admin.countries.permit-statuses.update', $country),
        )->assertRedirect();

        $freshCountry = $country->fresh();
        $this->assertSame([], $freshCountry->requestable_permit_types);
        $this->assertSame(['bilateral', 'transit', 'third_country'], $freshCountry->allowed_permit_types);
    }

    public function test_country_edit_keeps_inventory_definition_separate_from_request_selection(): void
    {
        $country = $this->country();

        $this->actingAs($this->admin())->put(
            route('admin.countries.update', $country),
            [
                'name' => $country->name,
                'code' => $country->code,
                'price' => 0,
                'member_price' => 0,
                'non_member_price' => 0,
                'validity_days' => 30,
                'permit_types' => ['bilateral', 'transit', 'third_country'],
                'requestable_permit_types_present' => '1',
                'requestable_permit_types' => ['transit'],
            ],
        )->assertRedirect();

        $freshCountry = $country->fresh();
        $this->assertSame(['bilateral', 'transit', 'third_country'], $freshCountry->allowed_permit_types);
        $this->assertSame(['transit'], $freshCountry->requestable_permit_types);
    }

    public function test_admin_can_toggle_country_availability_without_changing_permit_definitions(): void
    {
        $country = $this->country();
        $admin = $this->admin();

        $this->actingAs($admin)->post(
            route('admin.countries.toggle-status', $country),
        )->assertRedirect();

        $freshCountry = $country->fresh();
        $this->assertFalse((bool) $freshCountry->is_active);
        $this->assertSame(['bilateral', 'transit', 'third_country'], $freshCountry->allowed_permit_types);
        $this->assertSame(['bilateral', 'transit', 'third_country'], $freshCountry->requestable_permit_types);

        $this->actingAs($admin)->post(
            route('admin.countries.toggle-status', $country),
        )->assertRedirect();

        $this->assertTrue((bool) $country->fresh()->is_active);
    }

    public function test_disabled_permit_type_is_rejected_for_new_request_without_changing_inventory_definition(): void
    {
        $country = $this->country();
        $country->update(['requestable_permit_types' => ['transit']]);

        Route::post('/_test/requestable-permits', fn () => response('ok'))
            ->middleware(\App\Http\Middleware\EnsureRequestablePermitTypes::class);

        $this->from('/request-form')->post('/_test/requestable-permits', [
            'destinations' => [[
                'country_id' => $country->id,
                'permit_type' => 'bilateral',
            ]],
        ])->assertRedirect('/request-form')
            ->assertSessionHasErrors('destinations');

        $this->assertSame(
            ['bilateral', 'transit', 'third_country'],
            $country->fresh()->allowed_permit_types,
        );
    }

    public function test_inactive_country_is_rejected_for_new_request(): void
    {
        $country = $this->country();
        $country->update(['is_active' => false]);

        Route::post('/_test/inactive-country', fn () => response('ok'))
            ->middleware(\App\Http\Middleware\EnsureRequestablePermitTypes::class);

        $this->from('/request-form')->post('/_test/inactive-country', [
            'destinations' => [[
                'country_id' => $country->id,
                'permit_type' => 'bilateral',
            ]],
        ])->assertRedirect('/request-form')
            ->assertSessionHasErrors('destinations');
    }

    public function test_enabled_permit_type_can_continue_to_request_controller(): void
    {
        $country = $this->country();
        $country->update(['requestable_permit_types' => ['transit']]);

        Route::post('/_test/requestable-permits-enabled', fn () => response('ok'))
            ->middleware(\App\Http\Middleware\EnsureRequestablePermitTypes::class);

        $this->post('/_test/requestable-permits-enabled', [
            'destinations' => [[
                'country_id' => $country->id,
                'permit_type' => 'transit',
            ]],
        ])->assertOk();
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
            'allowed_permit_types' => ['bilateral', 'transit', 'third_country'],
            'requestable_permit_types' => ['bilateral', 'transit', 'third_country'],
        ]);
    }
}
