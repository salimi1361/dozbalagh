<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Services\ShahbazSectionService;
use App\Shahbaz\Models\CompanyVerificationHistory;
use App\Shahbaz\Models\LicenseRequest;
use App\Shahbaz\Models\LicenseRequestHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyDossierController extends Controller
{
    public function show(Request $request, ShahbazSectionService $sections)
    {
        $company = $request->user()->company;
        return view('Shahbaz.company.dossier', [
            'company' => $company, 'sections' => $sections->accessibleRows($company),
            'reminders' => $sections->reminders(),
        ]);
    }

    public function submit(Request $request, ShahbazSectionService $sections)
    {
        $company = $request->user()->company;
        abort_unless(in_array($company->shahbaz_verification_status, ['profile_incomplete','correction_required','shahbaz_mismatch'], true), 403);
        $incomplete = collect($sections->rows($company))->filter(fn ($row) => $row['visible'] && $row['required'] && ! $row['complete']);
        if ($incomplete->isNotEmpty()) return back()->withErrors(['dossier' => 'ابتدا بخش‌های اجباری را تکمیل کنید: '.$incomplete->pluck('label')->implode('، ')]);
        DB::transaction(function () use ($company, $request) {
            LicenseRequest::where('company_id', $company->id)->whereIn('status', ['draft', 'correction_required', 'shahbaz_mismatch'])
                ->get()->each(function (LicenseRequest $licenseRequest) use ($request) {
                    $from = $licenseRequest->status;
                    $licenseRequest->forceFill(['status' => 'submitted', 'submitted_at' => now()])->save();
                    LicenseRequestHistory::create([
                        'request_id' => $licenseRequest->id,
                        'from_status' => $from,
                        'to_status' => 'submitted',
                        'description' => 'درخواست همراه پرونده برای بررسی انجمن ارسال شد.',
                        'request_snapshot' => $licenseRequest->fresh()->toArray(),
                        'changed_by_user_id' => $request->user()->id,
                    ]);
                });
            $from = $company->shahbaz_verification_status;
            $company->forceFill(['shahbaz_verification_status'=>'pending_association_review','shahbaz_verified_by_user_id'=>null,'shahbaz_verified_at'=>null])->save();
            CompanyVerificationHistory::create(['company_id'=>$company->id,'from_status'=>$from,'to_status'=>'pending_association_review','result'=>'submitted','description'=>'پرونده کامل توسط شرکت برای بررسی انجمن ارسال شد.','company_snapshot'=>$company->fresh()->toArray(),'changed_by_user_id'=>$request->user()->id]);
        });
        return back()->with('success','پرونده برای کنترل انجمن و بررسی دستی شحباز ارسال و قفل شد.');
    }
}
