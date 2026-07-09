<?php

namespace Tests\Feature;

use App\Models\AssociationCompanyMessage;
use App\Models\AssociationMessageReceipt;
use App\Models\AssociationSupportTicket;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AssociationCrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_message_and_company_can_acknowledge_and_create_ticket(): void
    {
        $role = Role::create(['name' => 'admin', 'title_fa' => 'مدیر']);
        $admin = User::create([
            'role_id' => $role->id,
            'username' => 'admin',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);

        $companyUser = User::create([
            'role_id' => $role->id,
            'username' => 'company',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);

        $company = Company::create([
            'user_id' => $companyUser->id,
            'company_code' => 'C-TEST',
            'name' => 'Test Company',
            'name_fa' => 'شرکت تست',
            'name_en' => 'Test Company',
            'national_id' => '101010',
            'phone' => '021',
            'ceo_mobile' => '09120000000',
            'address_fa' => 'تهران',
            'address_en' => 'Tehran',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.association_crm.messages.store'), [
                'audience' => 'selected',
                'company_id' => $company->id,
                'title' => 'تکمیل مدارک',
                'body' => 'لطفا مدارک شرکت را تکمیل کنید.',
                'category' => 'documents',
                'priority' => 'important',
                'is_mandatory' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $message = AssociationCompanyMessage::firstOrFail();
        $this->assertSame($company->id, $message->company_id);

        $this->actingAs($companyUser)
            ->post(route('company.association_crm.messages.acknowledge', $message), [
                'acknowledgement_note' => 'خوانده شد.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('association_message_receipts', [
            'message_id' => $message->id,
            'company_id' => $company->id,
            'acknowledgement_note' => 'خوانده شد.',
        ]);

        $this->assertNotNull(AssociationMessageReceipt::first()->acknowledged_at);

        $this->actingAs($companyUser)
            ->post(route('company.association_crm.tickets.store'), [
                'message_id' => $message->id,
                'title' => 'مشکل در مدارک',
                'description' => 'برای بارگذاری مدارک نیاز به راهنمایی داریم.',
                'category' => 'documents',
                'priority' => 'important',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('association_support_tickets', [
            'company_id' => $company->id,
            'message_id' => $message->id,
            'title' => 'مشکل در مدارک',
            'status' => 'open',
        ]);

        $this->assertSame(1, AssociationSupportTicket::count());
    }
}
