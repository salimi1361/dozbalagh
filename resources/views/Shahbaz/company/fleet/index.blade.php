@extends('layouts.app')
@section('header_title','ناوگان پرونده شرکت')
@section('content')
<div dir="rtl" class="mx-auto max-w-6xl space-y-5">
    <section class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border bg-white p-6 shadow-sm">
        <div><h1 class="text-xl font-black">ناوگان مرکزی {{ $company->name_fa }}</h1><p class="mt-2 text-sm text-slate-500">این اطلاعات مستقیماً از ناوگان مشترک دوزوله و CMR خوانده می‌شود و نسخه جداگانه‌ای در شحباز ساخته نمی‌شود.</p></div>
        <div class="flex flex-wrap gap-2"><a href="{{ route('company.shahbaz.dossier.show') }}" class="rounded-xl border px-5 py-3 font-bold">بازگشت به پرونده</a>@if($editable)<a href="{{ route('web.company.fleet.index') }}" class="rounded-xl bg-emerald-700 px-5 py-3 font-black text-white">مدیریت و افزودن ناوگان</a>@endif</div>
    </section>
    @if(!$editable)<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-800">پرونده در حال بررسی یا بسته است؛ ناوگان فقط قابل مشاهده است.</div>@endif
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($fleets as $fleet)
            <article class="rounded-2xl border bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3"><strong>ناوگان #{{ $fleet->id }}</strong><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">متصل به شرکت</span></div>
                <div class="mt-4 flex justify-center"><x-iran-plate :plate="$fleet->transit_plate" size="sm" /></div>
                <dl class="mt-5 space-y-2 text-sm"><div>کارت هوشمند: <b dir="ltr">{{ $fleet->smart_card_number ?: 'ثبت نشده' }}</b></div><div>نوع وسیله/بارگیر: <b>{{ $fleet->truck_type ?: 'ثبت نشده' }}</b></div><div>ترانزیت اسب: <b dir="ltr">{{ $fleet->transit_horse ?: '---' }}</b></div><div>ترانزیت یدک: <b dir="ltr">{{ $fleet->transit_trailer ?: '---' }}</b></div><div>آخرین به‌روزرسانی: <b>{{ verta($fleet->updated_at)->format('Y/m/d H:i') }}</b></div></dl>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed bg-white p-10 text-center text-slate-500 md:col-span-2 xl:col-span-3">هنوز ناوگانی به شرکت متصل نشده است. برای تکمیل این مرحله، ناوگان را در کارتابل مرکزی شرکت ثبت کنید.</div>
        @endforelse
    </section>
</div>
@endsection
