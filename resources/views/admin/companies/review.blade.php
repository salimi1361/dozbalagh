@extends('layouts.admin')

@section('header_title', 'بررسی اطلاعات شرکت')
@section('hero_subtitle', 'کنترل اطلاعات ارسال‌شده و ثبت نتیجه بررسی پرونده شرکت')

@section('content')
@php
    $fields = [
        'نام رسمی فارسی' => $company->name_fa,
        'نام لاتین' => $company->name_en,
        'شناسه ملی' => $company->national_id,
        'شماره ثبت' => $company->registration_number,
        'نام مدیرعامل' => $company->ceo_name,
        'کد ملی مدیرعامل' => $company->ceo_national_code,
        'موبایل مدیرعامل' => $company->ceo_mobile,
        'تلفن شرکت' => $company->phone,
        'استان' => $company->province,
        'شهر' => $company->city,
        'کدپستی' => $company->postal_code,
        'نوع فعالیت' => $company->activity_type,
    ];
@endphp
<div class="mx-auto max-w-5xl space-y-5 p-4 md:p-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-xl font-black text-slate-800">بررسی اطلاعات شرکت</h1><p class="mt-1 text-sm text-slate-500">اطلاعات ارسال‌شده را کنترل و نتیجه را ثبت کنید.</p></div>
        <a href="{{ route('admin.companies.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-600">بازگشت به شرکت‌ها</a>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach($fields as $label => $value)
                <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs font-bold text-slate-500">{{ $label }}</div><div class="mt-1 break-words font-bold text-slate-800">{{ filled($value) ? $value : '—' }}</div></div>
            @endforeach
            <div class="rounded-xl bg-slate-50 p-3 md:col-span-2 xl:col-span-3"><div class="text-xs font-bold text-slate-500">آدرس فارسی</div><div class="mt-1 text-sm text-slate-800">{{ $company->address_fa ?: '—' }}</div></div>
            <div class="rounded-xl bg-slate-50 p-3 md:col-span-2 xl:col-span-3"><div class="text-xs font-bold text-slate-500">آدرس لاتین</div><div dir="ltr" class="mt-1 text-left text-sm text-slate-800">{{ $company->address_en ?: '—' }}</div></div>
        </div>
        @if($company->profile_submitted_at)<p class="mt-4 text-xs text-slate-500">ارسال شرکت: {{ verta($company->profile_submitted_at)->format('Y/m/d H:i') }}</p>@endif
    </section>

    @if($company->status === 'pending_review')
        <section class="grid gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('admin.companies.approve', $company->id) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">@csrf @method('PUT')
                <h2 class="font-black text-emerald-800">تأیید این مرحله و بازکردن پنل</h2><p class="mt-2 text-sm text-emerald-700">با تأیید مشخصات پایه، پنل شرکت باز می‌شود. هر مرحله جدید شحباز بعداً به‌صورت مستقل فعال و بررسی خواهد شد.</p>
                <button class="mt-4 rounded-xl bg-emerald-600 px-6 py-3 font-black text-white">تأیید مشخصات و بازکردن پنل</button>
            </form>
            <form method="POST" action="{{ route('admin.companies.request-correction', $company->id) }}" class="rounded-2xl border border-rose-200 bg-rose-50 p-5">@csrf @method('PUT')
                <h2 class="font-black text-rose-800">بازگشت برای اصلاح</h2>
                <textarea name="profile_review_note" required maxlength="2000" rows="3" class="mt-3 w-full rounded-lg border border-rose-200 bg-white p-3" placeholder="مواردی که شرکت باید اصلاح کند...">{{ old('profile_review_note') }}</textarea>
                @error('profile_review_note')<p class="mt-1 text-xs font-bold text-rose-700">{{ $message }}</p>@enderror
                <button class="mt-3 rounded-xl bg-rose-600 px-6 py-3 font-black text-white">ثبت و ارسال موارد اصلاحی</button>
            </form>
        </section>
    @else
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-600">این پرونده اکنون در وضعیت «{{ $company->status }}» است و در صف بررسی قرار ندارد.</div>
    @endif
</div>
@endsection
