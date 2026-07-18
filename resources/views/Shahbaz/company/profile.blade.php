@extends('layouts.app')
@section('header_title','تکمیل و تأیید اطلاعات شرکت در شحباز داخلی')
@section('content')
<div dir="rtl" class="mx-auto max-w-5xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
  <h1 class="text-xl font-black">اطلاعات مرکزی شرکت</h1>
  <p class="mt-2 text-sm text-slate-600">فعال‌شدن دوزوله و CMR منوط به تکمیل این فرم، کنترل دستی شحباز و تأیید انجمن است.</p>
  @if(session('warning'))<div class="mt-4 rounded-xl bg-amber-50 p-4 text-amber-800">{{ session('warning') }}</div>@endif
  @if(session('success'))<div class="mt-4 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="mt-4 rounded-xl bg-rose-50 p-4 text-rose-800">{{ $errors->first() }}</div>@endif
  <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm"><b>وضعیت:</b> {{ $company->shahbaz_verification_status }} @if($company->shahbaz_review_note)<div class="mt-2 text-rose-700">{{ $company->shahbaz_review_note }}</div>@endif</div>
  <form method="POST" action="{{ route('company.shahbaz.profile.update') }}" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">@csrf @method('PUT')
    @php($fields=['name_fa'=>'نام رسمی فارسی','name_en'=>'نام لاتین','national_id'=>'شناسه ملی','registration_number'=>'شماره ثبت','ceo_name'=>'نام مدیرعامل','ceo_national_code'=>'کد ملی مدیرعامل','ceo_mobile'=>'موبایل مدیرعامل','phone'=>'تلفن شرکت','postal_code'=>'کدپستی','province'=>'استان','city'=>'شهر','activity_type'=>'نوع فعالیت'])
    @foreach($fields as $name=>$label)<label class="text-sm font-bold">{{ $label }}<input name="{{ $name }}" value="{{ old($name,$company->$name) }}" required class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"></label>@endforeach
    <label class="text-sm font-bold md:col-span-2">آدرس فارسی<textarea name="address_fa" required class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('address_fa',$company->address_fa) }}</textarea></label>
    <label class="text-sm font-bold md:col-span-2">آدرس لاتین<textarea name="address_en" required dir="ltr" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-left">{{ old('address_en',$company->address_en) }}</textarea></label>
    <div class="md:col-span-2"><button class="rounded-xl bg-blue-600 px-6 py-3 font-black text-white">ذخیره و ارسال برای بررسی انجمن</button></div>
  </form>
</div>
@endsection
