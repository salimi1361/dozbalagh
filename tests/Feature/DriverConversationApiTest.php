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

    public function test_driver_only_sees_live_conversations_for_current_company(): void
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
        $oldCompanyMessage = $this->message($secondCompany, $driver, 'پیام شرکت دوم');

        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/driver/company-messages/conversations')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'type',
                    'participant_id',
                    'title',
                    'last_message',
                    'unread_count',
                ]],
            ])
            ->assertJsonPath('data.0.participant_id', $firstCompany->id)
            ->assertJsonMissing(['participant_id' => $secondCompany->id]);

        $this->getJson('/api/v1/driver/company-messages')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['company_id' => $secondCompany->id]);

        $this->getJson('/api/v1/driver/company-messages?company_id='.$secondCompany->id)
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('unread_count', 1);

        $this->postJson('/api/v1/driver/company-messages/'.$oldCompanyMessage->id.'/read')
            ->assertNotFound();

        $this->postJson('/api/v1/driver/company-messages/reply', [
            'message_id' => $oldCompanyMessage->id,
            'message' => 'پاسخ نباید برای شرکت قبلی ارسال شود.',
        ])->assertNotFound();

        $driver->forceFill(['current_company_id' => $secondCompany->id])->save();

        $this->getJson('/api/v1/driver/company-messages/conversations')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.participant_id', $secondCompany->id)
            ->assertJsonMissing(['participant_id' => $firstCompany->id]);
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
