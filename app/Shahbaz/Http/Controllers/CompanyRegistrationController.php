<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\CompanyRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

class CompanyRegistrationController extends Controller
{
    private const EDITABLE_STATUSES = ['profile_incomplete', 'correction_required', 'shahbaz_mismatch'];

    public function edit(Request $request): View
    {
        $company = $request->user()->company;

        return view('Shahbaz.company.registration.edit', [
            'company' => $company,
            'registration' => CompanyRegistration::firstOrNew(['company_id' => $company->id]),
            'editable' => in_array($company->shahbaz_verification_status, self::EDITABLE_STATUSES, true),
            'legalTypes' => ['سهامی خاص', 'سهامی عام', 'با مسئولیت محدود', 'تعاونی', 'مؤسسه غیرتجاری', 'شرکت دولتی', 'سایر'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_unless(in_array($company->shahbaz_verification_status, self::EDITABLE_STATUSES, true), 403);
        abort_unless(filled($company->national_id) && filled($company->registration_number), 422, 'ابتدا شناسه ملی و شماره ثبت را در مشخصات پایه شرکت تکمیل کنید.');

        $this->mergeJalaliDate($request, 'registered_on_jalali', 'registered_on', true);
        $this->mergeJalaliDate($request, 'introduction_letter_date_jalali', 'introduction_letter_date');
        $legalTypes = ['سهامی خاص', 'سهامی عام', 'با مسئولیت محدود', 'تعاونی', 'مؤسسه غیرتجاری', 'شرکت دولتی', 'سایر'];
        $data = $request->validate([
            'registered_on' => ['required', 'date'],
            'registration_city' => ['required', 'string', 'max:150'],
            'legal_type' => ['required', Rule::in($legalTypes)],
            'introduction_letter_number' => ['nullable', 'string', 'max:100'],
            'introduction_letter_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        CompanyRegistration::updateOrCreate(
            ['company_id' => $company->id],
            $data + ['updated_by_user_id' => $request->user()->id]
        );

        return back()->with('success', 'اطلاعات ثبت شرکت ذخیره شد.');
    }

    private function mergeJalaliDate(Request $request, string $source, string $target, bool $required = false): void
    {
        if (! $request->filled($source)) {
            $request->merge([$target => null]);
            if ($required) throw ValidationException::withMessages([$source => 'انتخاب تاریخ شمسی الزامی است.']);
            return;
        }
        try {
            $value = strtr((string) $request->input($source), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
            $request->merge([$target => Jalalian::fromFormat('Y/m/d', $value)->toCarbon()->format('Y-m-d')]);
        } catch (\Throwable) {
            throw ValidationException::withMessages([$source => 'تاریخ شمسی انتخاب‌شده معتبر نیست.']);
        }
    }
}
