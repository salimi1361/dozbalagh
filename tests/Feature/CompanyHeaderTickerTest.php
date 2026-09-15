<?php

namespace Tests\Feature;

use App\Models\CompanyHeaderTickerItem;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class CompanyHeaderTickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_association_user_can_create_and_delete_ticker_news(): void
    {
        $association = $this->authorizedAssociation('ticker-editor');

        $this->actingAs($association)
            ->post(route('association.company-header-ticker.store'), [
                'message' => 'اطلاع مهم برای همه شرکت‌ها',
                'sort_order' => 20,
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $item = CompanyHeaderTickerItem::firstOrFail();
        $this->assertSame('اطلاع مهم برای همه شرکت‌ها', $item->message);
        $this->assertTrue($item->is_active);

        $this->actingAs($association)
            ->delete(route('association.company-header-ticker.destroy', $item))
            ->assertRedirect();

        $this->assertDatabaseMissing('company_header_ticker_items', ['id' => $item->id]);
    }

    public function test_authorized_association_user_can_update_ticker_news(): void
    {
        $association = $this->authorizedAssociation('ticker-updater');
        $item = CompanyHeaderTickerItem::create([
            'message' => 'متن قدیمی',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->actingAs($association)
            ->put(route('association.company-header-ticker.update', $item), [
                'message' => 'متن جدید',
                'is_active' => '0',
                'sort_order' => 5,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('company_header_ticker_items', [
            'id' => $item->id,
            'message' => 'متن جدید',
            'is_active' => 0,
            'sort_order' => 5,
        ]);
    }

    public function test_jalali_schedule_is_converted_to_database_datetime(): void
    {
        $association = $this->authorizedAssociation('ticker-jalali');

        $this->actingAs($association)
            ->post(route('association.company-header-ticker.store'), [
                'message' => 'خبر زمان‌دار',
                'starts_at_jalali' => '۱۴۰۵/۰۶/۰۴ ۱۴:۳۰',
                'ends_at_jalali' => '1405/06/05 15:45',
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $item = CompanyHeaderTickerItem::firstOrFail();
        $this->assertSame(
            Jalalian::fromFormat('Y/m/d H:i', '1405/06/04 14:30')->toCarbon()->format('Y-m-d H:i'),
            $item->starts_at?->format('Y-m-d H:i'),
        );
        $this->assertSame(
            Jalalian::fromFormat('Y/m/d H:i', '1405/06/05 15:45')->toCarbon()->format('Y-m-d H:i'),
            $item->ends_at?->format('Y-m-d H:i'),
        );
    }

    public function test_association_user_without_ticker_feature_is_forbidden(): void
    {
        $association = $this->user('association', 'ticker-denied');
        $association->update(['association_feature_keys' => ['dashboard']]);
        SystemSetting::setValue('panel_features', [
            'association' => [
                'dashboard' => true,
                'company_header_ticker' => true,
            ],
        ]);

        $this->actingAs($association)
            ->get(route('association.company-header-ticker.edit'))
            ->assertForbidden();
    }

    public function test_visible_now_returns_only_active_items_in_their_schedule(): void
    {
        $this->assertCount(0, CompanyHeaderTickerItem::visibleNow()->get());

        CompanyHeaderTickerItem::create(['message' => 'فعال', 'is_active' => true, 'sort_order' => 1]);
        CompanyHeaderTickerItem::create(['message' => 'خاموش', 'is_active' => false, 'sort_order' => 2]);
        CompanyHeaderTickerItem::create(['message' => 'آینده', 'is_active' => true, 'starts_at' => now()->addHour(), 'sort_order' => 3]);
        CompanyHeaderTickerItem::create(['message' => 'منقضی', 'is_active' => true, 'ends_at' => now()->subHour(), 'sort_order' => 4]);

        $this->assertSame(['فعال'], CompanyHeaderTickerItem::visibleNow()->pluck('message')->all());
    }

    private function authorizedAssociation(string $username): User
    {
        $association = $this->user('association', $username);
        $association->update(['association_feature_keys' => ['company_header_ticker']]);
        SystemSetting::setValue('panel_features', [
            'association' => ['company_header_ticker' => true],
        ]);

        return $association;
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
