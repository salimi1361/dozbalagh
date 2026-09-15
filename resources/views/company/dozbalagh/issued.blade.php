@extends('layouts.app')
@section('header_title', 'دوزوله‌های صادرشده')
@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.vendor.min.css') }}">
<style>.datepicker-plot-area{font-family:'Vazirmatn',Tahoma,sans-serif!important;z-index:99999!important}.jalali-picker{cursor:pointer}</style>
@php
    $permitTypeLabels = [
        'bilateral' => 'دوجانبه', 'transit' => 'ترانزیتی',
        'bilateral_transit' => 'دوجانبه ترانزیتی', 'third_country' => 'کشور ثالث',
        'third_country_transit' => 'ترانزیت کشور ثالث',
    ];
    $headerStats = [
        'total' => (int) ($issuedStats['total'] ?? 0),
        'valid' => (int) ($issuedStats['valid'] ?? 0),
        'countries' => (int) ($issuedStats['countries'] ?? 0),
        'filtered' => (int) $items->total(),
    ];
@endphp
<div dir="rtl" class="mx-auto max-w-7xl space-y-5">
    <section class="company-page-header rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5">
        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3.75h7.5L19 8.25v12H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3.75v4.5h4.5M8.5 12h7M8.5 15.5h7"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">دوزوله‌های صادرشده شرکت</h1>
                    <p class="mt-1 text-xs font-semibold text-emerald-100">جست‌وجو، گزارش‌گیری، مشاهده و چاپ نسخه دوزوله‌های تخصیص‌یافته</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <div class="flex min-w-[7.5rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                    <span class="text-[10px] font-bold text-emerald-100">کل صادرشده</span>
                    <strong class="text-lg font-black">{{ number_format($headerStats['total']) }}</strong>
                </div>
                <div class="flex min-w-[7.5rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                    <span class="text-[10px] font-bold text-emerald-100">دارای اعتبار</span>
                    <strong class="text-lg font-black text-cyan-100">{{ number_format($headerStats['valid']) }}</strong>
                </div>
                <div class="flex min-w-[7.5rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                    <span class="text-[10px] font-bold text-emerald-100">کشورها</span>
                    <strong class="text-lg font-black text-amber-200">{{ number_format($headerStats['countries']) }}</strong>
                </div>
                <div class="flex min-w-[7.5rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                    <span class="text-[10px] font-bold text-emerald-100">نتیجه فیلتر</span>
                    <strong class="text-lg font-black text-violet-200">{{ number_format($headerStats['filtered']) }}</strong>
                </div>
            </div>
        </div>
    </section>
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="GET" action="{{ route('company.dozbalagh.issued') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-12">
            <label class="space-y-2 md:col-span-2 xl:col-span-6"><span class="text-xs font-black text-slate-700">جست‌وجو در گزارش</span><span class="relative block"><input name="search" value="{{ request('search') }}" class="w-full rounded-xl border border-slate-300 bg-white py-3 pr-11 text-sm shadow-sm outline-none placeholder:text-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" placeholder="سریال، کد رهگیری، کشور، راننده، شماره کشنده یا بارگیر"><span class="pointer-events-none absolute right-4 top-3 text-slate-400">🔎</span></span></label>
            <label class="space-y-2 xl:col-span-3"><span class="text-xs font-black text-slate-700">از تاریخ صدور <span class="font-medium text-slate-400">(شمسی)</span></span><span class="relative block"><input type="text" readonly autocomplete="off" name="date_from_jalali" value="{{ $dateFromJalali }}" class="jalali-picker w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-3 text-center text-sm shadow-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" dir="ltr" placeholder="انتخاب تاریخ شروع"><span class="pointer-events-none absolute left-3 top-3">🗓️</span></span></label>
            <label class="space-y-2 xl:col-span-3"><span class="text-xs font-black text-slate-700">تا تاریخ صدور <span class="font-medium text-slate-400">(شمسی)</span></span><span class="relative block"><input type="text" readonly autocomplete="off" name="date_to_jalali" value="{{ $dateToJalali }}" class="jalali-picker w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-3 text-center text-sm shadow-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" dir="ltr" placeholder="انتخاب تاریخ پایان"><span class="pointer-events-none absolute left-3 top-3">🗓️</span></span></label>
        </div>
        <div class="flex flex-col gap-4 border-t border-slate-100 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2 text-xs text-slate-600"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 font-black text-emerald-700">{{ number_format($items->total()) }}</span><span>دوزوله یافت شد؛ همین موارد در خروجی قرار می‌گیرند.</span></div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button class="rounded-xl bg-slate-900 px-6 py-2.5 text-xs font-black text-white shadow-sm hover:bg-slate-800">نمایش گزارش</button>
                <a href="{{ route('company.dozbalagh.issued', request()->only('search')) }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-center text-xs font-bold text-slate-600 hover:bg-slate-100">پاک‌کردن تاریخ‌ها</a>
                <a href="{{ route('company.dozbalagh.issued.export', request()->only(['search','date_from_jalali','date_to_jalali'])) }}" class="rounded-xl bg-emerald-600 px-6 py-2.5 text-center text-xs font-black text-white shadow-sm hover:bg-emerald-700">خروجی Excel همین بازه</a>
            </div>
        </div>
    </form>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="w-full min-w-[1050px] text-right text-xs"><thead class="whitespace-nowrap bg-slate-100 text-slate-600"><tr><th class="p-4">سریال / کد رهگیری</th><th class="p-4">کشور و نوع مجوز</th><th class="p-4">راننده</th><th class="p-4">شماره ترانزیت اسب (کشنده)</th><th class="p-4">شماره ترانزیت یدک (بارگیر)</th><th class="p-4">مسیر</th><th class="p-4">تاریخ صدور</th><th class="p-4 text-center">نسخه چاپی</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($items as $item)<tr class="hover:bg-slate-50"><td class="p-4"><strong class="block font-mono text-sm text-slate-800">{{ $item->d_serial_number }}</strong><span class="mt-1 block font-mono text-[10px] text-slate-400">{{ $item->d_code }}</span></td><td class="p-4"><strong class="block text-slate-700">{{ $item->country_name ?: '---' }}</strong><span class="mt-1 block font-bold text-indigo-600">{{ $permitTypeLabels[$item->permit_type] ?? $item->permit_type }}</span></td><td class="p-4"><strong class="block">{{ trim(($item->first_name_fa ?? '').' '.($item->last_name_fa ?? '')) ?: '---' }}</strong></td><td class="p-4 font-mono font-bold text-slate-700">{{ $item->transit_horse ?: 'ثبت نشده' }}</td><td class="p-4 font-mono font-bold text-slate-700">{{ $item->transit_trailer ?: 'ثبت نشده' }}</td><td class="p-4"><span>{{ $item->loading_origin ?: '---' }}</span><span class="mx-1 text-slate-300">←</span><span>{{ $item->loading_destination ?: '---' }}</span></td><td class="whitespace-nowrap p-4">{{ $item->issued_at ? verta($item->issued_at)->format('Y/m/d') : '---' }}</td><td class="p-4 text-center"><a href="{{ route('company.dozbalagh.copy',$item->id) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2 font-black text-white shadow hover:bg-sky-700">👁 مشاهده و چاپ</a></td></tr>@empty<tr><td colspan="8" class="p-14 text-center font-bold text-slate-400">نتیجه‌ای برای نمایش پیدا نشد.</td></tr>@endforelse</tbody></table></div>
        @if($items->hasPages())<div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>@endif
    </div>
</div>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/persian-date.vendor.min.js') }}"></script><script src="{{ asset('assets/js/persian-datepicker.vendor.min.js') }}"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{if(window.jQuery&&typeof jQuery.fn.pDatepicker==='function'){const placePicker=model=>requestAnimationFrame(()=>{const box=model.view.$container,inputRect=model.inputElement.getBoundingClientRect(),plot=box.find('.datepicker-plot-area')[0];if(!plot)return;const plotRect=plot.getBoundingClientRect(),above=window.innerHeight-inputRect.bottom<plotRect.height&&inputRect.top>plotRect.height,currentTop=parseFloat(box.css('top'))||0,delta=above?inputRect.top-4-plotRect.bottom:inputRect.bottom+4-plotRect.top;box.css('top',(currentTop+delta)+'px');});jQuery('.jalali-picker').pDatepicker({format:'YYYY/MM/DD',initialValue:false,initialValueType:'persian',autoClose:true,responsive:true,onlySelectOnDate:true,calendarType:'persian',calendar:{persian:{locale:'fa',showHint:false,leapYearMode:'algorithmic'},gregorian:{showHint:false}},navigator:{enabled:true,scroll:{enabled:true}},toolbox:{enabled:true,calendarSwitch:{enabled:false},todayButton:{enabled:true},submitButton:{enabled:false}},timePicker:{enabled:false},onShow:placePicker});const close=e=>{if(e.target instanceof Element&&e.target.closest('.datepicker-container,.datepicker-plot-area'))return;jQuery('.jalali-picker').each(function(){jQuery(this).data('datepicker')?.hide();});};window.addEventListener('wheel',close,{passive:true,capture:true});window.addEventListener('touchmove',close,{passive:true,capture:true});}});</script>
@endsection
