<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\OfficialGazette;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

class CompanyOfficialGazetteController extends Controller
{
    private const EDITABLE_STATUSES = ['profile_incomplete', 'correction_required', 'shahbaz_mismatch'];

    public function index(Request $request): View
    {
        $company = $request->user()->company;

        return view('Shahbaz.company.gazettes.index', [
            'company' => $company,
            'gazettes' => OfficialGazette::where('company_id', $company->id)->latest('gazette_date')->latest('id')->get(),
            'editable' => in_array($company->shahbaz_verification_status, self::EDITABLE_STATUSES, true),
            'changeGroups' => $this->changeGroups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_unless(in_array($company->shahbaz_verification_status, self::EDITABLE_STATUSES, true), 403);
        $this->mergeJalaliDate($request, 'gazette_date_jalali', 'gazette_date', true);
        $this->mergeJalaliDate($request, 'notice_date_jalali', 'notice_date');

        $data = $request->validate([
            'gazette_number' => ['required', 'string', 'max:50', Rule::unique('shahbaz_official_gazettes')->where('company_id', $company->id)],
            'gazette_date' => ['required', 'date'],
            'change_group' => ['required', Rule::in($this->changeGroups())],
            'notice_number' => ['nullable', 'string', 'max:100'],
            'notice_date' => ['nullable', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'gazette_number.unique' => 'این شماره روزنامه رسمی قبلاً برای شرکت ثبت شده است.',
        ]);

        OfficialGazette::create($data + [
            'company_id' => $company->id,
            'created_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'سابقه روزنامه رسمی ثبت شد. سوابق قبلی بدون تغییر نگهداری می‌شوند.');
    }

    private function changeGroups(): array
    {
        return ['تأسیس شرکت', 'تغییر هیئت‌مدیره', 'تغییر مدیرعامل', 'تغییر سهامداران', 'تغییر نشانی', 'تغییر نام شرکت', 'تغییر موضوع فعالیت', 'تغییر سرمایه', 'سایر تغییرات'];
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
