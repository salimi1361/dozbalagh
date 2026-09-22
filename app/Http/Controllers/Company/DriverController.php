<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyDriverMessage;
use App\Models\Driver;
use App\Models\MobileAppVersion;
use App\Models\SystemSetting;
use App\Notifications\CompanyDriverMessageNotification;
use App\Services\CompanyAssetReleaseGuard;
use App\Services\DriverIdentityService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class DriverController extends Controller
{
    private function normalizeDigits(mixed $value): string
    {
        return strtr(trim((string) $value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    private function driverRegistrationSmsEnabled(): bool
    {
        $value = SystemSetting::getValue('driver_registration_app_sms_enabled', true);

        if (is_bool($value)) {
            return $value;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? ((int) $value === 1);
    }

    private function driverAppDownloadUrl(): string
    {
        $version = MobileAppVersion::query()
            ->where('platform', 'android')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at')
            ->orderByDesc('latest_build')
            ->first();

        return trim((string) $version?->download_url) ?: url('/driver/index.html');
    }

    private function sendDriverRegistrationAppSms(
        SmsService $sms,
        string $mobile,
        string $firstNameFa,
        string $lastNameFa,
        string $companyName
    ): bool {
        try {
            $driverName = trim($firstNameFa . ' ' . $lastNameFa);
            $message = "راننده گرامی {$driverName}، اطلاعات شما در شرکت {$companyName} با موفقیت ثبت گردید.";

            return $sms->send($mobile, $message, 'driver_registration_app_link');
        } catch (Throwable $e) {
            Log::warning('Driver registration app link SMS failed: ' . $e->getMessage());

            return false;
        }
    }

    // ۱. لیست رانندگان شرکت به همراه تعداد دوزبلاغ‌های فعال
    public function index()
    {
        // 🟢 خواندن امن آیدی شرکت از کاربری که لاگین کرده است
        $companyId = auth()->user()->company->id;
        
        $drivers = DB::table('drivers')
                     ->select('drivers.*')
                     ->addSelect([
                         'active_dozbalaghs' => DB::table('dozbalagh_items')
                                                 ->whereColumn('dozbalagh_items.driver_id', 'drivers.id')
                                                 ->where('dozbalagh_items.company_id', $companyId)
                                                 ->whereIn('dozbalagh_items.lifecycle_status', ['issued', 'consumed'])
                                                 ->selectRaw('count(*)')
                     ])
                     ->addSelect([
                         'unread_company_messages' => DB::table('notifications')
                                                 ->whereColumn('notifications.notifiable_id', 'drivers.id')
                                                 ->where('notifications.notifiable_type', Driver::class)
                                                 ->whereNull('notifications.read_at')
                                                 ->where('notifications.type', CompanyDriverMessageNotification::class)
                                                 ->whereJsonContains('notifications.data->company_id', $companyId)
                                                 ->selectRaw('count(*)')
                     ])
                     ->addSelect([
                         'last_company_message_at' => DB::table('notifications')
                                                 ->whereColumn('notifications.notifiable_id', 'drivers.id')
                                                 ->where('notifications.notifiable_type', Driver::class)
                                                 ->where('notifications.type', CompanyDriverMessageNotification::class)
                                                 ->whereJsonContains('notifications.data->company_id', $companyId)
                                                 ->selectRaw('max(created_at)')
                     ])
                     ->where('current_company_id', $companyId) // فیلتر کردن دقیق بر اساس شرکت فعلی
                     ->where('is_active', true)
                     ->orderBy('id', 'desc')
                     ->get();

        return view('company.driver.index', compact('drivers'));
    }

    public function notify(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id ?? auth()->user()->company_id;

        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'شرکت کاربر لاگین‌شده شناسایی نشد.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'scope' => ['required', Rule::in(['selected', 'all'])],
            'driver_ids' => ['required_if:scope,selected', 'array'],
            'driver_ids.*' => ['integer'],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
            'category' => ['required', Rule::in(['general', 'warning', 'trip_change', 'action_required', 'document', 'settlement'])],
            'priority' => ['required', Rule::in(['normal', 'important', 'urgent'])],
            'requires_acknowledgement' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ], [
            'driver_ids.required_if' => 'حداقل یک راننده را برای ارسال پیام انتخاب کنید.',
            'title.required' => 'عنوان پیام را وارد کنید.',
            'message.required' => 'متن پیام را وارد کنید.',
            'expires_at.after' => 'تاریخ انقضای پیام باید بعد از زمان فعلی باشد.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $query = Driver::active()->where('current_company_id', $companyId);

        if ($request->scope === 'selected') {
            $query->whereIn('id', $request->input('driver_ids', []));
        }

        $drivers = $query->get();

        if ($drivers->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'راننده‌ای برای ارسال پیام یافت نشد.'], 404);
        }

        $companyName = $company->name_fa ?? $company->name ?? auth()->user()->name ?? 'شرکت حمل و نقل';
        $expiresAt = $request->filled('expires_at')
            ? Carbon::parse($request->expires_at)->toDateTimeString()
            : null;

        foreach ($drivers as $driver) {
            $companyDriverMessage = CompanyDriverMessage::create([
                'company_id' => $companyId,
                'driver_id' => $driver->id,
                'sender' => 'company',
                'title' => $request->title,
                'message' => $request->message,
                'category' => $request->category,
                'priority' => $request->priority,
                'requires_acknowledgement' => $request->boolean('requires_acknowledgement'),
            ]);

            $driver->notify(new CompanyDriverMessageNotification(
                companyId: $companyId,
                companyName: $companyName,
                title: $request->title,
                message: $request->message,
                category: $request->category,
                priority: $request->priority,
                requiresAcknowledgement: $request->boolean('requires_acknowledgement'),
                expiresAt: $expiresAt,
                messageId: $companyDriverMessage->id
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'پیام برای ' . $drivers->count() . ' راننده ثبت شد و در وب‌اپ راننده نمایش داده می‌شود.',
            'sent_count' => $drivers->count(),
        ]);
    }

    // ۲. استعلام زنده راننده با قابلیت رمزگشایی خطای ۴۰۰ بارگرام
    public function inquireApi(Request $request, DriverIdentityService $driverIdentity)
    {
        try {
            $nationalId = $this->normalizeDigits($request->input('national_code', $request->input('national_id')));
            $mobileNumber = $driverIdentity->normalizeMobile($request->input('mobile', $request->input('phone', $request->input('mobile_number'))));

            $inquiryValidator = Validator::make([
                'national_id' => $nationalId,
                'mobile' => $mobileNumber,
            ], [
                'national_id' => ['required', 'digits:10'],
                'mobile' => ['required', 'regex:/^09[0-9]{9}$/'],
            ], [
                'national_id.required' => 'کد ملی برای استعلام الزامی است.',
                'national_id.digits' => 'کد ملی باید دقیقاً ۱۰ رقم باشد.',
                'mobile.required' => 'شماره موبایل برای استعلام الزامی است.',
                'mobile.regex' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
            ]);

            if ($inquiryValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $inquiryValidator->errors()->first(),
                    'errors' => $inquiryValidator->errors()->toArray(),
                ], 422);
            }

            // گام اول: بررسی استعلام آفلاین از دیتابیس خودمان
            $localDriver = DB::table('drivers')->where('national_code', $nationalId)->first();
            $companyId = optional(auth()->user()->company)->id ?? auth()->user()->company_id ?? null;

            if ($localDriver && ! (bool) ($localDriver->is_active ?? true)) {
                return response()->json(['success' => false, 'message' => 'این راننده توسط انجمن غیرفعال شده و امکان ثبت یا ویرایش ندارد.'], 403);
            }

            if ($localDriver
                && $localDriver->current_company_id !== null
                && (int) $localDriver->current_company_id !== (int) $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'این راننده در حال حاضر تحت پوشش شرکت دیگری است؛ ابتدا باید توسط همان شرکت آزاد شود.',
                ], 409);
            }

            $mobileDriver = $driverIdentity->findByMobile($mobileNumber);
            if ($mobileDriver && (! $localDriver || (int) $mobileDriver->id !== (int) $localDriver->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'این شماره موبایل قبلاً برای راننده دیگری ثبت شده است؛ شماره را اصلاح کنید.',
                ], 409);
            }
            
            if ($localDriver && !empty($localDriver->first_name_fa) && !empty($localDriver->last_name_fa)) {
                $mockApiData = [
                    'driver' => [
                        'NAME' => $localDriver->first_name_fa,
                        'FAMILY' => $localDriver->last_name_fa
                    ]
                ];
                
                return response()->json([
                    'success' => true,
                    'data'    => $mockApiData,
                    'message' => 'اطلاعات راننده به صورت آفلاین از بانک اطلاعاتی سیستم بازیابی شد.'
                ]);
            }

            // توکن اختصاصی بارگرام
            $token = "eyJpdiI6IkhrVE8vVllaWUtHNE9Pb08xLzRSQ1E9PSIsInZhbHVlIjoiSEZSeC9URTVuc01Uc0FYKzBwWWIxTEhTc21ka0tENXFvR2ZYOUJFaFhkZVBHY0RyZ2ZSWkk2OGFOa1V2Y0lUM3NUV3dobmhwdFQzUlRLY2xLRTVnYlpHVE0wRGVuaGc4UzAxOUczQ2x2d0E9IiwibWFjIjoiZGRjMDYzMDBhZDVhY2M5ZmU0NDJmNmM0NDRhN2YxYTBlYjlhMzIyYmM1MWQyMDAzODY1OTBjYTg2OGIzN2VmMCIsInRhZyI6IiJ9";

            // ارسال درخواست آنلاین به بارگرام
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->post('https://api.bargram.ir/api/v2/ebl/driver_inquiry_mn', [
                    'national_id'   => $nationalId,
                    'mobile_number' => $mobileNumber ?? '',
                    'token'         => $token
                ]);

            if ($response->successful()) {
                $apiData = $response->json();
                
                if (isset($apiData['data']) && is_string($apiData['data'])) {
                    $apiData['data'] = json_decode($apiData['data'], true);
                }
                
                return response()->json([
                    'success' => true,
                    'data'    => $apiData,
                    'message' => 'اطلاعات با موفقیت از سازمان راهداری استعلام شد.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'ارتباط با سازمان قطع است؛ لطفاً اطلاعات را به‌صورت دستی وارد کنید.'
            ], 503);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'ارتباط با سازمان قطع است؛ لطفاً اطلاعات را به‌صورت دستی وارد کنید.'
            ], 503);
        }
    }

    // ۳. ثبت و ذخیره هوشمند (نسخه سازگار با AJAX)
    public function store(Request $request, SmsService $sms, DriverIdentityService $driverIdentity)
    {
        try {
            // 🟢 گرفتن آیدی شرکت به صورت کاملاً امن
            $companyId = auth()->user()->company->id ?? auth()->user()->company_id;
            $companyName = trim((string) (
                auth()->user()->company?->name_fa
                ?? auth()->user()->company?->name
                ?? 'شرکت حمل و نقل'
            ));
            $nationalCode = $this->normalizeDigits($request->input('national_id', $request->input('national_code')));
            $mobile = $driverIdentity->normalizeMobile($request->input('mobile', $request->input('phone')));
            $firstNameFa = trim((string) $request->input('first_name_fa', $request->input('first_name')));
            $lastNameFa = trim((string) $request->input('last_name_fa', $request->input('last_name')));
            $firstNameEn = strtoupper(trim((string) $request->input('first_name_en', $request->input('en_first_name'))));
            $lastNameEn = strtoupper(trim((string) $request->input('last_name_en', $request->input('en_last_name'))));
            $passportNumber = strtoupper(trim((string) $request->input('passport_number')));

            $validator = Validator::make([
                'national_id' => $nationalCode,
                'mobile' => $mobile,
                'first_name_fa' => $firstNameFa,
                'last_name_fa' => $lastNameFa,
                'first_name_en' => $firstNameEn,
                'last_name_en' => $lastNameEn,
                'passport_number' => $passportNumber,
            ], [
                'national_id' => ['required', 'digits:10'],
                'mobile' => ['required', 'regex:/^09[0-9]{9}$/'],
                'first_name_fa' => ['required', 'string', 'max:100'],
                'last_name_fa' => ['required', 'string', 'max:100'],
                'first_name_en' => ['required', 'string', 'max:100', "regex:/^[A-Za-z]+(?:[ '-][A-Za-z]+)*$/"],
                'last_name_en' => ['required', 'string', 'max:100', "regex:/^[A-Za-z]+(?:[ '-][A-Za-z]+)*$/"],
                'passport_number' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9][A-Za-z0-9 -]*$/'],
            ], [
                'national_id.required' => 'کد ملی راننده الزامی است.',
                'national_id.digits' => 'کد ملی باید دقیقاً ۱۰ رقم باشد.',
                'mobile.required' => 'شماره موبایل راننده الزامی است.',
                'mobile.regex' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
                'first_name_fa.required' => 'نام فارسی راننده را وارد کنید.',
                'last_name_fa.required' => 'نام خانوادگی فارسی راننده را وارد کنید.',
                'first_name_en.required' => 'نام لاتین راننده را وارد کنید.',
                'first_name_en.regex' => 'نام لاتین فقط باید با حروف انگلیسی وارد شود.',
                'last_name_en.required' => 'نام خانوادگی لاتین راننده را وارد کنید.',
                'last_name_en.regex' => 'نام خانوادگی لاتین فقط باید با حروف انگلیسی وارد شود.',
                'passport_number.required' => 'شماره پاسپورت راننده الزامی است.',
                'passport_number.regex' => 'شماره پاسپورت فقط می‌تواند شامل حروف انگلیسی، عدد، فاصله و خط تیره باشد.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()->toArray(),
                ], 422);
            }

            $existingDriver = DB::table('drivers')->where('national_code', $nationalCode)->first();
            if ($existingDriver && ! (bool) ($existingDriver->is_active ?? true)) {
                return response()->json(['success' => false, 'message' => 'این راننده توسط انجمن غیرفعال شده و امکان ثبت یا بازگردانی ندارد.'], 403);
            }

            $duplicateMobileDriver = $driverIdentity->findByMobile($mobile, $existingDriver?->id);
            if ($duplicateMobileDriver) {
                return response()->json([
                    'success' => false,
                    'message' => 'این شماره موبایل قبلاً برای راننده دیگری ثبت شده است؛ ابتدا شماره رکورد قبلی را اصلاح کنید.',
                ], 409);
            }

            $isEdit = $request->boolean('is_edit');
            if (!$isEdit) {
                $existingActive = DB::table('drivers')
                                    ->where('national_code', $nationalCode)
                                    ->where('current_company_id', $companyId)
                                    ->first();
                                    
                if ($existingActive) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'خطا: راننده‌ای با این کد ملی در حال حاضر در کارتابل شما فعال است.'
                    ], 400);
                }
            }

            // 🟢 [بخش جدید]: بررسی اینکه آیا این راننده از قبل حساب کاربری دارد یا نه
            $user = DB::table('users')->where('username', $nationalCode)->first();
            
            if ($user) {
                $userId = $user->id;
            } else {
                // اگر حساب نداشت، یکی برایش می‌سازیم
                // توجه: اگر در جدول roles نقشی با شناسه 3 (راننده) نداری، ممکن است اینجا ارور بدهد
                // در این صورت باید یک نقش با id=3 در دیتابیس (جدول roles) بسازی
                $userId = DB::table('users')->insertGetId([
                    'role_id'   => 3, 
                    'username'  => $nationalCode,
                    'password'  => Hash::make($mobile ?? $nationalCode),
                    'mobile'    => $mobile,
                    'status'    => 'active',
                    'is_manual' => 1,
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]);
            }

            $driverData = [
                    'user_id'            => $userId, // 🟢 اضافه شدن کلید کاربری به دیتابیس
                    'current_company_id' => $companyId,
                    'mobile'             => $mobile,
                    'first_name_fa'      => $firstNameFa,
                    'last_name_fa'       => $lastNameFa,
                    'first_name_en'      => $firstNameEn,
                    'last_name_en'       => $lastNameEn,
                    'passport_number'    => $passportNumber,
                    'contract_status'    => 'bound',
                    'updated_at'         => now(),
                ];

            $identityChanged = $existingDriver && (
                (string) ($existingDriver->mobile ?? '') !== $mobile
                || (int) ($existingDriver->current_company_id ?? 0) !== (int) $companyId
            );

            if ($existingDriver) {
                $updated = DB::table('drivers')
                    ->where('id', $existingDriver->id)
                    ->where(function ($query) use ($companyId) {
                        $query->whereNull('current_company_id')
                            ->orWhere('current_company_id', $companyId);
                    })
                    ->update($driverData);

                if ($updated === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'مالکیت راننده هم‌زمان تغییر کرده است؛ صفحه را تازه‌سازی و دوباره بررسی کنید.',
                    ], 409);
                }
            } else {
                DB::table('drivers')->insert($driverData + [
                    'national_code' => $nationalCode,
                    'created_at' => now(),
                ]);
            }

            DB::table('users')->where('id', $userId)->update([
                'mobile' => $mobile,
                'updated_at' => now(),
            ]);

            if ($identityChanged) {
                $updatedDriver = Driver::find($existingDriver->id);
                if ($updatedDriver) {
                    $updatedDriver->tokens()->delete();
                    $updatedDriver->appInstallations()->whereNull('revoked_at')->update([
                        'revoked_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 🟢 بازگشت خروجی JSON برای جاوااسکریپت (SweetAlert)
            $registrationSmsEnabled = $this->driverRegistrationSmsEnabled();
            $registrationSmsSent = false;
            if (!$isEdit && $registrationSmsEnabled) {
                $registrationSmsSent = $this->sendDriverRegistrationAppSms(
                    $sms,
                    $mobile,
                    $firstNameFa,
                    $lastNameFa,
                    $companyName
                );
            }

            $message = 'اطلاعات راننده با موفقیت در سیستم ثبت و به کارتابل شما اضافه شد.';
            if (!$isEdit && $registrationSmsEnabled) {
                $message .= $registrationSmsSent
                    ? ' لینک نصب اپلیکیشن برای راننده پیامک شد.'
                    : ' ثبت انجام شد، اما ارسال لینک نصب اپلیکیشن ناموفق بود.';
            } elseif (!$isEdit) {
                $message .= ' ارسال پیامک لینک نصب اپلیکیشن توسط مدیر غیرفعال است.';
            }

            return response()->json([
                'success' => true, 
                'message' => $message,
                'registration_sms_enabled' => $registrationSmsEnabled,
                'registration_sms_sent' => $registrationSmsSent,
            ]);

        } catch (QueryException $e) {
            report($e);

            $databaseError = strtolower($e->getMessage());
            $isMobileUniquenessViolation = str_contains($databaseError, 'drivers_mobile_unique')
                || ($e->getCode() === '23000'
                    && str_contains($databaseError, 'drivers')
                    && str_contains($databaseError, 'mobile'));

            if ($isMobileUniquenessViolation) {
                return response()->json([
                    'success' => false,
                    'message' => 'این شماره موبایل هم‌زمان برای راننده دیگری ثبت شد؛ شماره را اصلاح و دوباره تلاش کنید.',
                ], 409);
            }

            return response()->json(['success' => false, 'message' => 'خطا در ثبت اطلاعات راننده.'], 500);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'خطا در ثبت اطلاعات راننده: ' . $e->getMessage()], 500);
        }
    }

    // ۴. حذف راننده از شرکت
    public function destroy(Request $request, CompanyAssetReleaseGuard $releaseGuard)
    {
        try {
            $nationalCode = $request->national_code ?? $request->national_id;

            $driver = DB::table('drivers')->where('national_code', $nationalCode)->first();
            if (!$driver) {
                return response()->json(['success' => false, 'message' => 'راننده یافت نشد.'], 404);
            }

            $companyId = optional(auth()->user()->company)->id ?? auth()->user()->company_id ?? null;
            if (!$companyId || (int) $driver->current_company_id !== (int) $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'این راننده متعلق به شرکت شما نیست و امکان آزادسازی آن را ندارید.',
                ], 403);
            }

            $activeCount = $releaseGuard->activePermitsForDriver((int) $driver->id);

            if ($activeCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "❌ خطای سرور: این راننده دارای {$activeCount} پروانه دوزبلاغ فعال است و امکان حذف او وجود ندارد."
                ], 400);
            }

            DB::table('drivers')
                ->where('id', $driver->id)
                ->where('current_company_id', $companyId)
                ->update([
                    'current_company_id' => null,
                    'contract_status' => 'free',
                    'updated_at' => now(),
                ]);

            return response()->json(['success' => true, 'message' => 'راننده با موفقیت از کارتابل شرکت شما آزاد شد.']);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'خطایی در آزادسازی راننده رخ داد.'], 500);
        }
    }
}
