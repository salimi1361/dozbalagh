<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use App\Models\Role;
use App\Models\User;
use App\Services\PanelFeatureService;
use App\Services\SmsService;
use App\Shahbaz\Services\CompanyEligibilityService;

class AuthController extends Controller
{
    private const PASSWORD_RESET_SESSION_KEY = 'password_reset';
    private const PASSWORD_RESET_CACHE_PREFIX = 'password-reset:';
    private const PASSWORD_RESET_TTL_SECONDS = 300;
    private const PASSWORD_RESET_MAX_ATTEMPTS = 5;

    // نمایش صفحه لاگین
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if ($request->boolean('clear_recovery')) {
            $this->clearPasswordReset($request);
        }

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
        
        $passwordReset = $request->session()->get(self::PASSWORD_RESET_SESSION_KEY);
        if (! $this->passwordResetCachePayload($passwordReset)) {
            if ($passwordReset) {
                $this->clearPasswordReset($request);
            }
            $passwordReset = null;
        }

        return view('auth.login', [
            'passwordReset' => $passwordReset,
            'showPasswordRecovery' => $request->boolean('recover')
                || (bool) $passwordReset
                || (bool) $request->session()->get('password_recovery_open'),
        ]);
    }

    public function requestPasswordReset(Request $request, SmsService $sms): RedirectResponse
    {
        $request->merge([
            'username' => $this->normalizeDigits(trim((string) $request->input('username'))),
        ]);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:20', 'regex:/^[0-9]{11}$/'],
        ], [
            'username.required' => 'لطفاً شناسه ملی شرکت را وارد کنید.',
            'username.max' => 'شناسه ملی شرکت معتبر نیست.',
            'username.regex' => 'شناسه ملی شرکت باید دقیقاً ۱۱ رقم باشد.',
        ]);

        $this->clearPasswordReset($request);

        $username = $validated['username'];
        $user = User::query()
            ->where('username', $username)
            ->where('status', 'active')
            ->whereHas('company')
            ->with('company')
            ->first();
        $mobile = trim((string) ($user?->company?->ceo_mobile));

        if (! $user || $mobile === '') {
            return redirect()->route('login', ['recover' => 1])
                ->withErrors(['username' => 'حساب شرکت یا شماره موبایل مدیرعامل برای بازیابی رمز ثبت نشده است.'])
                ->withInput()
                ->with('password_recovery_open', true);
        }

        $token = Str::random(64);
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addSeconds(self::PASSWORD_RESET_TTL_SECONDS)->timestamp;
        $cacheKey = $this->passwordResetCacheKey($token);

        Cache::put($cacheKey, [
            'user_id' => $user->id,
            'otp_hash' => Hash::make($code),
            'attempts' => 0,
            'verified' => false,
            'expires_at' => $expiresAt,
        ], self::PASSWORD_RESET_TTL_SECONDS);

        $sent = $sms->send(
            $mobile,
            "کد موقت بازیابی رمز عبور سامانه دوزوله: {$code}\nاین کد تا ۵ دقیقه معتبر است.",
            'password_reset'
        );

        if (! $sent) {
            Cache::forget($cacheKey);

            return redirect()->route('login', ['recover' => 1])
                ->withErrors(['username' => 'ارسال کد پیامکی انجام نشد. لطفاً بعداً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.'])
                ->withInput()
                ->with('password_recovery_open', true);
        }

        $request->session()->put(self::PASSWORD_RESET_SESSION_KEY, [
            'token' => $token,
            'username' => $username,
            'mobile' => $this->maskMobile($mobile),
            'step' => 'verify',
        ]);

        return redirect()->route('login')
            ->with('success', 'کد موقت به شماره ثبت‌شده مدیرعامل ارسال شد. این کد را وارد کنید.')
            ->with('password_recovery_open', true)
            ->withInput(['username' => $username]);
    }

    public function verifyPasswordResetCode(Request $request): RedirectResponse
    {
        $request->merge([
            'otp' => $this->normalizeDigits(trim((string) $request->input('otp'))),
        ]);

        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'کد موقت پیامک‌شده را وارد کنید.',
            'otp.digits' => 'کد موقت باید ۶ رقم باشد.',
        ]);

        $reset = $request->session()->get(self::PASSWORD_RESET_SESSION_KEY);
        $payload = $this->passwordResetCachePayload($reset);

        if (! is_array($reset) || ! is_array($payload)) {
            $this->clearPasswordReset($request);

            return redirect()->route('login', ['recover' => 1])
                ->withErrors(['otp' => 'درخواست بازیابی منقضی شده است. دوباره کد دریافت کنید.'])
                ->with('password_recovery_open', true);
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        if ($attempts >= self::PASSWORD_RESET_MAX_ATTEMPTS) {
            $this->clearPasswordReset($request);

            return redirect()->route('login', ['recover' => 1])
                ->withErrors(['otp' => 'تعداد تلاش‌های ورود کد تمام شده است. دوباره کد دریافت کنید.'])
                ->withInput(['username' => $reset['username'] ?? ''])
                ->with('password_recovery_open', true);
        }

        if (! isset($payload['otp_hash']) || ! Hash::check($validated['otp'], $payload['otp_hash'])) {
            $payload['attempts'] = $attempts + 1;

            if ($payload['attempts'] >= self::PASSWORD_RESET_MAX_ATTEMPTS) {
                $this->clearPasswordReset($request);

                return redirect()->route('login', ['recover' => 1])
                    ->withErrors(['otp' => 'کد واردشده صحیح نیست و تعداد تلاش‌ها تمام شد. دوباره کد دریافت کنید.'])
                    ->withInput(['username' => $reset['username'] ?? ''])
                    ->with('password_recovery_open', true);
            }

            Cache::put(
                $this->passwordResetCacheKey($reset['token']),
                $payload,
                $this->passwordResetTtl($payload)
            );

            return redirect()->route('login')
                ->withErrors(['otp' => 'کد واردشده صحیح نیست.'])
                ->with('password_recovery_open', true);
        }

        $payload['verified'] = true;
        unset($payload['otp_hash']);
        Cache::put(
            $this->passwordResetCacheKey($reset['token']),
            $payload,
            $this->passwordResetTtl($payload)
        );
        $reset['step'] = 'new_password';
        $request->session()->put(self::PASSWORD_RESET_SESSION_KEY, $reset);

        return redirect()->route('login')
            ->with('success', 'کد تأیید شد. رمز جدید را در هر دو کادر وارد کنید.')
            ->with('password_recovery_open', true);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(8)],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'password.required' => 'رمز جدید را وارد کنید.',
            'password.min' => 'رمز جدید باید حداقل ۸ کاراکتر باشد.',
            'password_confirmation.required' => 'تکرار رمز جدید را وارد کنید.',
            'password_confirmation.same' => 'تکرار رمز جدید با رمز جدید یکسان نیست.',
        ]);

        $reset = $request->session()->get(self::PASSWORD_RESET_SESSION_KEY);
        $payload = $this->passwordResetCachePayload($reset);

        if (! is_array($reset) || ! is_array($payload) || ! ($payload['verified'] ?? false)) {
            $this->clearPasswordReset($request);

            return redirect()->route('login', ['recover' => 1])
                ->withErrors(['password' => 'ابتدا کد موقت پیامک‌شده را تأیید کنید.'])
                ->with('password_recovery_open', true);
        }

        $user = User::query()
            ->whereKey($payload['user_id'] ?? 0)
            ->where('status', 'active')
            ->whereHas('company')
            ->first();

        if (! $user) {
            $this->clearPasswordReset($request);

            return redirect()->route('login')
                ->withErrors(['password' => 'حساب کاربری برای تغییر رمز پیدا نشد.']);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => null,
        ])->save();

        $this->clearPasswordReset($request);
        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectToPanel($user)->with('success', 'رمز عبور با موفقیت تغییر کرد و وارد سامانه شدید.');
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

    private function passwordResetCachePayload($reset): ?array
    {
        if (! is_array($reset) || empty($reset['token'])) {
            return null;
        }

        $payload = Cache::get($this->passwordResetCacheKey($reset['token']));

        if (! is_array($payload) || (int) ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        return $payload;
    }

    private function passwordResetCacheKey(string $token): string
    {
        return self::PASSWORD_RESET_CACHE_PREFIX.$token;
    }

    private function passwordResetTtl(array $payload): int
    {
        $remaining = (int) ($payload['expires_at'] ?? 0) - now()->timestamp;

        return max(1, min(self::PASSWORD_RESET_TTL_SECONDS, $remaining));
    }

    private function clearPasswordReset(Request $request): void
    {
        $reset = $request->session()->pull(self::PASSWORD_RESET_SESSION_KEY);

        if (is_array($reset) && ! empty($reset['token'])) {
            Cache::forget($this->passwordResetCacheKey($reset['token']));
        }

        $request->session()->forget('password_recovery_open');
    }

    private function maskMobile(string $mobile): string
    {
        $mobile = $this->normalizeDigits($mobile);

        if (strlen($mobile) < 6) {
            return 'شماره ثبت‌شده';
        }

        return substr($mobile, 0, 4).'*****'.substr($mobile, -2);
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    private function redirectToPanel(User $user)
    {
        $user = $this->synchronizeCompanyRole($user);

        return match ($user->role?->name) {
            'admin' => redirect()->route('admin.dashboard')->with('success', 'به پنل مدیریت کل سامانه خوش آمدید!'),
            'association' => $this->redirectToFirstEnabledFeature($user, 'به پنل انجمن خوش آمدید!'),
            'company' => $this->redirectToFirstEnabledFeature($user, 'به پنل شرکت خوش آمدید!'),
            default => $this->logoutUnsupportedUser(),
        };
    }

    private function synchronizeCompanyRole(User $user): User
    {
        if ($user->role?->name === 'company' || ! $user->company()->exists()) {
            return $user;
        }

        $companyRoleId = Role::where('name', 'company')->value('id');

        if (! $companyRoleId) {
            return $user;
        }

        $user->forceFill(['role_id' => $companyRoleId])->save();
        $user->unsetRelation('role');

        return $user;
    }

    private function logoutUnsupportedUser()
    {
        Auth::logout();

        return redirect()->route('login')->withErrors([
            'username' => 'برای نقش این کاربر پنل وب تعریف نشده است.',
        ]);
    }

    private function redirectToFirstEnabledFeature(User $user, string $message)
    {
        if ($user->role?->name === 'company' && $user->company) {
            $eligibility = app(CompanyEligibilityService::class);

            if (! $eligibility->canOperate($user->company)) {
                return redirect()->route($eligibility->onboardingRoute($user->company))
                    ->with('warning', implode(' ', $eligibility->blockingReasons($user->company)));
            }
        }

        $route = app(PanelFeatureService::class)->landingRoute($user->role?->name ?? '', $user);

        if (! $route) {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'username' => 'تمام بخش‌های این پنل توسط مدیر سامانه غیرفعال شده‌اند.',
            ]);
        }

        return redirect()->route($route)->with('success', $message);
    }
}
