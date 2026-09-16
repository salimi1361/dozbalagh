@extends('layouts.admin') 

@section('header_title', 'مدیریت رانندگان')
@section('hero_subtitle', 'جستجو، کنترل وضعیت و مدیریت ارتباط رانندگان با شرکت‌های حمل‌ونقل')

@section('content')
@php
    $driverTotal = $drivers->count();
    $activeDrivers = $drivers->filter(fn ($driver) => (bool) ($driver->is_active ?? true))->count();
    $inactiveDrivers = $driverTotal - $activeDrivers;
    $assignedDrivers = $drivers->filter(fn ($driver) => (bool) ($driver->is_active ?? true))->whereNotNull('current_company_id')->count();
    $unassignedDrivers = $activeDrivers - $assignedDrivers;
    $activeDozbalaghs = (int) $drivers->sum(fn ($driver) => (int) ($driver->active_dozbalaghs ?? 0));
@endphp

<div class="mx-auto max-w-7xl space-y-5" dir="rtl">
    <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
        <div class="pointer-events-none absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
        <div class="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>
        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m8-6a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 6v-2a4 4 0 0 0-3-3.87m-1-7.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">مدیریت رانندگان</h1>
                    <p class="mt-1 text-xs font-semibold text-blue-100">مدیریت رانندگان سامانه، اتصال به شرکت‌ها و پایش دوزوله‌های فعال</p>
                    <button type="button" onclick="openDriverRegistrationModal()" class="mt-3 rounded-xl bg-white px-4 py-2 text-xs font-black text-sky-800 shadow-lg shadow-sky-950/20 transition hover:bg-sky-50">➕ ثبت راننده جدید</button>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-6 xl:min-w-[54rem]">
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">کل رانندگان</span><strong class="text-lg font-black">{{ number_format($driverTotal) }}</strong></div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">رانندگان فعال</span><strong class="text-lg font-black text-emerald-300">{{ number_format($activeDrivers) }}</strong></div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">غیرفعال‌شده</span><strong class="text-lg font-black text-rose-200">{{ number_format($inactiveDrivers) }}</strong></div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">متصل به شرکت</span><strong class="text-lg font-black text-cyan-200">{{ number_format($assignedDrivers) }}</strong></div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">رانندگان آزاد</span><strong class="text-lg font-black text-violet-200">{{ number_format($unassignedDrivers) }}</strong></div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">دوزوله فعال</span><strong class="text-lg font-black text-emerald-300">{{ number_format($activeDozbalaghs) }}</strong></div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">{{ session('error') }}</div>
    @endif

    @if(auth()->user()->role?->name === 'admin')
    <section class="flex flex-col gap-4 rounded-2xl border border-sky-200 bg-sky-50/70 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-sm font-black text-slate-900">ارسال لینک نصب اپلیکیشن برای راننده</h2>
            <p class="mt-1 text-xs font-semibold leading-6 text-slate-600">
                پس از ثبت موفق راننده، آخرین لینک نصب اپلیکیشن راننده به شماره موبایل او پیامک می‌شود.
            </p>
        </div>
        <form method="POST" action="{{ route('admin.drivers.registration_app_sms') }}" class="flex shrink-0 items-center gap-3">
            @csrf
            @method('PATCH')
            <input type="hidden" name="enabled" value="0">
            <label class="flex cursor-pointer items-center gap-2 text-xs font-black text-slate-700">
                <input type="checkbox" name="enabled" value="1" {{ $driverRegistrationSmsEnabled ? 'checked' : '' }}
                       class="h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                ارسال خودکار فعال باشد
            </label>
            <button type="submit" class="rounded-xl bg-sky-700 px-4 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-sky-800">
                ذخیره وضعیت
            </button>
        </form>
    </section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        {{-- کادر جستجوی زنده --}}
        <div class="border-b border-slate-100 bg-slate-50/60 p-5">
            <div class="relative w-full max-w-xl">
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 text-sm">🔍</span>
                <input type="text" id="table_search" placeholder="جستجو بر اساس نام، کد ملی یا موبایل..."
                       class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-4 pr-9 text-sm font-medium shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
        </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-right text-gray-600">
            <thead class="text-xs text-gray-700 uppercase bg-slate-100 rounded-t-lg">
                <tr>
                    <th class="px-4 py-3">نام و نام خانوادگی</th>
                    <th class="px-4 py-3 text-center">کد ملی</th>
                    <th class="px-4 py-3 text-center">شماره موبایل</th>
                    <th class="px-4 py-3 text-center">دوزوله فعال</th>
                    <th class="px-4 py-3 text-center">وضعیت دسترسی</th>
                    <th class="px-4 py-3">شرکت فعلی (انتقال راننده)</th>
                    <th class="px-4 py-3 text-center">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $driver)
                <tr class="border-b border-slate-100 transition driver-row {{ !($driver->is_active ?? true) ? 'bg-rose-50/30 hover:bg-rose-50/50' : 'hover:bg-slate-50' }}">
                    
                    <td class="px-4 py-3 search-target">
                        <div class="font-bold text-slate-900">{{ $driver->first_name_fa }} {{ $driver->last_name_fa }}</div>
                        <div class="text-[10px] text-sky-600 font-bold font-sans mt-1" dir="ltr">{{ strtoupper($driver->first_name_en) }} {{ strtoupper($driver->last_name_en) }}</div>
                    </td>
                    
                    <td class="px-4 py-3 font-mono text-center search-target" dir="ltr">{{ $driver->national_code }}</td>
                    
                    <td class="px-4 py-3 font-mono text-center search-target" dir="ltr">{{ $driver->mobile ?? '-' }}</td>
                    
                    <td class="px-4 py-3 text-center">
                        @if($driver->active_dozbalaghs > 0)
                            <span class="bg-emerald-100 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-full text-xs font-bold shadow-sm">
                                {{ $driver->active_dozbalaghs }} فعال
                            </span>
                        @else
                            <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-xs">ندارد</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-center">
                        @if($driver->is_active ?? true)
                            <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">فعال</span>
                        @else
                            <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">غیرفعال</span>
                        @endif
                    </td>
                    
                    <td class="px-4 py-3">
                        <form action="{{ route('admin.drivers.update_company', $driver->id) }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            @method('PUT')
                            <select name="company_id" @disabled(!($driver->is_active ?? true)) class="min-w-[260px] flex-1 rounded-md border border-slate-300 px-2 py-1 text-sm focus:ring-sky-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" class="text-rose-500 font-bold">-- 🛑 آزاد (بدون شرکت) --</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ $driver->current_company_id == $company->id ? 'selected' : '' }}>
                                        {{ $company->name_fa }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" @disabled(!($driver->is_active ?? true)) class="rounded px-3 py-1 text-xs font-bold shadow-sm transition {{ ($driver->is_active ?? true) ? 'bg-sky-100 text-sky-700 hover:bg-sky-200' : 'cursor-not-allowed bg-slate-100 text-slate-400' }}">تغییر</button>
                        </form>
                    </td>

                    <td class="px-4 py-3 text-center">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <form action="{{ route('admin.drivers.toggle_status', $driver->id) }}" method="POST" class="status-form" data-status="{{ ($driver->is_active ?? true) ? 'active' : 'inactive' }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded px-3 py-1 text-xs font-bold transition {{ ($driver->is_active ?? true) ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">{{ ($driver->is_active ?? true) ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}</button>
                            </form>
                            <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="delete-btn rounded bg-rose-50 px-3 py-1 text-xs font-bold text-rose-600 transition hover:bg-rose-100 hover:text-rose-700">حذف سیستمی</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="no_records_row">
                    <td colspan="7" class="p-8 text-center text-slate-400 font-bold">هیچ راننده‌ای در سیستم ثبت نشده است.</td>
                </tr>
                @endforelse

                {{-- ردیف خالی برای زمان جستجو --}}
                <tr id="search_empty_row" class="hidden">
                    <td colspan="7" class="p-8 text-center text-slate-400 font-bold">هیچ راننده‌ای با مشخصات وارد شده یافت نشد.</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- نوار ابزار صفحه‌بندی ۱۰ تایی --}}
    <div id="pagination_controls" class="flex justify-center items-center py-4 bg-slate-50 border-t border-slate-200 flex-wrap gap-1 mt-4 rounded-xl">
        </div>
    </section>
</div>

<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
@include('admin.drivers._registration_modal')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- سیستم هشدار حذف فرم ---
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const form = this.closest('.delete-form');
                Swal.fire({
                    title: 'آیا مطمئن هستید؟',
                    text: "هشدار: آیا از حذف کامل این راننده از دیتابیس سامانه مطمئن هستید؟ این عملیات غیرقابل بازگشت است!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444', 
                    cancelButtonColor: '#64748b', 
                    confirmButtonText: 'بله، حذف شود!',
                    cancelButtonText: 'انصراف',
                    fontFamily: 'Vazirmatn'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        const statusForms = document.querySelectorAll('.status-form');
        statusForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                const isActive = this.dataset.status === 'active';
                Swal.fire({
                    title: isActive ? 'غیرفعال‌سازی راننده؟' : 'فعال‌سازی راننده؟',
                    text: isActive
                        ? 'ورود اپلیکیشن و انتخاب این راننده در فرایندهای جدید متوقف می‌شود.'
                        : 'این راننده دوباره در فرایندهای جدید قابل انتخاب خواهد شد.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: isActive ? '#d97706' : '#059669',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: isActive ? 'بله، غیرفعال شود' : 'بله، فعال شود',
                    cancelButtonText: 'انصراف',
                    fontFamily: 'Vazirmatn'
                }).then(result => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // =========================================
        // 🟢 منطق صفحه‌بندی ۱۰ تایی + جستجوی زنده 
        // =========================================
        let currentPage = 1;
        const rowsPerPage = 10;

        function updatePagination() {
            let visibleRows = $('.driver-row:not(.search-hidden)');
            let totalRows = visibleRows.length;
            let totalPages = Math.ceil(totalRows / rowsPerPage);
            
            if (totalPages === 0) totalPages = 1;
            if (currentPage > totalPages) currentPage = totalPages;
            
            $('.driver-row').hide(); 
            
            visibleRows.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage).show();
            
            let paginationHTML = '';
            if (totalPages > 1) {
                for (let i = 1; i <= totalPages; i++) {
                    let activeClass = (i === currentPage) 
                        ? 'bg-blue-600 text-white shadow-md border-blue-600' 
                        : 'bg-white text-slate-700 hover:bg-slate-100 border-slate-300';
                    
                    paginationHTML += `<button type="button" onclick="goToPage(${i})" class="px-3 py-1.5 border rounded-lg mx-1 ${activeClass} text-xs font-bold transition">${i}</button>`;
                }
            }
            $('#pagination_controls').html(paginationHTML);
        }

        window.goToPage = function(page) {
            currentPage = page;
            updatePagination();
        };

        $('#table_search').on('keyup input', function() {
            let query = $(this).val().toLowerCase().trim();
            let matchesFound = 0;

            $('.driver-row').each(function() {
                let rowText = $(this).find('.search-target').text().toLowerCase();
                let isMatch = rowText.indexOf(query) > -1;
                
                if (isMatch) {
                    $(this).removeClass('search-hidden');
                    matchesFound++;
                } else {
                    $(this).addClass('search-hidden');
                }
            });

            if (matchesFound === 0) {
                $('#search_empty_row').removeClass('hidden');
                if($('#no_records_row').length) $('#no_records_row').addClass('hidden');
            } else {
                $('#search_empty_row').addClass('hidden');
            }
            
            currentPage = 1;
            updatePagination();
        });

        // اجرای اولیه صفحه‌بندی
        updatePagination();
    });
</script>
@endsection
