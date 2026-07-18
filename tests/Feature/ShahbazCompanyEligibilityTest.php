<?php
namespace Tests\Feature;
use App\Models\Company; use App\Models\Role; use App\Models\User; use Illuminate\Foundation\Testing\RefreshDatabase; use Illuminate\Support\Facades\Hash; use Tests\TestCase;
class ShahbazCompanyEligibilityTest extends TestCase
{
    use RefreshDatabase;
    public function test_company_cannot_open_new_dozbalagh_form_before_shahbaz_approval(): void
    {
        $role=Role::create(['name'=>'company','title_fa'=>'شرکت']); $user=User::create(['role_id'=>$role->id,'username'=>'blocked-company','password'=>Hash::make('secret'),'status'=>'active']);
        Company::create(['user_id'=>$user->id,'company_code'=>'BLOCK-1','name_fa'=>'شرکت ناقص','name_en'=>'Blocked','address_fa'=>'تهران','address_en'=>'Tehran'])->forceFill(['status'=>'approved'])->save();
        $this->actingAs($user)->get(route('dozbalagh.create'))->assertRedirect(route('company.shahbaz.profile.edit'));
    }
}
