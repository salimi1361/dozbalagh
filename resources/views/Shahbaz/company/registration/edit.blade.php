@extends('layouts.app')
@section('header_title','ثبت شرکت')
@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.vendor.min.css') }}">
<style>.datepicker-plot-area{font-family:'Vazirmatn',Tahoma,sans-serif!important;z-index:99999!important}.jalali-picker{cursor:pointer}</style>
<form dir="rtl" method="POST" action="{{ route('company.shahbaz.registration.update') }}" class="mx-auto max-w-6xl space-y-5">
    @csrf @method('PUT')
    <section class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border bg-white p-6 shadow-sm">
        <div><h1 class="text-xl font-black">اطلاعات ثبتی {{ $company->name_fa }}</h1><p class="mt-2 text-sm text-slate-500">شناسه ملی و شماره ثبت از پرونده مرکزی شرکت خوانده می‌شوند و در این بخش تکرار نمی‌شوند.</p></div>
        <a href="{{ route('company.shahbaz.dossier.show') }}" class="rounded-xl border px-5 py-3 font-bold">بازگشت به پرونده</a>
    </section>
    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 p-4 font-bold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl bg-rose-50 p-4 text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @if(!$editable)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 font-bold text-amber-800">پرونده در حال بررسی یا بسته است؛ اطلاعات فقط قابل مشاهده است.</div>
    @endif

    <section class="rounded-3xl border bg-white p-6 shadow-sm">
        <h2 class="font-black text-emerald-700">مشخصات ثبتی مرکزی</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="rounded-xl bg-slate-50 p-4">شناسه ملی: <b dir="ltr">{{ $company->national_id ?: 'تکمیل نشده' }}</b></div>
            <div class="rounded-xl bg-slate-50 p-4">شماره ثبت: <b dir="ltr">{{ $company->registration_number ?: 'تکمیل نشده' }}</b></div>
        </div>
    </section>

    <fieldset @disabled(!$editable) class="rounded-3xl border bg-white p-6 shadow-sm">
        <h2 class="font-black text-emerald-700">جزئیات ثبت شرکت</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <label class="space-y-2"><span class="font-bold">تاریخ ثبت (شمسی)</span><span class="relative block"><input type="text" readonly autocomplete="off" name="registered_on_jalali" required value="{{ old('registered_on_jalali',$registration->registered_on?verta($registration->registered_on)->format('Y/m/d'):'') }}" class="jalali-picker w-full rounded-xl border py-3 pl-11 pr-3 text-center" dir="ltr" placeholder="انتخاب تاریخ ثبت"><span class="pointer-events-none absolute left-3 top-3 text-lg">🗓️</span></span></label>
            <label class="space-y-2"><span class="font-bold">شهر محل ثبت</span><input name="registration_city" required value="{{ old('registration_city',$registration->registration_city ?: $company->city) }}" class="w-full rounded-xl border p-3"></label>
            <label class="space-y-2"><span class="font-bold">نوع حقوقی شرکت</span><select name="legal_type" required class="w-full rounded-xl border p-3"><option value="">انتخاب کنید</option>@foreach($legalTypes as $value)<option value="{{ $value }}" @selected(old('legal_type',$registration->legal_type)===$value)>{{ $value }}</option>@endforeach</select></label>
            <label class="space-y-2"><span class="font-bold">شماره معرفی‌نامه</span><input name="introduction_letter_number" value="{{ old('introduction_letter_number',$registration->introduction_letter_number) }}" class="w-full rounded-xl border p-3" dir="ltr"></label>
            <label class="space-y-2"><span class="font-bold">تاریخ معرفی‌نامه (شمسی)</span><span class="relative block"><input type="text" readonly autocomplete="off" name="introduction_letter_date_jalali" value="{{ old('introduction_letter_date_jalali',$registration->introduction_letter_date?verta($registration->introduction_letter_date)->format('Y/m/d'):'') }}" class="jalali-picker w-full rounded-xl border py-3 pl-11 pr-3 text-center" dir="ltr" placeholder="انتخاب تاریخ"><span class="pointer-events-none absolute left-3 top-3 text-lg">🗓️</span></span></label>
            <label class="space-y-2 md:col-span-3"><span class="font-bold">توضیحات</span><textarea name="notes" rows="3" class="w-full rounded-xl border p-3">{{ old('notes',$registration->notes) }}</textarea></label>
        </div>
    </fieldset>
    @if($editable)
        <button class="rounded-xl bg-emerald-700 px-7 py-3 font-black text-white">ذخیره اطلاعات ثبت شرکت</button>
    @endif
</form>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/persian-date.vendor.min.js') }}"></script><script src="{{ asset('assets/js/persian-datepicker.vendor.min.js') }}"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{if(window.jQuery&&typeof jQuery.fn.pDatepicker==='function'){const placePicker=model=>requestAnimationFrame(()=>{const box=model.view.$container,inputRect=model.inputElement.getBoundingClientRect(),plot=box.find('.datepicker-plot-area')[0];if(!plot)return;const plotRect=plot.getBoundingClientRect(),above=window.innerHeight-inputRect.bottom<plotRect.height&&inputRect.top>plotRect.height,currentTop=parseFloat(box.css('top'))||0,delta=above?inputRect.top-4-plotRect.bottom:inputRect.bottom+4-plotRect.top;box.css('top',(currentTop+delta)+'px');});jQuery('.jalali-picker').pDatepicker({format:'YYYY/MM/DD',initialValue:true,initialValueType:'persian',autoClose:true,responsive:true,onlySelectOnDate:true,calendarType:'persian',calendar:{persian:{locale:'fa',showHint:false,leapYearMode:'algorithmic'},gregorian:{showHint:false}},navigator:{enabled:true,scroll:{enabled:true}},toolbox:{enabled:true,calendarSwitch:{enabled:false},todayButton:{enabled:true},submitButton:{enabled:false}},timePicker:{enabled:false},onShow:placePicker});const close=e=>{if(e.target instanceof Element&&e.target.closest('.datepicker-container,.datepicker-plot-area'))return;jQuery('.jalali-picker').each(function(){jQuery(this).data('datepicker')?.hide();});};window.addEventListener('wheel',close,{passive:true,capture:true});window.addEventListener('touchmove',close,{passive:true,capture:true});}});</script>
@endsection
