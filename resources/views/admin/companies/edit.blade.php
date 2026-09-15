@extends('layouts.admin')

@section('header_title', 'ویرایش اطلاعات شرکت')
@section('hero_subtitle', 'اصلاح اطلاعات هویتی، عضویت، مدیرعامل و نشانی شرکت')

@section('content')
<div class="mx-auto max-w-5xl space-y-5 p-4 md:p-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-xl font-black text-slate-800">ویرایش اطلاعات شرکت</h1><p class="mt-1 text-sm text-slate-500">ساختار این فرم با ثبت دستی و برگه اطلاعات شرکت یکسان است.</p></div>
        <a href="{{ route('admin.companies.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-600">بازگشت به فهرست</a>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc space-y-1 pr-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('admin.companies.update', $company->id) }}" method="POST" class="space-y-5">
        @csrf @method('PUT')

        <section class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <h2 class="font-black text-blue-900">هویت و عضویت شرکت</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="text-sm font-bold text-slate-700">شناسه ملی / نام کاربری
                    <input value="{{ $company->national_id }}" disabled dir="ltr" class="mt-1.5 w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-left font-mono text-slate-500">
                    <span class="mt-1 block text-xs font-normal text-slate-500">برای جلوگیری از ایجاد حساب تکراری قابل تغییر نیست.</span>
                </label>
                <label class="text-sm font-bold text-slate-700">وضعیت عضویت <span class="text-rose-500">*</span>
                    <select name="is_association_member" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                        <option value="1" @selected((string) old('is_association_member', (int) $company->is_association_member) === '1')>عضو انجمن</option>
                        <option value="0" @selected((string) old('is_association_member', (int) $company->is_association_member) === '0')>غیرعضو</option>
                    </select>
                </label>
                <label class="text-sm font-bold text-slate-700">نام رسمی فارسی <span class="text-rose-500">*</span><input name="name" value="{{ old('name', $company->name_fa ?? $company->name) }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"></label>
                <label class="text-sm font-bold text-slate-700">نام لاتین <span class="text-rose-500">*</span><input name="name_en" value="{{ old('name_en', $company->name_en) }}" required dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-left"></label>
                <label class="text-sm font-bold text-slate-700">شماره ثبت <span class="text-rose-500">*</span><input name="registration_number" value="{{ old('registration_number', $company->registration_number) }}" required dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-left"></label>
                <label class="text-sm font-bold text-slate-700">نوع فعالیت <span class="text-rose-500">*</span><input name="activity_type" value="{{ old('activity_type', $company->activity_type) }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5" placeholder="حمل‌ونقل بین‌المللی کالا"></label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-black text-slate-800">مدیرعامل و اطلاعات تماس</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="text-sm font-bold text-slate-700">نام مدیرعامل <span class="text-rose-500">*</span><input name="ceo_name" value="{{ old('ceo_name', $company->ceo_name) }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
                <label class="text-sm font-bold text-slate-700">کد ملی مدیرعامل <span class="text-rose-500">*</span><input name="ceo_national_code" value="{{ old('ceo_national_code', $company->ceo_national_code) }}" required maxlength="10" inputmode="numeric" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono"></label>
                <label class="text-sm font-bold text-slate-700">موبایل مدیرعامل <span class="text-rose-500">*</span><input name="ceo_mobile" value="{{ old('ceo_mobile', $company->ceo_mobile) }}" required maxlength="11" inputmode="numeric" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono"></label>
                <label class="text-sm font-bold text-slate-700">تلفن شرکت <span class="text-rose-500">*</span><input name="phone" value="{{ old('phone', $company->phone === '000' ? '' : $company->phone) }}" required dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono"></label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-black text-slate-800">نشانی شرکت</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <label class="text-sm font-bold text-slate-700">استان <span class="text-rose-500">*</span><input name="province" value="{{ old('province', $company->province) }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
                <label class="text-sm font-bold text-slate-700">شهر <span class="text-rose-500">*</span><input name="city" value="{{ old('city', $company->city) }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
                <label class="text-sm font-bold text-slate-700">کدپستی <span class="text-rose-500">*</span><input name="postal_code" value="{{ old('postal_code', $company->postal_code) }}" required maxlength="10" inputmode="numeric" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono"></label>
                <label class="text-sm font-bold text-slate-700 md:col-span-3">آدرس فارسی <span class="text-rose-500">*</span><textarea name="address_fa" required rows="3" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5">{{ old('address_fa', $company->address_fa === 'ثبت نشده' ? '' : $company->address_fa) }}</textarea></label>
                <label class="text-sm font-bold text-slate-700 md:col-span-3">آدرس لاتین <span class="text-rose-500">*</span><textarea name="address_en" required rows="3" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left">{{ old('address_en', $company->address_en === 'N/A' ? '' : $company->address_en) }}</textarea></label>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <a href="{{ route('admin.companies.index') }}" class="rounded-lg bg-slate-200 px-5 py-2.5 font-bold text-slate-700">انصراف</a>
            <button class="rounded-lg bg-indigo-600 px-7 py-2.5 font-black text-white hover:bg-indigo-700">ذخیره تمام اطلاعات</button>
        </div>
    </form>
</div>
@endsection
