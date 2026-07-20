<?php
namespace App\Shahbaz\Http\Controllers;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;
use App\Http\Controllers\Controller; use App\Models\Company; use App\Shahbaz\Models\CompanyVerificationHistory; use App\Shahbaz\Services\CompanyEligibilityService; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB; use Illuminate\Validation\Rule;
class AssociationVerificationController extends Controller
{
    public function index()
    {
        $companies = Company::whereIn('shahbaz_verification_status', [
            'pending_association_review', 'ready_for_shahbaz_check', 'correction_required',
            'shahbaz_mismatch', 'rejected',
        ])->latest('updated_at')->paginate(25);

        return view('Shahbaz.association.index', compact('companies'));
    }
    public function show(Company $company, CompanyEligibilityService $eligibility)
    {
        $company->load(['shahbazVerificationHistories.changedBy']);
        $licenseRequests = \App\Shahbaz\Models\LicenseRequest::where('company_id', $company->id)->latest()->get();
        $paymentRequests = \App\Shahbaz\Models\PaymentRequest::with(['licenseRequest', 'approver'])
            ->where('company_id', $company->id)->latest()->get();

        return view('Shahbaz.association.show', compact('company', 'eligibility', 'licenseRequests', 'paymentRequests'));
    }
    public function paymentStore(Request $request, Company $company)
    {
        $data = $request->validate([
            'license_request_id' => [
                'required',
                Rule::exists('shahbaz_license_requests', 'id')->where('company_id', $company->id),
            ],
            'amount' => ['required', 'integer', 'min:10000', 'max:999999999999'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'license_request_id.exists' => 'درخواست انتخاب‌شده متعلق به این شرکت نیست.',
            'amount.min' => 'مبلغ صورتحساب باید حداقل ۱۰٬۰۰۰ ریال باشد.',
        ]);

        $licenseRequest = \App\Shahbaz\Models\LicenseRequest::where('company_id', $company->id)
            ->findOrFail($data['license_request_id']);

        if (\App\Shahbaz\Models\PaymentRequest::where('license_request_id', $licenseRequest->id)
            ->whereIn('status', ['pending_payment', 'processing', 'paid'])->exists()) {
            return back()->withErrors(['amount' => 'برای این درخواست قبلاً صورتحساب فعال یا پرداخت‌شده ثبت شده است.']);
        }

        \App\Shahbaz\Models\PaymentRequest::create([
            'company_id' => $company->id,
            'license_request_id' => $licenseRequest->id,
            'amount' => $data['amount'],
            'status' => 'pending_payment',
            'description' => $data['description'] ?? null,
            'approved_by_user_id' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'مبلغ تأیید و صورتحساب برای شرکت صادر شد.');
    }
    public function initialReview(Request $request, Company $company)
    {
        abort_unless($company->shahbaz_verification_status === 'pending_association_review', 403);
        $data = $request->validate([
            'result' => ['required', Rule::in(['initial_approved', 'correction_required', 'rejected'])],
            'description' => ['required', 'string', 'max:3000'],
        ]);
        $to = match ($data['result']) {
            'initial_approved' => 'ready_for_shahbaz_check',
            'correction_required' => 'correction_required',
            default => 'rejected',
        };

        DB::transaction(function () use ($company, $data, $to, $request): void {
            $from = $company->shahbaz_verification_status;
            $company->forceFill([
                'shahbaz_verification_status' => $to,
                'shahbaz_review_note' => $data['description'],
                'shahbaz_verified_by_user_id' => null,
                'shahbaz_verified_at' => null,
            ])->save();

            \App\Shahbaz\Models\LicenseRequest::where('company_id', $company->id)
                ->whereIn('status', ['submitted', 'association_review'])
                ->get()->each(function ($licenseRequest) use ($to, $data, $request): void {
                    $requestFrom = $licenseRequest->status;
                    $requestTo = $to === 'ready_for_shahbaz_check' ? 'ready_for_shahbaz_check' : $to;
                    $licenseRequest->forceFill([
                        'status' => $requestTo,
                        'correction_reason' => $to === 'correction_required' ? $data['description'] : null,
                        'finalized_at' => $to === 'rejected' ? now() : null,
                    ])->save();
                    \App\Shahbaz\Models\LicenseRequestHistory::create([
                        'request_id' => $licenseRequest->id,
                        'from_status' => $requestFrom,
                        'to_status' => $requestTo,
                        'description' => $data['description'],
                        'request_snapshot' => $licenseRequest->fresh()->toArray(),
                        'changed_by_user_id' => $request->user()->id,
                    ]);
                });

            CompanyVerificationHistory::create([
                'company_id' => $company->id,
                'from_status' => $from,
                'to_status' => $to,
                'result' => $data['result'],
                'description' => $data['description'],
                'company_snapshot' => $company->fresh()->toArray(),
                'changed_by_user_id' => $request->user()->id,
                'checked_at' => now(),
            ]);
        });

        return redirect()->route('association.shahbaz.companies.index')->with('success', 'نتیجه بررسی اولیه ثبت شد.');
    }
    public function review(Request $request, Company $company, CompanyEligibilityService $eligibility)
    {
        abort_unless($company->shahbaz_verification_status === 'ready_for_shahbaz_check', 403);
        foreach (['activity_license_issued_on_jalali'=>'activity_license_issued_on','activity_license_expires_on_jalali'=>'activity_license_expires_on'] as $jalaliField=>$dateField) {
            if (!$request->filled($jalaliField)) { $request->merge([$dateField=>null]); continue; }
            try { $value=strtr((string)$request->input($jalaliField),['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']); $request->merge([$dateField=>Jalalian::fromFormat('Y/m/d',$value)->toCarbon()->format('Y-m-d')]); }
            catch (\Throwable) { throw ValidationException::withMessages([$jalaliField=>'تاریخ شمسی انتخاب‌شده معتبر نیست.']); }
        }
        $data=$request->validate(['result'=>['required',Rule::in(['verified','correction_required','shahbaz_mismatch','company_not_found','follow_up'])],'description'=>['required','string','max:3000'],'activity_license_number'=>['nullable','required_if:result,verified','string','max:100',Rule::unique('companies')->ignore($company->id)],'activity_license_issued_on'=>['nullable','required_if:result,verified','date'],'activity_license_expires_on'=>['nullable','required_if:result,verified','date','after_or_equal:activity_license_issued_on']]);
        if($data['result']==='verified' && $eligibility->missingFields($company)!==[]) return back()->withErrors(['result'=>'تا تکمیل تمام اطلاعات الزامی، تأیید شرکت امکان‌پذیر نیست.']);
        DB::transaction(function() use($company,$data,$request){
            $from=$company->shahbaz_verification_status;
            $verified=$data['result']==='verified';
            $to=$verified ? 'verified' : (in_array($data['result'], ['shahbaz_mismatch','company_not_found'], true) ? 'shahbaz_mismatch' : ($data['result']==='follow_up' ? 'ready_for_shahbaz_check' : $data['result']));
            $company->forceFill(['shahbaz_verification_status'=>$to,'shahbaz_review_note'=>$data['description'],'shahbaz_verified_by_user_id'=>$verified?$request->user()->id:null,'shahbaz_verified_at'=>$verified?now():null,'activity_license_number'=>$verified?$data['activity_license_number']:$company->activity_license_number,'activity_license_issued_on'=>$verified?$data['activity_license_issued_on']:$company->activity_license_issued_on,'activity_license_expires_on'=>$verified?$data['activity_license_expires_on']:$company->activity_license_expires_on,'activity_license_status'=>$verified?'active':'unverified'])->save();
            \App\Shahbaz\Models\LicenseRequest::where('company_id',$company->id)->where('status','ready_for_shahbaz_check')->get()->each(function($licenseRequest) use($verified,$to,$data,$request){
                $requestFrom=$licenseRequest->status;
                $requestTo=$verified?'shahbaz_verified':$to;
                $licenseRequest->forceFill(['status'=>$requestTo,'correction_reason'=>in_array($requestTo,['correction_required','shahbaz_mismatch'],true)?$data['description']:null])->save();
                \App\Shahbaz\Models\LicenseRequestHistory::create(['request_id'=>$licenseRequest->id,'from_status'=>$requestFrom,'to_status'=>$requestTo,'description'=>$data['description'],'request_snapshot'=>$licenseRequest->fresh()->toArray(),'changed_by_user_id'=>$request->user()->id]);
            });
            CompanyVerificationHistory::create(['company_id'=>$company->id,'from_status'=>$from,'to_status'=>$to,'result'=>$data['result'],'description'=>$data['description'],'company_snapshot'=>$company->fresh()->toArray(),'changed_by_user_id'=>$request->user()->id,'checked_at'=>now()]);
        });
        return redirect()->route('association.shahbaz.companies.index')->with('success','نتیجه بررسی شحباز ثبت شد.');
    }
}
