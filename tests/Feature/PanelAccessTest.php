<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login_from_every_web_panel(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('association.dashboard'))->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_enter_admin_and_association_panels(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('association.dashboard'))->assertOk();
    }

    public function test_association_cannot_enter_admin_or_company_panels(): void
    {
        $association = $this->userWithRole('association');

        $this->actingAs($association)->get(route('association.dashboard'))->assertOk();
        $this->actingAs($association)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($association)->get(route('dashboard'))->assertForbidden();
    }

    public function test_company_cannot_enter_admin_or_association_panels(): void
    {
        $company = $this->userWithRole('company');

        $this->actingAs($company)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($company)->get(route('association.dashboard'))->assertForbidden();
    }

    public function test_login_redirects_each_active_role_to_its_own_panel(): void
    {
        foreach ([
            'admin' => 'admin.dashboard',
            'association' => 'association.dashboard',
            'company' => 'dashboard',
        ] as $role => $route) {
            $user = $this->userWithRole($role);

            $this->post(route('login.post'), [
                'username' => $user->username,
                'password' => 'secret-password',
            ])->assertRedirect(route($route));

            $this->post(route('logout'));
        }
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->userWithRole('admin', 'inactive');

        $this->post(route('login.post'), [
            'username' => $user->username,
            'password' => 'secret-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    private function userWithRole(string $roleName, string $status = 'active'): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['title_fa' => $roleName],
        );

        return User::create([
            'role_id' => $role->id,
            'username' => $roleName.'-'.$status.'-'.uniqid(),
            'password' => Hash::make('secret-password'),
            'status' => $status,
            'is_manual' => true,
        ]);
    }
}
