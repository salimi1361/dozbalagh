<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\DriverAnnouncement;
use App\Models\DriverAnnouncementReceipt;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverStartupAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_mandatory_announcement_blocks_until_driver_acknowledges_it(): void
    {
        $driver = $this->driver();
        $admin = $this->user('admin', 'announcement-admin');
        $announcement = DriverAnnouncement::create([
            'created_by_user_id' => $admin->id,
            'source_role' => 'admin',
            'title' => 'اطلاعیه مهم',
            'message' => 'متن اطلاعیه آزمایشی',
            'priority' => 'urgent',
            'display_mode' => 'mandatory',
            'audience_type' => 'all',
            'show_once' => true,
            'requires_acknowledgement' => true,
            'acknowledgement_text' => 'مطالعه کردم',
            'starts_at' => now()->subMinute(),
            'is_active' => true,
        ]);
        DriverAnnouncementReceipt::create([
            'announcement_id' => $announcement->id,
            'driver_id' => $driver->id,
        ]);

        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/driver/startup-announcements')
            ->assertOk()
            ->assertJson(['blocking' => true])
            ->assertJsonPath('data.0.id', $announcement->id);

        $this->postJson("/api/v1/driver/startup-announcements/{$announcement->id}/acknowledge", [
            'device_uuid' => 'test-device',
        ])->assertOk();

        $this->getJson('/api/v1/driver/startup-announcements')
            ->assertOk()
            ->assertJson(['blocking' => false, 'data' => []]);

        $this->assertDatabaseHas('driver_announcement_receipts', [
            'announcement_id' => $announcement->id,
            'driver_id' => $driver->id,
            'device_uuid' => 'test-device',
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

    private function driver(): Driver
    {
        $user = $this->user('driver', 'announcement-driver');

        return Driver::create([
            'user_id' => $user->id,
            'national_code' => '0012345678',
            'passport_number' => 'P1234567',
            'first_name_fa' => 'راننده',
            'last_name_fa' => 'آزمایشی',
            'first_name_en' => 'Test',
            'last_name_en' => 'Driver',
            'mobile' => '09120000000',
        ]);
    }
}
