@extends('layouts.admin') {{-- نام فایل قالب اصلی ادمین خودت را اینجا بنویس --}}

@section('content')
    <script src="{{ asset('assets/js/apexcharts.js') }}"></script>

    <div class="p-4 sm:p-6 lg:p-8 w-full">
        
<div class="mb-6">
        <h1 class="text-2xl font-black text-slate-800">داشبورد کل سامانه</h1>
        <p class="text-sm text-slate-500 mt-1 font-semibold">خلاصه وضعیت درخواست‌ها و ناوگان</p>
    </div>

        <div class="bg-amber-50 border-r-4 border-amber-500 p-4 rounded-xl mb-8 shadow-sm flex items-start gap-4">
            <svg class="w-6 h-6 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <h3 class="text-sm font-bold text-amber-800">هشدار کاهش موجودی انبار</h3>
                <p class="text-sm text-amber-700 mt-1">موجودی سریال های دوزوله برای مقاصد <strong>ترکمنستان</strong> رو به اتمام است (کمتر از ۵۰ عدد باقی مانده). لطفاً جهت تخصیص سهمیه جدید اقدام نمایید.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm relative overflow-hidden">
                <div class="absolute -left-4 -bottom-4 w-20 h-20 bg-emerald-50 rounded-full z-0"></div>
                <div class="flex justify-between items-center relative z-10">
                    <div>
                        <p class="text-sm font-bold text-slate-500 mb-1">شرکت‌های عضو</p>
                        <h3 class="text-3xl font-black text-slate-800">{{ $companiesCount }}</h3>
                    </div>
                    <div class="bg-emerald-100 p-3 rounded-xl text-emerald-600">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm relative overflow-hidden">
                <div class="absolute -left-4 -bottom-4 w-20 h-20 bg-indigo-50 rounded-full z-0"></div>
                <div class="flex justify-between items-center relative z-10">
                    <div>
                        <p class="text-sm font-bold text-slate-500 mb-1">رانندگان ثبت شده</p>
                        <h3 class="text-3xl font-black text-slate-800">{{ $driversCount }}</h3>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-xl text-indigo-600">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm relative overflow-hidden">
                <div class="absolute -left-4 -bottom-4 w-20 h-20 bg-amber-50 rounded-full z-0"></div>
                <div class="flex justify-between items-center relative z-10">
                    <div>
                        <p class="text-sm font-bold text-slate-500 mb-1">ناوگان (کامیون‌ها)</p>
                        <h3 class="text-3xl font-black text-slate-800">{{ $fleetsCount }}</h3>
                    </div>
                    <div class="bg-amber-100 p-3 rounded-xl text-amber-600">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm relative overflow-hidden">
                <div class="absolute -left-4 -bottom-4 w-20 h-20 bg-sky-50 rounded-full z-0"></div>
                <div class="flex justify-between items-center relative z-10">
                    <div>
                        <p class="text-sm font-bold text-slate-500 mb-1">درخواست‌های در انتظار</p>
                        <h3 class="text-3xl font-black text-slate-800">۰</h3>
                    </div>
                    <div class="bg-sky-100 p-3 rounded-xl text-sky-600">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm lg:col-span-2">
                <h3 class="text-base font-bold text-slate-800 mb-4">آمار درخواست های دوزوله (7 روز گذشته)</h3>
                <div id="barChart" class="w-full h-72"></div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm lg:col-span-1">
                <h3 class="text-base font-bold text-slate-800 mb-4">توزیع مقاصد پرتردد</h3>
                <div id="donutChart" class="w-full h-72 flex justify-center items-center"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const fontFamily = 'Vazirmatn, sans-serif';

            var barOptions = {
                series: [{ name: 'درخواست‌های ثبت شده', data: [44, 55, 57, 56, 61, 58, 63] }],
                chart: { type: 'bar', height: '100%', fontFamily: fontFamily, toolbar: { show: false } },
                plotOptions: { bar: { horizontal: false, columnWidth: '40%', borderRadius: 4 } },
                dataLabels: { enabled: false },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                xaxis: {
                    categories: ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'],
                    labels: { style: { fontFamily: fontFamily, fontWeight: 'bold' } }
                },
                yaxis: { labels: { style: { fontFamily: fontFamily } } },
                fill: { opacity: 1, colors: ['#0ea5e9'] },
                tooltip: { y: { formatter: function (val) { return val + " درخواست" } } }
            };
            new ApexCharts(document.querySelector("#barChart"), barOptions).render();

            var donutOptions = {
                series: [44, 55, 13, 33],
                labels: ['ترکیه', 'ترکمنستان', 'افغانستان', 'گرجستان'],
                chart: { type: 'donut', height: '100%', fontFamily: fontFamily },
                colors: ['#0ea5e9', '#10b981', '#f59e0b', '#6366f1'],
                plotOptions: { pie: { donut: { size: '70%', labels: { show: true, name: { fontSize: '14px', fontFamily: fontFamily }, value: { fontSize: '20px', fontWeight: 'bold', fontFamily: fontFamily }, total: { show: true, label: 'مجموع', formatter: function (w) { return w.globals.seriesTotals.reduce((a, b) => { return a + b }, 0) } } } } } },
                dataLabels: { enabled: false },
                legend: { position: 'bottom', fontFamily: fontFamily, fontWeight: 'bold' }
            };
            new ApexCharts(document.querySelector("#donutChart"), donutOptions).render();
        });
    </script>
@endsection