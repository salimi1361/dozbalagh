<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::all();
        return view('admin.countries.index', compact('countries'));
    }

    public function store(Request $request)
    {
        // اعتبارسنجی دقیق برای بررسی تک‌تک مجوزهای ارسالی و فیلد روز اعتبار
        $request->validate([
            'name'           => 'required|string|max:255|unique:countries,name',
            'code'           => 'nullable|string|max:10',
            'member_price'     => 'required|numeric|min:0',
            'non_member_price' => 'required|numeric|min:0',
            'validity_days'  => 'required|integer|min:0',
            'permit_types'   => 'required|array|min:1',
            'permit_types.*' => 'in:bilateral,transit,bilateral_transit,third_country_transit,third_country',
            'requestable_permit_types' => 'nullable|array',
            'requestable_permit_types.*' => 'in:bilateral,transit,bilateral_transit,third_country_transit,third_country',
        ], [
            'permit_types.required' => 'انتخاب حداقل یک نوع مجوز الزامی است.',
            'permit_types.*.in'     => 'نوع مجوز انتخاب شده در سیستم معتبر نیست.',
        ]);

        $definedPermitTypes = array_values($request->permit_types);
        $requestablePermitTypes = array_values(array_intersect(
            $definedPermitTypes,
            $request->input('requestable_permit_types', $definedPermitTypes),
        ));

        Country::create([
            'name'                 => trim($request->name),
            'code'                 => strtoupper(trim($request->code)),
            // price برای سازگاری با بخش‌های قدیمی تا زمان اتصال تعرفه عضویت نگه داشته می‌شود.
            'price'                => $request->non_member_price,
            'member_price'         => $request->member_price,
            'non_member_price'     => $request->non_member_price,
            'validity_days'        => $request->validity_days, // 👈 ذخیره در دیتابیس
            'allowed_permit_types' => $definedPermitTypes,
            'requestable_permit_types' => $requestablePermitTypes,
            'is_active'            => true,
        ]);

        return back()->with('success', 'کشور و مجوزهای مجاز با موفقیت ثبت شدند.');
    }

    public function update(Request $request, $id)
    {
        // اعتبارسنجی دقیق برای متد ویرایش
        $request->validate([
            'name'           => 'required|string|max:255|unique:countries,name,' . $id,
            'code'           => 'nullable|string|max:10',
            'member_price'     => 'required|numeric|min:0',
            'non_member_price' => 'required|numeric|min:0',
            'validity_days'  => 'required|integer|min:0',
            'permit_types'   => 'required|array|min:1',
            'permit_types.*' => 'in:bilateral,transit,bilateral_transit,third_country_transit,third_country',
            'requestable_permit_types' => 'nullable|array',
            'requestable_permit_types.*' => 'in:bilateral,transit,bilateral_transit,third_country_transit,third_country',
        ], [
            'permit_types.required' => 'انتخاب حداقل یک نوع مجوز الزامی است.',
            'permit_types.*.in'     => 'نوع مجوز انتخاب شده در سیستم معتبر نیست.',
        ]);

        $country = Country::findOrFail($id);

        $definedPermitTypes = array_values($request->permit_types);
        $requestedTypes = $request->has('requestable_permit_types_present')
            ? (array) $request->input('requestable_permit_types', [])
            : $country->requestablePermitTypes();
        $requestablePermitTypes = array_values(array_intersect($definedPermitTypes, $requestedTypes));
        
        $country->update([
            'name'                 => trim($request->name),
            'code'                 => strtoupper(trim($request->code)),
            'member_price'         => $request->member_price,
            'non_member_price'     => $request->non_member_price,
            'validity_days'        => $request->validity_days, // 👈 آپدیت در دیتابیس
            'allowed_permit_types' => $definedPermitTypes,
            'requestable_permit_types' => $requestablePermitTypes,
        ]);

        return back()->with('success', 'اطلاعات کشور و مجوزها بروزرسانی شد.');
    }

    public function toggleStatus(Country $country)
    {
        $country->update(['is_active' => ! $country->is_active]);
        $status = $country->is_active ? 'فعال' : 'غیرفعال';

        return back()->with(
            'success',
            "کشور «{$country->name}» اکنون {$status} است.",
        );
    }

    public function destroy($id)
    {
        $country = Country::findOrFail($id);
        $country->delete();
        return back()->with('success', 'کشور با موفقیت حذف شد.');
    }
}
