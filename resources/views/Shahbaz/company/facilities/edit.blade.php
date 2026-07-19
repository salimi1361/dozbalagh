@extends('layouts.app')
@section('header_title','محل و امکانات')
@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.vendor.min.css') }}">
<style>.datepicker-plot-area{font-family:'Vazirmatn',Tahoma,sans-serif!important;z-index:99999!important}.jalali-picker{cursor:pointer}</style>
@php($savedFacilities=collect(old('facilities',$facility->facilities ?? []))->keyBy('type'))
<form dir="rtl" method="POST" action="{{ route('company.shahbaz.facilities.update') }}" class="mx-auto max-w-6xl space-y-5">
    @csrf @method('PUT')
    <section class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border bg-white p-6 shadow-sm">
        <div><h1 class="text-xl font-black">محل فعالیت و امکانات شرکت</h1><p class="mt-2 text-sm text-slate-500">نشانی پایه از پرونده مشترک شرکت خوانده می‌شود و جزئیات محل در ماژول شحباز نگهداری خواهد شد.</p></div>
        <a href="{{ route('company.shahbaz.dossier.show') }}" class="rounded-xl border px-5 py-3 font-bold">بازگشت به پرونده</a>
    </section>
    @if(session('success'))<div class="rounded-xl bg-emerald-50 p-4 font-bold text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(!$editable)<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 font-bold text-amber-800">پرونده در حال بررسی یا بسته است؛ اطلاعات فقط قابل مشاهده است.</div>@endif

    <fieldset @disabled(!$editable) class="space-y-5">
        <section class="rounded-3xl border bg-white p-6">
            <h2 class="font-black text-emerald-700">مشخصات محل</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <label class="space-y-2"><span class="font-bold">نوع محل</span><select name="location_type" required class="w-full rounded-xl border p-3">@foreach(['پایانه اختصاصی','پایانه عمومی','دفتر مرکزی','شعبه','سایر'] as $value)<option value="{{ $value }}" @selected(old('location_type',$facility->location_type ?: 'دفتر مرکزی')===$value)>{{ $value }}</option>@endforeach</select></label>
                <label class="space-y-2"><span class="font-bold">نوع مالکیت</span><select name="ownership_type" required class="w-full rounded-xl border p-3">@foreach(['ملکی','استیجاری','واگذاری','سایر'] as $value)<option value="{{ $value }}" @selected(old('ownership_type',$facility->ownership_type)===$value)>{{ $value }}</option>@endforeach</select></label>
                <label class="space-y-2"><span class="font-bold">کدپستی</span><input name="postal_code" inputmode="numeric" maxlength="10" required value="{{ old('postal_code',$facility->postal_code ?: $company->postal_code) }}" class="w-full rounded-xl border p-3" dir="ltr"></label>
                <label class="space-y-2"><span class="font-bold">تلفن محل</span><input name="phone" value="{{ old('phone',$facility->phone ?: $company->phone) }}" class="w-full rounded-xl border p-3" dir="ltr"></label>
                <label class="space-y-2"><span class="font-bold">تاریخ شروع اجاره یا مالکیت (شمسی)</span><span class="relative block"><input type="text" readonly autocomplete="off" name="started_on_jalali" value="{{ old('started_on_jalali',$facility->started_on?verta($facility->started_on)->format('Y/m/d'):'') }}" class="jalali-picker w-full rounded-xl border py-3 pl-11 pr-3 text-center" dir="ltr" placeholder="انتخاب تاریخ"><span class="pointer-events-none absolute left-3 top-3 text-lg">🗓️</span></span></label>
                <label class="space-y-2 md:col-span-3"><span class="font-bold">نشانی کامل</span><textarea name="address" required rows="3" class="w-full rounded-xl border p-3">{{ old('address',$facility->address ?: $company->address_fa) }}</textarea></label>
            </div>
        </section>

        <section class="rounded-3xl border bg-white p-6">
            <h2 class="font-black text-emerald-700">امکانات موجود</h2><p class="mt-2 text-sm text-slate-500">حداقل یک مورد را انتخاب و مساحت آن را به مترمربع وارد کنید.</p>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach($facilityTypes as $index=>$type)
                    @php($saved=$savedFacilities->get($type))
                    <div class="facility-row flex items-center gap-3 rounded-xl border p-3">
                        <input type="checkbox" class="facility-toggle h-5 w-5" @checked($saved)>
                        <input type="hidden" class="facility-type" name="facilities[{{ $index }}][type]" value="{{ $type }}" @disabled(!$saved)>
                        <span class="flex-1 font-bold">{{ $type }}</span>
                        <label class="flex items-center gap-2"><input type="number" min="0.01" max="999999.99" step="0.01" class="facility-area w-32 rounded-lg border p-2" name="facilities[{{ $index }}][area]" value="{{ data_get($saved,'area') }}" placeholder="مساحت" @disabled(!$saved)><span class="text-xs text-slate-500">مترمربع</span></label>
                    </div>
                @endforeach
            </div>
            <label class="mt-5 block space-y-2"><span class="font-bold">توضیحات</span><textarea name="notes" rows="3" class="w-full rounded-xl border p-3">{{ old('notes',$facility->notes) }}</textarea></label>
        </section>
    </fieldset>
    @if($editable)<button class="rounded-xl bg-emerald-700 px-7 py-3 font-black text-white">ذخیره محل و امکانات</button>@endif
</form>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/persian-date.vendor.min.js') }}"></script><script src="{{ asset('assets/js/persian-datepicker.vendor.min.js') }}"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('.facility-row').forEach(row=>{const toggle=row.querySelector('.facility-toggle'),type=row.querySelector('.facility-type'),area=row.querySelector('.facility-area');const sync=()=>{type.disabled=!toggle.checked;area.disabled=!toggle.checked;area.required=toggle.checked;if(!toggle.checked)area.value='';};toggle.addEventListener('change',sync);sync();});if(window.jQuery&&typeof jQuery.fn.pDatepicker==='function'){const placePicker=model=>requestAnimationFrame(()=>{const box=model.view.$container,inputRect=model.inputElement.getBoundingClientRect(),plot=box.find('.datepicker-plot-area')[0];if(!plot)return;const plotRect=plot.getBoundingClientRect(),above=window.innerHeight-inputRect.bottom<plotRect.height&&inputRect.top>plotRect.height,currentTop=parseFloat(box.css('top'))||0,delta=above?inputRect.top-4-plotRect.bottom:inputRect.bottom+4-plotRect.top;box.css('top',(currentTop+delta)+'px');});jQuery('.jalali-picker').pDatepicker({format:'YYYY/MM/DD',initialValue:true,initialValueType:'persian',autoClose:true,responsive:true,onlySelectOnDate:true,calendarType:'persian',calendar:{persian:{locale:'fa',showHint:false,leapYearMode:'algorithmic'},gregorian:{showHint:false}},navigator:{enabled:true,scroll:{enabled:true}},toolbox:{enabled:true,calendarSwitch:{enabled:false},todayButton:{enabled:true},submitButton:{enabled:false}},timePicker:{enabled:false},onShow:placePicker});const close=e=>{if(e.target instanceof Element&&e.target.closest('.datepicker-container,.datepicker-plot-area'))return;jQuery('.jalali-picker').each(function(){jQuery(this).data('datepicker')?.hide();});};window.addEventListener('wheel',close,{passive:true,capture:true});window.addEventListener('touchmove',close,{passive:true,capture:true});}});</script>
@endsection
