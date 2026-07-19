<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\BranchPermit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

class CompanyBranchPermitController extends Controller
{
    private const EDITABLE_STATUSES = ['profile_incomplete', 'correction_required', 'shahbaz_mismatch'];

    public function index(Request $request): View
    {
        $company = $request->user()->company;

        return view('Shahbaz.company.branches.index', [
            'company' => $company,
            'permits' => BranchPermit::where('company_id', $company->id)->latest('id')->get(),
            'editable' => in_array($company->shahbaz_verification_status, self::EDITABLE_STATUSES, true),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_unless(in_array($company->shahbaz_verification_status, self::EDITABLE_STATUSES, true), 403);
        if (! filled($company->activity_license_number)) {
            throw ValidationException::withMessages([
                'permit_number' => 'ابتدا پروانه اصلی شرکت باید توسط انجمن ثبت و تأیید شود.',
            ]);
        }
        $this->mergeJalaliDate($request, 'issued_on_jalali', 'issued_on');
        $this->mergeJalaliDate($request, 'expires_on_jalali', 'expires_on');

        $data = $request->validate([
            'branch_type' => ['required', Rule::in(['شعبه', 'نمایندگی'])],
            'name' => ['required', 'string', 'max:200'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:2000'],
            'manager_name' => ['nullable', 'string', 'max:200'],
            'permit_number' => ['required', 'string', 'max:100', Rule::unique('shahbaz_branch_permits')],
            'issued_on' => ['required', 'date'],
            'expires_on' => ['required', 'date', 'after_or_equal:issued_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'permit_number.unique' => 'این شماره مجوز قبلاً در سامانه ثبت شده است.',
            'expires_on.after_or_equal' => 'تاریخ پایان اعتبار نمی‌تواند قبل از تاریخ صدور باشد.',
        ]);

        BranchPermit::create($data + [
            'company_id' => $company->id,
            'status' => 'active',
            'created_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'مجوز شعبه یا نمایندگی ثبت شد.');
    }

    private function mergeJalaliDate(Request $request, string $source, string $target): void
    {
        if (! $request->filled($source)) {
            $request->merge([$target => null]);
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
