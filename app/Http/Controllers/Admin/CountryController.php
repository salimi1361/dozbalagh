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
            'price'          => 'required|numeric|min:0',
            'validity_days'  => 'required|integer|min:0',
            'permit_types'   => 'required|array|min:1',
            'permit_types.*' => 'in:bilateral,transit,bilateral_transit,third_country_transit,third_country',
        ], [
            'permit_types.required' => 'انتخاب حداقل یک نوع مجوز الزامی است.',
            'permit_types.*.in'     => 'نوع مجوز انتخاب شده در سیستم معتبر نیست.',
        ]);

        Country::create([
            'name'                 => trim($request->name),
            'code'                 => strtoupper(trim($request->code)),
            'price'                => $request->price,
            'validity_days'        => $request->validity_days, // 👈 ذخیره در دیتابیس
            'allowed_permit_types' => $request->permit_types,
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
            'price'          => 'required|numeric|min:0',
            'validity_days'  => 'required|integer|min:0',
            'permit_types'   => 'required|array|min:1',
            'permit_types.*' => 'in:bilateral,transit,bilateral_transit,third_country_transit,third_country',
        ], [
            'permit_types.required' => 'انتخاب حداقل یک نوع مجوز الزامی است.',
            'permit_types.*.in'     => 'نوع مجوز انتخاب شده در سیستم معتبر نیست.',
        ]);

        $country = Country::findOrFail($id);
        
        $country->update([
            'name'                 => trim($request->name),
            'code'                 => strtoupper(trim($request->code)),
            'price'                => $request->price,
            'validity_days'        => $request->validity_days, // 👈 آپدیت در دیتابیس
            'allowed_permit_types' => $request->permit_types,
        ]);

        return back()->with('success', 'اطلاعات کشور و مجوزها بروزرسانی شد.');
    }

    public function destroy($id)
    {
        $country = Country::findOrFail($id);
        $country->delete();
        return back()->with('success', 'کشور با موفقیت حذف شد.');
    }
}
