<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Company; // 👈 نام مدل شرکت خودت
use App\Models\CompanyQuota;
use Illuminate\Http\Request;

class AllocationController extends Controller
{
    // ۱. نمایش صفحه مدیریت سهمیه‌ها
    public function index()
    {
        $countries = Country::where('is_active', true)->get();
        $companies = Company::all(); 
        $quotas = CompanyQuota::with(['company', 'country'])->get();
        
        return view('admin.allocation.index', compact('countries', 'companies', 'quotas'));
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