<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\CompanyVerificationHistory;
use App\Shahbaz\Services\CompanyEligibilityService;
use App\Shahbaz\Services\ShahbazSectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyProfileController extends Controller
{
    public function edit(Request $request, CompanyEligibilityService $eligibility, ShahbazSectionService $sections)
    {
        $company = $request->user()->company;
        $editable = in_array($company->shahbaz_verification_status, [
            'profile_incomplete', 'correction_required', 'shahbaz_mismatch',
        ], true);

        return view('Shahbaz.company.profile', [
            'company' => $company,
            'editable' => $editable,
            'missingFields' => $eligibility->missingFields($company),
            'blockingReasons' => $eligibility->blockingReasons($company),
            'requiredSections' => array_values(array_filter(
                $sections->rows($company),
                static fn (array $row): bool => $row['required']
            )),
        ]);
    }

    public function update(Request $request)
    {
        $company = $request->user()->company;
        abort_unless(in_array($company->shahbaz_verification_status, [
            'profile_incomplete', 'correction_required', 'shahbaz_mismatch',
        ], true), 403);

        $data = $request->validate([
            'name_fa' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255', 'regex:/[A-Za-z]/'],
            'national_id' => [
                'required', 'string', 'max:30', 'regex:/^\d+$/',
                Rule::unique('companies', 'national_id')->ignore($company->id),
                Rule::unique('users', 'username')->ignore($company->user_id),
            ],
            'registration_number' => ['required', 'string', 'max:100'],
            'ceo_name' => ['required', 'string', 'max:255'],
            'ceo_national_code' => ['required', 'string', 'max:20'],
            'ceo_mobile' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30', Rule::notIn(['000'])],
            'postal_code' => ['required', 'digits:10'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'activity_type' => ['required', 'string', 'max:255'],
            'address_fa' => ['required', 'string', Rule::notIn(['ثبت نشده'])],
            'address_en' => ['required', 'string', 'regex:/[A-Za-z]/', Rule::notIn(['N/A', 'n/a'])],
        ], [
            'name_en.regex' => 'نام لاتین باید با حروف انگلیسی وارد شود.',
            'address_en.regex' => 'آدرس لاتین باید با حروف انگلیسی وارد شود.',
            'postal_code.digits' => 'کدپستی باید دقیقاً ۱۰ رقم باشد.',
        ]);

        DB::transaction(function () use ($company, $data, $request): void {
            $from = $company->shahbaz_verification_status;
            $legacyStatus = in_array($from, ['correction_required', 'shahbaz_mismatch'], true)
                ? 'correction_required'
                : 'profile_incomplete';

            $company->fill($data + ['name' => $data['name_fa']]);
            $company->forceFill([
                'status' => $legacyStatus,
                'shahbaz_verified_by_user_id' => null,
                'shahbaz_verified_at' => null,
            ])->save();

            // The company username is its national ID. Keep the credential in
            // sync if the association-approved profile is corrected.
            $request->user()->update([
                'username' => $data['national_id'],
                'mobile' => $data['ceo_mobile'],
            ]);

            CompanyVerificationHistory::create([
                'company_id' => $company->id,
                'from_status' => $from,
                'to_status' => $from,
                'result' => 'draft_updated',
                'description' => 'مشخصات پایه شرکت در پیش‌نویس پرونده به‌روزرسانی شد.',
                'company_snapshot' => $company->fresh()->toArray(),
                'changed_by_user_id' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'اطلاعات ذخیره شد. پس از تکمیل همه گام‌های اجباری، پرونده را از صفحه اصلی برای انجمن ارسال کنید.');
    }
}
