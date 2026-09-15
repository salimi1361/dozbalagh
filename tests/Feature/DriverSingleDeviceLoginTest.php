<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\MobileAppInstallation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DriverSingleDeviceLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_login_binds_the_first_device_before_issuing_a_token(): void
    {
        $driver = $this->driver('09121112233');

        $this->verifyOtp($driver, 'first-device')
            ->assertOk()
            ->assertJsonStructure(['token', 'driver']);

        Cache::put('otp_'.$driver->mobile, '12345', now()->addMinutes(3));
        $secondResponse = $this->postJson('/api/v1/driver/auth/verify-otp', [
            ...$this->devicePayload('second-device'),
            'mobile' => $driver->mobile,
            'code' => '12345',
        ]);

        $secondResponse
            ->assertStatus(409)
            ->assertJson([
                'error_code' => 'DEVICE_ALREADY_BOUND',
                'message' => 'این حساب قبلاً روی گوشی دیگری فعال شده است. لطفاً با گوشی اصلی وارد شوید.',
            ])
            ->assertJsonMissingPath('token');

        $this->assertDatabaseCount('mobile_app_installations', 1);
        $this->assertDatabaseHas('mobile_app_installations', [
            'driver_id' => $driver->id,
            'device_uuid' => 'first-device',
            'revoked_at' => null,
        ]);
    }

    public function test_reset_allows_a_new_device_to_bind_after_old_sessions_are_revoked(): void
    {
        $association = $this->user('association', 'association-device-reset');
        $driver = $this->driver('09121112234');

        $this->verifyOtp($driver, 'old-device')->assertOk();

        $this->actingAs($association)
            ->delete(route('driver-device-reset.destroy', $driver))
            ->assertRedirect();

        $newDeviceResponse = $this->verifyOtp($driver, 'new-device');
        $newDeviceResponse->assertOk()->assertJsonStructure(['token', 'driver']);

        $this->assertDatabaseHas('mobile_app_installations', [
            'driver_id' => $driver->id,
            'device_uuid' => 'old-device',
        ]);
        $this->assertDatabaseMissing('mobile_app_installations', [
            'driver_id' => $driver->id,
            'device_uuid' => 'old-device',
            'revoked_at' => null,
        ]);
        $this->assertDatabaseHas('mobile_app_installations', [
            'driver_id' => $driver->id,
            'device_uuid' => 'new-device',
            'revoked_at' => null,
        ]);
    }

    private function verifyOtp(Driver $driver, string $deviceUuid)
    {
        Cache::put('otp_'.$driver->mobile, '12345', now()->addMinutes(3));

        return $this->postJson('/api/v1/driver/auth/verify-otp', [
            ...$this->devicePayload($deviceUuid),
            'mobile' => $driver->mobile,
            'code' => '12345',
        ]);
    }

    private function devicePayload(string $deviceUuid): array
    {
        return [
            'device_uuid' => $deviceUuid,
            'platform' => 'android',
            'manufacturer' => 'Test',
            'model' => 'Test Phone',
            'app_identifier' => 'ir.itcakh.dozoleh.mobile_driver',
        ];
    }

    private function driver(string $mobile): Driver
    {
        $user = $this->user('driver', 'driver-'.$mobile);

        return Driver::create([
            'user_id' => $user->id,
            'national_code' => substr($mobile, 1),
            'passport_number' => 'P'.substr($mobile, 4),
            'first_name_fa' => 'راننده',
            'last_name_fa' => 'آزمایشی',
            'first_name_en' => 'Test',
            'last_name_en' => 'Driver',
            'mobile' => $mobile,
        ]);
    }

    private function user(string $role, string $username): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role], ['title_fa' => $role]);

        return User::create([
            'role_id' => $roleModel->id,
            'username' => $username,
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);
    }
}
