<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    // نمایش فرم تکمیل مدارک
    public function edit()
    {
        $company = auth()->user()->company;
        return view('company.profile.edit', compact('company'));
    }

    // ذخیره اطلاعات و مدارک
    public function update(Request $request)
    {
        $company = auth()->user()->company;

        // اعتبارسنجی اطلاعات دریافتی
        $request->validate([
            'name_fa' => 'required',
            'name_en' => 'required',
            'ceo_name' => 'required',
            'ceo_mobile' => 'required',
            'address_fa' => 'required',
            'address_en' => 'required',
        ]);

        // آپدیت تمام فیلدها در دیتابیس
        $company->name_fa = $request->name_fa;
        $company->name = $request->name_fa; // همگام‌سازی فیلد نام پایه
        $company->name_en = $request->name_en;
        $company->ceo_name = $request->ceo_name;
        $company->ceo_mobile = $request->ceo_mobile;
        $company->phone = $request->phone;
        $company->address_fa = $request->address_fa;
        $company->address_en = $request->address_en;

        $company->save();

        // آپدیت شماره موبایل در جدول کاربران (در صورت تغییر)
        $user = auth()->user();
        if ($user->mobile !== $request->ceo_mobile) {
            $user->mobile = $request->ceo_mobile;
            $user->save();
        }

        return back()->with('success', 'اطلاعات شما با موفقیت به‌روزرسانی شد. لطفاً تا بررسی و تایید مدیریت شکیبا باشید.');
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

        return back()->with('success', 'رمز عبور شما با موفقیت تغییر کرد.');
    }
}