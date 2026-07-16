@extends('layouts.admin')
@section('header_title', 'ایجاد پیش‌نویس e-CMR')
@section('content')
<form method="POST" action="{{ route('admin.cmr.store') }}" class="space-y-5" dir="rtl" id="cmr-form">@csrf
@if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-rose-700">{{ $errors->first() }}</div>@endif
<div class="rounded-2xl border bg-white p-4">
 <div class="flex flex-wrap gap-2 text-sm font-bold" id="steps">
  @foreach(['۱. تخصیص','۲. طرفین و مسیر','۳. کالا','۴. اسناد و هزینه‌ها','۵. بازبینی'] as $i=>$label)<button type="button" data-go="{{ $i }}" class="step-tab rounded-xl px-4 py-2 {{ $i===0?'bg-sky-600 text-white':'bg-slate-100' }}">{{ $label }}</button>@endforeach
 </div>
 <p class="mt-3 text-xs text-amber-700">راهنما فارسی است، اما تمام اطلاعاتی که روی CMR چاپ می‌شوند باید با حروف لاتین وارد شوند.</p>
</div>

<section class="cmr-step space-y-4" data-step="0">
 <div class="grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-3">
  <label>شرکت<select name="company_id" class="mt-1 w-full rounded-xl border p-2" required>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(old('company_id')==$company->id)>{{ $company->name_fa ?: $company->name }}</option>@endforeach</select></label>
  <label>راننده<select name="driver_id" class="mt-1 w-full rounded-xl border p-2"><option value="">انتخاب نشده</option>@foreach($drivers as $driver)<option value="{{ $driver->id }}" @selected(old('driver_id')==$driver->id)>{{ $driver->first_name_en }} {{ $driver->last_name_en }}</option>@endforeach</select></label>
  <label>ناوگان<select name="fleet_id" class="mt-1 w-full rounded-xl border p-2"><option value="">انتخاب نشده</option>@foreach($fleets as $fleet)<option value="{{ $fleet->id }}" @selected(old('fleet_id')==$fleet->id)>{{ $fleet->transit_plate ?: $fleet->smart_card_number ?: '#'.$fleet->id }}</option>@endforeach</select></label>
  <label>زبان رسمی<input name="language" value="en" readonly class="mt-1 w-full rounded-xl border bg-slate-100 p-2" dir="ltr"></label>
  <label>شماره سریال شرکت<input name="company_serial" value="{{ old('company_serial') }}" class="mt-1 w-full rounded-xl border p-2" dir="ltr" placeholder="Only for manual serial mode"></label>
  <label>نوع حمل<input name="transport_type" value="{{ old('transport_type','International road carriage') }}" class="mt-1 w-full rounded-xl border p-2" dir="ltr"></label>
 </div>
</section>

<section class="cmr-step hidden space-y-4" data-step="1">
 @foreach(['consignor'=>'فرستنده — خانه ۱','consignee'=>'گیرنده — خانه ۲','carrier'=>'حمل‌کننده — خانه ۱۶'] as $key=>$title)
 <div class="grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-3"><h3 class="md:col-span-3 font-black">{{ $title }}</h3>
  <input class="rounded-xl border p-2" dir="ltr" name="{{ $key }}_name" value="{{ old($key.'_name') }}" placeholder="Legal name" required>
  <input class="rounded-xl border p-2" dir="ltr" name="{{ $key }}_identifier" value="{{ old($key.'_identifier') }}" placeholder="Registration / identifier">
  <input class="rounded-xl border p-2" dir="ltr" name="{{ $key }}_country_code" value="{{ old($key.'_country_code') }}" maxlength="2" placeholder="Country code, e.g. IR">
  <textarea class="rounded-xl border p-2 md:col-span-3" dir="ltr" name="{{ $key }}_address" placeholder="Full address">{{ old($key.'_address') }}</textarea>
 </div>@endforeach
 <div class="grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-2">
  <label>محل تحویل کالا به حمل‌کننده — خانه ۴<input dir="ltr" class="mt-1 w-full rounded-xl border p-2" name="taking_over_place" value="{{ old('taking_over_place') }}" required></label>
  <label>تاریخ تحویل<input class="mt-1 w-full rounded-xl border p-2" type="datetime-local" name="taking_over_at" value="{{ old('taking_over_at') }}"></label>
  <label>محل تحویل نهایی — خانه ۳<input dir="ltr" class="mt-1 w-full rounded-xl border p-2" name="delivery_place" value="{{ old('delivery_place') }}" required></label>
  <label>تاریخ برنامه‌ریزی‌شده<input class="mt-1 w-full rounded-xl border p-2" type="datetime-local" name="planned_delivery_at" value="{{ old('planned_delivery_at') }}"></label>
 </div>
</section>

<section class="cmr-step hidden space-y-4" data-step="2">
 <div id="goods-list" class="space-y-4"><div class="goods-row grid gap-3 rounded-2xl border bg-white p-5 md:grid-cols-4">
  <div class="flex items-center justify-between md:col-span-4"><h3 class="font-black">ردیف کالای ۱ — خانه‌های ۶ تا ۱۲</h3><button type="button" class="remove-good hidden rounded-lg border border-rose-300 px-3 py-1 text-sm font-bold text-rose-700">حذف ردیف</button></div>
  <input dir="ltr" class="rounded-xl border p-2 md:col-span-2" name="goods[0][description]" placeholder="Nature of goods *" required>
  <input dir="ltr" class="rounded-xl border p-2" name="goods[0][marks_and_numbers]" placeholder="Marks and numbers">
  <input dir="ltr" class="rounded-xl border p-2" name="goods[0][package_type]" placeholder="Method of packing">
  <input dir="ltr" class="rounded-xl border p-2" type="number" step="0.001" min="0" name="goods[0][package_count]" placeholder="Packages">
  <input dir="ltr" class="rounded-xl border p-2" type="number" step="0.001" min="0" name="goods[0][gross_weight_kg]" placeholder="Gross weight kg">
  <input dir="ltr" class="rounded-xl border p-2" type="number" step="0.001" min="0" name="goods[0][volume_m3]" placeholder="Volume m³">
  <input dir="ltr" class="rounded-xl border p-2" name="goods[0][commodity_code]" placeholder="Commodity code">
  <input dir="ltr" class="rounded-xl border p-2" name="goods[0][un_number]" placeholder="UN number (ADR)">
  <input dir="ltr" class="rounded-xl border p-2" name="goods[0][adr_class]" placeholder="ADR class">
 </div></div>
 <button type="button" id="add-good" class="rounded-xl border border-sky-600 px-4 py-2 font-bold text-sky-700">افزودن ردیف کالا</button>
</section>

<section class="cmr-step hidden space-y-4" data-step="3">
 <div class="grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-2">
  <label>اسناد پیوست — خانه ۵<textarea dir="ltr" name="attached_documents_text" class="mt-1 w-full rounded-xl border p-2" placeholder="Commercial invoice, Packing list, ...">{{ old('attached_documents_text') }}</textarea></label>
  <label>دستورهای فرستنده — خانه ۱۳<textarea dir="ltr" name="sender_instructions" class="mt-1 w-full rounded-xl border p-2">{{ old('sender_instructions') }}</textarea></label>
  <label>ملاحظات حمل‌کننده — خانه ۱۸<textarea dir="ltr" name="carrier_reservations" class="mt-1 w-full rounded-xl border p-2">{{ old('carrier_reservations') }}</textarea></label>
  <label>توافق‌های ویژه — خانه ۱۹<textarea dir="ltr" name="special_agreements" class="mt-1 w-full rounded-xl border p-2">{{ old('special_agreements') }}</textarea></label>
  <label>پرداخت کرایه — خانه ۱۴<select name="carriage_payment" class="mt-1 w-full rounded-xl border p-2"><option value="">انتخاب نشده</option><option value="paid">Carriage paid</option><option value="carriage_forward">Carriage forward</option></select></label>
  <label>وجه هنگام تحویل — خانه ۱۵<input dir="ltr" type="number" min="0" step="0.01" name="cash_on_delivery" class="mt-1 w-full rounded-xl border p-2"></label>
  @foreach(['freight'=>'Freight','supplementary'=>'Supplementary','customs'=>'Customs','other'=>'Other'] as $key=>$label)<label>{{ $label }} — خانه ۲۰<input dir="ltr" type="number" min="0" step="0.01" name="charges[{{ $key }}]" class="mt-1 w-full rounded-xl border p-2"></label>@endforeach
  <label>محل تنظیم سند — خانه ۲۱<input dir="ltr" name="established_at_place" class="mt-1 w-full rounded-xl border p-2"></label>
  <label>تاریخ تنظیم سند<input type="date" name="established_at_date" class="mt-1 w-full rounded-xl border p-2"></label>
 </div>
</section>

<section class="cmr-step hidden" data-step="4"><div class="rounded-2xl border bg-white p-6">
 <h3 class="text-lg font-black">بازبینی قبل از ایجاد پیش‌نویس</h3><p class="mt-2 text-sm text-slate-600">پس از ذخیره، نسخه استاندارد ۲۴ خانه‌ای را پیش‌نمایش می‌کنید. تا زمانی که «صدور رسمی» را نزنید هزینه‌ای از کیف پول کسر نمی‌شود.</p>
 <ul class="mt-4 list-disc space-y-2 pr-5 text-sm"><li>نام‌ها و نشانی‌ها لاتین باشند.</li><li>راننده و ناوگان با سیاست شرکت کنترل می‌شوند.</li><li>شماره سریال در حالت خودکار هنگام صدور تخصیص می‌یابد.</li><li>پس از صدور، تغییر مستقیم مجاز نیست و اصلاح باید نسخه‌دار باشد.</li></ul>
 <button class="mt-6 rounded-xl bg-emerald-600 px-6 py-3 font-black text-white">ذخیره پیش‌نویس و مشاهده پیش‌نمایش</button>
</div></section>

<div class="flex justify-between"><button type="button" id="prev" class="hidden rounded-xl border px-5 py-2 font-bold">مرحله قبل</button><button type="button" id="next" class="mr-auto rounded-xl bg-sky-600 px-5 py-2 font-bold text-white">مرحله بعد</button></div>
</form>
<script>
(()=>{let step=0;const sections=[...document.querySelectorAll('.cmr-step')],tabs=[...document.querySelectorAll('.step-tab')],next=document.getElementById('next'),prev=document.getElementById('prev');
function show(i){step=Math.max(0,Math.min(4,i));sections.forEach((s,n)=>s.classList.toggle('hidden',n!==step));tabs.forEach((t,n)=>{t.classList.toggle('bg-sky-600',n===step);t.classList.toggle('text-white',n===step);t.classList.toggle('bg-slate-100',n!==step)});prev.classList.toggle('hidden',step===0);next.classList.toggle('hidden',step===4);window.scrollTo({top:0,behavior:'smooth'})}
next.onclick=()=>show(step+1);prev.onclick=()=>show(step-1);tabs.forEach(t=>t.onclick=()=>show(+t.dataset.go));
const goodsList=document.getElementById('goods-list');
function renumberGoods(){[...goodsList.children].forEach((row,i)=>{row.querySelector('h3').textContent=`ردیف کالای ${i+1} — خانه‌های ۶ تا ۱۲`;row.querySelectorAll('[name^="goods["]').forEach(x=>x.name=x.name.replace(/goods\[\d+\]/,`goods[${i}]`));row.querySelector('.remove-good').classList.toggle('hidden',i===0)})}
document.getElementById('add-good').onclick=()=>{const row=goodsList.firstElementChild.cloneNode(true);row.querySelectorAll('input').forEach(x=>x.value='');goodsList.appendChild(row);renumberGoods()};
goodsList.addEventListener('click',event=>{const button=event.target.closest('.remove-good');if(!button)return;button.closest('.goods-row').remove();renumberGoods()});
})();
</script>
@endsection
