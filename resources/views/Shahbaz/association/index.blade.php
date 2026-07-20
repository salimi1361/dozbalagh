@extends('layouts.admin')
@section('header_title','صف کنترل شرکت‌ها در شحباز داخلی')
@section('content')
@php($statusLabels=['pending_association_review'=>'در انتظار بررسی اولیه','ready_for_shahbaz_check'=>'آماده استعلام شحباز','correction_required'=>'برگشت برای رفع نقص','shahbaz_mismatch'=>'مغایرت شحباز','rejected'=>'رد شده'])
<div dir="rtl" class="rounded-2xl border bg-white p-6"><h1 class="text-xl font-black">صف بررسی پرونده شرکت‌ها</h1>
@if(session('success'))<div class="my-4 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
<div class="mt-5 overflow-x-auto"><table class="w-full text-right text-sm"><thead><tr class="border-b"><th class="p-3">شرکت</th><th>شناسه ملی</th><th>وضعیت</th><th></th></tr></thead><tbody>
@forelse($companies as $company)<tr class="border-b"><td class="p-3 font-bold">{{ $company->name_fa }}</td><td>{{ $company->national_id }}</td><td><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ $statusLabels[$company->shahbaz_verification_status] ?? $company->shahbaz_verification_status }}</span></td><td><a class="font-bold text-blue-700" href="{{ route('association.shahbaz.companies.show',$company) }}">مشاهده و بررسی</a></td></tr>@empty<tr><td colspan="4" class="p-8 text-center">پرونده‌ای در صف نیست.</td></tr>@endforelse
</tbody></table></div>{{ $companies->links() }}</div>
@endsection
