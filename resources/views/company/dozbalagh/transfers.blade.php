@extends('layouts.app')

@section('content')
<div dir="rtl" class="mx-auto max-w-7xl space-y-5">
    <x-company.page-header
        title="دوزوله‌های انتقالی شرکت"
        subtitle="در این بخش فقط پرونده‌های جدیدی که به شرکت شما تخصیص یافته‌اند نمایش داده می‌شوند."
        icon="document"
    >
        <x-slot:actions>
            <div class="min-w-32 rounded-xl bg-white bg-opacity-15 px-4 py-2.5 text-center ring-1 ring-white ring-opacity-20">
                <div class="text-xs font-bold text-blue-100">تعداد پرونده‌ها</div>
                <div class="mt-1 text-xl font-black text-white">{{ number_format($transfers->total()) }}</div>
            </div>
        </x-slot:actions>
    </x-company.page-header>

    <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_auto]">
        <input
            name="search"
            value="{{ request('search') }}"
            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-inner outline-none placeholder:text-slate-500 focus:border-sky-600 focus:bg-white focus:ring-2 focus:ring-sky-100"
            placeholder="شماره دوزوله یا کد پرونده جدید"
        >
        <button class="rounded-xl bg-sky-700 px-6 py-2 font-bold text-white">جست‌وجو</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @forelse($transfers as $transfer)
            <div class="grid gap-4 border-b border-slate-100 p-5 last:border-0 md:grid-cols-[150px_1fr_190px] md:items-center">
                <div><div class="text-xs text-slate-500">شماره دوزوله</div><div class="mt-1 text-xl font-black text-slate-900">{{ $transfer->serial_number }}</div></div>
                <div>
                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">دریافت‌شده از انتقال</span>
                    <div class="mt-2 text-xs text-slate-500">پرونده جدید: {{ $transfer->destination_d_code }}</div>
                    <div class="mt-1 text-xs text-slate-400">تاریخ تخصیص: {{ verta($transfer->created_at)->format('Y/m/d H:i') }}</div>
                </div>
                <a href="{{ route('company.dozbalagh.item.show', $transfer->permit_request_item_id) }}" class="rounded-xl bg-sky-700 px-4 py-3 text-center text-xs font-black text-white">مشاهده پرونده جدید</a>
            </div>
        @empty
            <div class="p-12 text-center text-sm text-slate-500">دوزوله انتقالی برای این شرکت ثبت نشده است.</div>
        @endforelse
    </div>

    {{ $transfers->links() }}
</div>
@endsection
