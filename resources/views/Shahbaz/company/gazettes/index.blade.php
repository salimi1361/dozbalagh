@extends('layouts.app')
@section('header_title','روزنامه رسمی')
@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.vendor.min.css') }}">
<style>.datepicker-plot-area{font-family:'Vazirmatn',Tahoma,sans-serif!important;z-index:99999!important}.jalali-picker{cursor:pointer}</style>
<div dir="rtl" class="mx-auto max-w-6xl space-y-5">
    <section class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border bg-white p-6 shadow-sm"><div><h1 class="text-xl font-black">سوابق روزنامه رسمی {{ $company->name_fa }}</h1><p class="mt-2 text-sm text-slate-500">هر آگهی به‌صورت یک سابقه مستقل ثبت می‌شود و سوابق قبلی بازنویسی یا حذف نمی‌شوند.</p></div><a href="{{ route('company.shahbaz.dossier.show') }}" class="rounded-xl border px-5 py-3 font-bold">بازگشت به پرونده</a></section>
    @if(session('success'))<div class="rounded-xl bg-emerald-50 p-4 font-bold text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if($editable)
    <form method="POST" action="{{ route('company.shahbaz.gazettes.store') }}" class="rounded-3xl border bg-white p-6 shadow-sm">@csrf
        <h2 class="font-black text-emerald-700">ثبت آگهی جدید</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <label class="space-y-2"><span class="font-bold">شماره روزنامه رسمی</span><input name="gazette_number" required value="{{ old('gazette_number') }}" class="w-full rounded-xl border p-3" dir="ltr"></label>
            <label class="space-y-2"><span class="font-bold">تاریخ روزنامه رسمی (شمسی)</span><span class="relative block"><input type="text" readonly autocomplete="off" name="gazette_date_jalali" required value="{{ old('gazette_date_jalali') }}" class="jalali-picker w-full rounded-xl border py-3 pl-11 pr-3 text-center" dir="ltr" placeholder="انتخاب تاریخ"><span class="pointer-events-none absolute left-3 top-3 text-lg">🗓️</span></span></label>
            <label class="space-y-2"><span class="font-bold">گروه تغییرات</span><select name="change_group" required class="w-full rounded-xl border p-3"><option value="">انتخاب کنید</option>@foreach($changeGroups as $value)<option value="{{ $value }}" @selected(old('change_group')===$value)>{{ $value }}</option>@endforeach</select></label>
            <label class="space-y-2"><span class="font-bold">شماره نامه/اعلامیه</span><input name="notice_number" value="{{ old('notice_number') }}" class="w-full rounded-xl border p-3" dir="ltr"></label>
            <label class="space-y-2"><span class="font-bold">تاریخ نامه (شمسی)</span><span class="relative block"><input type="text" readonly autocomplete="off" name="notice_date_jalali" value="{{ old('notice_date_jalali') }}" class="jalali-picker w-full rounded-xl border py-3 pl-11 pr-3 text-center" dir="ltr" placeholder="انتخاب تاریخ"><span class="pointer-events-none absolute left-3 top-3 text-lg">🗓️</span></span></label>
            <label class="space-y-2"><span class="font-bold">موضوع آگهی</span><input name="subject" required value="{{ old('subject') }}" class="w-full rounded-xl border p-3"></label>
            <label class="space-y-2 md:col-span-3"><span class="font-bold">توضیحات</span><textarea name="notes" rows="2" class="w-full rounded-xl border p-3">{{ old('notes') }}</textarea></label>
        </div>
        <button class="mt-5 rounded-xl bg-emerald-700 px-7 py-3 font-black text-white">ثبت سابقه روزنامه رسمی</button>
    </form>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 font-bold text-amber-800">پرونده در حال بررسی یا بسته است؛ سوابق فقط قابل مشاهده‌اند.</div>
    @endif

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($gazettes as $gazette)
            <article class="rounded-2xl border bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <strong>{{ $gazette->change_group }}</strong>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">ثبت شده</span>
                </div>
                <dl class="mt-4 space-y-2 text-sm">
                    <div>شماره روزنامه: <b dir="ltr">{{ $gazette->gazette_number }}</b></div>
                    <div>تاریخ روزنامه: <b>{{ verta($gazette->gazette_date)->format('Y/m/d') }}</b></div>
                    <div>موضوع: <b>{{ $gazette->subject }}</b></div>
                    <div>شماره اعلامیه: <b dir="ltr">{{ $gazette->notice_number ?: 'ثبت نشده' }}</b></div>
                    @if($gazette->notice_date)
                        <div>تاریخ اعلامیه: <b>{{ verta($gazette->notice_date)->format('Y/m/d') }}</b></div>
                    @endif
                    @if($gazette->notes)
                        <div class="border-t pt-2 text-slate-600">{{ $gazette->notes }}</div>
                    @endif
                </dl>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed bg-white p-10 text-center text-slate-500 md:col-span-2 xl:col-span-3">هنوز سابقه‌ای از روزنامه رسمی ثبت نشده است.</div>
        @endforelse
    </section>
</div>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/persian-date.vendor.min.js') }}"></script><script src="{{ asset('assets/js/persian-datepicker.vendor.min.js') }}"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{if(window.jQuery&&typeof jQuery.fn.pDatepicker==='function'){const placePicker=model=>requestAnimationFrame(()=>{const box=model.view.$container,inputRect=model.inputElement.getBoundingClientRect(),plot=box.find('.datepicker-plot-area')[0];if(!plot)return;const plotRect=plot.getBoundingClientRect(),above=window.innerHeight-inputRect.bottom<plotRect.height&&inputRect.top>plotRect.height,currentTop=parseFloat(box.css('top'))||0,delta=above?inputRect.top-4-plotRect.bottom:inputRect.bottom+4-plotRect.top;box.css('top',(currentTop+delta)+'px');});jQuery('.jalali-picker').pDatepicker({format:'YYYY/MM/DD',initialValue:false,initialValueType:'persian',autoClose:true,responsive:true,onlySelectOnDate:true,calendarType:'persian',calendar:{persian:{locale:'fa',showHint:false,leapYearMode:'algorithmic'},gregorian:{showHint:false}},navigator:{enabled:true,scroll:{enabled:true}},toolbox:{enabled:true,calendarSwitch:{enabled:false},todayButton:{enabled:true},submitButton:{enabled:false}},timePicker:{enabled:false},onShow:placePicker});const close=e=>{if(e.target instanceof Element&&e.target.closest('.datepicker-container,.datepicker-plot-area'))return;jQuery('.jalali-picker').each(function(){jQuery(this).data('datepicker')?.hide();});};window.addEventListener('wheel',close,{passive:true,capture:true});window.addEventListener('touchmove',close,{passive:true,capture:true});}});</script>
@endsection
