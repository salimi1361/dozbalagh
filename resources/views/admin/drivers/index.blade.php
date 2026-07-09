@extends('layouts.admin') 

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6" dir="rtl">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-800">مدیریت جامع رانندگان سامانه</h2>
        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full">کل رانندگان: {{ count($drivers) }}</span>
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
            <input type="text" id="table_search" placeholder="جستجو بر اساس نام، کد ملی یا موبایل..." 
                   class="w-full pr-9 pl-4 py-2.5 text-xs border border-slate-200 rounded-xl bg-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition shadow-sm font-medium">
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
                    <th class="px-4 py-3">شرکت فعلی (انتقال راننده)</th>
                    <th class="px-4 py-3 text-center">عملیات خطرناک</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $driver)
                <tr class="border-b border-slate-100 hover:bg-slate-50 transition driver-row">
                    
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
                    
                    <td class="px-4 py-3">
                        <form action="{{ route('admin.drivers.update_company', $driver->id) }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            @method('PUT')
                            <select name="company_id" class="border border-slate-300 rounded-md px-2 py-1 text-sm focus:ring-sky-500 min-w-[260px] flex-1">
                                <option value="" class="text-rose-500 font-bold">-- 🛑 آزاد (بدون شرکت) --</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ $driver->current_company_id == $company->id ? 'selected' : '' }}>
                                        {{ $company->name_fa }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-sky-100 text-sky-700 hover:bg-sky-200 px-3 py-1 rounded text-xs font-bold transition shadow-sm">تغییر</button>
                        </form>
                    </td>

                    <td class="px-4 py-3 text-center">
                        <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="delete-btn bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 px-3 py-1 rounded text-xs font-bold transition">حذف سیستمی</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr id="no_records_row">
                    <td colspan="6" class="p-8 text-center text-slate-400 font-bold">هیچ راننده‌ای در سیستم ثبت نشده است.</td>
                </tr>
                @endforelse

                {{-- ردیف خالی برای زمان جستجو --}}
                <tr id="search_empty_row" class="hidden">
                    <td colspan="6" class="p-8 text-center text-slate-400 font-bold">هیچ راننده‌ای با مشخصات وارد شده یافت نشد.</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- نوار ابزار صفحه‌بندی ۱۰ تایی --}}
    <div id="pagination_controls" class="flex justify-center items-center py-4 bg-slate-50 border-t border-slate-200 flex-wrap gap-1 mt-4 rounded-xl">
        </div>
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