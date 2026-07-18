<?php
namespace App\Shahbaz\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Shahbaz\Models\CompanyVerificationHistory;
use App\Shahbaz\Services\CompanyEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CompanyProfileController extends Controller
{
    public function edit(Request $request, CompanyEligibilityService $eligibility) { $company=$request->user()->company; return view('Shahbaz.company.profile',['company'=>$company,'missingFields'=>$eligibility->missingFields($company),'blockingReasons'=>$eligibility->blockingReasons($company)]); }
    public function update(Request $request)
    {
        $company=$request->user()->company;
        $data=$request->validate([
            'name_fa'=>['required','string','max:255'],'name_en'=>['required','string','max:255'],'national_id'=>['required','string','max:30','unique:companies,national_id,'.$company->id],'registration_number'=>['required','string','max:100'],
            'ceo_name'=>['required','string','max:255'],'ceo_national_code'=>['required','string','max:20'],'ceo_mobile'=>['required','string','max:20'],
            'phone'=>['required','string','max:30'],'postal_code'=>['required','string','max:20'],'province'=>['required','string','max:100'],'city'=>['required','string','max:100'],
            'address_fa'=>['required','string'],'address_en'=>['required','string'],'activity_type'=>['required','string','max:255'],
        ]);
        DB::transaction(function() use($company,$data,$request){ $from=$company->shahbaz_verification_status; $company->fill($data+['name'=>$data['name_fa']]); $company->forceFill(['shahbaz_verification_status'=>'pending_association_review','shahbaz_verified_by_user_id'=>null,'shahbaz_verified_at'=>null])->save(); CompanyVerificationHistory::create(['company_id'=>$company->id,'from_status'=>$from,'to_status'=>'pending_association_review','result'=>'submitted','description'=>'اطلاعات توسط شرکت تکمیل و برای بررسی انجمن ارسال شد.','company_snapshot'=>$company->fresh()->toArray(),'changed_by_user_id'=>$request->user()->id]); });
        return back()->with('success','اطلاعات برای کنترل انجمن و بررسی دستی شحباز ارسال شد.');
    }
}
