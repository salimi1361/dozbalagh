@extends('layouts.admin')

@section('header_title', 'داشبورد انجمن')

@section('content')
<script src="{{ asset('assets/js/apexcharts.js') }}"></script>

<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900">داشبورد انجمن</h1>
            <p class="text-sm font-semibold text-slate-500 mt-1">نمای زنده از درخواست‌ها، رویدادهای ارسالی شرکت‌ها و وضعیت تردد دوزبلاغ‌ها</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/web/association/driver/list" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 rounded-lg text-xs font-black text-slate-700 shadow-sm transition">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                کارتابل بررسی
            </a>
            <a href="/web/association/transit-permits" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 border border-slate-900 rounded-lg text-xs font-black text-white shadow-sm transition">
                مدیریت تردد
            </a>
        </div>
    </div>

    @if($lowStockCountries->isNotEmpty())
        <div class="bg-amber-50 border border-amber-200 border-r-4 border-r-amber-500 rounded-lg p-4 shadow-sm flex items-start gap-3">
            <div class="h-10 w-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-black text-amber-900">هشدار کاهش موجودی انبار</h3>
                <p class="text-sm text-amber-800 mt-1 leading-7">
                    موجودی سریال خام برای
                    @foreach($lowStockCountries as $country)
                        <span class="font-black">{{ $country->country_name }} ({{ number_format($country->available_count) }})</span>@if(!$loop->last)،@endif
                    @endforeach
                    به آستانه هشدار رسیده است.
                </p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @php
            $summaryCards = [
                ['label' => 'رویدادهای امروز', 'value' => $stats['events_today'], 'sub' => 'ثبت شده از اپ رانندگان', 'color' => 'sky', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
                ['label' => 'رویدادهای ۷ روز اخیر', 'value' => $stats['events_week'], 'sub' => 'همه وضعیت‌های مسیر', 'color' => 'emerald', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75zm6.75-6C9.75 6.504 10.254 6 10.875 6h2.25c.621 0 1.125.504 1.125 1.125v12.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V7.125zM16.5 4.125C16.5 3.504 17.004 3 17.625 3h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125z'],
                ['label' => 'رانندگان فعال هفته', 'value' => $stats['active_drivers_week'], 'sub' => 'دارای حداقل یک رویداد', 'color' => 'indigo', 'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.5 20.25a8.25 8.25 0 1 1 15 0'],
                ['label' => 'درخواست‌های معلق', 'value' => $stats['pending'], 'sub' => 'منتظر بررسی انجمن', 'color' => 'amber', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z'],
            ];
            $colorMap = [
                'sky' => 'bg-sky-50 text-sky-600 border-sky-100',
                'emerald' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                'indigo' => 'bg-indigo-50 text-indigo-600 border-indigo-100',
                'amber' => 'bg-amber-50 text-amber-600 border-amber-100',
            ];
        @endphp

        @foreach($summaryCards as $card)
            <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-5 relative overflow-hidden">
                <div class="absolute -left-6 -bottom-6 h-24 w-24 {{ explode(' ', $colorMap[$card['color']])[0] }} rounded-full"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-black text-slate-500">{{ $card['label'] }}</p>
                        <div class="text-3xl font-black text-slate-900 mt-2">{{ number_format($card['value']) }}</div>
                        <p class="text-xs font-bold text-slate-400 mt-1">{{ $card['sub'] }}</p>
                    </div>
                    <div class="h-12 w-12 rounded-lg border {{ $colorMap[$card['color']] }} flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $card['icon'] }}"/></svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
        <section class="xl:col-span-8 bg-white rounded-lg border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-base font-black text-slate-900">روند درخواست‌ها و رویدادها</h2>
                    <p class="text-xs font-bold text-slate-400 mt-1">مقایسه ۷ روز اخیر بر اساس زمان ثبت</p>
                </div>
                <span class="text-xs font-black text-sky-700 bg-sky-50 border border-sky-100 px-3 py-1 rounded-lg">۷ روز اخیر</span>
            </div>
            <div id="associationTrendChart" class="h-80"></div>
        </section>

        <section class="xl:col-span-4 bg-white rounded-lg border border-slate-200 shadow-sm p-5">
            <div class="mb-4">
                <h2 class="text-base font-black text-slate-900">نوع رویدادهای ارسالی</h2>
                <p class="text-xs font-bold text-slate-400 mt-1">۳۰ روز اخیر</p>
            </div>
            <div id="eventTypeChart" class="h-80"></div>
        </section>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
        <section class="xl:col-span-4 bg-white rounded-lg border border-slate-200 shadow-sm p-5">
            <div class="mb-4">
                <h2 class="text-base font-black text-slate-900">مقاصد پرتردد</h2>
                <p class="text-xs font-bold text-slate-400 mt-1">بر اساس همه درخواست‌های ثبت‌شده</p>
            </div>
            <div id="destinationChart" class="h-72"></div>
        </section>

        <section class="xl:col-span-4 bg-white rounded-lg border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-black text-slate-900">وضعیت پرونده‌ها</h2>
                    <p class="text-xs font-bold text-slate-400 mt-1">خلاصه چرخه کاری انجمن</p>
                </div>
                <span class="text-xl font-black text-slate-900">{{ number_format($stats['requests_week']) }}</span>
            </div>
            <div class="space-y-3">
                @forelse($statusCards as $status)
                    @php
                        $maxStatus = max(1, $statusCards->max('value'));
                        $width = min(100, round(($status['value'] / $maxStatus) * 100));
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs font-black mb-1">
                            <span class="text-slate-600">{{ $status['label'] }}</span>
                            <span class="text-slate-900">{{ number_format($status['value']) }}</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-slate-900 rounded-full" style="width: {{ $width }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-sm font-bold text-slate-400 py-10">هنوز پرونده‌ای ثبت نشده است.</div>
                @endforelse
            </div>
        </section>

        <section class="xl:col-span-4 bg-white rounded-lg border border-slate-200 shadow-sm p-5">
            <div class="mb-4">
                <h2 class="text-base font-black text-slate-900">شرکت‌های فعال در ارسال رویداد</h2>
                <p class="text-xs font-bold text-slate-400 mt-1">رتبه‌بندی ۳۰ روز اخیر</p>
            </div>
            <div class="space-y-3">
                @forelse($companyEventLeaders as $index => $company)
                    <div class="flex items-center justify-between gap-3 p-3 rounded-lg bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="h-8 w-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-xs font-black text-slate-500">{{ $index + 1 }}</span>
                            <span class="text-sm font-black text-slate-700 truncate">{{ $company->company_name }}</span>
                        </div>
                        <span class="text-sm font-black text-slate-900">{{ number_format($company->total) }}</span>
                    </div>
                @empty
                    <div class="text-center text-sm font-bold text-slate-400 py-10">رویدادی برای رتبه‌بندی وجود ندارد.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-black text-slate-900">آخرین رویدادهای ارسالی</h2>
                <p class="text-xs font-bold text-slate-400 mt-1">آخرین پیام‌های مسیر که از سمت رانندگان/شرکت‌ها ثبت شده‌اند</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-xs font-black text-slate-500 whitespace-nowrap">
                        <th class="p-4">رویداد</th>
                        <th class="p-4">شرکت</th>
                        <th class="p-4">راننده</th>
                        <th class="p-4">کد / سریال</th>
                        <th class="p-4">موقعیت</th>
                        <th class="p-4">زمان ثبت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($latestEvents as $event)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-lg bg-sky-50 text-sky-700 border border-sky-100 text-xs font-black">{{ $event->event_label }}</span>
                            </td>
                            <td class="p-4 font-bold text-slate-700">{{ $event->company_display }}</td>
                            <td class="p-4">
                                <div class="font-black text-slate-800">{{ $event->driver_name }}</div>
                                <div class="text-xs font-bold text-slate-400 mt-1">{{ $event->national_code ?? '---' }}</div>
                            </td>
                            <td class="p-4 font-mono font-black text-slate-700">
                                {{ $event->d_code ?: ($event->serial_number ?: '---') }}
                            </td>
                            <td class="p-4 text-xs font-bold text-slate-500" dir="ltr">
                                @if($event->latitude && $event->longitude)
                                    {{ $event->latitude }}, {{ $event->longitude }}
                                @else
                                    ---
                                @endif
                            </td>
                            <td class="p-4 text-xs font-black text-slate-500 whitespace-nowrap">
                                {{ \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($event->created_at))->format('Y/m/d H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-sm font-bold text-slate-400">هنوز رویدادی از سمت ناوگان ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fontFamily = 'Vazirmatn, Tahoma, sans-serif';
    const trendLabels = @json($trendLabels);
    const requestTrend = @json($requestTrend);
    const eventTrend = @json($eventTrend);
    const eventTypeLabels = @json($eventTypeChart['labels']);
    const eventTypeSeries = @json($eventTypeChart['series']);
    const destinationLabels = @json($destinationChart['labels']);
    const destinationSeries = @json($destinationChart['series']);

    new ApexCharts(document.querySelector('#associationTrendChart'), {
        series: [
            { name: 'درخواست‌ها', data: requestTrend },
            { name: 'رویدادها', data: eventTrend },
        ],
        chart: { type: 'bar', height: '100%', fontFamily, toolbar: { show: false } },
        colors: ['#0ea5e9', '#10b981'],
        plotOptions: { bar: { borderRadius: 5, columnWidth: '42%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
        xaxis: { categories: trendLabels, labels: { style: { fontFamily, fontWeight: 700 } } },
        yaxis: { labels: { style: { fontFamily } } },
        legend: { position: 'top', horizontalAlign: 'left', fontFamily, fontWeight: 700 },
        tooltip: { theme: 'light' }
    }).render();

    new ApexCharts(document.querySelector('#eventTypeChart'), {
        series: eventTypeSeries.length ? eventTypeSeries : [1],
        labels: eventTypeLabels.length ? eventTypeLabels : ['بدون رویداد'],
        chart: { type: 'donut', height: '100%', fontFamily },
        colors: ['#0ea5e9', '#10b981', '#f59e0b', '#6366f1', '#ef4444', '#14b8a6'],
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontFamily, fontWeight: 700 },
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'مجموع',
                            formatter: function (w) {
                                return eventTypeSeries.length ? w.globals.seriesTotals.reduce((a, b) => a + b, 0) : 0;
                            }
                        }
                    }
                }
            }
        }
    }).render();

    new ApexCharts(document.querySelector('#destinationChart'), {
        series: destinationSeries.length ? destinationSeries : [1],
        labels: destinationLabels.length ? destinationLabels : ['بدون مقصد'],
        chart: { type: 'polarArea', height: '100%', fontFamily },
        colors: ['#0ea5e9', '#10b981', '#f59e0b', '#6366f1', '#ef4444', '#14b8a6'],
        stroke: { colors: ['#fff'] },
        fill: { opacity: 0.88 },
        yaxis: { show: false },
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontFamily, fontWeight: 700 }
    }).render();
});
</script>
@endsection
