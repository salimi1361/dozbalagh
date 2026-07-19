<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\CompanyFacility;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

class CompanyFacilitiesController extends Controller
{
    private const EDITABLE_STATUSES = ['profile_incomplete', 'correction_required', 'shahbaz_mismatch'];

    private const FACILITY_TYPES = ['دفتر کار', 'پارکینگ', 'سرویس بهداشتی', 'استراحتگاه', 'نمازخانه', 'انبار', 'تعمیرگاه'];

    public function edit(Request $request): View
    {
        $company = $request->user()->company;
        $facility = CompanyFacility::firstOrNew(['company_id' => $company->id]);

        return view('Shahbaz.company.facilities.edit', [
            'company' => $company,
            'facility' => $facility,
            'facilityTypes' => self::FACILITY_TYPES,
            'editable' => in_array($company->shahbaz_verification_status, self::EDITABLE_STATUSES, true),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_unless(in_array($company->shahbaz_verification_status, self::EDITABLE_STATUSES, true), 403);

        $this->mergeJalaliDate($request);
        $data = $request->validate([
            'location_type' => ['required', Rule::in(['پایانه اختصاصی', 'پایانه عمومی', 'دفتر مرکزی', 'شعبه', 'سایر'])],
            'ownership_type' => ['required', Rule::in(['ملکی', 'استیجاری', 'واگذاری', 'سایر'])],
            'postal_code' => ['required', 'digits:10'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:2000'],
            'started_on' => ['nullable', 'date'],
            'facilities' => ['required', 'array', 'min:1'],
            'facilities.*.type' => ['required', Rule::in(self::FACILITY_TYPES)],
            'facilities.*.area' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'facilities.required' => 'ثبت حداقل یک امکان الزامی است.',
            'facilities.min' => 'ثبت حداقل یک امکان الزامی است.',
            'postal_code.digits' => 'کدپستی باید دقیقاً ۱۰ رقم باشد.',
        ]);

        CompanyFacility::updateOrCreate(
            ['company_id' => $company->id],
            $data + ['updated_by_user_id' => $request->user()->id]
        );

        return redirect()->route('company.shahbaz.facilities.edit')->with('success', 'اطلاعات محل و امکانات ذخیره شد.');
    }

    private function mergeJalaliDate(Request $request): void
    {
        if (! $request->filled('started_on_jalali')) {
            $request->merge(['started_on' => null]);
            return;
        }

        try {
            $value = strtr((string) $request->input('started_on_jalali'), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
            $request->merge(['started_on' => Jalalian::fromFormat('Y/m/d', $value)->toCarbon()->format('Y-m-d')]);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['started_on_jalali' => 'تاریخ شمسی انتخاب‌شده معتبر نیست.']);
        }
    }
}
