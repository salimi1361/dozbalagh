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
                    
                    <td class="relative z-40 px-4 py-3">
                        @php
                            $isDriverActive = (bool) ($driver->is_active ?? true);
                            $selectedCompanyName = $driver->company_name ?: '-- 🛑 آزاد (بدون شرکت) --';
                        @endphp
                        <form action="{{ route('admin.drivers.update_company', $driver->id) }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            @method('PUT')
                            <div class="company-combobox relative min-w-[260px] flex-1" data-company-combobox>
                                <input type="hidden" name="company_id" value="{{ $driver->current_company_id ?? '' }}" data-company-id-input>
                                <button type="button" data-company-toggle aria-haspopup="listbox" aria-expanded="false"
                                        aria-controls="company_options_{{ $driver->id }}" @disabled(!$isDriverActive)
                                        class="flex w-full items-center justify-between gap-2 rounded-md border border-slate-300 bg-white px-2 py-1 text-right text-sm text-slate-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                                    <span class="truncate" data-company-selected-label>{{ $selectedCompanyName }}</span>
                                    <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                                </button>

                                <div id="company_options_{{ $driver->id }}" data-company-panel role="listbox" aria-label="انتخاب شرکت"
                                     class="company-options-panel absolute right-0 z-50 mt-1 hidden w-full overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg">
                                    <div class="border-b border-slate-100 p-2">
                                        <label class="sr-only" for="company_search_{{ $driver->id }}">جستجوی نام شرکت</label>
                                        <input id="company_search_{{ $driver->id }}" type="search" autocomplete="off" data-company-search
                                               placeholder="جستجوی نام شرکت..."
                                               class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-xs focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                    </div>
                                    <div class="max-h-48 overflow-y-auto p-1">
                                        <button type="button" role="option" aria-selected="{{ $driver->current_company_id ? 'false' : 'true' }}"
                                                data-company-option data-company-id="" data-company-name="-- 🛑 آزاد (بدون شرکت) --"
                                                class="w-full rounded px-2 py-2 text-right text-xs text-rose-600 transition hover:bg-rose-50 {{ $driver->current_company_id ? '' : 'bg-sky-50 font-bold text-sky-800' }}">
                                            -- 🛑 آزاد (بدون شرکت) --
                                        </button>
                                        @foreach($companies as $company)
                                            @php $isSelectedCompany = (int) $driver->current_company_id === (int) $company->id; @endphp
                                            <button type="button" role="option" aria-selected="{{ $isSelectedCompany ? 'true' : 'false' }}"
                                                    data-company-option data-company-id="{{ $company->id }}" data-company-name="{{ $company->name_fa }}"
                                                    class="w-full rounded px-2 py-2 text-right text-xs text-slate-700 transition hover:bg-sky-50 {{ $isSelectedCompany ? 'bg-sky-50 font-bold text-sky-800' : '' }}">
                                                {{ $company->name_fa }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <button type="submit" @disabled(!$isDriverActive) class="rounded px-3 py-1 text-xs font-bold shadow-sm transition {{ $isDriverActive ? 'bg-sky-100 text-sky-700 hover:bg-sky-200' : 'cursor-not-allowed bg-slate-100 text-slate-400' }}">تغییر</button>
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

        // کشوی محلی شرکت با جستجو درون خود فهرست، بدون ذخیره خودکار تغییرات.
        const companyComboboxes = Array.from(document.querySelectorAll('[data-company-combobox]'));

        function getCompanyPanel(combobox) {
            return combobox._companyPanel || combobox.querySelector('[data-company-panel]');
        }

        function closeCompanyCombobox(combobox) {
            const panel = getCompanyPanel(combobox);
            const toggle = combobox.querySelector('[data-company-toggle]');
            const searchInput = panel.querySelector('[data-company-search]');

            if (combobox._companyPanelPlaceholder && panel.parentNode !== combobox._companyPanelPlaceholder.parentNode) {
                combobox._companyPanelPlaceholder.parentNode.insertBefore(panel, combobox._companyPanelPlaceholder);
            }

            panel.classList.add('hidden');
            panel.classList.remove('fixed', 'top-full', 'bottom-full', 'mb-1');
            panel.classList.add('absolute', 'right-0', 'z-50', 'mt-1', 'w-full');
            panel.style.removeProperty('direction');
            panel.style.removeProperty('left');
            panel.style.removeProperty('top');
            panel.style.removeProperty('visibility');
            panel.style.removeProperty('width');
            panel.style.removeProperty('z-index');
            toggle.setAttribute('aria-expanded', 'false');
            searchInput.value = '';
            combobox.querySelectorAll('[data-company-option]').forEach(function(option) {
                option.classList.remove('hidden');
            });
        }

        function openCompanyCombobox(combobox) {
            const panel = getCompanyPanel(combobox);
            const toggle = combobox.querySelector('[data-company-toggle]');
            const searchInput = panel.querySelector('[data-company-search]');
            const toggleRect = toggle.getBoundingClientRect();
            const pageMargin = 8;

            document.body.appendChild(panel);
            panel.classList.remove('absolute', 'right-0', 'z-50', 'top-full', 'bottom-full', 'mt-1', 'mb-1', 'w-full', 'hidden');
            panel.classList.add('fixed');
            panel.style.direction = 'rtl';
            panel.style.visibility = 'hidden';
            panel.style.zIndex = '1000';

            const panelWidth = Math.min(toggleRect.width, window.innerWidth - (pageMargin * 2));
            panel.style.width = `${panelWidth}px`;
            panel.style.left = `${Math.max(pageMargin, Math.min(toggleRect.left, window.innerWidth - panelWidth - pageMargin))}px`;
            panel.style.top = `${pageMargin}px`;

            const panelHeight = panel.getBoundingClientRect().height;
            const roomBelow = window.innerHeight - toggleRect.bottom - pageMargin;
            const roomAbove = toggleRect.top - pageMargin;
            const opensUpward = roomBelow < panelHeight && roomAbove > roomBelow;
            const top = opensUpward
                ? Math.max(pageMargin, toggleRect.top - panelHeight - 4)
                : Math.min(Math.max(pageMargin, toggleRect.bottom + 4), Math.max(pageMargin, window.innerHeight - panelHeight - pageMargin));

            panel.style.top = `${top}px`;
            panel.style.removeProperty('visibility');
            toggle.setAttribute('aria-expanded', 'true');
            searchInput.focus();
        }

        function updateCompanySelection(combobox, selectedId) {
            combobox.querySelectorAll('[data-company-option]').forEach(function(option) {
                const isSelected = option.dataset.companyId === selectedId;
                option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                option.classList.toggle('bg-sky-50', isSelected);
                option.classList.toggle('font-bold', isSelected);
                option.classList.toggle('text-sky-800', isSelected);
            });
        }

        companyComboboxes.forEach(function(combobox) {
            const toggle = combobox.querySelector('[data-company-toggle]');
            const panel = getCompanyPanel(combobox);
            const searchInput = combobox.querySelector('[data-company-search]');
            const selectedLabel = combobox.querySelector('[data-company-selected-label]');
            const companyIdInput = combobox.querySelector('[data-company-id-input]');
            const companyOptions = Array.from(combobox.querySelectorAll('[data-company-option]'));

            combobox._companyPanel = panel;
            combobox._companyPanelPlaceholder = document.createComment('company-options-panel');
            panel.parentNode.insertBefore(combobox._companyPanelPlaceholder, panel);

            toggle.addEventListener('click', function() {
                const isOpen = !panel.classList.contains('hidden');
                companyComboboxes.forEach(closeCompanyCombobox);

                if (isOpen) return;

                openCompanyCombobox(combobox);
            });

            searchInput.addEventListener('input', function() {
                const query = this.value.trim().toLocaleLowerCase('fa-IR');

                companyOptions.forEach(function(option) {
                    const isUnassignedOption = option.dataset.companyId === '';
                    const isMatch = option.dataset.companyName.toLocaleLowerCase('fa-IR').includes(query);
                    option.classList.toggle('hidden', !isUnassignedOption && query !== '' && !isMatch);
                });
            });

            companyOptions.forEach(function(option) {
                option.addEventListener('click', function() {
                    companyIdInput.value = option.dataset.companyId;
                    selectedLabel.textContent = option.dataset.companyName;
                    updateCompanySelection(combobox, option.dataset.companyId);
                    closeCompanyCombobox(combobox);
                    toggle.focus();
                });
            });
        });

        document.addEventListener('click', function(event) {
            companyComboboxes.forEach(function(combobox) {
                const panel = getCompanyPanel(combobox);
                if (!combobox.contains(event.target) && !panel.contains(event.target)) closeCompanyCombobox(combobox);
            });
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') companyComboboxes.forEach(closeCompanyCombobox);
        });

        // اجرای اولیه صفحه‌بندی
        updatePagination();
    });
</script>
@endsection
