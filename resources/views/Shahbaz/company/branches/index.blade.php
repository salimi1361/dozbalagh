@extends('layouts.app')
@section('header_title','مجوزهای نمایندگی و شعب')
@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.vendor.min.css') }}">
<style>.datepicker-plot-area{font-family:'Vazirmatn',Tahoma,sans-serif!important;z-index:99999!important}.jalali-picker{cursor:pointer}</style>
<div dir="rtl" class="mx-auto max-w-6xl space-y-5">
    <section class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border bg-white p-6 shadow-sm"><div><h1 class="text-xl font-black">مجوزهای نمایندگی و شعب {{ $company->name_fa }}</h1><p class="mt-2 text-sm text-slate-500">هر شعبه یا نمایندگی و اعتبار مجوز آن به‌صورت مستقل ثبت می‌شود.</p></div><a href="{{ route('company.shahbaz.dossier.show') }}" class="rounded-xl border px-5 py-3 font-bold">بازگشت به پرونده</a></section>
    @if(session('success'))<div class="rounded-xl bg-emerald-50 p-4 font-bold text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if($editable)
        <form method="POST" action="{{ route('company.shahbaz.branches.store') }}" class="rounded-3xl border bg-white p-6 shadow-sm">@csrf
            <h2 class="font-black text-emerald-700">ثبت مجوز جدید</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <label class="space-y-2"><span class="font-bold">نوع واحد</span><select name="branch_type" required class="w-full rounded-xl border p-3"><option value="شعبه" @selected(old('branch_type')==='شعبه')>شعبه</option><option value="نمایندگی" @selected(old('branch_type')==='نمایندگی')>نمایندگی</option></select></label>
                <label class="space-y-2"><span class="font-bold">نام شعبه یا نمایندگی</span><input name="name" required value="{{ old('name') }}" class="w-full rounded-xl border p-3"></label>
                <label class="space-y-2"><span class="font-bold">نام مسئول</span><input name="manager_name" value="{{ old('manager_name') }}" class="w-full rounded-xl border p-3"></label>
                <label class="space-y-2"><span class="font-bold">استان</span><input name="province" required value="{{ old('province') }}" class="w-full rounded-xl border p-3"></label>
                <label class="space-y-2"><span class="font-bold">شهر</span><input name="city" required value="{{ old('city') }}" class="w-full rounded-xl border p-3"></label>
                <label class="space-y-2"><span class="font-bold">شماره مجوز</span><input name="permit_number" required value="{{ old('permit_number') }}" class="w-full rounded-xl border p-3" dir="ltr"></label>
                <label class="space-y-2"><span class="font-bold">تاریخ صدور (شمسی)</span><span class="relative block"><input type="text" readonly autocomplete="off" name="issued_on_jalali" required value="{{ old('issued_on_jalali') }}" class="jalali-picker w-full rounded-xl border py-3 pl-11 pr-3 text-center" dir="ltr" placeholder="انتخاب تاریخ"><span class="pointer-events-none absolute left-3 top-3 text-lg">🗓️</span></span></label>
                <label class="space-y-2"><span class="font-bold">پایان اعتبار (شمسی)</span><span class="relative block"><input type="text" readonly autocomplete="off" name="expires_on_jalali" required value="{{ old('expires_on_jalali') }}" class="jalali-picker w-full rounded-xl border py-3 pl-11 pr-3 text-center" dir="ltr" placeholder="انتخاب تاریخ"><span class="pointer-events-none absolute left-3 top-3 text-lg">🗓️</span></span></label>
                <label class="space-y-2 md:col-span-3"><span class="font-bold">نشانی</span><textarea name="address" required rows="2" class="w-full rounded-xl border p-3">{{ old('address') }}</textarea></label>
                <label class="space-y-2 md:col-span-3"><span class="font-bold">توضیحات</span><textarea name="notes" rows="2" class="w-full rounded-xl border p-3">{{ old('notes') }}</textarea></label>
            </div>
            <button class="mt-5 rounded-xl bg-emerald-700 px-7 py-3 font-black text-white">ثبت مجوز</button>
        </form>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 font-bold text-amber-800">پرونده در حال بررسی یا بسته است؛ سوابق فقط قابل مشاهده‌اند.</div>
    @endif

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($permits as $permit)
            @php($remaining=today()->diffInDays($permit->expires_on,false))
            <article class="rounded-2xl border bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3"><strong>{{ $permit->branch_type }} {{ $permit->name }}</strong><span class="rounded-full px-3 py-1 text-xs font-bold {{ $remaining < 0 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $remaining < 0 ? 'منقضی' : 'فعال' }}</span></div>
                <dl class="mt-4 space-y-2 text-sm"><div>شماره مجوز: <b dir="ltr">{{ $permit->permit_number }}</b></div><div>محل: <b>{{ $permit->province }}، {{ $permit->city }}</b></div><div>مسئول: <b>{{ $permit->manager_name ?: 'ثبت نشده' }}</b></div><div>صدور: <b>{{ verta($permit->issued_on)->format('Y/m/d') }}</b></div><div>پایان اعتبار: <b>{{ verta($permit->expires_on)->format('Y/m/d') }}</b></div><div class="text-slate-600">{{ $permit->address }}</div></dl>
                @if($remaining <= 60)<div class="mt-3 rounded-lg bg-amber-50 p-3 text-xs font-bold text-amber-900">{{ $remaining < 0 ? 'اعتبار این مجوز پایان یافته است.' : $remaining.' روز تا پایان اعتبار باقی مانده است.' }}</div>@endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed bg-white p-10 text-center text-slate-500 md:col-span-2 xl:col-span-3">مجوز شعبه یا نمایندگی ثبت نشده است.</div>
        @endforelse
    </section>
</div>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/persian-date.vendor.min.js') }}"></script><script src="{{ asset('assets/js/persian-datepicker.vendor.min.js') }}"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{if(window.jQuery&&typeof jQuery.fn.pDatepicker==='function'){const placePicker=model=>requestAnimationFrame(()=>{const box=model.view.$container,inputRect=model.inputElement.getBoundingClientRect(),plot=box.find('.datepicker-plot-area')[0];if(!plot)return;const plotRect=plot.getBoundingClientRect(),above=window.innerHeight-inputRect.bottom<plotRect.height&&inputRect.top>plotRect.height,currentTop=parseFloat(box.css('top'))||0,delta=above?inputRect.top-4-plotRect.bottom:inputRect.bottom+4-plotRect.top;box.css('top',(currentTop+delta)+'px');});jQuery('.jalali-picker').pDatepicker({format:'YYYY/MM/DD',initialValue:false,initialValueType:'persian',autoClose:true,responsive:true,onlySelectOnDate:true,calendarType:'persian',calendar:{persian:{locale:'fa',showHint:false,leapYearMode:'algorithmic'},gregorian:{showHint:false}},navigator:{enabled:true,scroll:{enabled:true}},toolbox:{enabled:true,calendarSwitch:{enabled:false},todayButton:{enabled:true},submitButton:{enabled:false}},timePicker:{enabled:false},onShow:placePicker});const close=e=>{if(e.target instanceof Element&&e.target.closest('.datepicker-container,.datepicker-plot-area'))return;jQuery('.jalali-picker').each(function(){jQuery(this).data('datepicker')?.hide();});};window.addEventListener('wheel',close,{passive:true,capture:true});window.addEventListener('touchmove',close,{passive:true,capture:true});}});</script>
@endsection
