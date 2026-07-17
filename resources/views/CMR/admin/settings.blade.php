@extends('layouts.admin')
@section('header_title', 'تعرفه e-CMR')
@section('content')
@include('CMR.admin.partials.module-header', ['title' => 'تعرفه و امور مالی e-CMR', 'subtitle' => 'مدیریت هزینه صدور و تاریخچه تغییر تعرفه'])
<form method="POST" action="{{ route('admin.cmr.settings.update') }}" class="max-w-xl space-y-4 rounded-2xl border bg-white p-6" dir="rtl">@csrf @method('PUT')
@if(session('success'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-700">{{ session('success') }}</div>@endif
<label class="flex items-center gap-2"><input type="checkbox" name="billing_enabled" value="1" @checked($settings->billing_enabled)> کسر هزینه هنگام صدور فعال باشد</label><label class="block">مبلغ صدور<input class="mt-1 w-full rounded-xl border p-3" type="number" min="0" step="1" name="issuance_fee" value="{{ $settings->issuance_fee }}" required></label><label class="block">ارز<input class="mt-1 w-full rounded-xl border p-3" name="currency" maxlength="3" value="{{ $settings->currency }}" required></label><p class="text-sm text-amber-700">تعرفه جدید فقط روی صدورهای بعدی اثر دارد. لغو سند صادرشده بدون استرداد است.</p><button class="rounded-xl bg-sky-600 px-5 py-3 font-black text-white">ذخیره تنظیمات</button></form>
<div class="mt-5 max-w-3xl rounded-2xl border bg-white p-5" dir="rtl"><h3 class="mb-3 font-black">تاریخچه تعرفه</h3>@forelse($history as $item)<div class="grid grid-cols-4 gap-2 border-t py-2 text-sm"><span>{{ number_format((float)$item->issuance_fee) }} {{ $item->currency }}</span><span>{{ $item->billing_enabled ? 'فعال' : 'غیرفعال' }}</span><span>کاربر #{{ $item->changed_by ?: '—' }}</span><span>{{ $item->effective_from }}</span></div>@empty<p class="text-slate-400">هنوز تغییری ثبت نشده است.</p>@endforelse</div>
@endsection
