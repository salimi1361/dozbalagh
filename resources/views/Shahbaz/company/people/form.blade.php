@extends('layouts.app')
@php
    $titles=['personnel'=>'پرسنل','board'=>'عضو هیئت‌مدیره','shareholders'=>'سهامدار'];
    $selectedJob=old('job_title_choice', in_array($item->job_title,$jobTitles ?? [],true) ? $item->job_title : ($item->job_title ? '__other__' : ''));
@endphp
@section('header_title',($item->exists?'ویرایش ':'ایجاد ').$titles[$type])
@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.vendor.min.css') }}">
<style>.datepicker-plot-area{font-family:'Vazirmatn',Tahoma,sans-serif!important;z-index:99999!important}.jalali-picker{cursor:pointer}</style>
<form dir="rtl" method="POST" action="{{ $item->exists?route('company.shahbaz.people.update',[$type,$item]):route('company.shahbaz.people.store',$type) }}" class="mx-auto max-w-6xl space-y-5">
@csrf @if($item->exists)@method('PUT')@endif
@if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="rounded-3xl border bg-white p-6">
    <h2 class="font-black text-emerald-700">مشخصات شناسنامه‌ای</h2>
    <div class="mt-5 grid gap-4 md:grid-cols-3">
        @php($fields=['nationality'=>'تابعیت','national_code'=>'کد ملی','passport_number'=>'گذرنامه','first_name'=>'نام','last_name'=>'نام خانوادگی','father_name'=>'نام پدر','birth_certificate_number'=>'شماره شناسنامه','birth_place'=>'محل تولد','issue_city'=>'شهر صدور'])
        @foreach($fields as $name=>$label)<label class="space-y-2"><span class="font-bold">{{ $label }}</span><input type="text" name="{{ $name }}" value="{{ old($name,$person->$name) }}" class="w-full rounded-xl border p-3"></label>@endforeach
        <label class="space-y-2"><span class="font-bold">تاریخ تولد (شمسی)</span><span class="relative block"><input type="text" readonly autocomplete="off" name="birth_date_jalali" value="{{ old('birth_date_jalali',$person->birth_date?verta($person->birth_date)->format('Y/m/d'):'') }}" class="jalali-picker w-full rounded-xl border py-3 pl-11 pr-3 text-center" dir="ltr" placeholder="انتخاب تاریخ تولد"><span class="pointer-events-none absolute left-3 top-3 text-lg">🗓️</span></span></label>
        <label class="space-y-2"><span class="font-bold">تاریخ صدور (شمسی)</span><span class="relative block"><input type="text" readonly autocomplete="off" name="issued_on_jalali" value="{{ old('issued_on_jalali',$person->issued_on?verta($person->issued_on)->format('Y/m/d'):'') }}" class="jalali-picker w-full rounded-xl border py-3 pl-11 pr-3 text-center" dir="ltr" placeholder="انتخاب تاریخ صدور"><span class="pointer-events-none absolute left-3 top-3 text-lg">🗓️</span></span></label>
        <label class="space-y-2"><span class="font-bold">جنسیت</span><select name="gender" class="w-full rounded-xl border p-3"><option value="">انتخاب</option><option value="مرد" @selected(old('gender',$person->gender)==='مرد')>مرد</option><option value="زن" @selected(old('gender',$person->gender)==='زن')>زن</option></select></label>
        <label class="flex items-center gap-2 pt-8"><input type="checkbox" name="is_veteran" value="1" @checked(old('is_veteran',$person->is_veteran))> شرایط ایثارگری</label>
    </div>
</section>
<section class="rounded-3xl border bg-white p-6">
    <h2 class="font-black text-emerald-700">اطلاعات شغلی</h2>
    <div class="mt-5 grid gap-4 md:grid-cols-3">
        @if($type==='personnel')
            <label class="space-y-2"><span class="font-bold">عنوان شغلی</span><select id="job_title_choice" name="job_title_choice" required class="w-full rounded-xl border p-3"><option value="">انتخاب کنید</option>@foreach($jobTitles as $jobTitle)<option value="{{ $jobTitle }}" @selected($selectedJob===$jobTitle)>{{ $jobTitle }}</option>@endforeach<option value="__other__" @selected($selectedJob==='__other__')>سایر</option></select></label>
            <label id="custom_job_title_wrap" class="space-y-2 {{ $selectedJob==='__other__'?'':'hidden' }}"><span class="font-bold">عنوان شغلی سایر</span><input name="custom_job_title" value="{{ old('custom_job_title',$selectedJob==='__other__'?$item->job_title:'') }}" maxlength="150" class="w-full rounded-xl border p-3"></label>
        @elseif($type==='board')
            <label class="space-y-2"><span class="font-bold">سمت</span><input name="board_position" required value="{{ old('board_position',$item->board_position) }}" class="w-full rounded-xl border p-3"></label>
        @else
            <label class="space-y-2"><span class="font-bold">نوع سهامدار</span><input name="shareholder_type" required value="{{ old('shareholder_type',$item->shareholder_type) }}" class="w-full rounded-xl border p-3"></label><label class="space-y-2"><span class="font-bold">نوع سهام</span><input name="share_type" value="{{ old('share_type',$item->share_type) }}" class="w-full rounded-xl border p-3"></label><label class="space-y-2"><span class="font-bold">مبلغ سهام</span><input type="number" name="share_amount" value="{{ old('share_amount',$item->share_amount) }}" class="w-full rounded-xl border p-3"></label><label class="space-y-2"><span class="font-bold">درصد سهام</span><input type="number" step="0.0001" name="share_percentage" value="{{ old('share_percentage',$item->share_percentage) }}" class="w-full rounded-xl border p-3"></label>
        @endif
        <label class="space-y-2"><span class="font-bold">تاریخ شروع همکاری (شمسی)</span><span class="relative block"><input type="text" readonly autocomplete="off" name="started_on_jalali" value="{{ old('started_on_jalali',$item->started_on?verta($item->started_on)->format('Y/m/d'):'') }}" class="jalali-picker w-full rounded-xl border py-3 pl-11 pr-3 text-center" dir="ltr" placeholder="انتخاب تاریخ شروع"><span class="pointer-events-none absolute left-3 top-3 text-lg">🗓️</span></span></label>
        <label class="space-y-2"><span class="font-bold">تاریخ پایان همکاری (شمسی)</span><span class="relative block"><input type="text" readonly autocomplete="off" name="ended_on_jalali" value="{{ old('ended_on_jalali',$item->ended_on?verta($item->ended_on)->format('Y/m/d'):'') }}" class="jalali-picker w-full rounded-xl border py-3 pl-11 pr-3 text-center" dir="ltr" placeholder="انتخاب تاریخ پایان"><span class="pointer-events-none absolute left-3 top-3 text-lg">🗓️</span></span></label>
    </div>
</section>
<div class="flex gap-3"><button class="rounded-xl bg-emerald-600 px-7 py-3 font-black text-white">ذخیره</button><a href="{{ route('company.shahbaz.people.index',$type) }}" class="rounded-xl border px-7 py-3 font-bold">انصراف</a></div>
</form>
@endsection
@section('scripts')
<script src="{{ asset('assets/js/persian-date.vendor.min.js') }}"></script><script src="{{ asset('assets/js/persian-datepicker.vendor.min.js') }}"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{if(window.jQuery&&typeof jQuery.fn.pDatepicker==='function'){const placePicker=model=>requestAnimationFrame(()=>{const input=jQuery(model.inputElement),box=model.view.$container,rect=model.inputElement.getBoundingClientRect(),height=box.outerHeight()||340,spaceBelow=window.innerHeight-rect.bottom;box.css('top',(spaceBelow<height&&rect.top>height?input.offset().top-height-8:input.offset().top+input.outerHeight()+8)+'px');});const pickerOptions={format:'YYYY/MM/DD',initialValue:true,initialValueType:'persian',autoClose:true,responsive:true,onlySelectOnDate:true,calendarType:'persian',calendar:{persian:{locale:'fa',showHint:false,leapYearMode:'algorithmic'},gregorian:{showHint:false}},navigator:{enabled:true,scroll:{enabled:true}},toolbox:{enabled:true,calendarSwitch:{enabled:false},todayButton:{enabled:true},submitButton:{enabled:false}},timePicker:{enabled:false},onShow:placePicker};jQuery('.jalali-picker').each(function(){jQuery(this).pDatepicker(pickerOptions);});const closeOnOutsideMove=e=>{if(e.target instanceof Element&&e.target.closest('.datepicker-container,.datepicker-plot-area'))return;jQuery('.jalali-picker').each(function(){const picker=jQuery(this).data('datepicker');if(picker)picker.hide();});};window.addEventListener('wheel',closeOnOutsideMove,{passive:true,capture:true});window.addEventListener('touchmove',closeOnOutsideMove,{passive:true,capture:true});}@if($type==='personnel')const select=document.getElementById('job_title_choice'),wrap=document.getElementById('custom_job_title_wrap'),input=wrap.querySelector('input');const sync=()=>{const other=select.value==='__other__';wrap.classList.toggle('hidden',!other);input.required=other;if(!other)input.value='';};select.addEventListener('change',sync);sync();@endif});</script>
@endsection
