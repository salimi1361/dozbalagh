@extends('layouts.app')

@section('header_title', 'تکمیل و تأیید مشخصات شرکت')

@section('content')
@php
    $statusLabels = [
        'pending' => 'اطلاعات تکمیل نشده',
        'profile_incomplete' => 'اطلاعات تکمیل نشده',
        'pending_review' => 'در انتظار بررسی انجمن',
        'shahbaz_required' => 'در حال تکمیل پرونده شحباز',
        'shahbaz_review' => 'در انتظار تأیید نهایی شحباز',
        'shahbaz_correction' => 'پرونده شحباز نیازمند اصلاح',
        'correction_required' => 'نیازمند اصلاح',
        'approved' => 'تأییدشده',
    ];
    $editable = in_array($company->status, ['pending', 'profile_incomplete', 'correction_required'], true);
@endphp

<div class="mx-auto max-w-5xl space-y-5 text-right" dir="rtl">
    <section class="company-page-header rounded-2xl border border-slate-200 p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-black text-slate-800">برگه اطلاعات شرکت</h1>
                <p class="mt-1 text-sm text-slate-500">اطلاعات را دقیق کنترل و تکمیل کنید. پس از ارسال، انجمن آن را بررسی می‌کند.</p>
            </div>
            <span class="rounded-full px-4 py-2 text-xs font-black {{ $company->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($company->status === 'correction_required' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800') }}">
                {{ $statusLabels[$company->status] ?? $company->status }}
            </span>
        </div>

        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-800">
            تذکر: اطلاعات درج‌شده باید دقیق و صحیح باشد و مسئولیت صحت آن بر عهده شرکت ثبت‌کننده است.
        </div>
        @if(session('success'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
        @endif
        @if($company->profile_review_note)
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                <b>توضیح انجمن:</b> {{ $company->profile_review_note }}
            </div>
        @endif
        @if($errors->any())
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <ul class="list-disc space-y-1 pr-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
    </section>

    <form action="{{ route('company.profile.update') }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        @method('PUT')
        <fieldset @disabled(!$editable) class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <label class="text-sm font-bold text-slate-700">شناسه ملی شرکت
                <input value="{{ $company->national_id }}" disabled dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-left font-mono text-slate-500">
            </label>
            <label class="text-sm font-bold text-slate-700">شماره ثبت <span class="text-rose-500">*</span>
                <input name="registration_number" value="{{ old('registration_number', $company->registration_number) }}" required dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left">
            </label>
            <label class="text-sm font-bold text-slate-700">نام رسمی فارسی <span class="text-rose-500">*</span>
                <input name="name_fa" value="{{ old('name_fa', $company->name_fa ?? $company->name) }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5">
            </label>
            <label class="text-sm font-bold text-slate-700">نام لاتین شرکت <span class="text-rose-500">*</span>
                <input name="name_en" value="{{ old('name_en', $company->name_en) }}" required dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left">
            </label>
            <label class="text-sm font-bold text-slate-700">نام مدیرعامل <span class="text-rose-500">*</span>
                <input name="ceo_name" value="{{ old('ceo_name', $company->ceo_name) }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5">
            </label>
            <label class="text-sm font-bold text-slate-700">کد ملی مدیرعامل <span class="text-rose-500">*</span>
                <input name="ceo_national_code" value="{{ old('ceo_national_code', $company->ceo_national_code) }}" required maxlength="10" inputmode="numeric" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono">
            </label>
            <label class="text-sm font-bold text-slate-700">موبایل مدیرعامل <span class="text-rose-500">*</span>
                <input name="ceo_mobile" value="{{ old('ceo_mobile', $company->ceo_mobile) }}" required maxlength="11" inputmode="numeric" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono">
            </label>
            <label class="text-sm font-bold text-slate-700">تلفن شرکت <span class="text-rose-500">*</span>
                <input name="phone" value="{{ old('phone', $company->phone === '000' ? '' : $company->phone) }}" required dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono">
            </label>
            <label class="text-sm font-bold text-slate-700">استان <span class="text-rose-500">*</span>
                <input name="province" value="{{ old('province', $company->province) }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5">
            </label>
            <label class="text-sm font-bold text-slate-700">شهر <span class="text-rose-500">*</span>
                <input name="city" value="{{ old('city', $company->city) }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5">
            </label>
            <label class="text-sm font-bold text-slate-700">کدپستی <span class="text-rose-500">*</span>
                <input name="postal_code" value="{{ old('postal_code', $company->postal_code) }}" required maxlength="10" inputmode="numeric" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono">
            </label>
            <label class="text-sm font-bold text-slate-700">نوع فعالیت <span class="text-rose-500">*</span>
                <input name="activity_type" value="{{ old('activity_type', $company->activity_type) }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="حمل‌ونقل بین‌المللی کالا">
            </label>
            <label class="text-sm font-bold text-slate-700 md:col-span-2">آدرس فارسی <span class="text-rose-500">*</span>
                <textarea name="address_fa" required rows="3" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5">{{ old('address_fa', $company->address_fa === 'ثبت نشده' ? '' : $company->address_fa) }}</textarea>
            </label>
            <label class="text-sm font-bold text-slate-700 md:col-span-2">آدرس لاتین <span class="text-rose-500">*</span>
                <textarea name="address_en" required rows="3" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left">{{ old('address_en', $company->address_en === 'N/A' ? '' : $company->address_en) }}</textarea>
            </label>
        </fieldset>

        <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
            @if($editable)
                <button class="rounded-xl bg-blue-600 px-7 py-3 font-black text-white hover:bg-blue-700">ارسال اطلاعات برای بررسی انجمن</button>
                <p class="text-xs text-slate-500">پس از ارسال، فرم تا اعلام نتیجه انجمن قفل می‌شود.</p>
            @elseif($company->status === 'pending_review')
                <span class="rounded-xl bg-amber-50 px-5 py-3 text-sm font-bold text-amber-800">اطلاعات ارسال شده و در صف بررسی انجمن است.</span>
            @else
                <a href="{{ route('dashboard') }}" class="rounded-xl bg-emerald-600 px-6 py-3 font-black text-white">ورود به سامانه</a>
            @endif
        </div>
    </form>

    <div class="flex justify-start">
        <a href="{{ route('company.password.edit') }}#change-password" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-700">
            <span>🔐</span> تنظیمات امنیتی و تغییر رمز عبور
        </a>
    </div>
</div>
@endsection
