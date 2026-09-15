<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // نمایش فرم تکمیل مدارک
    public function edit()
    {
        $company = auth()->user()->company;
        return view('company.profile.edit', compact('company'));
    }

    public function passwordEdit()
    {
        return view('company.profile.password');
    }

    // ذخیره اطلاعات و مدارک
    public function update(Request $request)
    {
        $company = auth()->user()->company;

        abort_unless(in_array($company->status, ['pending', 'profile_incomplete', 'correction_required'], true), 403);

        $data = $request->validate([
            'name_fa' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255', 'regex:/[A-Za-z]/'],
            'registration_number' => ['required', 'string', 'max:100'],
            'ceo_name' => ['required', 'string', 'max:255'],
            'ceo_national_code' => ['required', 'digits:10'],
            'ceo_mobile' => ['required', 'regex:/^09[0-9]{9}$/'],
            'phone' => ['required', 'string', 'max:30', Rule::notIn(['000'])],
            'postal_code' => ['required', 'digits:10'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'activity_type' => ['required', 'string', 'max:255'],
            'address_fa' => ['required', 'string', 'max:2000', Rule::notIn(['ثبت نشده'])],
            'address_en' => ['required', 'string', 'max:2000', 'regex:/[A-Za-z]/', Rule::notIn(['N/A'])],
        ], [
            'name_en.regex' => 'نام لاتین باید با حروف انگلیسی وارد شود.',
            'address_en.regex' => 'آدرس لاتین باید با حروف انگلیسی وارد شود.',
            'ceo_national_code.digits' => 'کد ملی مدیرعامل باید ۱۰ رقم باشد.',
            'ceo_mobile.regex' => 'شماره همراه مدیرعامل باید ۱۱ رقم و با 09 شروع شود.',
            'postal_code.digits' => 'کدپستی باید ۱۰ رقم باشد.',
        ]);

        DB::transaction(function () use ($company, $data): void {
            $company->fill($data + ['name' => $data['name_fa']]);
            $company->forceFill([
                'status' => 'pending_review',
                'profile_submitted_at' => now(),
                'profile_review_note' => null,
                'profile_reviewed_by_user_id' => null,
                'profile_reviewed_at' => null,
            ])->save();

            auth()->user()->update(['mobile' => $data['ceo_mobile']]);
        });

        return back()->with('success', 'اطلاعات شرکت برای بررسی انجمن ارسال شد. پس از تأیید انجمن، دسترسی کامل سامانه فعال می‌شود.');
    }
	// تغییر رمز عبور شرکت
    public function updatePassword(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ], [
            'password.min' => 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور جدید تطابق ندارد.',
        ]);

        $user = auth()->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'رمز عبور فعلی اشتباه است.']);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->save();

        return redirect()
            ->route('company.password.edit')
            ->with('success', 'رمز عبور شما با موفقیت تغییر کرد.');
    }
}
