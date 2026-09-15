<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\SystemSetting;
use App\Services\PanelFeatureService;
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
            'features' => ['shahbaz_company_review'],
        ])->assertRedirect();

        $user = User::where('username', 'association-two')->firstOrFail();
        $this->assertTrue($user->hasRole('association'));
        $this->assertSame('active', $user->status);
        $this->assertSame(['shahbaz_company_review'], $user->association_feature_keys);
    }

    public function test_admin_can_update_an_association_users_feature_access(): void
    {
        $admin = $this->user('admin', 'admin-test');
        $association = $this->user('association', 'association-test');

        $this->actingAs($admin)->put(route('admin.association-users.features', $association), [
            'features' => ['shahbaz_company_review'],
        ])->assertRedirect();

        $this->assertSame(
            ['shahbaz_company_review'],
            $association->fresh()->association_feature_keys,
        );
    }

    public function test_autofilled_password_is_ignored_when_only_feature_access_is_updated(): void
    {
        $admin = $this->user('admin', 'admin-autofill');
        $association = $this->user('association', 'association-autofill');
        $originalPassword = $association->password;

        $this->actingAs($admin)->put(route('admin.association-users.features', $association), [
            'password' => 'browser-autofilled-password',
            'features' => ['shahbaz_company_review'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($originalPassword, $association->fresh()->password);
    }

    public function test_password_confirmation_is_required_only_when_password_change_is_selected(): void
    {
        $admin = $this->user('admin', 'admin-password');
        $association = $this->user('association', 'association-password');

        $this->actingAs($admin)->put(route('admin.association-users.password', $association), [
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
            'features' => ['shahbaz_company_review'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-strong-password', $association->fresh()->password));
    }

    public function test_association_user_feature_access_blocks_other_direct_routes(): void
    {
        $association = $this->user('association', 'shahbaz-reviewer');
        $association->update(['association_feature_keys' => ['shahbaz_company_review']]);
        SystemSetting::setValue('panel_features', [
            'association' => [
                'dashboard' => true,
                'shahbaz_company_review' => true,
            ],
        ]);

        $features = app(PanelFeatureService::class);
        $this->assertTrue($features->enabledForUser($association, 'shahbaz_company_review'));
        $this->assertFalse($features->enabledForUser($association, 'dashboard'));

        $this->actingAs($association)
            ->get(route('association.dashboard'))
            ->assertForbidden();
        $this->actingAs($association)
            ->get(route('association.shahbaz.companies.index'))
            ->assertOk();
    }

    public function test_limited_association_user_lands_on_first_personally_enabled_feature(): void
    {
        $association = $this->user('association', 'shahbaz-landing');
        $association->update(['association_feature_keys' => ['shahbaz_company_review']]);
        SystemSetting::setValue('panel_features', [
            'association' => [
                'dashboard' => true,
                'shahbaz_company_review' => true,
            ],
        ]);

        $this->assertSame(
            'association.shahbaz.companies.index',
            app(PanelFeatureService::class)->landingRoute('association', $association),
        );
    }

    public function test_explicit_user_access_does_not_require_the_role_wide_dashboard_or_feature_toggle(): void
    {
        $association = $this->user('association', 'shahbaz-only');
        $association->update(['association_feature_keys' => ['shahbaz_company_review']]);
        SystemSetting::setValue('panel_features', [
            'association' => [
                'dashboard' => false,
                'admin_dashboard' => false,
                'shahbaz_company_review' => false,
            ],
        ]);

        $features = app(PanelFeatureService::class);

        $this->assertTrue($features->enabledForUser($association, 'shahbaz_company_review'));
        $this->assertFalse($features->enabledForUser($association, 'dashboard'));
        $this->assertFalse($features->enabledForUser($association, 'admin_dashboard'));
        $this->assertSame(
            'association.shahbaz.companies.index',
            $features->landingRoute('association', $association),
        );
    }

    public function test_admin_can_soft_delete_an_association_user(): void
    {
        $admin = $this->user('admin', 'admin-delete');
        $association = $this->user('association', 'association-delete');

        $this->actingAs($admin)
            ->delete(route('admin.association-users.destroy', $association))
            ->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $association->id]);
        $this->assertDatabaseHas('users', [
            'id' => $association->id,
            'status' => 'inactive',
            'mobile' => null,
        ]);
    }

    private function user(string $roleName, string $username): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['title_fa' => $roleName],
        );

        return User::create([
            'role_id' => $role->id,
            'username' => $username,
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);
    }
}
