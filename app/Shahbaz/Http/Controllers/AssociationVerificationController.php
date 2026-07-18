<?php
namespace App\Shahbaz\Http\Controllers;
use App\Http\Controllers\Controller; use App\Models\Company; use App\Shahbaz\Models\CompanyVerificationHistory; use App\Shahbaz\Services\CompanyEligibilityService; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB; use Illuminate\Validation\Rule;
class AssociationVerificationController extends Controller
{
    public function index(){ $companies=Company::whereIn('shahbaz_verification_status',['pending_association_review','correction_required','shahbaz_mismatch'])->latest('updated_at')->paginate(25); return view('Shahbaz.association.index',compact('companies')); }
    public function show(Company $company, CompanyEligibilityService $eligibility){ $company->load(['shahbazVerificationHistories.changedBy']); return view('Shahbaz.association.show',compact('company','eligibility')); }
    public function review(Request $request, Company $company, CompanyEligibilityService $eligibility)
    {
        $data=$request->validate(['result'=>['required',Rule::in(['verified','correction_required','shahbaz_mismatch','company_not_found','follow_up'])],'description'=>['required','string','max:3000'],'activity_license_number'=>['nullable','required_if:result,verified','string','max:100',Rule::unique('companies')->ignore($company->id)],'activity_license_issued_on'=>['nullable','required_if:result,verified','date'],'activity_license_expires_on'=>['nullable','required_if:result,verified','date','after_or_equal:activity_license_issued_on']]);
        if($data['result']==='verified' && $eligibility->missingFields($company)!==[]) return back()->withErrors(['result'=>'تا تکمیل تمام اطلاعات الزامی، تأیید شرکت امکان‌پذیر نیست.']);
        DB::transaction(function() use($company,$data,$request){ $from=$company->shahbaz_verification_status; $verified=$data['result']==='verified'; $to=$verified?'verified':($data['result']==='company_not_found'?'shahbaz_mismatch':$data['result']); $company->forceFill(['shahbaz_verification_status'=>$to,'shahbaz_review_note'=>$data['description'],'shahbaz_verified_by_user_id'=>$verified?$request->user()->id:null,'shahbaz_verified_at'=>$verified?now():null,'activity_license_number'=>$verified?$data['activity_license_number']:$company->activity_license_number,'activity_license_issued_on'=>$verified?$data['activity_license_issued_on']:$company->activity_license_issued_on,'activity_license_expires_on'=>$verified?$data['activity_license_expires_on']:$company->activity_license_expires_on,'activity_license_status'=>$verified?'active':'unverified'])->save(); CompanyVerificationHistory::create(['company_id'=>$company->id,'from_status'=>$from,'to_status'=>$to,'result'=>$data['result'],'description'=>$data['description'],'company_snapshot'=>$company->fresh()->toArray(),'changed_by_user_id'=>$request->user()->id,'checked_at'=>now()]); });
        return redirect()->route('association.shahbaz.companies.index')->with('success','نتیجه بررسی شحباز ثبت شد.');
    }
}
