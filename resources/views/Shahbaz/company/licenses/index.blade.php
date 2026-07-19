@extends('layouts.app')
@section('header_title','مجوزها و پروانه فعالیت')
@section('content')
@php
    $statusLabels=['active'=>'فعال','suspended'=>'تعلیق‌شده','expired'=>'منقضی','revoked'=>'ابطال‌شده','unverified'=>'تأییدنشده'];
    $remaining=$company->activity_license_expires_on ? today()->diffInDays($company->activity_license_expires_on,false) : null;
@endphp
<div dir="rtl" class="mx-auto max-w-6xl space-y-5">
    <section class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border bg-white p-6 shadow-sm">
        <div><h1 class="text-xl font-black">مجوزها و پروانه فعالیت {{ $company->name_fa }}</h1><p class="mt-2 text-sm text-slate-500">اطلاعات این صفحه از پروانه مرکزی شرکت خوانده می‌شود و در دوزوله، CMR و شحباز مشترک است.</p></div>
        <a href="{{ route('company.shahbaz.dossier.show') }}" class="rounded-xl border px-5 py-3 font-bold">بازگشت به پرونده</a>
    </section>

    @if($remaining !== null && $remaining <= 60)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 font-bold text-amber-900">
            @if($remaining < 0)
                اعتبار پروانه پایان یافته است؛ درخواست تمدید ثبت کنید.
            @else
                فقط {{ $remaining }} روز تا پایان اعتبار پروانه باقی مانده است.
            @endif
        </div>
    @endif

    @if($company->activity_license_number)
        <section id="license-card" class="rounded-3xl border bg-white p-7 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div><span class="rounded-full px-3 py-1 text-xs font-bold {{ $company->activity_license_status==='active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $statusLabels[$company->activity_license_status] ?? $company->activity_license_status }}</span><h2 class="mt-4 text-2xl font-black">پروانه فعالیت شرکت حمل‌ونقل</h2></div>
                <button type="button" onclick="window.print()" class="rounded-xl border px-5 py-3 font-bold print:hidden">چاپ پروانه</button>
            </div>
            <dl class="mt-7 grid gap-4 rounded-2xl bg-slate-50 p-5 md:grid-cols-2">
                <div>شماره پروانه: <b dir="ltr">{{ $company->activity_license_number }}</b></div>
                <div>نوع فعالیت: <b>{{ $company->activity_type ?: 'ثبت نشده' }}</b></div>
                <div>تاریخ صدور: <b>{{ $company->activity_license_issued_on ? verta($company->activity_license_issued_on)->format('Y/m/d') : 'ثبت نشده' }}</b></div>
                <div>پایان اعتبار: <b>{{ $company->activity_license_expires_on ? verta($company->activity_license_expires_on)->format('Y/m/d') : 'ثبت نشده' }}</b></div>
                <div>شناسه ملی شرکت: <b dir="ltr">{{ $company->national_id }}</b></div>
                <div>نام شرکت: <b>{{ $company->name_fa }}</b></div>
            </dl>
            @if($editable)
                <a href="{{ route('company.shahbaz.requests.create',['type'=>'renewal']) }}" class="mt-5 inline-block rounded-xl bg-emerald-700 px-6 py-3 font-black text-white print:hidden">درخواست تمدید پروانه</a>
            @endif
        </section>
    @else
        <div class="rounded-2xl border border-dashed bg-white p-10 text-center text-slate-500">هنوز پروانه‌ای برای این شرکت ثبت یا صادر نشده است.</div>
    @endif

    @if($requests->isNotEmpty())
        <section class="rounded-3xl border bg-white p-6 shadow-sm">
            <h2 class="font-black">درخواست‌های مرتبط با پروانه قبلی</h2>
            <div class="mt-4 space-y-3">
                @foreach($requests as $item)
                    <div class="flex flex-wrap justify-between gap-3 rounded-xl bg-slate-50 p-4">
                        <span>پروانه قبلی: <b dir="ltr">{{ $item->previous_license_number }}</b></span>
                        <span>کد رهگیری: <b dir="ltr">{{ $item->tracking_code }}</b></span>
                        <span>وضعیت: <b>{{ $item->status }}</b></span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
