@extends('layouts.admin')

@section('header_title', auth()->user()?->hasRole('association') ? 'داشبورد انجمن' : 'داشبورد کل سامانه')

@section('content')
<script src="{{ asset('assets/js/apexcharts.js') }}"></script>

<div class="space-y-8">
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-black text-slate-900">{{ auth()->user()?->hasRole('association') ? 'داشبورد انجمن' : 'داشبورد کل سامانه' }}</h1>
        <p class="text-sm text-slate-500 font-semibold">خلاصه وضعیت واقعی درخواست‌ها، ناوگان، شرکت‌ها و موجودی انبار</p>
    </div>

    @if($lowStockCountries->isNotEmpty())
        <div class="bg-amber-50 border border-amber-100 border-r-4 border-r-amber-500 p-4 rounded-lg shadow-sm flex items-start gap-4">
            <svg class="w-6 h-6 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            </svg>
            <div>
                <h3 class="text-sm font-black text-amber-900">هشدار کاهش موجودی انبار</h3>
                <p class="text-sm text-amber-800 mt-1 leading-7">
                    موجودی سریال‌های خام برای
                    @foreach($lowStockCountries as $country)
                        <strong>{{ $country->country_name }}</strong>
                        <span>({{ number_format($country->available_count) }} عدد)</span>@if(!$loop->last)،@endif
                    @endforeach
                    به آستانه هشدار رسیده است.
                </p>
            </div>
        </div>
    @else
        <div class="bg-emerald-50 border border-emerald-100 border-r-4 border-r-emerald-500 p-4 rounded-lg shadow-sm flex items-start gap-4">
            <svg class="w-6 h-6 text-emerald-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4.5 12.75 6 6 9-13.5"></path>
            </svg>
            <div>
                <h3 class="text-sm font-black text-emerald-900">موجودی انبار در وضعیت عادی است</h3>
                <p class="text-sm text-emerald-800 mt-1">در حال حاضر مقصدی با موجودی کمتر یا مساوی ۵۰ سریال خام شناسایی نشده است.</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        @php
            $cards = [
                [
                    'label' => 'شرکت‌های عضو',
                    'value' => $companiesCount,
                    'color' => 'emerald',
                    'icon' => 'M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4',
                ],
                [
                    'label' => 'رانندگان ثبت شده',
                    'value' => $driversCount,
                    'color' => 'indigo',
                    'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.5 20.25a8.25 8.25 0 1 1 15 0',
                ],
                [
                    'label' => 'ناوگان (کامیون‌ها)',
                    'value' => $fleetsCount,
                    'color' => 'amber',
                    'icon' => 'M8.25 18.75a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM15.75 18.75a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM3 13.5h2.25m13.5 0H21m-15.75 0h13.5M5.25 13.5 6.6 6.75A2.25 2.25 0 0 1 8.8 5h5.4a2.25 2.25 0 0 1 2.2 1.75l1.35 6.75',
                ],
                [
                    'label' => 'درخواست‌های در انتظار',
                    'value' => $pendingRequestsCount,
                    'color' => 'sky',
                    'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-5.25z',
                ],
            ];

            $colors = [
                'emerald' => ['bg' => 'bg-emerald-50', 'icon' => 'bg-emerald-100 text-emerald-600'],
                'indigo' => ['bg' => 'bg-indigo-50', 'icon' => 'bg-indigo-100 text-indigo-600'],
                'amber' => ['bg' => 'bg-amber-50', 'icon' => 'bg-amber-100 text-amber-600'],
                'sky' => ['bg' => 'bg-sky-50', 'icon' => 'bg-sky-100 text-sky-600'],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="absolute -left-4 -bottom-4 w-20 h-20 {{ $colors[$card['color']]['bg'] }} rounded-full z-0"></div>
                <div class="flex justify-between items-center relative z-10 gap-4">
                    <div>
                        <p class="text-sm font-black text-slate-500 mb-1">{{ $card['label'] }}</p>
                        <h3 class="text-3xl font-black text-slate-900">{{ number_format($card['value']) }}</h3>
                    </div>
                    <div class="{{ $colors[$card['color']]['icon'] }} p-3 rounded-lg shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $card['icon'] }}"></path>
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-base font-black text-slate-900">آمار درخواست‌های دوزبلاغ (۷ روز گذشته)</h3>
                    <p class="text-xs text-slate-400 font-bold mt-1">بر اساس تاریخ ثبت درخواست در سامانه</p>
                </div>
                <span class="text-xs font-black text-sky-700 bg-sky-50 border border-sky-100 px-3 py-1 rounded-lg">واقعی</span>
            </div>
            <div id="barChart" class="w-full h-72"></div>
        </section>

        <section class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-base font-black text-slate-900">توزیع مقاصد پرتردد</h3>
                    <p class="text-xs text-slate-400 font-bold mt-1">بر اساس ردیف مقصدهای ثبت‌شده</p>
                </div>
                <span class="text-xs font-black text-slate-700 bg-slate-50 border border-slate-100 px-3 py-1 rounded-lg">{{ number_format($destinationTotal) }}</span>
            </div>
            <div id="donutChart" class="w-full h-72"></div>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fontFamily = 'Vazirmatn, Tahoma, sans-serif';
    const requestLabels = @json($requestChartLabels);
    const requestData = @json($requestChartData);
    const destinationLabels = @json($destinationChartLabels);
    const destinationData = @json($destinationChartData);

    new ApexCharts(document.querySelector('#barChart'), {
        series: [{ name: 'درخواست ثبت شده', data: requestData }],
        chart: { type: 'bar', height: '100%', fontFamily, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: false, columnWidth: '40%', borderRadius: 5 } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
        colors: ['#0ea5e9'],
        xaxis: { categories: requestLabels, labels: { style: { fontFamily, fontWeight: 700 } } },
        yaxis: { labels: { style: { fontFamily } } },
        tooltip: { y: { formatter: function (val) { return val + ' درخواست'; } } }
    }).render();

    new ApexCharts(document.querySelector('#donutChart'), {
        series: destinationData.length ? destinationData : [1],
        labels: destinationLabels.length ? destinationLabels : ['بدون مقصد'],
        chart: { type: 'donut', height: '100%', fontFamily },
        colors: ['#0ea5e9', '#10b981', '#f59e0b', '#6366f1', '#ef4444'],
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontFamily, fontWeight: 700 },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: { fontFamily, fontSize: '14px' },
                        value: { fontFamily, fontSize: '20px', fontWeight: 900 },
                        total: {
                            show: true,
                            label: 'مجموع',
                            formatter: function (w) {
                                return destinationData.length ? w.globals.seriesTotals.reduce((a, b) => a + b, 0) : 0;
                            }
                        }
                    }
                }
            }
        }
    }).render();
});
</script>
@endsection
