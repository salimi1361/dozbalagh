<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Company; // 👈 نام مدل شرکت خودت
use App\Models\CompanyQuota;
use App\Models\SystemSetting;
use App\Services\PermitRequestWindowService;
use Illuminate\Http\Request;

class AllocationController extends Controller
{
    // ۱. نمایش صفحه مدیریت سهمیه‌ها
    public function index()
    {
        $countries = Country::where('is_active', true)->get();
        $companies = Company::all(); 
        $quotas = CompanyQuota::with(['company', 'country'])->get();
        $requestWindowSettings = app(PermitRequestWindowService::class)->settings();
        $weekdays = PermitRequestWindowService::WEEKDAYS;
        
        return view('admin.allocation.index', compact('countries', 'companies', 'quotas', 'requestWindowSettings', 'weekdays'));
    }

    public function updateRequestWindow(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'closed_weekdays' => 'nullable|array',
            'closed_weekdays.*' => 'integer|between:0,6',
            'closed_message' => 'nullable|string|max:255',
        ]);

        SystemSetting::setMany([
            'permit_request_window_enabled' => $request->boolean('enabled') ? '1' : '0',
            'permit_request_start_time' => $validated['start_time'],
            'permit_request_end_time' => $validated['end_time'],
            'permit_request_closed_weekdays' => array_values($validated['closed_weekdays'] ?? []),
            'permit_request_closed_message' => $validated['closed_message'] ?: 'ثبت درخواست فقط در بازه زمانی مجاز انجمن امکان‌پذیر است.',
        ]);

        return back()->with('success', 'تنظیمات زمان مجاز ثبت درخواست با موفقیت ذخیره شد.');
    }

    // ۲. ثبت قوانین و سقف‌ها (همراه با پیام مدیر)
    public function updateQuota(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'max_limit' => 'nullable|integer|min:0', 
            'reject_message' => 'nullable|string|max:255', // پیام دلخواه مدیر
        ]);

        try {
            if (empty($request->company_id)) {
                // قانون عمومی برای همه شرکت‌ها
                Country::where('id', $request->country_id)->update([
                    'default_quota' => $request->max_limit,
                    'reject_message' => $request->reject_message
                ]);
                $msg = 'قانون کلی سهمیه این کشور با موفقیت آپدیت شد.';
            } else {
                // قانون استثنا برای یک شرکت خاص
                CompanyQuota::updateOrCreate(
                    ['company_id' => $request->company_id, 'country_id' => $request->country_id],
                    [
                        'custom_limit' => $request->max_limit,
                        'reject_message' => $request->reject_message
                    ]
                );
                $msg = 'محدودیت اختصاصی برای این شرکت با موفقیت ثبت شد.';
            }

            return back()->with('success', $msg);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'خطا در ثبت محدودیت: ' . $e->getMessage()]);
        }
    }
	// ۳. حذف استثنای شرکت (بازگشت به قانون پیش‌فرض)
    public function destroyQuota($id)
    {
        try {
            $quota = CompanyQuota::findOrFail($id);
            $quota->delete();
            
            return back()->with('success', 'استثنای این شرکت حذف شد و از این پس مجدداً از قانون کلی کشور پیروی می‌کند.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'خطا در حذف قانون: ' . $e->getMessage()]);
        }
    }
	// ۴. حذف قانون کلی کشور (بازگشت به حالت نامحدود)
    public function resetCountryRule($id)
    {
        try {
            $country = Country::findOrFail($id);
            $country->update([
                'default_quota' => null,
                'reject_message' => null
            ]);
            
            // 👈 علامت‌های " از دور کلمه نامحدود حذف شد تا گرافیک به هم نریزد
            return back()->with('success', 'قانون کلی این کشور حذف شد و وضعیت مجدداً به حالت نامحدود برگشت.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'خطا در حذف قانون کشور: ' . $e->getMessage()]);
        }
    }
}
