@extends('layouts.app')
@section('header_title','پرونده شرکت و شحباز داخلی')
@section('content')
@php($statusLabels=['profile_incomplete'=>'پیش‌نویس پرونده','correction_required'=>'نیازمند اصلاح','pending_association_review'=>'در انتظار بررسی انجمن','ready_for_shahbaz_check'=>'آماده استعلام شحباز','shahbaz_mismatch'=>'دارای مغایرت شحباز','verified'=>'تأیید شده','rejected'=>'رد شده'])
@php($sectionRoutes = [
        'profile' => route('company.shahbaz.profile.edit'),
        'requests' => route('company.shahbaz.requests.index'),
        'fleet' => route('company.shahbaz.fleet.index'),
        'facilities' => route('company.shahbaz.facilities.edit'),
        'gazettes' => route('company.shahbaz.gazettes.index'),
        'registration' => route('company.shahbaz.registration.edit'),
        'licenses' => route('company.shahbaz.licenses.index'),
        'branches' => route('company.shahbaz.branches.index'),
        'manual_status' => route('company.shahbaz.manual-status.show'),
        'misc_documents' => route('company.shahbaz.misc-documents.index'),
        'workflow' => route('company.shahbaz.workflow.show'),
    ])
<div dir="rtl" class="mx-auto max-w-6xl space-y-6">
<section class="rounded-3xl border bg-white p-6 shadow-sm"><div class="flex flex-wrap items-center justify-between gap-4"><div><h1 class="text-2xl font-black">{{ $company->name_fa }}</h1><p class="mt-2 text-sm text-slate-500">شناسه ملی: {{ $company->national_id ?: 'ثبت نشده' }} — وضعیت بررسی: {{ $statusLabels[$company->shahbaz_verification_status] ?? $company->shahbaz_verification_status }}</p></div><a href="{{ route('company.shahbaz.profile.edit') }}" class="rounded-xl bg-emerald-600 px-5 py-3 font-black text-white">تکمیل مشخصات پایه</a></div></section>
@if(in_array($company->shahbaz_verification_status,['correction_required','shahbaz_mismatch','rejected'],true) && $company->shahbaz_review_note)
<div class="rounded-2xl border border-rose-300 bg-rose-50 p-5 text-rose-900"><b>نتیجه بررسی انجمن:</b><p class="mt-2">{{ $company->shahbaz_review_note }}</p></div>
@endif
@if($reminders['panel_enabled'] && $company->activity_license_expires_on)
@php($remaining=today()->diffInDays($company->activity_license_expires_on,false))
@if($remaining <= 60)
<div class="rounded-2xl border border-amber-300 bg-amber-50 p-5 font-bold text-amber-900">
هشدار اعتبار پروانه:
@if($remaining < 0)
پروانه فعالیت منقضی شده است.
@else
فقط {{ $remaining }} روز تا پایان اعتبار باقی مانده است.
@endif
</div>
@endif
@endif
<section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@foreach($sections as $section)
@php($url = in_array($section['key'], ['personnel','board','shareholders'], true) ? route('company.shahbaz.people.index', $section['key']) : ($sectionRoutes[$section['key']] ?? null))
@php($sectionCorrections=$correctionsBySection->get($section['key'],collect()))
@php($hasCorrection=$sectionCorrections->isNotEmpty())
<article class="rounded-2xl border p-5 shadow-sm {{ $hasCorrection ? 'border-rose-300 bg-rose-50' : ($section['locked'] ? 'border-slate-200 bg-slate-100 opacity-70' : ($section['complete'] ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-white')) }}">
<div class="flex items-center justify-between"><h2 class="font-black">{{ $loop->iteration }}. {{ $section['label'] }}</h2><span class="rounded-full px-3 py-1 text-xs font-bold {{ $hasCorrection ? 'bg-rose-200 text-rose-900' : ($section['complete'] ? 'bg-emerald-200 text-emerald-900' : ($section['locked'] ? 'bg-slate-200 text-slate-600' : 'bg-amber-100 text-amber-900')) }}">{{ $hasCorrection ? 'اعلام نقص انجمن' : ($section['complete'] ? 'تکمیل شده' : ($section['locked'] ? 'قفل' : 'نیازمند تکمیل')) }}</span></div>
<p class="mt-3 text-sm text-slate-600">{{ $section['required'] ? 'این بخش برای ادامه فرایند اجباری است.' : 'این بخش اختیاری یا صرفاً نمایشی است.' }}</p>
@if($hasCorrection)<div class="mt-3 space-y-2">@foreach($sectionCorrections as $correction)<div class="rounded-lg border border-rose-200 bg-white p-3 text-sm text-rose-900"><b>دلیل نقص:</b> {{ $correction->note }}</div>@endforeach</div>@endif
@if((!$section['locked'] || $hasCorrection) && $url)
<a href="{{ $url }}" class="mt-4 inline-block font-bold text-emerald-700">{{ $section['company_read_only'] ? 'مشاهده وضعیت' : 'ورود به بخش' }}</a>
@elseif(!$section['locked'])
<span class="mt-4 inline-block text-xs font-bold text-slate-400">فرم این بخش در فاز بعد فعال می‌شود.</span>
@endif
</article>
@endforeach
</section>
@if(in_array($company->shahbaz_verification_status,['profile_incomplete','correction_required','shahbaz_mismatch']))
<form method="POST" action="{{ route('company.shahbaz.dossier.submit') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">@csrf
@if($errors->has('dossier'))<div class="mb-3 font-bold text-rose-700">{{ $errors->first('dossier') }}</div>@endif
<button class="rounded-xl bg-emerald-700 px-7 py-3 font-black text-white">ارسال نهایی پرونده برای بررسی انجمن</button><p class="mt-2 text-xs text-emerald-900">پس از ارسال، پرونده تا اعلام نتیجه انجمن فقط قابل مشاهده خواهد بود.</p></form>
@endif
</div>
@endsection
