@extends('layouts.app')
@section('header_title','تکمیل و تأیید اطلاعات شرکت در شحباز')
@section('content')
@php
    $statusLabels = [
        'profile_incomplete' => 'در انتظار تکمیل اطلاعات',
        'correction_required' => 'نیازمند اصلاح',
        'shahbaz_mismatch' => 'دارای مغایرت و نیازمند اصلاح',
        'pending_association_review' => 'در انتظار بررسی انجمن',
        'ready_for_shahbaz_check' => 'آماده کنترل شحباز',
        'verified' => 'تأیید نهایی شده',
        'rejected' => 'رد شده',
    ];
    $status = $company->shahbaz_verification_status ?: 'profile_incomplete';
    $statusClass = in_array($status, ['correction_required', 'shahbaz_mismatch', 'rejected'], true)
        ? 'bg-rose-100 text-rose-800'
        : ($status === 'verified' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800');
    $fields = [
        'name_fa' => 'نام رسمی فارسی', 'name_en' => 'نام لاتین', 'national_id' => 'شناسه ملی',
        'registration_number' => 'شماره ثبت', 'ceo_name' => 'نام مدیرعامل',
        'ceo_national_code' => 'کد ملی مدیرعامل', 'ceo_mobile' => 'موبایل مدیرعامل',
        'phone' => 'تلفن شرکت', 'postal_code' => 'کدپستی', 'province' => 'استان',
        'city' => 'شهر', 'activity_type' => 'نوع فعالیت',
    ];
@endphp

<div dir="rtl" class="mx-auto max-w-6xl space-y-5">
    <section class="company-page-header rounded-3xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-blue-100">پرونده مرکزی شرکت</p>
                <h1 class="mt-1 text-2xl font-black">تکمیل مشخصات شرکت</h1>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-blue-100">این برگه مرجع اطلاعات شرکت در شحباز است. پس از تکمیل اطلاعات و الزامات فعال، پرونده برای کنترل انجمن ارسال می‌شود.</p>
            </div>
            <span class="rounded-full bg-white/15 px-4 py-2 text-sm font-black text-white">{{ $statusLabels[$status] ?? $status }}</span>
        </div>
    </section>

    @if(session('warning'))<div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-900">{{ session('warning') }}</div>@endif
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-900">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><ul class="list-disc space-y-1 pr-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if($company->shahbaz_review_note && in_array($status, ['correction_required', 'shahbaz_mismatch', 'rejected'], true))
        <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-900"><b>توضیح بررسی انجمن:</b><p class="mt-2 leading-7">{{ $company->shahbaz_review_note }}</p></section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-lg font-black text-slate-800">وضعیت تکمیل پرونده</h2><p class="mt-1 text-sm text-slate-500">الزامات فعال در صفحه «خانه پرونده» قابل مشاهده و مرحله‌بندی هستند.</p></div>
            <a href="{{ route('company.shahbaz.dossier.show') }}" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-black text-white">مشاهده خانه پرونده</a>
        </div>
        @if($missingFields !== [])
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><b>اطلاعات پایه ناقص:</b> {{ count($missingFields) }} مورد باید تکمیل شود.</div>
        @elseif($requiredSections !== [])
            <div class="mt-4 grid gap-2 sm:grid-cols-2">@foreach($requiredSections as $section)<div class="rounded-xl border {{ $section['complete'] ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-900' }} p-3 text-sm font-bold">{{ $section['label'] }}: {{ $section['complete'] ? 'تکمیل شده' : 'نیازمند تکمیل' }}</div>@endforeach</div>
        @endif
    </section>

    <form method="POST" action="{{ route('company.shahbaz.profile.update') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @csrf @method('PUT')
        <fieldset @disabled(!$editable) class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($fields as $name => $label)
                <label class="text-sm font-bold text-slate-700">{{ $label }} <span class="text-rose-500">*</span>
                    <input name="{{ $name }}" value="{{ old($name, $company->$name) }}" required @if(in_array($name, ['national_id','ceo_national_code','ceo_mobile','phone','postal_code'], true)) dir="ltr" inputmode="numeric" @endif class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3 font-normal focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </label>
            @endforeach
            <label class="text-sm font-bold text-slate-700 md:col-span-2">آدرس فارسی <span class="text-rose-500">*</span><textarea name="address_fa" required rows="3" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3 font-normal focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('address_fa', $company->address_fa) }}</textarea></label>
            <label class="text-sm font-bold text-slate-700 md:col-span-2">آدرس لاتین <span class="text-rose-500">*</span><textarea name="address_en" required rows="3" dir="ltr" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3 text-left font-normal focus:border-blue-500 focus:ring-2 focus:ring-blue-100">{{ old('address_en', $company->address_en) }}</textarea></label>
        </fieldset>
        <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
            @if($editable)
                <button class="rounded-xl bg-blue-600 px-7 py-3 font-black text-white hover:bg-blue-700">ذخیره پیش‌نویس اطلاعات</button>
                <span class="text-xs text-slate-500">بعد از تکمیل همه بخش‌های اجباری، از خانه پرونده ارسال نهایی را بزنید.</span>
            @elseif($status === 'pending_association_review')
                <span class="rounded-xl bg-amber-50 px-5 py-3 text-sm font-bold text-amber-800">پرونده برای بررسی انجمن ارسال شده و فرم موقتاً قفل است.</span>
            @elseif($status === 'ready_for_shahbaz_check')
                <span class="rounded-xl bg-blue-50 px-5 py-3 text-sm font-bold text-blue-800">بررسی اولیه انجمن انجام شده و در صف کنترل شحباز است.</span>
            @elseif($status === 'verified')
                <span class="rounded-xl bg-emerald-50 px-5 py-3 text-sm font-bold text-emerald-800">پرونده تأیید شده است؛ اطلاعات فقط از مسیر اصلاحیه قابل تغییر است.</span>
            @endif
        </div>
    </form>
</div>
@endsection
