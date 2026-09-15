@extends('layouts.admin')

@section('header_title', 'قالب‌های چاپ')
@section('hero_subtitle', 'مدیریت چیدمان، سربرگ و قالب خروجی اسناد انجمن')
@section('content')
@php
    $layoutTotal = $layouts->count();
    $activeLayouts = $layouts->filter(fn ($layout) => (bool) $layout->is_active)->count();
    $inactiveLayouts = max(0, $layoutTotal - $activeLayouts);
    $countryTotal = $layouts->pluck('country_id')->filter()->unique()->count();
    $permitTypeTotal = $layouts->pluck('permit_type')->filter()->unique()->count();
@endphp

<div dir="rtl" class="mx-auto max-w-7xl space-y-5 pb-10">
    <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
        <div class="pointer-events-none absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
        <div class="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>
        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3.75h7.5L19 8.25v12H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3.75v4.5h4.5M8.5 12h7M8.5 15.5h7"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">تنظیمات چاپ دوزوله</h1>
                    <p class="mt-1 text-xs font-semibold text-blue-100">مدیریت قالب مستقل برای هر کشور و نوع مجوز</p>
                </div>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <div class="flex min-w-[8rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                        <span class="text-[10px] font-bold text-blue-100">کل قالب‌ها</span>
                        <strong class="text-lg font-black">{{ number_format($layoutTotal) }}</strong>
                    </div>
                    <div class="flex min-w-[8rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                        <span class="text-[10px] font-bold text-blue-100">قالب فعال</span>
                        <strong class="text-lg font-black text-emerald-300">{{ number_format($activeLayouts) }}</strong>
                    </div>
                    <div class="flex min-w-[8rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                        <span class="text-[10px] font-bold text-blue-100">غیرفعال</span>
                        <strong class="text-lg font-black text-amber-200">{{ number_format($inactiveLayouts) }}</strong>
                    </div>
                    <div class="flex min-w-[8rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                        <span class="text-[10px] font-bold text-blue-100">کشورها</span>
                        <strong class="text-lg font-black text-cyan-200">{{ number_format($countryTotal) }}</strong>
                    </div>
                </div>
                <a href="{{ route('association.print-layouts.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-black text-blue-900 shadow-sm transition hover:bg-blue-50">
                    <span class="text-lg leading-none">+</span>
                    قالب جدید
                </a>
            </div>
        </div>
    </section>
    @if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 p-4 text-emerald-700">{{ session('success') }}</div>@endif
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-1 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-800">فهرست قالب‌های چاپ</h2>
                <p class="mt-1 text-xs font-medium text-slate-500">قالب مناسب کشور و نوع مجوز را انتخاب و جانمایی آن را ویرایش کنید.</p>
            </div>
            <span class="inline-flex w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ number_format($permitTypeTotal) }} نوع مجوز</span>
        </div>
        <div class="overflow-x-auto"><table class="w-full text-right text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="p-4">عنوان</th><th>کشور</th><th>نوع مجوز</th><th>اندازه</th><th>نسخه</th><th></th></tr></thead><tbody>
    @forelse($layouts as $layout)<tr class="border-t"><td class="p-4 font-bold">{{ $layout->name }}</td><td>{{ $layout->country->name }}</td><td>{{ $permitTypeLabels[$layout->permit_type] ?? $layout->permit_type }}</td><td dir="ltr">{{ $layout->paper_width_mm }} × {{ $layout->paper_height_mm }} mm</td><td>{{ $layout->version }} {{ $layout->is_active ? '— فعال' : '— غیرفعال' }}</td><td><div class="flex items-center justify-center gap-3"><a class="font-bold text-sky-600" href="{{ route('association.print-layouts.edit', $layout) }}">ویرایش و جانمایی</a><form method="POST" action="{{ route('association.print-layouts.destroy', $layout) }}" data-delete-layout data-layout-name="{{ $layout->name }}">@csrf @method('DELETE')<button type="submit" class="rounded-lg bg-red-50 px-3 py-1.5 font-bold text-red-600 hover:bg-red-100">حذف</button></form></div></td></tr>
    @empty<tr><td colspan="6" class="p-12 text-center text-slate-400">هنوز قالبی ساخته نشده است.</td></tr>@endforelse
    </tbody></table></div>
    </section>
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
