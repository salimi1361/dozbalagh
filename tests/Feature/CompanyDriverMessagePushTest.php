<?php

namespace Tests\Feature;

use App\Jobs\SendCompanyDriverMessagePush;
use App\Models\Company;
use App\Models\CompanyDriverMessage;
use App\Models\Driver;
use App\Models\MobileAppInstallation;
use App\Models\Role;
use App\Models\User;
use App\Services\FirebaseCloudMessaging;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class CompanyDriverMessagePushTest extends TestCase
{
    use DatabaseMigrations;

    public function test_company_message_dispatches_push_job(): void
    {
        Queue::fake();
        [$company, $driver] = $this->companyAndDriver();

        DB::beginTransaction();
        $message = $this->message($company, $driver, 'company');
        Queue::assertNotPushed(SendCompanyDriverMessagePush::class);
        DB::commit();

        Queue::assertPushed(SendCompanyDriverMessagePush::class, fn ($job) => $job->messageId === $message->id);
    }

    public function test_driver_message_does_not_dispatch_push_job(): void
    {
        Queue::fake();
        [$company, $driver] = $this->companyAndDriver();

        $this->message($company, $driver, 'driver');

        Queue::assertNotPushed(SendCompanyDriverMessagePush::class);
    }

    public function test_unregistered_firebase_token_is_cleared(): void
    {
        Queue::fake();
        [$company, $driver] = $this->companyAndDriver();
        $installation = MobileAppInstallation::create([
            'driver_id' => $driver->id,
            'device_uuid' => 'invalid-token-device',
            'platform' => 'android',
            'installed_at' => now(),
            'last_seen_at' => now(),
            'fcm_token' => str_repeat('x', 120),
            'fcm_token_updated_at' => now(),
            'notifications_enabled' => true,
        ]);
        $message = $this->message($company, $driver, 'company');
        $response = new Response(new PsrResponse(404, [], json_encode([
            'error' => ['details' => [['errorCode' => 'UNREGISTERED']]],
        ])));
        $firebase = Mockery::mock(FirebaseCloudMessaging::class);
        $firebase->shouldReceive('send')->once()->andReturn($response);
        $firebase->shouldReceive('tokenIsInvalid')->once()->with($response)->andReturnTrue();

        (new SendCompanyDriverMessagePush($message->id))->handle($firebase);

        $this->assertNull($installation->fresh()->fcm_token);
        $this->assertNull($installation->fresh()->fcm_token_updated_at);
    }

    private function companyAndDriver(): array
    {
        $companyUser = $this->user('company', 'push-company');
        $driverUser = $this->user('driver', 'push-driver');
        $company = Company::create([
            'user_id' => $companyUser->id,
            'company_code' => 'PUSH-COMPANY',
            'name_fa' => 'Test Company',
            'name_en' => 'Test Company',
            'address_fa' => 'Mashhad',
            'address_en' => 'Mashhad',
        ]);
        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'current_company_id' => $company->id,
            'national_code' => fake()->unique()->numerify('##########'),
            'passport_number' => fake()->unique()->bothify('P#######'),
            'first_name_fa' => 'Test',
            'last_name_fa' => 'Driver',
            'first_name_en' => 'Test',
            'last_name_en' => 'Driver',
            'mobile' => fake()->unique()->numerify('09#########'),
        ]);

        return [$company, $driver];
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

    private function message(Company $company, Driver $driver, string $sender): CompanyDriverMessage
    {
        return CompanyDriverMessage::create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'sender' => $sender,
            'title' => 'Message title',
            'message' => 'Message body',
            'category' => 'general',
            'priority' => 'normal',
        ]);
    }
}
