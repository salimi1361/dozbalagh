@extends('layouts.admin')
@section('header_title','تعرفه e-CMR')
@section('content')
@include('CMR.admin.partials.module-header',['title'=>'تعرفه و امور مالی e-CMR','subtitle'=>'کسر هزینه صدور از کیف پول شرکت و نگهداری تاریخچه تعرفه'])
<div class="space-y-5" dir="rtl">
@if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-rose-700">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('admin.cmr.settings.update') }}" class="max-w-2xl space-y-5 rounded-2xl border bg-white p-6">@csrf @method('PUT')
 <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"><label class="flex items-center gap-2 font-black text-emerald-900"><input type="checkbox" name="billing_enabled" value="1" @checked($settings->billing_enabled)> کسر هزینه هنگام صدور فعال باشد</label><p class="mt-2 text-sm text-emerald-800">با صدور رسمی هر CMR، مبلغ زیر مستقیماً از کیف پول شرکت صادرکننده کسر می‌شود.</p></div>
 <label class="block font-bold">مبلغ صدور به ریال<input id="cmr-fee" class="mt-1 w-full rounded-xl border p-3 text-left text-lg font-black" dir="ltr" type="number" min="0" step="1" name="issuance_fee" value="{{ (int)$settings->issuance_fee }}" required><span id="cmr-fee-preview" class="mt-2 block text-sm text-slate-500"></span></label>
 <div class="rounded-xl bg-slate-100 p-3 text-sm"><b>واحد مالی:</b> ریال ایران <span dir="ltr">(IRR)</span><input type="hidden" name="currency" value="IRR"></div>
 <p class="text-sm text-amber-700">تعرفه جدید فقط روی صدورهای بعدی اثر دارد و مبلغ هر سند هنگام صدور در همان سند قفل می‌شود. لغو سند بدون استرداد وجه است.</p>
 <button class="rounded-xl bg-emerald-700 px-5 py-3 font-black text-white">ذخیره تعرفه جاری</button>
</form>
<section class="rounded-2xl border bg-white p-5"><div><h3 class="font-black">تاریخچه تغییر تعرفه</h3><p class="mt-1 text-xs text-slate-500">هر بار ذخیره تعرفه جاری یک ردیف ثبت می‌کند. ردیف‌های اضافی را می‌توانید از نمایش حذف کنید.</p></div>
 <div class="mt-4 space-y-3">@forelse($history as $item)<div class="grid items-center gap-3 rounded-xl border p-4 text-sm md:grid-cols-[1fr_1fr_1fr_1fr_2fr]"><b>{{ number_format((float)$item->issuance_fee) }} ریال</b><span>{{ $item->billing_enabled?'فعال':'غیرفعال' }}</span><span>کاربر #{{ $item->changed_by?:'—' }}</span><span dir="ltr">{{ verta($item->effective_from)->format('Y/m/d H:i') }}</span><form method="POST" action="{{ route('admin.cmr.settings.history.destroy',$item) }}" class="flex gap-2" data-confirm="این ردیف از نمایش تاریخچه تعرفه حذف شود؟" data-confirm-button="حذف ردیف" data-confirm-tone="danger">@csrf @method('DELETE')<input name="deletion_reason" required class="min-w-0 flex-1 rounded-lg border p-2" placeholder="دلیل حذف"><button class="rounded-lg bg-rose-600 px-3 py-2 font-bold text-white">حذف</button></form></div>@empty<p class="rounded-xl bg-slate-50 p-6 text-center text-slate-400">هنوز تغییری ثبت نشده است.</p>@endforelse</div>
</section></div>
<script>const fee=document.getElementById('cmr-fee'),preview=document.getElementById('cmr-fee-preview');function renderFee(){preview.textContent=(Number(fee.value||0)).toLocaleString('fa-IR')+' ریال'}fee.addEventListener('input',renderFee);renderFee();</script>
@endsection
