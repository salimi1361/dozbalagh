<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_assistant(): void
    {
        $this->postJson(route('assistant.message'), [
            'message' => 'وضعیت درخواست',
        ])->assertUnauthorized();
    }

    public function test_company_answer_uses_backend_role_and_ignores_submitted_role(): void
    {
        config()->set('panel_features.company.assistant.default', true);
        $company = $this->userWithRole('company');

        $this->actingAs($company)
            ->postJson(route('assistant.message'), [
                'message' => 'ثبت درخواست',
                'role' => 'admin',
            ])
            ->assertOk()
            ->assertJsonPath(
                'answer',
                'برای ثبت درخواست، از بخش «درخواست‌ها و ثبت دوزوله» در پنل شرکت وارد فرم درخواست جدید شوید و اطلاعات و مدارک لازم را تکمیل کنید.',
            );
    }

    public function test_unsupported_role_cannot_use_web_assistant(): void
    {
        $driver = $this->userWithRole('driver');

        $this->actingAs($driver)
            ->postJson(route('assistant.message'), [
                'message' => 'شماره سریال',
            ])
            ->assertForbidden();
    }

    public function test_unknown_question_returns_configured_fallback(): void
    {
        config()->set('panel_features.admin.assistant.default', true);
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->postJson(route('assistant.message'), [
                'message' => 'موضوعی که در دانش فعلی وجود ندارد',
            ])
            ->assertOk()
            ->assertJsonPath('answer', config('assistant.fallback_message'));
    }

    public function test_company_cannot_use_assistant_when_feature_is_disabled(): void
    {
        $company = $this->userWithRole('company');

        $this->actingAs($company)
            ->postJson(route('assistant.message'), [
                'message' => 'ثبت جدید را چطور انجام بدهم',
            ])
            ->assertForbidden();
    }

    public function test_admin_cannot_use_assistant_when_feature_is_disabled(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->postJson(route('assistant.message'), [
                'message' => 'چطور باید ثبت انجام بشه',
            ])
            ->assertForbidden();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->create([
            'name' => $roleName,
            'title_fa' => $roleName,
        ]);

        return User::query()->create([
            'role_id' => $role->getKey(),
            'username' => 'assistant-'.$roleName,
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }
}
