<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_own_password(): void
    {
        $role = Role::create(['name' => 'admin', 'title_fa' => 'مدیر']);
        $admin = User::create([
            'role_id' => $role->id,
            'username' => 'admin-security-test',
            'password' => Hash::make('old-password'),
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.account.security.edit'))
            ->assertOk()
            ->assertSee('تغییر رمز ورود پنل مدیریت');

        $this->actingAs($admin)
            ->put(route('admin.account.security.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('admin.account.security.edit'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password', $admin->fresh()->password));
    }

    public function test_non_admin_cannot_open_admin_password_page(): void
    {
        $role = Role::create(['name' => 'association', 'title_fa' => 'انجمن']);
        $user = User::create([
            'role_id' => $role->id,
            'username' => 'association-security-test',
            'password' => Hash::make('old-password'),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('admin.account.security.edit'))
            ->assertForbidden();
    }
}
