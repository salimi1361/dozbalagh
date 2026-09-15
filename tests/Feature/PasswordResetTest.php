<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_reset_password_with_code_sent_to_ceo_mobile(): void
    {
        $user = $this->companyUser();
        config(['services.kavenegar.key' => 'test-kavenegar-key']);

        $message = null;
        Http::fake(function (ClientRequest $request) use (&$message) {
            $message = $request->data()['message'] ?? null;

            return Http::response(['return' => ['status' => 200]], 200);
        });

        $this->post(route('password.reset.request'), [
            'username' => $user->username,
        ])->assertRedirect(route('login'))
            ->assertSessionHas('password_reset.step', 'verify');

        $this->assertSame('0912*****67', session('password_reset.mobile'));
        Http::assertSent(fn (ClientRequest $request): bool => $request->data()['receptor'] === '09121234567');

        preg_match('/(?<!\d)(\d{6})(?!\d)/', (string) $message, $matches);
        $this->assertNotEmpty($matches[1] ?? null);

        $this->post(route('password.reset.verify'), [
            'otp' => $matches[1],
        ])->assertRedirect(route('login'))
            ->assertSessionHas('password_reset.step', 'new_password');

        $this->post(route('password.reset.update'), [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertFalse(session()->has('password_reset'));
    }

    public function test_password_reset_does_not_send_code_for_an_unlinked_account(): void
    {
        $role = Role::create(['name' => 'association', 'title_fa' => 'انجمن']);
        $user = User::create([
            'role_id' => $role->id,
            'username' => '14007654321',
            'password' => Hash::make('secret-password'),
            'mobile' => '09121234567',
            'status' => 'active',
        ]);
        config(['services.kavenegar.key' => 'test-kavenegar-key']);
        Http::fake();

        $this->post(route('password.reset.request'), [
            'username' => $user->username,
        ])->assertRedirect(route('login', ['recover' => 1]))
            ->assertSessionHasErrors('username');

        Http::assertNothingSent();
    }

    private function companyUser(): User
    {
        $role = Role::create(['name' => 'company', 'title_fa' => 'شرکت']);
        $user = User::create([
            'role_id' => $role->id,
            'username' => '14001234567',
            'password' => Hash::make('old-password'),
            'mobile' => '09121234567',
            'status' => 'active',
            'is_manual' => true,
        ]);

        Company::forceCreate([
            'user_id' => $user->id,
            'company_code' => 'C-N-14001234567',
            'name' => 'شرکت آزمایشی',
            'name_fa' => 'شرکت آزمایشی',
            'name_en' => 'Test Company',
            'national_id' => $user->username,
            'registration_number' => '12345',
            'ceo_name' => 'مدیر آزمایشی',
            'ceo_national_code' => '0921234567',
            'ceo_mobile' => '09121234567',
            'phone' => '05137654321',
            'postal_code' => '9187654321',
            'province' => 'خراسان رضوی',
            'city' => 'مشهد',
            'activity_type' => 'حمل‌ونقل بین‌المللی',
            'address_fa' => 'مشهد',
            'address_en' => 'Mashhad',
            'status' => 'approved',
            'is_association_member' => true,
            'shahbaz_verification_status' => 'verified',
        ]);

        return $user;
    }
}
