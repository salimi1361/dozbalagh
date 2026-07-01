<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // نمایش صفحه لاگین
    public function showLoginForm()
    {
        // اگر کاربر قبلاً لاگین بود، بر اساس نوع کاربری هدایتش کن
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->company) {
                return redirect()->route('dashboard'); // هدایت به پنل شرکت
            }
            return redirect()->route('admin.dashboard')->with('success', 'به پنل مدیریت سامانه خوش آمدید!');        
        }
        
        return view('auth.login');
    }

    // پردازش اطلاعات ورود
    public function login(Request $request)
    {
        // ۱. اعتبارسنجی ورودی‌های فرم
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => 'لطفاً نام کاربری یا شناسه ملی را وارد کنید.',
            'password.required' => 'لطفاً رمز عبور را وارد کنید.',
        ]);

        // ۲. تنظیم کلیدهای ورود
        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        // ۳. تلاش برای لاگین
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // جلوگیری از حملات Session Fixation
            $request->session()->regenerate();

            $user = Auth::user();

            // ۴. بررسی نقش کاربر و هدایت به مسیر درست
            if ($user->company) {
                // اگر رکوردی در جدول شرکت‌ها داشت، به داشبورد اختصاصی شرکت برود
                return redirect()->route('dashboard')->with('success', 'به پنل کاربری خوش آمدید!');
            }

            // 🔴 اصلاح مهم: در غیر این صورت ادمین است و باید به داشبورد کل برود
            return redirect()->route('admin.dashboard')->with('success', 'به پنل مدیریت سامانه خوش آمدید!');
        }

        // ۵. اگر اطلاعات غلط بود
        return back()->withErrors([
            'username' => 'نام کاربری/شناسه ملی یا رمز عبور اشتباه است.',
        ])->onlyInput('username');
    }

    // خروج از حساب کاربری
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'با موفقیت از سامانه خارج شدید.');
    }
}