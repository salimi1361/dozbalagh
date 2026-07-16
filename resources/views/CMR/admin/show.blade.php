@extends('layouts.admin')
@section('header_title', 'جزئیات e-CMR')
@section('content')
<div class="space-y-5" dir="rtl">
@if(session('success'))<div class="rounded-xl bg-emerald-50 p-4 text-emerald-700">{{ session('success') }}</div>@endif
@if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-rose-700">{{ $errors->first() }}</div>@endif
<div class="flex justify-end"><a href="{{ route('admin.cmr.print', $cmr) }}" target="_blank" class="rounded-xl border border-sky-600 px-5 py-2 font-black text-sky-700">پیش‌نمایش برگه استاندارد ۲۴ خانه‌ای</a></div>
<div class="flex items-center justify-between rounded-2xl border bg-white p-5"><div><h2 class="text-xl font-black">{{ $cmr->number ?: 'پیش‌نویس #'.$cmr->id }}</h2><p class="text-sm text-slate-500">{{ $cmr->company->name_fa ?: $cmr->company->name }} — {{ $cmr->status }}</p></div>@if($cmr->status === 'draft')<form method="POST" action="{{ route('admin.cmr.issue',$cmr) }}">@csrf<button class="rounded-xl bg-emerald-600 px-5 py-3 font-black text-white" onclick="return confirm('CMR صادر و هزینه از کیف پول شرکت کسر شود؟')">صدور رسمی</button></form>@endif</div>
<div class="grid gap-4 md:grid-cols-3">@foreach([['فرستنده',$cmr->consignor_name,$cmr->consignor_address],['حمل‌کننده',$cmr->carrier_name,$cmr->carrier_address],['گیرنده',$cmr->consignee_name,$cmr->consignee_address]] as $party)<div class="rounded-2xl border bg-white p-5"><h3 class="font-black">{{ $party[0] }}</h3><p>{{ $party[1] }}</p><p class="text-sm text-slate-500">{{ $party[2] }}</p></div>@endforeach</div>
<div class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">کالاها</h3>@foreach($cmr->goods as $good)<div class="border-t py-2">{{ $good->line_number }}. {{ $good->description }} — {{ $good->package_count }} {{ $good->package_type }} — {{ $good->gross_weight_kg }} kg</div>@endforeach</div>
<div class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">سوابق</h3>@foreach($cmr->events as $event)<div class="border-t py-2 text-sm"><b>{{ $event->event_type }}</b> — {{ $event->description }} — {{ $event->occurred_at }}</div>@endforeach</div>
@if(in_array($cmr->status, ['draft','issued'], true))<form method="POST" action="{{ route('admin.cmr.cancel',$cmr) }}" class="rounded-2xl border border-rose-200 bg-rose-50 p-5">@csrf<label class="block font-black text-rose-800">لغو سند بدون استرداد وجه<textarea name="reason" required maxlength="2000" class="mt-2 w-full rounded-xl border border-rose-200 bg-white p-3" placeholder="دلیل لغو"></textarea></label><button class="mt-3 rounded-xl bg-rose-600 px-5 py-2 font-black text-white" onclick="return confirm('سند لغو شود؟ مبلغ صدور مسترد نخواهد شد.')">لغو قطعی</button></form>@endif
@if($cmr->integrity_hash)<div class="break-all rounded-2xl bg-slate-900 p-4 font-mono text-xs text-white">SHA-256: {{ $cmr->integrity_hash }}</div>@endif
</div>
@endsection
