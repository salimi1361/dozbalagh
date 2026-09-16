@extends('layouts.admin') 

@section('header_title', 'مدیریت ناوگان')
@section('hero_subtitle', 'مدیریت جامع ناوگان، وضعیت فعالیت و اتصال وسایل نقلیه به شرکت‌ها')

@section('content')
@php
    $fleetTotal = $fleets->count();
    $activeFleets = $fleets->filter(fn ($fleet) => (bool) ($fleet->is_active ?? true))->count();
    $inactiveFleets = $fleetTotal - $activeFleets;
    $assignedFleets = $fleets->filter(fn ($fleet) => (bool) ($fleet->is_active ?? true))->whereNotNull('company_id')->count();
    $unassignedFleets = $activeFleets - $assignedFleets;
    $activeDozbalaghs = (int) $fleets->sum(fn ($fleet) => (int) ($fleet->active_dozbalaghs ?? 0));
@endphp

<div class="mx-auto max-w-7xl space-y-5" dir="rtl">
    <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
        <div class="pointer-events-none absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
        <div class="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>
        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 17h2l1.5-5h11L19 17h2m-16 0v2h2m12-2v2h-2M7 12l1.5-4h7L17 12M5 17h14M8 19h8"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">مدیریت ناوگان</h1>
                    <p class="mt-1 text-xs font-semibold text-blue-100">مدیریت ناوگان، وضعیت دسترسی و اتصال وسایل نقلیه به شرکت‌ها</p>
                    <button type="button" onclick="openFleetRegistrationModal()" class="mt-3 rounded-xl bg-white px-4 py-2 text-xs font-black text-sky-800 shadow-lg shadow-sky-950/20 transition hover:bg-sky-50">➕ ثبت ناوگان جدید</button>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-6 xl:min-w-[54rem]">
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">کل ناوگان</span><strong class="text-lg font-black">{{ number_format($fleetTotal) }}</strong></div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">ناوگان فعال</span><strong class="text-lg font-black text-emerald-300">{{ number_format($activeFleets) }}</strong></div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">غیرفعال‌شده</span><strong class="text-lg font-black text-rose-200">{{ number_format($inactiveFleets) }}</strong></div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">متصل به شرکت</span><strong class="text-lg font-black text-cyan-200">{{ number_format($assignedFleets) }}</strong></div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">ناوگان آزاد</span><strong class="text-lg font-black text-violet-200">{{ number_format($unassignedFleets) }}</strong></div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">دوزوله فعال</span><strong class="text-lg font-black text-emerald-300">{{ number_format($activeDozbalaghs) }}</strong></div>
            </div>
        </div>
    </section>

<section class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-800">فهرست ناوگان سامانه</h2>
        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full">کل ناوگان: {{ count($fleets) }}</span>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 text-emerald-700 p-4 rounded-md mb-6 font-bold border border-emerald-200">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 text-red-700 p-4 rounded-md mb-6 font-bold border border-red-200">{{ session('error') }}</div>
    @endif

    {{-- کادر جستجوی زنده --}}
    <div class="mb-4 flex flex-col sm:flex-row gap-4 justify-between items-center bg-slate-50 p-4 rounded-xl border border-slate-200">
        <div class="relative w-full sm:w-80">
            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 text-sm">🔍</span>
            <input type="text" id="table_search" placeholder="جستجو بر اساس پلاک یا کارت هوشمند..." 
                   class="w-full pr-9 pl-4 py-2.5 text-xs border border-slate-200 rounded-xl bg-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition shadow-sm font-medium">
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-right text-gray-600 border-collapse">
            <thead class="text-xs text-gray-700 uppercase bg-slate-100 rounded-t-lg border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-center">پلاک ترانزیت (سیستمی)</th>
                    <th class="px-4 py-3 text-center">کارت هوشمند</th>
                    <th class="px-4 py-3 text-center">دوزوله های فعال</th>
                    <th class="px-4 py-3 text-center">وضعیت دسترسی</th>
                    <th class="px-4 py-3">شرکت فعلی (انتقال ناوگان)</th>
                    <th class="px-4 py-3 text-center">عملیات خطرناک</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($fleets as $fleet)
                <tr class="transition fleet-row {{ !($fleet->is_active ?? true) ? 'bg-rose-50/30 hover:bg-rose-50/50' : 'hover:bg-slate-50' }}">
                    
                    <td class="px-4 py-3 text-center search-target" dir="ltr">
                        <x-iran-plate :plate="$fleet->transit_plate" size="sm" />
                        @if(false)
                        @php
                            $plateParts = explode('-', $fleet->transit_plate ?? '');
                            $p1 = $plateParts[0] ?? '';
                            $letter = $plateParts[1] ?? '';
                            $p2 = $plateParts[2] ?? '';
                            $p3 = $plateParts[3] ?? '';
                        @endphp
                        
                        @if(count($plateParts) >= 4)
                            <div class="inline-flex shadow-sm border border-slate-400 rounded-md overflow-hidden bg-[#ffb800] h-8 items-center justify-center text-black font-bold font-mono mx-auto" dir="ltr">
                                <div class="h-full w-4 bg-[#003399] flex flex-col items-center pt-1 border-r border-slate-400">
                                    <div class="w-2 h-1.5 flex flex-col">
                                        <div class="h-1/3 bg-[#239f40]"></div>
                                        <div class="h-1/3 bg-white"></div>
                                        <div class="h-1/3 bg-[#da0000]"></div>
                                    </div>
                                    <span class="text-white text-[4px] mt-1 font-sans tracking-widest origin-center" style="writing-mode: vertical-rl; transform: rotate(180deg);">I.R.IRAN</span>
                                </div>
                                <div class="px-2 text-sm tracking-widest">{{ $p1 }}</div>
                                <div class="px-1 text-sm font-sans">{{ $letter }}</div>
                                <div class="px-2 text-sm tracking-widest">{{ $p2 }}</div>
                                <div class="h-full w-[1px] bg-slate-500/50"></div>
                                <div class="px-2 h-full flex flex-col items-center justify-center min-w-[35px]">
                                    <span class="text-[8px] font-sans leading-none mb-0.5 text-slate-800">ایران</span>
                                    <span class="text-sm leading-none">{{ $p3 }}</span>
                                </div>
                            </div>
                            <span class="hidden">{{ $fleet->transit_plate }}</span>
                        @else
                            <span class="bg-yellow-100 border border-yellow-400 px-3 py-1 rounded text-sm tracking-widest font-mono font-bold">{{ $fleet->transit_plate ?? 'نامشخص' }}</span>
                        @endif
                        @endif
                    </td>
                    
                    <td class="px-4 py-3 font-mono font-bold text-center text-slate-800 search-target" dir="ltr">{{ $fleet->smart_card_number ?? '-' }}</td>
                    
                    <td class="px-4 py-3 text-center">
                        @if($fleet->active_dozbalaghs > 0)
                            <span class="bg-emerald-100 text-emerald-700 border border-emerald-200 px-3 py-1 rounded-full text-xs font-bold shadow-sm">
                                {{ $fleet->active_dozbalaghs }} فعال
                            </span>
                        @else
                            <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-xs">ندارد</span>
                        @endif
                    </td>
                    
                    <td class="px-4 py-3 text-center">
                        @if($fleet->is_active ?? true)
                            <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">فعال</span>
                        @else
                            <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">غیرفعال</span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <form action="{{ route('admin.fleets.update_company', $fleet->id) }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            @method('PUT')
                            <select name="company_id" @disabled(!($fleet->is_active ?? true)) class="border border-slate-300 rounded-md px-2 py-1 text-sm focus:ring-sky-500 min-w-[260px] flex-1 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" class="text-rose-500 font-bold">-- 🛑 آزاد (بدون شرکت) --</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ $fleet->company_id == $company->id ? 'selected' : '' }}>
                                        {{ $company->name_fa }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" @disabled(!($fleet->is_active ?? true)) class="px-3 py-1 rounded text-xs font-bold transition shadow-sm {{ ($fleet->is_active ?? true) ? 'bg-sky-100 text-sky-700 hover:bg-sky-200' : 'cursor-not-allowed bg-slate-100 text-slate-400' }}">تغییر</button>
                        </form>
                    </td>

                    <td class="px-4 py-3 text-center">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <form action="{{ route('admin.fleets.toggle_status', $fleet->id) }}" method="POST" class="status-form" data-status="{{ ($fleet->is_active ?? true) ? 'active' : 'inactive' }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded px-3 py-1 text-xs font-bold transition {{ ($fleet->is_active ?? true) ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">{{ ($fleet->is_active ?? true) ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}</button>
                            </form>
                            <form action="{{ route('admin.fleets.destroy', $fleet->id) }}" method="POST" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="delete-btn bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 px-3 py-1 rounded text-xs font-bold transition">حذف سیستمی</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="no_records_row">
                    <td colspan="6" class="p-8 text-center text-slate-400 font-bold">هیچ ناوگانی در سیستم ثبت نشده است.</td>
                </tr>
                @endforelse
                
                {{-- ردیف خالی برای زمان جستجو --}}
                <tr id="search_empty_row" class="hidden">
                    <td colspan="6" class="p-8 text-center text-slate-400 font-bold">هیچ ناوگانی با مشخصات وارد شده یافت نشد.</td>
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
@include('admin.fleets._registration_modal')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- سیستم هشدار حذف فرم ---
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const form = this.closest('.delete-form');
                Swal.fire({
                    title: 'آیا مطمئن هستید؟',
                    text: "هشدار: آیا از حذف کامل این کامیون از دیتابیس سامانه مطمئن هستید؟ این عملیات غیرقابل بازگشت است!",
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
                    title: isActive ? 'غیرفعال‌سازی ناوگان؟' : 'فعال‌سازی ناوگان؟',
                    text: isActive
                        ? 'این ناوگان در فرایندهای جدید قابل انتخاب نخواهد بود.'
                        : 'این ناوگان دوباره در فرایندهای جدید قابل انتخاب خواهد شد.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: isActive ? '#d97706' : '#059669',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: isActive ? 'بله، غیرفعال شود' : 'بله، فعال شود',
                    cancelButtonText: 'انصراف',
                    fontFamily: 'Vazirmatn'
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

        // =========================================
        // 🟢 منطق صفحه‌بندی ۱۰ تایی + جستجوی زنده 
        // =========================================
        let currentPage = 1;
        const rowsPerPage = 10;

        function updatePagination() {
            let visibleRows = $('.fleet-row:not(.search-hidden)');
            let totalRows = visibleRows.length;
            let totalPages = Math.ceil(totalRows / rowsPerPage);
            
            if (totalPages === 0) totalPages = 1;
            if (currentPage > totalPages) currentPage = totalPages;
            
            $('.fleet-row').hide(); 
            
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

            $('.fleet-row').each(function() {
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
