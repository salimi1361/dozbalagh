<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\PanelFeatureService;

class AuthController extends Controller
{
    // نمایش صفحه لاگین
    public function showLoginForm()
    {
        // اگر کاربر قبلاً لاگین بود، بر اساس نوع کاربری هدایتش کن
        if (Auth::check()) {
            if (! Auth::user()->isActive()) {
                Auth::logout();

                return redirect()->route('login')->withErrors([
                    'username' => 'حساب کاربری شما فعال نیست.',
                ]);
            }

            return $this->redirectToPanel(Auth::user());
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
            'status' => 'active',
        ];

        // ۳. تلاش برای لاگین
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // جلوگیری از حملات Session Fixation
            $request->session()->regenerate();

            return $this->redirectToPanel(Auth::user());
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

    private function redirectToPanel(User $user)
    {
        return match ($user->role?->name) {
            'admin' => redirect()->route('admin.dashboard')->with('success', 'به پنل مدیریت کل سامانه خوش آمدید!'),
            'association' => $this->redirectToFirstEnabledFeature('association', 'به پنل انجمن خوش آمدید!'),
            'company' => $this->redirectToFirstEnabledFeature('company', 'به پنل شرکت خوش آمدید!'),
            default => $this->logoutUnsupportedUser(),
        };
    }

    private function logoutUnsupportedUser()
    {
        Auth::logout();

        return redirect()->route('login')->withErrors([
            'username' => 'برای نقش این کاربر پنل وب تعریف نشده است.',
        ]);
    }

    private function redirectToFirstEnabledFeature(string $role, string $message)
    {
        $route = app(PanelFeatureService::class)->landingRoute($role);

        if (! $route) {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'username' => 'تمام بخش‌های این پنل توسط مدیر سامانه غیرفعال شده‌اند.',
            ]);
        }

        return redirect()->route($route)->with('success', $message);
    }
}
