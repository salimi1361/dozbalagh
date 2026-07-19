<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\LicenseRequest;
use App\Shahbaz\Models\PaymentRequest;
use App\Shahbaz\Services\ShahbazSectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CompanyWorkflowController extends Controller
{
    public function show(Request $request, ShahbazSectionService $sections): View
    {
        $company = $request->user()->company;
        $licenseRequest = LicenseRequest::with('histories')
            ->where('company_id', $company->id)->latest('id')->first();
        $payment = PaymentRequest::where('company_id', $company->id)->latest('id')->first();

        $sectionRows = $sections->accessibleRows($company);
        $requestStatus = $licenseRequest?->status;
        $companyStatus = $company->shahbaz_verification_status;
        $requiredComplete = collect($sectionRows)->where('required', true)->every(fn (array $row) => $row['complete']);
        $steps = [
            ['title' => 'تکمیل اطلاعات پرونده', 'done' => $requiredComplete, 'current' => $companyStatus === 'profile_incomplete'],
            ['title' => 'ثبت درخواست پروانه', 'done' => (bool) $licenseRequest, 'current' => ! $licenseRequest],
            ['title' => 'ارسال برای بررسی انجمن', 'done' => in_array($companyStatus, ['pending_association_review', 'verified'], true), 'current' => $companyStatus === 'pending_association_review'],
            ['title' => 'رفع نقص یا مغایرت', 'done' => ! in_array($companyStatus, ['correction_required', 'shahbaz_mismatch'], true), 'current' => in_array($companyStatus, ['correction_required', 'shahbaz_mismatch'], true)],
            ['title' => 'کنترل دستی شحباز', 'done' => $companyStatus === 'verified', 'current' => $companyStatus === 'pending_association_review'],
            ['title' => 'تعیین هزینه توسط انجمن', 'done' => (bool) $payment, 'current' => $companyStatus === 'verified' && ! $payment],
            ['title' => 'پرداخت هزینه', 'done' => $payment?->status === 'paid', 'current' => $payment && $payment->status !== 'paid'],
            ['title' => 'صدور پروانه', 'done' => $requestStatus === 'issued' || filled($company->activity_license_number), 'current' => $payment?->status === 'paid' && $requestStatus !== 'issued'],
        ];

        return view('Shahbaz.company.workflow.show', [
            'company' => $company,
            'licenseRequest' => $licenseRequest,
            'payment' => $payment,
            'sections' => $sectionRows,
            'steps' => $steps,
            'requestStatus' => $requestStatus,
            'companyStatus' => $companyStatus,
            'verificationHistories' => $company->shahbazVerificationHistories()->with('changedBy')->latest('id')->get(),
        ]);
    }
}
