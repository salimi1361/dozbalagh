@extends('layouts.app')
@section('header_title', 'درخواست‌های پروانه فعالیت')
@section('content')
<div dir="rtl" class="mx-auto max-w-6xl space-y-5">
    @php($statusLabels=['draft'=>'پیش‌نویس','submitted'=>'ارسال‌شده','association_review'=>'در حال بررسی انجمن','correction_required'=>'نیازمند اصلاح','ready_for_shahbaz_check'=>'آماده استعلام شحباز','shahbaz_review'=>'در حال بررسی شحباز','shahbaz_mismatch'=>'دارای مغایرت','shahbaz_verified'=>'تأیید شحباز','ready_for_issue'=>'آماده صدور','issued'=>'صادر شده','rejected'=>'رد شده','cancelled'=>'لغو شده'])
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-white p-5">
        <div><h1 class="text-xl font-black">درخواست‌های {{ $company->name_fa }}</h1><p class="mt-1 text-sm text-slate-500">هر درخواست و تاریخچه آن مستقل نگهداری می‌شود.</p></div>
        <div class="flex gap-2"><a href="{{ route('company.shahbaz.dossier.show') }}" class="rounded-xl border px-4 py-2 font-bold">بازگشت به پرونده</a>@if($editable)<a href="{{ route('company.shahbaz.requests.create') }}" class="rounded-xl bg-emerald-700 px-4 py-2 font-bold text-white">ثبت درخواست جدید</a>@endif</div>
    </div>
    @if(session('success'))<div class="rounded-xl bg-emerald-50 p-4 font-bold text-emerald-800">{{ session('success') }}</div>@endif
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($items as $item)
            <article class="rounded-2xl border bg-white p-5 shadow-sm">
                <div class="flex justify-between gap-3"><strong>{{ $types[$item->request_type] ?? $item->request_type }}</strong><span class="rounded-full bg-slate-100 px-3 py-1 text-xs">{{ $statusLabels[$item->status] ?? $item->status }}</span></div>
                <dl class="mt-4 space-y-2 text-sm"><div>کد رهگیری: <b dir="ltr">{{ $item->tracking_code }}</b></div><div>حوزه فعالیت: {{ ['domestic'=>'داخلی','international'=>'بین‌المللی','both'=>'داخلی و بین‌المللی'][$item->activity_scope] ?? $item->activity_scope }}</div><div>نوع فعالیت: {{ $item->activity_type }}</div><div>تاریخ ثبت: {{ verta($item->created_at)->format('Y/m/d H:i') }}</div></dl>
                @if($item->correction_reason)<div class="mt-3 rounded-lg bg-rose-50 p-3 text-sm text-rose-800">دلیل نقص یا مغایرت: {{ $item->correction_reason }}</div>@endif
                @if($editable && in_array($item->status,['draft','correction_required','shahbaz_mismatch']))<a href="{{ route('company.shahbaz.requests.edit',$item) }}" class="mt-4 inline-block font-bold text-emerald-700">ویرایش درخواست</a>@endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed bg-white p-8 text-center text-slate-500 md:col-span-2 xl:col-span-3">هنوز درخواستی ثبت نشده است.</div>
        @endforelse
    </div>
</div>
@endsection
