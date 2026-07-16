<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\MobileAppInstallation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAppInstallationTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_device_information_is_upserted_for_the_real_app(): void
    {
        $role = Role::firstOrCreate(['name' => 'driver'], ['title_fa' => 'راننده']);
        $user = User::create([
            'role_id' => $role->id,
            'username' => 'mobile-device-test',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);
        $driver = Driver::create([
            'user_id' => $user->id,
            'national_code' => '0012345678',
            'passport_number' => 'P1234567',
            'first_name_fa' => 'راننده',
            'last_name_fa' => 'آزمایشی',
            'first_name_en' => 'Test',
            'last_name_en' => 'Driver',
            'mobile' => '09120000000',
        ]);

        Sanctum::actingAs($driver);

        $payload = [
            'device_uuid' => 'android-secure-id-1',
            'platform' => 'android',
            'manufacturer' => 'Xiaomi',
            'model' => '23129RAA4G',
            'device_name' => 'garnet',
            'os_version' => '15',
            'sdk_version' => 35,
            'app_version' => '1.0.0',
            'app_build' => '1',
            'app_identifier' => 'ir.itcakh.dozoleh.mobile_driver',
            'locale' => 'fa-IR',
            'fcm_token' => str_repeat('a', 120),
        ];

        $this->postJson('/api/v1/driver/app-installations', $payload)->assertOk();
        $this->postJson('/api/v1/driver/app-installations', [...$payload, 'app_build' => '2'])->assertOk();

        $this->assertDatabaseCount('mobile_app_installations', 1);
        $this->assertDatabaseHas('mobile_app_installations', [
            'driver_id' => $driver->id,
            'device_uuid' => 'android-secure-id-1',
            'manufacturer' => 'Xiaomi',
            'model' => '23129RAA4G',
            'app_build' => '2',
            'fcm_token' => str_repeat('a', 120),
        ]);

        $this->postJson('/api/v1/driver/app-installations', [
            ...$payload,
            'fcm_token' => str_repeat('b', 120),
            'notifications_enabled' => false,
        ])->assertOk();

        $installation = MobileAppInstallation::firstOrFail();
        $this->assertSame(str_repeat('b', 120), $installation->fcm_token);
        $this->assertNotNull($installation->fcm_token_updated_at);
        $this->assertFalse($installation->notifications_enabled);
    }

    public function test_revoked_device_cannot_register_an_fcm_token(): void
    {
        [$driver] = $this->authenticatedDriver('revoked-device-test', '0012345679');
        MobileAppInstallation::create([
            'driver_id' => $driver->id,
            'device_uuid' => 'revoked-device',
            'platform' => 'android',
            'installed_at' => now(),
            'last_seen_at' => now(),
            'revoked_at' => now(),
        ]);

        $this->postJson('/api/v1/driver/app-installations', [
            'device_uuid' => 'revoked-device',
            'platform' => 'android',
            'fcm_token' => str_repeat('c', 120),
        ])->assertStatus(409);

        $this->assertDatabaseMissing('mobile_app_installations', ['fcm_token' => str_repeat('c', 120)]);
    }

    private function authenticatedDriver(string $username, string $nationalCode): array
    {
        $role = Role::firstOrCreate(['name' => 'driver'], ['title_fa' => 'Driver']);
        $user = User::create([
            'role_id' => $role->id,
            'username' => $username,
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);
        $driver = Driver::create([
            'user_id' => $user->id,
            'national_code' => $nationalCode,
            'passport_number' => 'P' . $nationalCode,
            'first_name_fa' => 'Test',
            'last_name_fa' => 'Driver',
            'first_name_en' => 'Test',
            'last_name_en' => 'Driver',
            'mobile' => '09' . substr($nationalCode, 0, 9),
        ]);

        Sanctum::actingAs($driver);

        return [$driver, $user];
    }
}
