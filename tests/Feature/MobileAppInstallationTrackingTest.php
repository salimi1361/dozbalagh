<?php

namespace Tests\Feature;

use App\Models\Driver;
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
        ]);
    }
}
