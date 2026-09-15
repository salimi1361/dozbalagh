@extends('layouts.admin')

@section('header_title', 'گزارش دوزوله‌ها')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.vendor.min.css') }}">
<style>
    .datepicker-plot-area {
        font-family: 'Vazirmatn', Tahoma, sans-serif !important;
        z-index: 99999 !important;
        border: 1px solid #dbe3ef !important;
        border-radius: 14px !important;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .16) !important;
    }
</style>
@php
    $statusColors = [
        'pending' => 'bg-amber-50 text-amber-700 border-amber-200', 'under_review' => 'bg-amber-50 text-amber-700 border-amber-200',
        'approved' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'issued' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'company_returned' => 'bg-violet-50 text-violet-700 border-violet-200',
        'returned' => 'bg-orange-50 text-orange-700 border-orange-200', 'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
        'collected' => 'bg-sky-50 text-sky-700 border-sky-200', 'archived' => 'bg-slate-100 text-slate-700 border-slate-200',
        'lost' => 'bg-red-50 text-red-700 border-red-200',
    ];
@endphp
<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
        <div class="pointer-events-none absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
        <div class="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>
        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h15M7.5 13.5l3-3 2.25 2.25L16.5 9l2.25 2.25"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">گزارش جامع دوزوله‌ها</h1>
                    <p class="mt-1 text-xs font-semibold text-blue-100">مشاهده، فیلتر و دریافت خروجی اکسل از پرونده‌ها در همه وضعیت‌ها</p>
                </div>
            </div>
            <div class="flex min-w-28 items-center justify-between gap-4 rounded-xl border border-white border-opacity-10 bg-white bg-opacity-10 px-4 py-2.5 sm:block sm:text-center">
                <span class="text-[10px] font-bold text-blue-100">تعداد نتایج</span>
                <strong class="block text-xl font-black sm:mt-0.5">{{ number_format($permits->total()) }}</strong>
            </div>
        </div>
    </section>

    <form method="GET" action="{{ route('association.reports.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div><label class="mb-2 block text-xs font-black text-slate-600">جست‌وجو</label><input name="search" value="{{ request('search') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs outline-none focus:border-indigo-500" placeholder="کد رهگیری، سریال، شرکت، راننده، پلاک..."></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">وضعیت</label><select name="status" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"><option value="">همه وضعیت‌ها</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }} ({{ number_format($statusCounts[$value] ?? 0) }})</option>@endforeach</select></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">شرکت</label><select name="company_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"><option value="">همه شرکت‌ها</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name_fa ?: $company->name }}</option>@endforeach</select></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">کشور مقصد</label><select name="country_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"><option value="">همه کشورها</option>@foreach($countries as $country)<option value="{{ $country->id }}" @selected((string) request('country_id') === (string) $country->id)>{{ $country->name }}</option>@endforeach</select></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">نوع درخواست</label><select name="request_type" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"><option value="">همه انواع</option><option value="new" @selected(request('request_type') === 'new')>جدید</option><option value="renewal" @selected(request('request_type') === 'renewal')>تمدید</option></select></div>
            <div class="relative">
                <label class="mb-2 block text-xs font-black text-slate-600">از تاریخ صدور (شمسی)</label>
                <div class="relative"><input type="text" id="report_date_from_view" name="date_from_view" value="{{ request('date_from_view') }}" readonly class="jalali-picker w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 py-2.5 pr-10 pl-3 text-xs font-bold text-slate-700 outline-none focus:border-emerald-600" placeholder="انتخاب تاریخ شروع"><span class="pointer-events-none absolute inset-y-0 right-3 flex items-center">📅</span></div>
                <input type="hidden" id="report_date_from" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div class="relative">
                <label class="mb-2 block text-xs font-black text-slate-600">تا تاریخ صدور (شمسی)</label>
                <div class="relative"><input type="text" id="report_date_to_view" name="date_to_view" value="{{ request('date_to_view') }}" readonly class="jalali-picker w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 py-2.5 pr-10 pl-3 text-xs font-bold text-slate-700 outline-none focus:border-emerald-600" placeholder="انتخاب تاریخ پایان"><span class="pointer-events-none absolute inset-y-0 right-3 flex items-center">📅</span></div>
                <input type="hidden" id="report_date_to" name="date_to" value="{{ request('date_to') }}">
            </div>
            <div class="flex items-end gap-2"><button class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white">اعمال فیلتر</button><a href="{{ route('association.reports.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-600">پاک‌کردن</a></div>
        </div>
        <div class="mt-4 flex justify-end border-t border-slate-100 pt-4"><a href="{{ route('association.reports.export', request()->query()) }}" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-black text-white shadow-sm hover:bg-emerald-700">خروجی اکسل از نتایج فیلترشده</a></div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full text-right text-xs"><thead class="whitespace-nowrap bg-slate-100 text-slate-600"><tr><th class="p-4">کد رهگیری / سریال</th><th class="p-4">شرکت</th><th class="p-4">راننده</th><th class="p-4">ناوگان</th><th class="p-4">کشور</th><th class="p-4">نوع</th><th class="p-4">وضعیت</th><th class="p-4">تاریخ ثبت / صدور</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($permits as $permit)<tr class="hover:bg-slate-50"><td class="p-4"><strong class="block font-mono text-slate-800">{{ $permit->d_code ?: '#' . $permit->id }}</strong><span class="mt-1 block font-mono text-[10px] text-slate-400">{{ $permit->serial_number }}</span></td><td class="p-4 font-bold text-slate-700">{{ $permit->company_name ?: '-' }}</td><td class="p-4"><strong class="block text-slate-700">{{ trim(($permit->driver_first_name ?? '').' '.($permit->driver_last_name ?? '')) ?: '-' }}</strong><span class="text-[10px] text-slate-400">{{ $permit->driver_national_code }}</span></td><td class="p-4"><span class="block font-mono">{{ $permit->fleet_smart_card ?: '-' }}</span><span class="text-[10px] text-slate-400">{{ $permit->fleet_plate }}</span></td><td class="p-4">{{ $permit->country_name ?: '-' }}</td><td class="p-4">{{ ($permit->request_type ?? 'new') === 'renewal' ? 'تمدید' : 'جدید' }}</td><td class="p-4"><span class="whitespace-nowrap rounded-full border px-2 py-1 font-black {{ $statusColors[$permit->report_status] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">{{ $statusOptions[$permit->report_status] ?? $permit->report_status }}</span></td><td class="whitespace-nowrap p-4 text-slate-500"><span class="block">ثبت: {{ $permit->created_at ? verta($permit->created_at)->format('Y/m/d H:i') : '-' }}</span><span class="mt-1 block text-[10px]">صدور: {{ $permit->issued_at ? verta($permit->issued_at)->format('Y/m/d H:i') : '-' }}</span></td></tr>@empty<tr><td colspan="8" class="p-12 text-center font-bold text-slate-400">نتیجه‌ای با فیلترهای انتخاب‌شده پیدا نشد.</td></tr>@endforelse</tbody></table></div>
        @if($permits->hasPages())<div class="border-t border-slate-100 p-4">{{ $permits->links() }}</div>@endif
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/persian-date.vendor.min.js') }}"></script>
<script src="{{ asset('assets/js/persian-datepicker.vendor.min.js') }}"></script>
<script>
const reportDatepickerJQuery = window.jQuery;

document.addEventListener('DOMContentLoaded', function () {
    if (!reportDatepickerJQuery || typeof reportDatepickerJQuery.fn.pDatepicker !== 'function') return;

    const pickerJQuery = reportDatepickerJQuery;

    const placePicker = model => requestAnimationFrame(() => {
        const box = model.view.$container;
        const inputRect = model.inputElement.getBoundingClientRect();
        const plot = box.find('.datepicker-plot-area')[0];
        if (!plot) return;

        const plotRect = plot.getBoundingClientRect();
        const above = window.innerHeight - inputRect.bottom < plotRect.height && inputRect.top > plotRect.height;
        const currentTop = parseFloat(box.css('top')) || 0;
        const delta = above
            ? inputRect.top - 4 - plotRect.bottom
            : inputRect.bottom + 4 - plotRect.top;

        box.css('top', (currentTop + delta) + 'px');
    });

    const pickerOptions = {
        format: 'YYYY/MM/DD',
        initialValue: false,
        initialValueType: 'persian',
        autoClose: true,
        responsive: true,
        onlySelectOnDate: true,
        calendarType: 'persian',
        calendar: {
            persian: { locale: 'fa', showHint: false, leapYearMode: 'algorithmic' },
            gregorian: { showHint: false }
        },
        navigator: { enabled: true, scroll: { enabled: true } },
        toolbox: { enabled: true, calendarSwitch: { enabled: false }, todayButton: { enabled: true }, submitButton: { enabled: false } },
        timePicker: { enabled: false },
        onShow: placePicker
    };

    pickerJQuery('.jalali-picker').each(function () {
        const input = pickerJQuery(this);
        input.pDatepicker(Object.assign({}, pickerOptions, {
            onSelect: function (unixTimestamp) {
                const date = new Date(unixTimestamp);
                const gregorian = date.getFullYear() + '-'
                    + String(date.getMonth() + 1).padStart(2, '0') + '-'
                    + String(date.getDate()).padStart(2, '0');
                pickerJQuery(input.attr('id') === 'report_date_from_view' ? '#report_date_from' : '#report_date_to').val(gregorian);
            }
        }));
    });

    const closeOnOutsideMove = event => {
        if (event.target instanceof Element && event.target.closest('.datepicker-container, .datepicker-plot-area')) return;
        pickerJQuery('.jalali-picker').each(function () {
            const picker = pickerJQuery(this).data('datepicker');
            if (picker) picker.hide();
        });
    };

    window.addEventListener('wheel', closeOnOutsideMove, { passive: true, capture: true });
    window.addEventListener('touchmove', closeOnOutsideMove, { passive: true, capture: true });
});
</script>
@endsection
