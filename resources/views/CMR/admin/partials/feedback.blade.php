@php
 $cmrFeedbackType = $errors->any() ? 'error' : (session('success') ? 'success' : (session('error') ? 'error' : null));
 $cmrFeedbackText = $errors->any() ? $errors->first() : (session('success') ?: session('error'));
@endphp
@if($cmrFeedbackType)
<div id="cmr-toast" class="fixed left-5 top-5 z-[100] flex max-w-md items-start gap-3 rounded-2xl border bg-white p-4 shadow-2xl {{ $cmrFeedbackType === 'success' ? 'border-emerald-200' : 'border-rose-200' }}" dir="rtl" role="alert">
 <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xl text-white {{ $cmrFeedbackType === 'success' ? 'bg-emerald-600' : 'bg-rose-600' }}">{{ $cmrFeedbackType === 'success' ? '✓' : '!' }}</span>
 <div class="pt-1"><b class="block text-sm {{ $cmrFeedbackType === 'success' ? 'text-emerald-800' : 'text-rose-800' }}">{{ $cmrFeedbackType === 'success' ? 'عملیات با موفقیت انجام شد' : 'امکان انجام عملیات وجود ندارد' }}</b><p class="mt-1 text-xs leading-6 text-slate-600">{{ $cmrFeedbackText }}</p></div>
 <button type="button" class="mr-2 text-xl text-slate-400" onclick="this.parentElement.remove()">×</button>
</div>
@endif
<div id="cmr-confirm" class="fixed inset-0 z-[110] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm" dir="rtl" role="dialog" aria-modal="true">
 <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
  <div class="bg-gradient-to-l from-slate-950 to-emerald-950 p-5 text-white"><div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-2xl">؟</div><h3 id="cmr-confirm-title" class="mt-4 text-lg font-black">تأیید عملیات</h3></div>
  <div class="p-5"><p id="cmr-confirm-message" class="leading-7 text-slate-600"></p><div class="mt-6 flex gap-3"><button type="button" id="cmr-confirm-yes" class="flex-1 rounded-xl bg-emerald-700 px-4 py-3 font-black text-white">تأیید و ادامه</button><button type="button" id="cmr-confirm-no" class="rounded-xl border border-slate-300 px-5 py-3 font-bold text-slate-600">انصراف</button></div></div>
 </div>
</div>
<script>
(()=>{const modal=document.getElementById('cmr-confirm');if(!modal)return;const message=document.getElementById('cmr-confirm-message'),yes=document.getElementById('cmr-confirm-yes'),no=document.getElementById('cmr-confirm-no');let pending=null;
document.addEventListener('submit',event=>{const form=event.target.closest('form[data-confirm]');if(!form||form.dataset.confirmed==='1')return;event.preventDefault();pending=form;message.textContent=form.dataset.confirm;yes.textContent=form.dataset.confirmButton||'تأیید و ادامه';yes.className=`flex-1 rounded-xl px-4 py-3 font-black text-white ${form.dataset.confirmTone==='danger'?'bg-rose-600':'bg-emerald-700'}`;modal.classList.remove('hidden');modal.classList.add('flex')});
const close=()=>{pending=null;modal.classList.add('hidden');modal.classList.remove('flex')};no.onclick=close;modal.addEventListener('click',event=>{if(event.target===modal)close()});yes.onclick=()=>{if(!pending)return;const form=pending;form.dataset.confirmed='1';close();form.requestSubmit()};
const toast=document.getElementById('cmr-toast');if(toast)setTimeout(()=>{toast.style.opacity='0';toast.style.transform='translateY(-8px)';toast.style.transition='.3s';setTimeout(()=>toast.remove(),300)},5500);
})();
</script>
