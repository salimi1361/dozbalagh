<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyDriverMessage;
use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverConversationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_messages_are_returned_as_separate_conversations_with_unread_counts(): void
    {
        $companyRole = Role::firstOrCreate(['name' => 'company'], ['title_fa' => 'شرکت']);
        $driverRole = Role::firstOrCreate(['name' => 'driver'], ['title_fa' => 'راننده']);

        $firstCompany = $this->company($companyRole, 'company-one', 'C-ONE', 'شرکت اول');
        $secondCompany = $this->company($companyRole, 'company-two', 'C-TWO', 'شرکت دوم');
        $driverUser = User::create([
            'role_id' => $driverRole->id,
            'username' => 'conversation-driver',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);
        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'current_company_id' => $firstCompany->id,
            'national_code' => '0012345678',
            'passport_number' => 'P1234567',
            'first_name_fa' => 'راننده',
            'last_name_fa' => 'گفتگو',
            'first_name_en' => 'Chat',
            'last_name_en' => 'Driver',
            'mobile' => '09120000000',
        ]);

        $this->message($firstCompany, $driver, 'پیام اول شرکت اول');
        $this->message($firstCompany, $driver, 'پاسخ راننده', 'driver');
        $this->message($secondCompany, $driver, 'پیام شرکت دوم');

        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/driver/company-messages/conversations')
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'type',
                    'participant_id',
                    'title',
                    'last_message',
                    'unread_count',
                ]],
            ]);

        $this->getJson('/api/v1/driver/company-messages?company_id='.$firstCompany->id)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['company_id' => $secondCompany->id]);
    }

    private function company(Role $role, string $username, string $code, string $name): Company
    {
        $user = User::create([
            'role_id' => $role->id,
            'username' => $username,
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        return Company::create([
            'user_id' => $user->id,
            'company_code' => $code,
            'name' => $name,
            'name_fa' => $name,
            'name_en' => $code,
            'address_fa' => 'مشهد',
            'address_en' => 'Mashhad',
        ]);
    }

    private function message(
        Company $company,
        Driver $driver,
        string $message,
        string $sender = 'company',
    ): CompanyDriverMessage {
        return CompanyDriverMessage::create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'sender' => $sender,
            'title' => 'گفتگو',
            'message' => $message,
            'category' => 'general',
            'priority' => 'normal',
        ]);
    }
}
