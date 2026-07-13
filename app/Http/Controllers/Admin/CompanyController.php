<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::orderBy('id', 'desc')->get();
        return view('admin.companies.list', compact('companies'));
    }

    public function create()
    {
        return view('admin.companies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'national_id' => 'required|unique:companies,national_id',
            'name' => 'required',
            'ceo_mobile' => 'required',
        ]);

        if (User::where('username', $request->national_id)->exists()) {
            return back()
                ->withInput()
                ->with('error', 'این شناسه ملی قبلاً به یک حساب کاربری متصل شده است.');
        }

        DB::transaction(function () use ($request) {
            $companyRole = Role::where('name', 'company')->firstOrFail();

            $user = User::create([
                'role_id' => $companyRole->id,
                'username' => $request->national_id,
                'mobile' => $request->ceo_mobile,
                'password' => Hash::make('12345678'),
                'status' => 'active',
                'is_manual' => true,
            ]);

            $company = new Company();
            $company->user_id = $user->id;
            $company->company_code = 'C-' . time();
            $company->name = $request->name;
            $company->name_fa = $request->name;
            $company->name_en = $request->name_en ?? $request->name;
            $company->national_id = $request->national_id;
            $company->phone = $request->phone ?? '000';
            $company->ceo_mobile = $request->ceo_mobile;
            $company->address_fa = $request->address ?? 'ثبت نشده';
            $company->address_en = $request->address_en ?? 'N/A';
            $company->status = 'pending';
            $company->save();
        });

        return back()->with('success', 'شرکت با موفقیت ثبت شد.');
    }

    public function edit($id)
    {
        $company = Company::findOrFail($id);
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'national_id' => 'required|unique:companies,national_id,' . $id,
            'name' => 'required',
            'ceo_mobile' => 'required',
        ]);

        $company = Company::findOrFail($id);
        
        $company->name = $request->name;
        $company->name_fa = $request->name; 
        $company->national_id = $request->national_id;
        $company->phone = $request->phone ?? '000';
        $company->ceo_mobile = $request->ceo_mobile; 
        $company->address_fa = $request->address ?? 'ثبت نشده';
        $company->save();

        if ($company->user) {
            $company->user->update([
                'username' => $request->national_id,
                'mobile' => $request->ceo_mobile,
            ]);
        }

        return redirect()->route('admin.companies.index')->with('success', 'اطلاعات شرکت بروزرسانی شد.');
    }

    public function approve($id)
    {
        $company = Company::findOrFail($id);
        $company->status = 'approved';
        $company->save();
        return back()->with('success', 'وضعیت شرکت به «تایید شده» تغییر یافت.');
    }

    public function resetPassword($id)
    {
        $company = Company::findOrFail($id);
        if ($company->user) {
            $company->user->update(['password' => Hash::make('12345678')]);
            return back()->with('success', 'رمز عبور به 12345678 بازنشانی شد.');
        }
        return back()->with('error', 'کاربری یافت نشد.');
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);

        // روش امن برای بررسی دیتابیس بدون نیاز به فایلِ مدل
        if (\Illuminate\Support\Facades\Schema::hasTable('dozbalaghs')) {
            $hasDozbalagh = \Illuminate\Support\Facades\DB::table('dozbalaghs')
                ->where('company_id', $company->id)
                ->exists();

            if ($hasDozbalagh) {
                return back()->with('error', 'امکان حذف وجود ندارد؛ این شرکت دارای سوابق دوزبلاغ است.');
            }
        }

        if ($company->user) {
            $company->user->delete();
        }
        
        $company->delete();
        
        return back()->with('success', 'شرکت و حساب کاربری با موفقیت حذف شدند.');
    }
}
