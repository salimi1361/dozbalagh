@extends('layouts.app')
@section('header_title', $item->exists ? 'ویرایش درخواست پروانه' : 'ثبت درخواست پروانه')
@section('content')
<div dir="rtl" class="mx-auto max-w-3xl rounded-2xl border bg-white p-6 shadow-sm">
    <h1 class="mb-5 text-xl font-black">{{ $item->exists ? 'ویرایش درخواست '.$item->tracking_code : 'درخواست جدید پروانه فعالیت' }}</h1>
    @if($errors->any())<div class="mb-5 rounded-xl bg-rose-50 p-4 text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $item->exists ? route('company.shahbaz.requests.update',$item) : route('company.shahbaz.requests.store') }}" class="grid gap-5 md:grid-cols-2">@csrf @if($item->exists)@method('PUT')@endif
        <label class="space-y-2"><span class="font-bold">نوع درخواست</span><select name="request_type" required class="w-full rounded-xl border p-3"><option value="">انتخاب کنید</option>@foreach($types as $value=>$label)<option value="{{ $value }}" @selected(old('request_type',$item->request_type)===$value)>{{ $label }}</option>@endforeach</select></label>
        <label class="space-y-2"><span class="font-bold">حوزه فعالیت</span><select name="activity_scope" required class="w-full rounded-xl border p-3">@foreach(['domestic'=>'داخلی','international'=>'بین‌المللی','both'=>'داخلی و بین‌المللی'] as $value=>$label)<option value="{{ $value }}" @selected(old('activity_scope',$item->activity_scope)===$value)>{{ $label }}</option>@endforeach</select></label>
        <label class="space-y-2 md:col-span-2"><span class="font-bold">نوع فعالیت</span><input name="activity_type" value="{{ old('activity_type',$item->activity_type) }}" required maxlength="100" class="w-full rounded-xl border p-3" placeholder="مثلاً حمل‌ونقل کالا"></label>
        <label class="space-y-2 md:col-span-2"><span class="font-bold">توضیحات شرکت</span><textarea name="company_description" rows="4" maxlength="3000" class="w-full rounded-xl border p-3">{{ old('company_description',$item->company_description) }}</textarea></label>
        @if($company->activity_license_number)<div class="rounded-xl bg-slate-50 p-4 text-sm md:col-span-2">پروانه فعلی: <b>{{ $company->activity_license_number }}</b> — برای تمدید به‌طور خودکار به همین پروانه مرتبط می‌شود.</div>@endif
        <div class="flex gap-3 md:col-span-2"><button class="rounded-xl bg-emerald-700 px-6 py-3 font-black text-white">ذخیره درخواست</button><a href="{{ route('company.shahbaz.requests.index') }}" class="rounded-xl border px-6 py-3 font-bold">انصراف</a></div>
    </form>
</div>
@endsection
