<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AssociationUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_association_user(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'title_fa' => 'ادمین']);
        $admin = User::create([
            'role_id' => $adminRole->id,
            'username' => 'admin-test',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.association-users.store'), [
            'username' => 'association-two',
            'mobile' => '09120000000',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ])->assertRedirect();

        $user = User::where('username', 'association-two')->firstOrFail();
        $this->assertTrue($user->hasRole('association'));
        $this->assertSame('active', $user->status);
    }
}
