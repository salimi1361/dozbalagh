@extends('layouts.admin')
@section('header_title', $editing ? 'ویرایش پیش‌نویس e-CMR' : 'ایجاد پیش‌نویس e-CMR')
@section('content')
@include('CMR.admin.partials.module-header', ['title' => $editing ? 'ویرایش پیش‌نویس e-CMR' : 'صدور e-CMR', 'subtitle' => 'ثبت مرحله‌ای اطلاعات استاندارد حمل بین‌المللی جاده‌ای'])
<div class="mb-4 flex justify-end" dir="rtl"><a href="{{ route('admin.cmr.help') }}" target="_blank" class="rounded-xl border border-emerald-600 bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-800">؟ راهنمای فارسی صدور e‑CMR</a></div>
<form method="POST" action="{{ $editing ? route('admin.cmr.update',$editing) : route('admin.cmr.store') }}" class="space-y-5" dir="rtl" id="cmr-form">@csrf @if($editing) @method('PUT') @endif
@if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-rose-700">{{ $errors->first() }}</div>@endif
<div id="recovery-banner" class="hidden items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
 <span>اطلاعات ذخیره‌شده‌ای از تکمیل قبلی این فرم وجود دارد.</span>
 <div class="flex gap-2"><button type="button" id="restore-recovery" class="rounded-lg bg-emerald-700 px-3 py-2 font-bold text-white">بازیابی اطلاعات</button><button type="button" id="discard-recovery" class="rounded-lg border border-amber-300 bg-white px-3 py-2 font-bold">حذف نسخه ذخیره‌شده</button></div>
</div>
<div class="rounded-2xl border bg-white p-4">
 <div class="flex flex-wrap gap-2 text-sm font-bold" id="steps">
  @foreach(['۱. تخصیص','۲. طرفین و مسیر','۳. کالا','۴. اسناد و هزینه‌ها','۵. بازبینی'] as $i=>$label)<button type="button" data-go="{{ $i }}" class="step-tab rounded-xl px-4 py-2 {{ $i===0?'bg-emerald-600 text-white':'bg-slate-100' }}">{{ $label }}</button>@endforeach
 </div>
 <p class="mt-3 text-xs text-amber-700">راهنما فارسی است، اما تمام اطلاعاتی که روی CMR چاپ می‌شوند باید با حروف لاتین وارد شوند.</p>
</div>

<section class="cmr-step space-y-4" data-step="0">
 <div class="grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-3">
  <label>شرکت<select id="cmr-company" name="company_id" class="mt-1 w-full rounded-xl border p-2" required>@foreach($companies as $company)<option value="{{ $company->id }}" @selected(old('company_id')==$company->id)>{{ $company->name_fa ?: $company->name }}</option>@endforeach</select></label>
  <label>راننده<select name="driver_id" class="mt-1 w-full rounded-xl border p-2"><option value="">انتخاب نشده</option>@foreach($drivers as $driver)<option value="{{ $driver->id }}" @selected(old('driver_id')==$driver->id)>{{ $driver->first_name_en }} {{ $driver->last_name_en }}</option>@endforeach</select></label>
  <label>ناوگان<select name="fleet_id" class="mt-1 w-full rounded-xl border p-2"><option value="">انتخاب نشده</option>@foreach($fleets as $fleet)<option value="{{ $fleet->id }}" @selected(old('fleet_id')==$fleet->id)>{{ collect([$fleet->truck_type, $fleet->transit_plate ? 'پلاک '.$fleet->transit_plate : null, $fleet->smart_card_number ? 'کارت '.$fleet->smart_card_number : null])->filter()->join(' | ') ?: '#'.$fleet->id }}</option>@endforeach</select></label>
  <label>زبان رسمی<input name="language" value="en" readonly class="mt-1 w-full rounded-xl border bg-slate-100 p-2" dir="ltr"></label>
  <div class="rounded-xl bg-indigo-50 p-3 text-sm text-indigo-800">شماره رسمی CMR هنگام صدور از شماره‌ها یا بازه ثبت‌شده شرکت تخصیص می‌یابد.</div>
  <label>دامنه عملیات حمل <span class="text-xs text-slate-500" dir="ltr">(Transport scope)</span><select name="transport_type" class="mt-1 w-full rounded-xl border p-2"><option value="International road carriage" @selected(old('transport_type','International road carriage')==='International road carriage')>حمل بین‌المللی جاده‌ای</option><option value="International road leg of combined transport" @selected(old('transport_type')==='International road leg of combined transport')>بخش جاده‌ای حمل ترکیبی بین‌المللی</option></select><small class="mt-1 block text-slate-500">این گزینه دامنه کاربرد سند CMR را مشخص می‌کند، نه نوع کامیون.</small></label>
 </div>
</section>

<section class="cmr-step hidden space-y-4" data-step="1">
 <div class="grid gap-3 xl:grid-cols-3">
 @foreach(['consignor'=>'مشخصات فرستنده','consignee'=>'مشخصات گیرنده','carrier'=>'مشخصات حمل‌کننده'] as $key=>$title)
 <div class="grid gap-2 rounded-2xl border bg-white p-4"><h3 class="font-black text-emerald-700">{{ $title }}</h3><select class="master-party rounded-xl border bg-emerald-50 p-2" data-type="{{ $key }}"><option value="">انتخاب از اطلاعات پایه</option>@foreach($masterParties->where('party_type',$key) as $party)<option data-company="{{ $party->company_id }}" data-name="{{ $party->legal_name }}" data-identifier="{{ $party->identifier }}" data-address="{{ $party->address }}" data-country="{{ $party->country_code }}">{{ $party->legal_name }}</option>@endforeach</select>
  <input class="rounded-xl border p-2" dir="ltr" name="{{ $key }}_name" value="{{ old($key.'_name') }}" placeholder="Legal name" required>
  <input class="rounded-xl border p-2" dir="ltr" name="{{ $key }}_identifier" value="{{ old($key.'_identifier') }}" placeholder="Registration / identifier">
  <input class="rounded-xl border p-2" dir="ltr" name="{{ $key }}_country_code" value="{{ old($key.'_country_code') }}" maxlength="2" placeholder="Country code, e.g. IR">
  <textarea rows="2" class="rounded-xl border p-2" dir="ltr" name="{{ $key }}_address" placeholder="Full address">{{ old($key.'_address') }}</textarea><label class="text-xs"><input type="checkbox" name="save_party[{{ $key }}]" value="1"> ذخیره برای استفاده بعدی</label>
 </div>@endforeach
 </div>
 <div class="grid gap-3 rounded-2xl border bg-white p-4 md:grid-cols-4">
  <label>محل تحویل کالا به حمل‌کننده<select class="master-location mt-1 w-full rounded-xl border bg-emerald-50 p-2" data-type="taking_over"><option value="">انتخاب از اطلاعات پایه</option>@foreach($masterLocations->where('location_type','taking_over') as $location)<option data-company="{{ $location->company_id }}" data-name="{{ $location->name }}">{{ $location->name }}</option>@endforeach</select><input dir="ltr" class="mt-1 w-full rounded-xl border p-2" name="taking_over_place" value="{{ old('taking_over_place') }}" required></label>
  <label>تاریخ تحویل<input class="mt-1 w-full rounded-xl border p-2" type="datetime-local" name="taking_over_at" value="{{ old('taking_over_at') }}"></label>
  <label>محل تحویل نهایی<select class="master-location mt-1 w-full rounded-xl border bg-emerald-50 p-2" data-type="delivery"><option value="">انتخاب از اطلاعات پایه</option>@foreach($masterLocations->where('location_type','delivery') as $location)<option data-company="{{ $location->company_id }}" data-name="{{ $location->name }}">{{ $location->name }}</option>@endforeach</select><input dir="ltr" class="mt-1 w-full rounded-xl border p-2" name="delivery_place" value="{{ old('delivery_place') }}" required></label>
  <label>تاریخ برنامه‌ریزی‌شده<input class="mt-1 w-full rounded-xl border p-2" type="datetime-local" name="planned_delivery_at" value="{{ old('planned_delivery_at') }}"></label>
 </div><label class="block text-sm"><input type="checkbox" name="save_locations" value="1"> مکان‌های واردشده در اطلاعات پایه ذخیره شوند</label>
</section>

<section class="cmr-step hidden space-y-4" data-step="2">
 <div id="goods-list" class="space-y-4"><div class="goods-row grid gap-3 rounded-2xl border bg-white p-5 md:grid-cols-4">
  <div class="flex items-center justify-between md:col-span-4"><h3 class="font-black text-emerald-700">مشخصات محموله ۱</h3><button type="button" class="remove-good hidden rounded-lg border border-rose-300 px-3 py-1 text-sm font-bold text-rose-700">حذف ردیف</button></div><select class="master-good rounded-xl border bg-emerald-50 p-2 md:col-span-4"><option value="">انتخاب کالا از اطلاعات پایه</option>@foreach($masterGoods as $template)<option data-company="{{ $template->company_id }}" data-description="{{ $template->description }}" data-package="{{ $template->package_type }}" data-code="{{ $template->commodity_code }}" data-un="{{ $template->un_number }}" data-adr="{{ $template->adr_class }}">{{ $template->name }}</option>@endforeach</select>
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
 <button type="button" id="add-good" class="rounded-xl border border-sky-600 px-4 py-2 font-bold text-sky-700">افزودن ردیف کالا</button><label class="mr-3 text-sm"><input type="checkbox" name="save_goods" value="1"> کالاهای جدید در اطلاعات پایه ذخیره شوند</label>
</section>

<section class="cmr-step hidden space-y-4" data-step="3">
 <div class="grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-2">
  <label>اسناد همراه <span dir="ltr" class="text-xs text-slate-500">Attached documents</span><textarea dir="ltr" name="attached_documents_text" class="mt-1 w-full rounded-xl border p-2" placeholder="Commercial invoice, Packing list, ...">{{ old('attached_documents_text') }}</textarea></label>
  <label>دستورهای فرستنده <span dir="ltr" class="text-xs text-slate-500">Sender's instructions</span><textarea dir="ltr" name="sender_instructions" class="mt-1 w-full rounded-xl border p-2">{{ old('sender_instructions') }}</textarea></label>
  <label>ملاحظات حمل‌کننده <span dir="ltr" class="text-xs text-slate-500">Carrier's reservations</span><textarea dir="ltr" name="carrier_reservations" class="mt-1 w-full rounded-xl border p-2">{{ old('carrier_reservations') }}</textarea></label>
  <label>توافق‌های ویژه <span dir="ltr" class="text-xs text-slate-500">Special agreements</span><textarea dir="ltr" name="special_agreements" class="mt-1 w-full rounded-xl border p-2">{{ old('special_agreements') }}</textarea></label>
  <label>نحوه پرداخت کرایه <span dir="ltr" class="text-xs text-slate-500">Carriage payment</span><select name="carriage_payment" class="mt-1 w-full rounded-xl border p-2"><option value="">انتخاب نشده</option><option value="paid">پرداخت‌شده — Carriage paid</option><option value="carriage_forward">پرداخت در مقصد — Carriage forward</option></select></label>
  <label>وجه هنگام تحویل <span dir="ltr" class="text-xs text-slate-500">Cash on delivery</span><input dir="ltr" type="number" min="0" step="0.01" name="cash_on_delivery" class="mt-1 w-full rounded-xl border p-2"></label>
  @foreach(['freight'=>'کرایه حمل — Freight','supplementary'=>'هزینه تکمیلی — Supplementary','customs'=>'هزینه گمرکی — Customs','other'=>'سایر هزینه‌ها — Other'] as $key=>$label)<label>{{ $label }}<input dir="ltr" type="number" min="0" step="0.01" name="charges[{{ $key }}]" class="mt-1 w-full rounded-xl border p-2"></label>@endforeach
  <label>محل تنظیم سند <span dir="ltr" class="text-xs text-slate-500">Established at</span><input dir="ltr" name="established_at_place" class="mt-1 w-full rounded-xl border p-2"></label>
  <label>تاریخ تنظیم سند <span dir="ltr" class="text-xs text-slate-500">Document date</span><input type="date" name="established_at_date" class="mt-1 w-full rounded-xl border p-2"></label>
 </div>
</section>

<section class="cmr-step hidden" data-step="4"><div class="rounded-2xl border bg-white p-6">
 <h3 class="text-lg font-black">بازبینی قبل از ایجاد پیش‌نویس</h3><p class="mt-2 text-sm text-slate-600">پس از ذخیره، نسخه استاندارد سند حمل را پیش‌نمایش می‌کنید. تا زمانی که «صدور رسمی» را نزنید هزینه‌ای از کیف پول کسر نمی‌شود.</p>
 <div class="mt-4 overflow-hidden rounded-xl border"><table class="w-full text-sm"><tbody id="cmr-review"></tbody></table></div>
 <p class="mt-3 text-xs text-slate-500">اطلاعات چاپی باید لاتین باشند. شماره رسمی هنگام صدور تخصیص می‌یابد و اصلاح سند صادرشده فقط به‌صورت نسخه‌دار انجام می‌شود.</p>
 <button class="mt-6 rounded-xl bg-emerald-600 px-6 py-3 font-black text-white">{{ $editing ? 'ذخیره تغییرات پیش‌نویس' : 'ذخیره پیش‌نویس و مشاهده پیش‌نمایش' }}</button>
</div></section>

<div class="flex justify-between"><button type="button" id="prev" class="hidden rounded-xl border border-slate-700 px-5 py-2 font-bold text-slate-800">مرحله قبل</button><button type="button" id="next" class="mr-auto rounded-xl bg-emerald-600 px-5 py-2 font-bold text-white shadow">مرحله بعد</button></div>
</form>
<script>
(()=>{let step=0;const sections=[...document.querySelectorAll('.cmr-step')],tabs=[...document.querySelectorAll('.step-tab')],next=document.getElementById('next'),prev=document.getElementById('prev');
function updateReview(){const value=name=>document.querySelector(`[name="${name}"]`)?.value||'—',selected=name=>document.querySelector(`[name="${name}"]`)?.selectedOptions?.[0]?.text||'—';const rows=[['شرکت',selected('company_id')],['راننده',selected('driver_id')],['ناوگان',selected('fleet_id')],['فرستنده',value('consignor_name')],['گیرنده',value('consignee_name')],['حمل‌کننده',value('carrier_name')],['مسیر',`${value('taking_over_place')} ← ${value('delivery_place')}`],['تعداد ردیف کالا',document.querySelectorAll('.goods-row').length],['نحوه پرداخت',selected('carriage_payment')]];document.getElementById('cmr-review').innerHTML=rows.map(([label,data],index)=>`<tr class="${index?'border-t':''}"><th class="w-1/3 bg-slate-50 p-3 text-right">${label}</th><td class="p-3" dir="auto">${data}</td></tr>`).join('')}
function show(i){step=Math.max(0,Math.min(4,i));sections.forEach((s,n)=>s.classList.toggle('hidden',n!==step));tabs.forEach((t,n)=>{t.classList.toggle('bg-emerald-600',n===step);t.classList.toggle('text-white',n===step);t.classList.toggle('bg-slate-100',n!==step)});prev.classList.toggle('hidden',step===0);next.classList.toggle('hidden',step===4);if(step===4)updateReview();window.scrollTo({top:0,behavior:'smooth'})}
next.onclick=()=>show(step+1);prev.onclick=()=>show(step-1);tabs.forEach(t=>t.onclick=()=>show(+t.dataset.go));
const goodsList=document.getElementById('goods-list');
function renumberGoods(){[...goodsList.children].forEach((row,i)=>{row.querySelector('h3').textContent=`مشخصات محموله ${i+1}`;row.querySelectorAll('[name^="goods["]').forEach(x=>x.name=x.name.replace(/goods\[\d+\]/,`goods[${i}]`));row.querySelector('.remove-good').classList.toggle('hidden',i===0)})}
document.getElementById('add-good').onclick=()=>{const row=goodsList.firstElementChild.cloneNode(true);row.querySelectorAll('input').forEach(x=>x.value='');row.querySelector('.master-good').value='';goodsList.appendChild(row);renumberGoods()};
goodsList.addEventListener('click',event=>{const button=event.target.closest('.remove-good');if(!button)return;button.closest('.goods-row').remove();renumberGoods()});
const company=document.getElementById('cmr-company');
function filterMasterData(){document.querySelectorAll('.master-party option[data-company],.master-location option[data-company],.master-good option[data-company]').forEach(option=>{option.hidden=option.dataset.company!==company.value;option.disabled=option.hidden});document.querySelectorAll('.master-party,.master-location,.master-good').forEach(select=>select.value='')}
company.addEventListener('change',filterMasterData);filterMasterData();
document.querySelectorAll('.master-party').forEach(select=>select.addEventListener('change',()=>{const option=select.selectedOptions[0],type=select.dataset.type;if(!option?.dataset.name)return;document.querySelector(`[name="${type}_name"]`).value=option.dataset.name||'';document.querySelector(`[name="${type}_identifier"]`).value=option.dataset.identifier||'';document.querySelector(`[name="${type}_address"]`).value=option.dataset.address||'';document.querySelector(`[name="${type}_country_code"]`).value=option.dataset.country||''}));
document.querySelectorAll('.master-location').forEach(select=>select.addEventListener('change',()=>{const option=select.selectedOptions[0],field=select.dataset.type==='taking_over'?'taking_over_place':'delivery_place';if(option?.dataset.name)document.querySelector(`[name="${field}"]`).value=option.dataset.name}));
goodsList.addEventListener('change',event=>{const select=event.target.closest('.master-good');if(!select)return;const option=select.selectedOptions[0],row=select.closest('.goods-row');if(!option?.dataset.description)return;row.querySelector('[name$="[description]"]').value=option.dataset.description||'';row.querySelector('[name$="[package_type]"]').value=option.dataset.package||'';row.querySelector('[name$="[commodity_code]"]').value=option.dataset.code||'';row.querySelector('[name$="[un_number]"]').value=option.dataset.un||'';row.querySelector('[name$="[adr_class]"]').value=option.dataset.adr||''});
const editing=@json($editing);
if(editing){Object.entries(editing).forEach(([key,value])=>{if(['goods','attached_documents'].includes(key)||value===null)return;const field=document.querySelector(`[name="${key}"]`);if(field)field.value=typeof value==='string'?value:value});const docs=document.querySelector('[name="attached_documents_text"]');if(docs)docs.value=(editing.attached_documents||[]).join('\n');(editing.goods||[]).forEach((good,index)=>{if(index>0)document.getElementById('add-good').click();const row=goodsList.children[index];Object.entries(good).forEach(([key,value])=>{const field=row?.querySelector(`[name$="[${key}]"]`);if(field&&value!==null)field.value=value})});filterMasterData()}

// Browser-side recovery protects an unfinished draft from refresh, connection loss, or session timeout.
const form=document.getElementById('cmr-form'),draftKey=`cmr-form:${editing?.id||'new'}`;
const saveRecovery=()=>{const fields={};new FormData(form).forEach((value,key)=>{if(key==='_token'||key==='_method')return;if(fields[key]===undefined)fields[key]=value;else fields[key]=[].concat(fields[key],value)});localStorage.setItem(draftKey,JSON.stringify({savedAt:new Date().toISOString(),fields}))};
if(!editing){try{const recovery=JSON.parse(localStorage.getItem(draftKey)||'null'),banner=document.getElementById('recovery-banner');if(recovery?.fields){banner.classList.remove('hidden');banner.classList.add('flex');document.getElementById('restore-recovery').onclick=()=>{const goodsIndexes=Object.keys(recovery.fields).map(key=>key.match(/^goods\[(\d+)\]/)?.[1]).filter(Boolean).map(Number);const maxGoods=Math.max(0,...goodsIndexes);while(goodsList.children.length<=maxGoods)document.getElementById('add-good').click();Object.entries(recovery.fields).forEach(([name,value])=>{const values=[].concat(value),controls=[...form.querySelectorAll(`[name="${CSS.escape(name)}"]`)];controls.forEach((control,index)=>{const restored=values[Math.min(index,values.length-1)];if(control.type==='checkbox'||control.type==='radio')control.checked=values.includes(control.value);else control.value=restored??''})});filterMasterData();banner.remove()};document.getElementById('discard-recovery').onclick=()=>{localStorage.removeItem(draftKey);banner.remove()}}}catch(error){localStorage.removeItem(draftKey)}}
let recoveryTimer;form.addEventListener('input',()=>{clearTimeout(recoveryTimer);recoveryTimer=setTimeout(saveRecovery,700)});form.addEventListener('change',saveRecovery);form.addEventListener('submit',()=>localStorage.removeItem(draftKey));
})();
</script>
@endsection
