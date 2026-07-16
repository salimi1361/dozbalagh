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

class DriverRecognizedDeviceLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_active_device_can_login_with_its_pin_without_an_otp(): void
    {
        $driver = $this->driver();
        $installation = MobileAppInstallation::create([
            'driver_id' => $driver->id,
            'device_uuid' => 'same-android-device',
            'platform' => 'android',
            'installed_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->postJson('/api/v1/driver/auth/device-status', [
            'mobile' => $driver->mobile,
            'device_uuid' => 'same-android-device',
        ])->assertOk()->assertJson([
            'recognized' => false,
            'login_method' => 'otp',
        ]);

        Sanctum::actingAs($driver);
        $this->postJson('/api/v1/driver/auth/device-pin', [
            'device_uuid' => 'same-android-device',
            'pin' => '2580',
        ])->assertOk();

        $installation->refresh();
        $this->assertNotSame('2580', $installation->device_pin_hash);
        $this->assertTrue(Hash::check('2580', $installation->device_pin_hash));

        $this->postJson('/api/v1/driver/auth/device-status', [
            'mobile' => $driver->mobile,
            'device_uuid' => 'same-android-device',
        ])->assertOk()->assertJson([
            'recognized' => true,
            'login_method' => 'device_pin',
        ]);

        $this->postJson('/api/v1/driver/auth/device-login', [
            'mobile' => $driver->mobile,
            'device_uuid' => 'same-android-device',
            'pin' => '1111',
        ])->assertStatus(422);

        $this->postJson('/api/v1/driver/auth/device-login', [
            'mobile' => $driver->mobile,
            'device_uuid' => 'same-android-device',
            'pin' => '2580',
        ])->assertOk()
            ->assertJsonPath('driver.id', $driver->id)
            ->assertJsonStructure(['token', 'driver']);
    }

    public function test_revoked_or_different_device_is_not_recognized(): void
    {
        $driver = $this->driver();
        MobileAppInstallation::create([
            'driver_id' => $driver->id,
            'device_uuid' => 'revoked-device',
            'platform' => 'android',
            'installed_at' => now(),
            'last_seen_at' => now(),
            'device_pin_hash' => Hash::make('2580'),
            'revoked_at' => now(),
        ]);

        foreach (['revoked-device', 'another-device'] as $deviceUuid) {
            $this->postJson('/api/v1/driver/auth/device-status', [
                'mobile' => $driver->mobile,
                'device_uuid' => $deviceUuid,
            ])->assertOk()->assertJson(['recognized' => false]);
        }
    }

    private function driver(): Driver
    {
        $role = Role::firstOrCreate(['name' => 'driver'], ['title_fa' => 'راننده']);
        $user = User::create([
            'role_id' => $role->id,
            'username' => 'recognized-device-driver',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        return Driver::create([
            'user_id' => $user->id,
            'national_code' => '0012345688',
            'passport_number' => 'P0012345688',
            'first_name_fa' => 'راننده',
            'last_name_fa' => 'آزمایشی',
            'first_name_en' => 'Test',
            'last_name_en' => 'Driver',
            'mobile' => '09121112233',
        ]);
    }
}
