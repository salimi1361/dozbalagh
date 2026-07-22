@extends('layouts.admin')
@section('content')
<div dir="rtl" class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div><h1 class="text-2xl font-black text-slate-800">تنظیمات چاپ دوزوله</h1><p class="mt-2 text-sm text-slate-500">قالب مستقل برای هر کشور و نوع مجوز</p></div>
        <a href="{{ route('association.print-layouts.create') }}" class="rounded-xl bg-sky-600 px-5 py-3 text-sm font-bold text-white">قالب جدید</a>
    </div>
    @if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 p-4 text-emerald-700">{{ session('success') }}</div>@endif
    <div class="overflow-hidden rounded-2xl border bg-white"><table class="w-full text-right text-sm"><thead class="bg-slate-100"><tr><th class="p-4">عنوان</th><th>کشور</th><th>نوع مجوز</th><th>اندازه</th><th>نسخه</th><th></th></tr></thead><tbody>
    @forelse($layouts as $layout)<tr class="border-t"><td class="p-4 font-bold">{{ $layout->name }}</td><td>{{ $layout->country->name }}</td><td>{{ $permitTypeLabels[$layout->permit_type] ?? $layout->permit_type }}</td><td dir="ltr">{{ $layout->paper_width_mm }} × {{ $layout->paper_height_mm }} mm</td><td>{{ $layout->version }} {{ $layout->is_active ? '— فعال' : '— غیرفعال' }}</td><td><div class="flex items-center justify-center gap-3"><a class="font-bold text-sky-600" href="{{ route('association.print-layouts.edit', $layout) }}">ویرایش و جانمایی</a><form method="POST" action="{{ route('association.print-layouts.destroy', $layout) }}" data-delete-layout data-layout-name="{{ $layout->name }}">@csrf @method('DELETE')<button type="submit" class="rounded-lg bg-red-50 px-3 py-1.5 font-bold text-red-600 hover:bg-red-100">حذف</button></form></div></td></tr>
    @empty<tr><td colspan="6" class="p-12 text-center text-slate-400">هنوز قالبی ساخته نشده است.</td></tr>@endforelse
    </tbody></table></div>
</div>

<div id="delete-layout-modal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" dir="rtl" role="dialog" aria-modal="true" aria-labelledby="delete-layout-title">
    <div class="w-full max-w-md overflow-hidden rounded-3xl border border-white/70 bg-white shadow-2xl">
        <div class="p-6 sm:p-7">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.7 2.7 17a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/></svg>
            </div>
            <h2 id="delete-layout-title" class="mt-4 text-center text-xl font-black text-slate-800">حذف تنظیم چاپ</h2>
            <p class="mt-3 text-center text-sm leading-7 text-slate-600">قالب <strong id="delete-layout-name" class="text-slate-800"></strong> و تمام جانمایی‌های آن حذف می‌شود. این عملیات قابل بازگشت نیست.</p>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <button id="cancel-layout-delete" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">انصراف</button>
                <button id="confirm-layout-delete" type="button" class="rounded-xl bg-rose-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-rose-200 transition hover:bg-rose-700">بله، حذف شود</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('delete-layout-modal');
    const name = document.getElementById('delete-layout-name');
    const cancel = document.getElementById('cancel-layout-delete');
    const confirmDelete = document.getElementById('confirm-layout-delete');
    let pendingForm = null;

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        pendingForm = null;
    };

    document.querySelectorAll('form[data-delete-layout]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === '1') return;
            event.preventDefault();
            pendingForm = form;
            name.textContent = `«${form.dataset.layoutName}»`;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            cancel.focus();
        });
    });

    cancel.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
    confirmDelete.addEventListener('click', () => {
        if (!pendingForm) return;
        const form = pendingForm;
        form.dataset.confirmed = '1';
        closeModal();
        form.requestSubmit();
    });
});
</script>
@endsection
