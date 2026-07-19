@extends('layouts.app')
@section('header_title','وضعیت فعالیت شرکت')
@section('content')
@php
    $labels = ['active'=>'فعال','inactive'=>'غیرفعال','suspended'=>'تعلیق‌شده','expired'=>'منقضی','unverified'=>'تأییدنشده'];
    $status = $company->activity_license_status ?: 'unverified';
    $active = $status === 'active';
@endphp
<div dir="rtl" class="mx-auto max-w-5xl space-y-6">
<section class="rounded-3xl border bg-white p-6 shadow-sm"><div class="flex flex-wrap items-center justify-between gap-4"><div><h1 class="text-2xl font-black">فعال/غیرفعال دستی</h1><p class="mt-2 text-sm text-slate-500">این وضعیت فقط توسط انجمن تغییر می‌کند و شرکت امکان تغییر آن را ندارد.</p></div><a href="{{ route('company.shahbaz.dossier.show') }}" class="rounded-xl border px-5 py-3 font-black">بازگشت به پرونده</a></div></section>
<section class="rounded-3xl border p-6 {{ $active ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
<div class="flex items-center gap-3"><span class="h-4 w-4 rounded-full {{ $active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span><h2 class="text-xl font-black">وضعیت فعلی: {{ $labels[$status] ?? $status }}</h2></div>
<div class="mt-5 grid gap-3 text-sm md:grid-cols-2"><div>شماره پروانه: <b>{{ $company->activity_license_number ?: 'ثبت نشده' }}</b></div><div>پایان اعتبار: <b>{{ $company->activity_license_expires_on ? verta($company->activity_license_expires_on)->format('Y/m/d') : 'ثبت نشده' }}</b></div></div>
@if($company->shahbaz_review_note)<div class="mt-5 rounded-xl bg-white/70 p-4"><b>آخرین توضیح انجمن:</b><p class="mt-2">{{ $company->shahbaz_review_note }}</p></div>@endif
</section>
<section class="rounded-3xl border bg-white p-6 shadow-sm"><h2 class="text-lg font-black">تاریخچه تغییر وضعیت دستی</h2><div class="mt-4 space-y-3">@forelse($histories as $row)<div class="rounded-xl border p-4 text-sm"><b>{{ $labels[$row->to_status] ?? $row->to_status }}</b> — {{ verta($row->checked_at ?: $row->created_at)->format('Y/m/d H:i') }}<p class="mt-2 text-slate-600">{{ $row->description }}</p><span class="mt-2 block text-xs text-slate-400">ثبت‌کننده: {{ $row->changedBy?->name ?? 'کاربر انجمن' }}</span></div>@empty<div class="rounded-xl border border-dashed p-5 text-sm text-slate-500">هنوز تغییر وضعیت دستی ثبت نشده است.</div>@endforelse</div></section>
</div>
@endsection
