<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    // نمایش تمام رانندگان سامانه
    public function index()
    {
        // خواندن رانندگان به همراه نام شرکتی که به آن متصل هستند + تعداد دوزبلاغ فعال
        $drivers = DB::table('drivers')
            ->leftJoin('companies', 'drivers.current_company_id', '=', 'companies.id')
            ->select('drivers.*', 'companies.name_fa as company_name')
            ->addSelect([
                'active_dozbalaghs' => DB::table('permit_requests') 
                                        ->whereColumn('permit_requests.driver_id', 'drivers.id') 
                                        ->where('permit_requests.status', 'صادر شده') 
                                        ->selectRaw('count(*)')
            ])
            ->orderBy('drivers.id', 'desc')
            ->get(); 

        // لیست شرکت‌های فعال برای نمایش در منوی کشویی انتقال راننده
        $companies = DB::table('companies')->where('status', 'approved')->select('id', 'name_fa')->get();
        $driverRegistrationSmsEnabled = $this->driverRegistrationSmsEnabled();

        return view('admin.drivers.index', compact('drivers', 'companies', 'driverRegistrationSmsEnabled'));
    }

    public function toggleRegistrationSms(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        SystemSetting::setValue('driver_registration_app_sms_enabled', (bool) $validated['enabled']);

        return back()->with(
            'success',
            $validated['enabled']
                ? 'ارسال خودکار لینک نصب اپلیکیشن برای رانندگان فعال شد.'
                : 'ارسال خودکار لینک نصب اپلیکیشن برای رانندگان غیرفعال شد.'
        );
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

    // تغییر شرکتِ راننده توسط ادمین
    public function updateCompany(Request $request, $id)
    {
        $driver = DB::table('drivers')->where('id', $id)->first();
        if (!$driver) {
            return back()->with('error', 'راننده مورد نظر یافت نشد.');
        }
        if (!(bool) $driver->is_active) {
            return back()->with('error', 'راننده غیرفعال است و تا زمان فعال‌سازی امکان اتصال به شرکت ندارد.');
        }

        $companyId = $request->company_id;
        $companyChanged = (int) ($driver->current_company_id ?? 0) !== (int) ($companyId ?: 0);
        
        // اگر ادمین آیدی شرکت را روی "آزاد" بگذارد، راننده از شرکت فعلی جدا می‌شود
        DB::table('drivers')->where('id', $id)->update([
            'current_company_id' => empty($companyId) ? null : $companyId,
            'updated_at' => now()
        ]);

        if ($companyChanged) {
            $driverModel = Driver::find($id);
            if ($driverModel) {
                $driverModel->tokens()->delete();
                $driverModel->appInstallations()->whereNull('revoked_at')->update([
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'وضعیت و شرکت راننده با موفقیت بروزرسانی شد.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);
        $isActive = ! $driver->isActive();

        $driver->forceFill([
            'is_active' => $isActive,
            'updated_at' => now(),
        ])->save();

        if (! $isActive) {
            $driver->tokens()->delete();
            $driver->appInstallations()->whereNull('revoked_at')->update([
                'revoked_at' => now(),
                'revoked_by_user_id' => $request->user()->getKey(),
                'updated_at' => now(),
            ]);
        }

        return back()->with(
            'success',
            $isActive
                ? 'راننده با موفقیت فعال شد و دوباره در فرایندها قابل انتخاب است.'
                : 'راننده به‌طور کامل غیرفعال شد؛ ورود اپلیکیشن و انتخاب او در فرایندهای جدید متوقف شد.'
        );
    }

    // حذف کامل راننده از سیستم
    public function destroy($id)
    {
        // بررسی پروانه‌های فعال دوزبلاغ قبل از حذف
        $activeCount = DB::table('permit_requests')
                         ->where('driver_id', $id)
                         ->where('status', 'صادر شده')
                         ->count();

        if ($activeCount > 0) {
            return back()->with('error', "خطا: این راننده دارای پروانه فعال است. برای حذف کامل، ابتدا باید پروانه‌های او لغو شود یا اینکه او را از شرکت فعلی «آزاد» کنید.");
        }

        DB::table('drivers')->where('id', $id)->delete();
        return back()->with('success', 'راننده برای همیشه از دیتابیس سامانه حذف شد.');
    }
}
