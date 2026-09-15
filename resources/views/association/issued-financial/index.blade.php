@extends('layouts.admin')

@section('header_title', 'مبالغ دوزوله‌های صادرشده')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.vendor.min.css') }}">
<style>
    .datepicker-plot-area { font-family: 'Vazirmatn', Tahoma, sans-serif !important; z-index: 99999 !important; }
    .datepicker-plot-area .datepicker-day-view .table-days td span { border-radius: .5rem; }
    .jalali-picker { cursor: pointer; background-image: none; }
</style>
<div class="space-y-5">
    <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
        <div class="pointer-events-none absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
        <div class="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>
        <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.173 1.453-.342 1.453-1.09V5.239c0-.748-.726-1.263-1.453-1.09A60.057 60.057 0 0 0 2.25 6.25v12.5ZM12 9v6m3-3H9"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">گزارش مالی دوزوله‌های صادرشده</h1>
                    <p class="mt-1 text-xs font-semibold text-blue-100">مبالغ قطعی دوزوله‌های سریال‌دار و صادرشده، بدون موجودی یا مبالغ بلوکه‌شده</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 lg:min-w-[390px]">
                <div class="flex items-center justify-between gap-3 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 lg:block lg:text-center">
                    <span class="text-[10px] font-bold text-blue-100">تعداد صادرشده</span>
                    <strong class="text-lg font-black lg:mt-0.5 lg:block">{{ number_format($totalCount) }}</strong>
                </div>
                <div class="flex items-center justify-between gap-3 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 lg:block lg:text-center">
                    <span class="text-[10px] font-bold text-blue-100">جمع مبلغ</span>
                    <strong class="text-sm font-black text-emerald-300 lg:mt-0.5 lg:block">{{ number_format($totalAmount) }} <small class="text-[9px]">ریال</small></strong>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

    <form method="GET" action="{{ route('association.issued-financial.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div><label class="mb-2 block text-xs font-black text-slate-600">از تاریخ صدور (شمسی)</label><div class="relative"><input id="issued_date_from" type="text" name="date_from_jalali" value="{{ $dateFromJalali }}" placeholder="۱۴۰۵/۰۴/۰۱" dir="ltr" required readonly autocomplete="off" class="jalali-picker w-full rounded-xl border border-slate-200 bg-slate-50 px-10 py-2.5 text-center text-xs font-bold outline-none focus:border-emerald-500"><span class="pointer-events-none absolute left-3 top-2.5 text-slate-400">📅</span></div></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">تا تاریخ صدور (شمسی)</label><div class="relative"><input id="issued_date_to" type="text" name="date_to_jalali" value="{{ $dateToJalali }}" placeholder="۱۴۰۵/۰۴/۳۱" dir="ltr" required readonly autocomplete="off" class="jalali-picker w-full rounded-xl border border-slate-200 bg-slate-50 px-10 py-2.5 text-center text-xs font-bold outline-none focus:border-emerald-500"><span class="pointer-events-none absolute left-3 top-2.5 text-slate-400">📅</span></div></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">شرکت</label><select name="company_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"><option value="">همه شرکت‌ها</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string)$companyId === (string)$company->id)>{{ $company->name_fa ?: $company->name }}</option>@endforeach</select></div>
            <div class="flex items-end gap-2"><button class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white">نمایش گزارش</button><a href="{{ route('association.issued-financial.export', request()->query()) }}" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white">خروجی Excel</a></div>
        </div>
    </form>

    <div class="rounded-2xl border {{ $settlementWindowOpen ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white' }} p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div><h3 class="font-black text-slate-800">تسویه ماه بسته‌شده {{ $currentPeriod['label'] }}</h3><p class="mt-1 text-xs text-slate-500">پس از پایان ماه، مبلغ فقط از دوزوله‌های صادرشده همان ماه محاسبه و هنگام درخواست ثابت می‌شود.</p></div>
            @if($currentSettlement)
                <div class="rounded-xl px-4 py-3 text-xs font-black {{ $currentSettlement->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                    {{ $currentSettlement->status === 'paid' ? 'تسویه پرداخت و ثبت شده است' : 'درخواست تسویه ثبت شده و منتظر پرداخت است' }}
                </div>
            @elseif($settlementWindowOpen)
                <form method="POST" action="{{ route('association.issued-financial.request-settlement') }}" onsubmit="return confirm('مبلغ دوزوله‌های صادرشده این ماه قفل و درخواست تسویه ثبت شود؟')">@csrf<button class="rounded-xl bg-amber-500 px-5 py-3 text-xs font-black text-white shadow hover:bg-amber-600">ثبت درخواست تسویه ماه</button></form>
            @else
                <div class="rounded-xl bg-slate-100 px-4 py-3 text-xs font-bold text-slate-500">دکمه درخواست از روز ۱ تا ۵ ماه بعد، برای ماه بسته‌شده فعال می‌شود.</div>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="w-full text-right text-xs"><thead class="whitespace-nowrap bg-slate-100 text-slate-600"><tr><th class="p-4">کد رهگیری</th><th class="p-4">شماره دوزوله</th><th class="p-4">شرکت</th><th class="p-4">مقصد</th><th class="p-4">مبلغ دوزوله</th><th class="p-4">تاریخ صدور شمسی</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($rows as $row)<tr class="hover:bg-slate-50"><td class="p-4 font-mono font-bold">{{ $row->tracking_code }}</td><td class="p-4 font-mono">{{ $row->serial_number }}</td><td class="p-4 font-bold">{{ $row->company_name_fa ?: $row->company_name ?: 'نامشخص' }}</td><td class="p-4"><span class="block">{{ $row->country_name ?: 'نامشخص' }}</span><span class="text-[10px] text-slate-400">{{ $row->loading_destination ?: '---' }}</span></td><td class="p-4 font-mono font-black text-emerald-700">{{ number_format($row->deducted_amount) }} ریال</td><td class="p-4 whitespace-nowrap">{{ \Morilog\Jalali\Jalalian::fromCarbon(\Illuminate\Support\Carbon::parse($row->issued_at))->format('Y/m/d H:i') }}</td></tr>@empty<tr><td colspan="6" class="p-12 text-center font-bold text-slate-400">در این بازه دوزوله صادرشده‌ای وجود ندارد.</td></tr>@endforelse</tbody></table></div>
        @if($rows->hasPages())<div class="border-t border-slate-100 p-4">{{ $rows->links() }}</div>@endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-violet-100 bg-violet-50 p-5 md:flex-row md:items-center">
            <div><h3 class="font-black text-violet-950">هزینه‌های انتقال دوزوله برگشتی بدون استفاده</h3><p class="mt-1 text-xs text-violet-700">هزینه انتقال و وجه برگشتی شرکت مبدأ به‌صورت مستقل گزارش می‌شود.</p></div>
            <div class="flex gap-2 text-xs font-black"><span class="rounded-xl bg-rose-100 px-3 py-2 text-rose-700">هزینه: {{ number_format($totalTransferFees) }} ریال</span><span class="rounded-xl bg-emerald-100 px-3 py-2 text-emerald-700">برگشتی: {{ number_format($totalTransferRefunds) }} ریال</span></div>
        </div>
        <div class="overflow-x-auto"><table class="w-full text-right text-xs"><thead class="bg-slate-100 text-slate-600"><tr><th class="p-4">شماره دوزوله</th><th class="p-4">شرکت مبدأ</th><th class="p-4">شرکت مقصد</th><th class="p-4">هزینه انتقال</th><th class="p-4">وجه برگشتی</th><th class="p-4">تاریخ</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($transferRows as $transfer)<tr><td class="p-4 font-mono font-bold">{{ $transfer->serial_number }}</td><td class="p-4">{{ $transfer->source_company_name }}</td><td class="p-4">{{ $transfer->destination_company_name }}</td><td class="p-4 font-black text-rose-600">{{ number_format($transfer->transfer_fee) }} ریال</td><td class="p-4 font-black text-emerald-700">{{ number_format($transfer->source_refund) }} ریال</td><td class="p-4 whitespace-nowrap">{{ \Morilog\Jalali\Jalalian::fromCarbon(\Illuminate\Support\Carbon::parse($transfer->created_at))->format('Y/m/d H:i') }}</td></tr>@empty<tr><td colspan="6" class="p-10 text-center font-bold text-slate-400">در این بازه انتقال مالی ثبت نشده است.</td></tr>@endforelse</tbody></table></div>
        @if($transferRows->hasPages())<div class="border-t border-slate-100 p-4">{{ $transferRows->links() }}</div>@endif
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5"><h3 class="font-black text-slate-800">سوابق درخواست و پرداخت تسویه</h3></div>
        <div class="space-y-4 p-5">
            @forelse($settlements as $settlement)
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div><strong class="text-sm text-slate-800">ماه {{ str_replace('-', '/', $settlement->period_key) }}</strong><div class="mt-2 text-xs text-slate-500">{{ number_format($settlement->issued_count) }} دوزوله — {{ number_format($settlement->requested_amount) }} ریال</div></div>
                        <span class="rounded-full px-3 py-1 text-[11px] font-black {{ $settlement->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $settlement->status === 'paid' ? 'پرداخت‌شده' : 'در انتظار پرداخت' }}</span>
                    </div>
                    @if($settlement->status === 'paid')
                        <div class="mt-3 grid grid-cols-1 gap-2 rounded-lg bg-emerald-50 p-3 text-xs text-emerald-800 md:grid-cols-4"><span>مبلغ: {{ number_format($settlement->paid_amount) }}</span><span>بانک: {{ $settlement->bank_name }}</span><span>پیگیری: {{ $settlement->payment_reference }}</span><a class="font-black underline" target="_blank" href="{{ asset('storage/'.$settlement->receipt_file) }}">مشاهده سند پرداخت</a></div>
                    @elseif($isAdmin)
                        <form method="POST" enctype="multipart/form-data" action="{{ route('association.issued-financial.confirm-settlement', $settlement) }}" class="mt-4 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 md:grid-cols-5">@csrf
                            <input type="number" name="paid_amount" value="{{ (int)$settlement->requested_amount }}" required class="rounded-lg border border-slate-200 px-3 py-2 text-xs" placeholder="مبلغ پرداختی">
                            <input name="bank_name" required class="rounded-lg border border-slate-200 px-3 py-2 text-xs" placeholder="نام بانک">
                            <input name="payment_reference" required class="rounded-lg border border-slate-200 px-3 py-2 text-xs" placeholder="شماره پیگیری">
                            <input type="file" name="receipt_file" accept=".jpg,.jpeg,.png,.pdf" required class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-[10px]">
                            <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-black text-white">ثبت پرداخت و سند</button>
                        </form>
                    @endif
                </div>
            @empty<div class="py-8 text-center text-xs font-bold text-slate-400">هنوز درخواست تسویه‌ای ثبت نشده است.</div>@endforelse
        </div>
        @if($settlements->hasPages())<div class="border-t border-slate-100 p-4">{{ $settlements->links() }}</div>@endif
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/persian-date.vendor.min.js') }}"></script>
<script src="{{ asset('assets/js/persian-datepicker.vendor.min.js') }}"></script>
<script>
const issuedFinancialDatepickerJQuery = window.jQuery;

document.addEventListener('DOMContentLoaded', function () {
    if (!issuedFinancialDatepickerJQuery || typeof issuedFinancialDatepickerJQuery.fn.pDatepicker !== 'function') return;

    const pickerJQuery = issuedFinancialDatepickerJQuery;

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

    pickerJQuery('#issued_date_from, #issued_date_to').each(function () {
        pickerJQuery(this).pDatepicker({
        format: 'YYYY/MM/DD',
        initialValue: true,
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
        });
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
