<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\Role;
use App\Shahbaz\Models\CompanyVerificationHistory;
use App\Shahbaz\Services\CompanyEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CompanyController extends Controller
{
    public function index()
    {
        $companyStats = Company::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_association_member = 1 THEN 1 ELSE 0 END) as members')
            ->selectRaw('SUM(CASE WHEN COALESCE(is_association_member, 0) = 0 THEN 1 ELSE 0 END) as non_members')
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved")
            ->selectRaw("SUM(CASE WHEN status IN ('pending_review', 'correction_required', 'shahbaz_required', 'shahbaz_review', 'shahbaz_correction') THEN 1 ELSE 0 END) as follow_up")
            ->first();

        $companies = Company::query()
            ->when(request('q'), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name_fa', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%");
                });
            })
            ->when(request()->filled('status'), fn ($query) => $query->where('status', request('status')))
            ->when(in_array((string) request('membership'), ['0', '1'], true), function ($query) {
                request('membership') === '1'
                    ? $query->where('is_association_member', true)
                    : $query->where(function ($query) {
                        $query->where('is_association_member', false)
                            ->orWhereNull('is_association_member');
                    });
            })
            ->orderBy('name_fa')
            ->paginate(30)
            ->withQueryString();

        return view('admin.companies.list', compact('companies', 'companyStats'));
    }

    public function create()
    {
        return view('admin.companies.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'national_id' => preg_replace('/\D+/', '', (string) $request->national_id),
            'ceo_national_code' => preg_replace('/\D+/', '', (string) $request->ceo_national_code),
            'ceo_mobile' => preg_replace('/\D+/', '', (string) $request->ceo_mobile),
            'postal_code' => preg_replace('/\D+/', '', (string) $request->postal_code),
        ]);

        $data = $request->validate([
            'national_id' => ['required', 'digits:11', Rule::unique('companies', 'national_id'), Rule::unique('users', 'username')],
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255', 'regex:/[A-Za-z]/'],
            'registration_number' => ['required', 'string', 'max:100'],
            'ceo_name' => ['required', 'string', 'max:255'],
            'ceo_national_code' => ['required', 'digits:10'],
            'ceo_mobile' => ['required', 'regex:/^09[0-9]{9}$/'],
            'phone' => ['required', 'string', 'max:30'],
            'postal_code' => ['required', 'digits:10'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'activity_type' => ['required', 'string', 'max:255'],
            'address_fa' => ['required', 'string', 'max:2000'],
            'address_en' => ['required', 'string', 'max:2000', 'regex:/[A-Za-z]/'],
            'is_association_member' => ['required', 'boolean'],
        ], [
            'national_id.unique' => 'این شناسه ملی قبلاً برای یک شرکت یا حساب کاربری ثبت شده است.',
            'national_id.digits' => 'شناسه ملی شرکت باید دقیقاً ۱۱ رقم باشد.',
            'ceo_national_code.digits' => 'کد ملی مدیرعامل باید دقیقاً ۱۰ رقم باشد.',
            'ceo_mobile.regex' => 'موبایل مدیرعامل باید ۱۱ رقم و با 09 شروع شود.',
            'postal_code.digits' => 'کدپستی باید دقیقاً ۱۰ رقم باشد.',
            'name_en.regex' => 'نام لاتین باید با حروف انگلیسی وارد شود.',
            'address_en.regex' => 'آدرس لاتین باید با حروف انگلیسی وارد شود.',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $companyRole = Role::where('name', 'company')->firstOrFail();

                $user = User::create([
                    'role_id' => $companyRole->id,
                    'username' => $data['national_id'],
                    'mobile' => $data['ceo_mobile'],
                    'password' => Hash::make('12345678'),
                    'status' => 'active',
                    'is_manual' => true,
                ]);

                $company = new Company();
                $company->forceFill([
                    'user_id' => $user->id,
                    'company_code' => ($data['is_association_member'] ? 'C-M-' : 'C-N-').$data['national_id'],
                    'name' => trim($data['name']),
                    'name_fa' => trim($data['name']),
                    'name_en' => trim($data['name_en']),
                    'national_id' => $data['national_id'],
                    'registration_number' => trim($data['registration_number']),
                    'ceo_name' => trim($data['ceo_name']),
                    'ceo_national_code' => $data['ceo_national_code'],
                    'ceo_mobile' => $data['ceo_mobile'],
                    'phone' => trim($data['phone']),
                    'postal_code' => $data['postal_code'],
                    'province' => trim($data['province']),
                    'city' => trim($data['city']),
                    'activity_type' => trim($data['activity_type']),
                    'address_fa' => trim($data['address_fa']),
                    'address_en' => trim($data['address_en']),
                    'is_association_member' => (bool) $data['is_association_member'],
                    'status' => 'profile_incomplete',
                ])->save();
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages([
                    'national_id' => 'این شناسه ملی قبلاً ثبت شده و امکان ساخت حساب دوم وجود ندارد.',
                ]);
            }

            throw $exception;
        }

        return back()->with('success', 'شرکت ثبت شد. نام کاربری حساب شرکت همان شناسه ملی است.');
    }

    public function edit($id)
    {
        $company = Company::findOrFail($id);
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $request->merge([
            'ceo_national_code' => preg_replace('/\D+/', '', (string) $request->ceo_national_code),
            'ceo_mobile' => preg_replace('/\D+/', '', (string) $request->ceo_mobile),
            'postal_code' => preg_replace('/\D+/', '', (string) $request->postal_code),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255', 'regex:/[A-Za-z]/'],
            'registration_number' => ['required', 'string', 'max:100'],
            'ceo_name' => ['required', 'string', 'max:255'],
            'ceo_national_code' => ['required', 'digits:10'],
            'ceo_mobile' => ['required', 'regex:/^09[0-9]{9}$/'],
            'phone' => ['required', 'string', 'max:30'],
            'postal_code' => ['required', 'digits:10'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'activity_type' => ['required', 'string', 'max:255'],
            'address_fa' => ['required', 'string', 'max:2000'],
            'address_en' => ['required', 'string', 'max:2000', 'regex:/[A-Za-z]/'],
            'is_association_member' => ['required', 'boolean'],
        ], [
            'ceo_national_code.digits' => 'کد ملی مدیرعامل باید دقیقاً ۱۰ رقم باشد.',
            'ceo_mobile.regex' => 'موبایل مدیرعامل باید ۱۱ رقم و با 09 شروع شود.',
            'postal_code.digits' => 'کدپستی باید دقیقاً ۱۰ رقم باشد.',
            'name_en.regex' => 'نام لاتین باید با حروف انگلیسی وارد شود.',
            'address_en.regex' => 'آدرس لاتین باید با حروف انگلیسی وارد شود.',
        ]);

        if ($company->user && User::query()
            ->where('username', $company->national_id)
            ->whereKeyNot($company->user_id)
            ->exists()) {
            throw ValidationException::withMessages([
                'national_id' => 'این شناسه ملی قبلاً به حساب کاربری دیگری متصل شده است.',
            ]);
        }

        try {
            DB::transaction(function () use ($company, $data): void {
                $company->forceFill([
                    'name' => trim($data['name']),
                    'name_fa' => trim($data['name']),
                    'name_en' => trim($data['name_en']),
                    'registration_number' => trim($data['registration_number']),
                    'ceo_name' => trim($data['ceo_name']),
                    'ceo_national_code' => $data['ceo_national_code'],
                    'ceo_mobile' => $data['ceo_mobile'],
                    'phone' => trim($data['phone']),
                    'postal_code' => $data['postal_code'],
                    'province' => trim($data['province']),
                    'city' => trim($data['city']),
                    'activity_type' => trim($data['activity_type']),
                    'address_fa' => trim($data['address_fa']),
                    'address_en' => trim($data['address_en']),
                    'is_association_member' => (bool) $data['is_association_member'],
                ])->save();

                $company->user?->update([
                    'username' => $company->national_id,
                    'mobile' => $data['ceo_mobile'],
                ]);
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages([
                    'national_id' => 'این شناسه ملی قبلاً ثبت شده و امکان اتصال حساب دوم وجود ندارد.',
                ]);
            }

            throw $exception;
        }

        return redirect()->route('admin.companies.index')->with('success', 'تمام اطلاعات شرکت و حساب کاربری به‌روزرسانی شد.');
    }

    public function approve($id, CompanyEligibilityService $eligibility)
    {
        $company = Company::findOrFail($id);
        abort_unless($company->status === 'pending_review', 403);
        $from = $company->shahbaz_verification_status;
        $remainingSections = collect($eligibility->incompleteRequiredSections($company))
            ->reject(fn (array $section) => $section['key'] === 'profile');
        $profileOnly = $remainingSections->isEmpty();
        $company->forceFill([
            'status' => $profileOnly ? 'approved' : 'shahbaz_correction',
            'profile_review_note' => null,
            'profile_reviewed_by_user_id' => auth()->id(),
            'profile_reviewed_at' => now(),
            'shahbaz_verification_status' => $profileOnly ? 'verified' : 'correction_required',
            'shahbaz_review_note' => $profileOnly ? null : 'مرحله بعدی الزامی: '.$remainingSections->pluck('label')->implode('، '),
            'shahbaz_verified_by_user_id' => $profileOnly ? auth()->id() : null,
            'shahbaz_verified_at' => $profileOnly ? now() : null,
        ])->save();
        CompanyVerificationHistory::create([
            'company_id' => $company->id,
            'from_status' => $from,
            'to_status' => $profileOnly ? 'verified' : 'correction_required',
            'result' => 'profile_approved',
            'description' => $profileOnly
                ? 'مرحله مشخصات پایه شرکت تأیید و دسترسی پنل باز شد.'
                : 'مشخصات پایه تأیید شد؛ شرکت باید مرحله بعدی را تکمیل کند: '.$remainingSections->pluck('label')->implode('، '),
            'company_snapshot' => $company->fresh()->toArray(),
            'changed_by_user_id' => auth()->id(),
            'checked_at' => now(),
        ]);
        return back()->with('success', $profileOnly
            ? 'مشخصات پایه شرکت تأیید شد و دسترسی پنل شرکت باز شد. با فعال‌شدن هر مرحله جدید شحباز، فقط همان مرحله باید تکمیل و تأیید شود.'
            : 'مشخصات پایه تأیید شد. اکنون فقط مرحله بعدی شحباز برای شرکت باز است: '.$remainingSections->pluck('label')->implode('، '));
    }

    public function review($id)
    {
        $company = Company::findOrFail($id);

        return view('admin.companies.review', compact('company'));
    }

    public function requestCorrection(Request $request, $id)
    {
        $data = $request->validate([
            'profile_review_note' => ['required', 'string', 'max:2000'],
        ]);

        $company = Company::findOrFail($id);
        $company->forceFill([
            'status' => 'correction_required',
            'profile_review_note' => $data['profile_review_note'],
            'profile_reviewed_by_user_id' => auth()->id(),
            'profile_reviewed_at' => now(),
        ])->save();

        return redirect()->route('admin.companies.index')->with('success', 'موارد اصلاحی برای شرکت ثبت شد.');
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
