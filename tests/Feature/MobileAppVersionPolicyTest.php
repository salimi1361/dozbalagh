<?php

namespace Tests\Feature;

use App\Jobs\SendMobileAppUpdatePush;
use App\Models\MobileAppVersion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MobileAppVersionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_version_api_requires_update_below_minimum_build(): void
    {
        MobileAppVersion::create([
            'platform' => 'android',
            'version_name' => '2.1.0',
            'latest_build' => 21,
            'minimum_build' => 18,
            'download_url' => 'https://example.test/app.apk',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/mobile-app/version?platform=android&build=17')
            ->assertOk()
            ->assertJson([
                'configured' => true,
                'update_available' => true,
                'update_required' => true,
                'latest_build' => 21,
                'minimum_build' => 18,
            ]);
    }

    public function test_optional_update_does_not_block_supported_build(): void
    {
        MobileAppVersion::create([
            'platform' => 'android',
            'version_name' => '2.1.0',
            'latest_build' => 21,
            'minimum_build' => 18,
            'force_update' => false,
            'download_url' => 'https://example.test/app.apk',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/mobile-app/version?platform=android&build=19')
            ->assertOk()
            ->assertJson([
                'update_available' => true,
                'update_required' => false,
            ]);
    }

    public function test_admin_can_queue_update_notification_while_saving_version(): void
    {
        Queue::fake();
        $role = Role::firstOrCreate(['name' => 'admin'], ['title_fa' => 'مدیر']);
        $admin = User::create([
            'role_id' => $role->id,
            'username' => 'version-notification-admin',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.mobile-app.versions.update', 'android'), [
            'version_name' => '2.2.0',
            'latest_build' => 22,
            'minimum_build' => 20,
            'download_url' => 'https://example.test/app.apk',
            'message' => 'نسخه جدید را دریافت کنید.',
            'is_active' => '1',
            'send_push_notification' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $version = MobileAppVersion::where('platform', 'android')->firstOrFail();
        Queue::assertPushed(
            SendMobileAppUpdatePush::class,
            fn (SendMobileAppUpdatePush $job) => $job->versionId === $version->id,
        );
    }
}
