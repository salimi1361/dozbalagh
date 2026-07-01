@extends('layouts.app')

@section('header_title')
    مدیریت ناوگان / <span class="text-blue-600 font-black">ناوگان ترانزیتی تحت پوشش</span>
@endsection

@section('header_actions')
    <button onclick="openFleetModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 shadow-sm transition">
        ➕ ثبت ناوگان جدید
    </button>
@endsection

@section('content')
    <div class="max-w-6xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-950 p-6 text-white flex justify-between items-center">
            <div>
                <h1 class="text-base font-black">لیست ناوگان فعال شرکت</h1>
                <p class="text-slate-400 text-[11px] mt-0.5">آزادسازی ناوگان منوط به تسویه کامل دوزبلاغ‌های فعال می‌باشد.</p>
            </div>
            <button onclick="openFleetModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black px-5 py-2.5 rounded-xl text-xs shadow-lg transition">
                ➕ ثبت ناوگان جدید
            </button>
        </div>

        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row gap-4 justify-between items-center">
            <div class="relative w-full sm:w-80">
                <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 text-sm">🔍</span>
                <input type="text" id="table_search" placeholder="جستجو بر اساس پلاک، کارت هوشمند یا نوع بارگیر..." 
                       class="w-full pr-9 pl-4 py-2.5 text-xs border border-slate-200 rounded-xl bg-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition shadow-sm font-medium">
            </div>
            <div class="text-[11px] text-slate-500 font-bold flex gap-2">
                <span>تعداد کل کامیون‌ها:</span>
                <span class="text-slate-800 font-black bg-slate-200 px-2 py-0.5 rounded-full">{{ count($fleets) }} ناوگان</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs border-collapse" id="fleets_table">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <th class="p-4">پلاک ترانزیت</th>
                        <th class="p-4">کارت هوشمند</th>
                        <th class="p-4">نوع بارگیر</th>
                        <th class="p-4 text-center">دوزبلاغ فعال</th> 
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                    @forelse($fleets as $f)
                        <tr class="hover:bg-slate-50 transition fleet-row">
                            
                            <td class="p-4 text-center search-target" dir="ltr">
                                @php
                                    $plateParts = explode('-', $f->transit_plate);
                                    $p1 = $plateParts[0] ?? '';
                                    $letter = $plateParts[1] ?? '';
                                    $p2 = $plateParts[2] ?? '';
                                    $p3 = $plateParts[3] ?? '';
                                @endphp
                                
                                @if(count($plateParts) >= 4)
                                    <div class="inline-flex shadow-sm border border-slate-400 rounded-md overflow-hidden bg-[#ffb800] h-9 items-center justify-center text-black font-bold font-mono" dir="ltr">
                                        <div class="h-full w-4 bg-[#003399] flex flex-col items-center pt-1 border-r border-slate-400">
                                            <div class="w-2 h-1.5 flex flex-col">
                                                <div class="h-1/3 bg-[#239f40]"></div>
                                                <div class="h-1/3 bg-white"></div>
                                                <div class="h-1/3 bg-[#da0000]"></div>
                                            </div>
                                            <span class="text-white text-[4px] mt-1 font-sans tracking-widest origin-center" style="writing-mode: vertical-rl; transform: rotate(180deg);">I.R.IRAN</span>
                                        </div>
                                        
                                        <div class="px-2 text-[15px] tracking-widest">{{ $p1 }}</div>
                                        <div class="px-1 text-[15px] font-sans">{{ $letter }}</div>
                                        <div class="px-2 text-[15px] tracking-widest">{{ $p2 }}</div>
                                        
                                        <div class="h-full w-[1px] bg-slate-500/50"></div>
                                        
                                        <div class="px-2.5 h-full flex flex-col items-center justify-center min-w-[40px]">
                                            <span class="text-[9px] font-sans leading-none mb-0.5 text-slate-800">ایران</span>
                                            <span class="text-[15px] leading-none">{{ $p3 }}</span>
                                        </div>
                                    </div>
                                    <span class="hidden">{{ $f->transit_plate }}</span>
                                @else
                                    <span class="font-mono text-slate-900 font-bold bg-[#ffb800] px-3 py-1 rounded-lg border border-slate-400">{{ $f->transit_plate }}</span>
                                @endif
                            </td>

                            <td class="p-4 font-mono text-center search-target">{{ $f->smart_card_number ?? '---' }}</td>
                            
                            <td class="p-4 text-center search-target">{{ $f->truck_type ?? '---' }}</td>
                            
                            <td class="p-4 text-center">
                                @if(isset($f->active_dozbalagh_count) && $f->active_dozbalagh_count > 0)
                                    <span class="bg-rose-100 text-rose-700 px-2 py-1 rounded-md font-bold inline-block min-w-[40px]">
                                        {{ $f->active_dozbalagh_count }}
                                    </span>
                                @else
                                    <span class="bg-slate-100 text-slate-400 px-2 py-1 rounded-md font-bold inline-block min-w-[40px]">
                                        0
                                    </span>
                                @endif
                            </td>

                            <td class="p-4 text-center flex justify-center items-center gap-2">
                                <button 
                                    type="button"
                                    onclick="editFleet(this)"
                                    data-smart="{{ $f->smart_card_number }}"
                                    data-loading="{{ $f->truck_type }}"
                                    data-horse="{{ $f->transit_horse }}"
                                    data-trailer="{{ $f->transit_trailer }}"
                                    data-plate="{{ $f->transit_plate }}"
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition">
                                    ✏️ ویرایش
                                </button>
                                
                                @if(isset($f->active_dozbalagh_count) && $f->active_dozbalagh_count > 0)
                                    <span class="bg-slate-200 text-slate-500 font-bold px-3 py-1.5 rounded-lg text-xs flex items-center cursor-not-allowed border border-slate-300" title="به دلیل داشتن بارنامه فعال، امکان آزادسازی وجود ندارد">
                                        🔒 قفل
                                    </span>
                                @else
                                    <button onclick="releaseFleet('{{ $f->transit_plate }}')" 
                                            class="bg-rose-500 hover:bg-rose-600 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition shadow-sm">
                                        🔓 آزادسازی
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="no_records_row"><td colspan="5" class="p-8 text-center text-slate-400 font-bold">هیچ ناوگانی یافت نشد.</td></tr>
                    @endforelse
                    
                    <tr id="search_empty_row" class="hidden">
                        <td colspan="5" class="p-8 text-center text-slate-400 font-bold">هیچ ناوگانی با مشخصات جستجو شده یافت نشد.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        {{-- 🟢 نوار ابزار صفحه‌بندی ۱۰ تایی --}}
        <div id="pagination_controls" class="flex justify-center items-center py-4 bg-slate-50 border-t border-slate-200 flex-wrap gap-1">
            </div>
    </div>

    <div id="fleet_modal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full shadow-2xl overflow-hidden border border-slate-200">
            <div class="bg-slate-950 p-5 flex justify-between items-center text-white">
                <h3 class="font-black text-lg">ثبت و استعلام ناوگان ترانزیتی جدید</h3>
                <button onclick="closeFleetModal()" class="text-slate-400 hover:text-white text-2xl">✕</button>
            </div>
            
            <div class="p-8 space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">شماره کارت هوشمند</label>
                        <input type="text" id="m_smart_card" class="w-full p-3 border rounded-xl text-center font-mono" placeholder="مثال: 1768168">
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">نوع بارگیر / کشنده</label>
                        <input type="text" id="m_loading_type" class="w-full p-3 border rounded-xl text-center" placeholder="مثال: کامیون کشنده ترانزیت">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">شماره ترانزیت اسب (کشنده)</label>
                        <input type="text" id="m_transit_horse" class="w-full p-3 border rounded-xl text-center font-mono uppercase" placeholder="مثال: TR-12345">
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">شماره ترانزیت یدک (بارگیر)</label>
                        <input type="text" id="m_transit_trailer" class="w-full p-3 border rounded-xl text-center font-mono uppercase" placeholder="مثال: TR-98765">
                    </div>
                </div>

                <div class="border border-slate-200 p-4 rounded-xl bg-slate-50">
                    <label class="block text-slate-700 font-bold mb-3 text-sm">شماره پلاک ایران خودرو</label>
                    <div class="flex flex-row-reverse items-center justify-center gap-2" dir="ltr">
                        <input type="text" id="p_p1" maxlength="2" class="w-16 p-3 border rounded-lg text-center font-bold text-lg outline-none" placeholder="44">
                        <input type="text" id="p_letter" maxlength="1" class="w-12 p-3 border rounded-lg text-center font-bold text-lg outline-none" placeholder="ع">
                        <input type="text" id="p_p2" maxlength="3" class="w-20 p-3 border rounded-lg text-center font-bold text-lg outline-none" placeholder="444">
                        <span class="font-bold text-slate-500">ایران</span>
                        <input type="text" id="p_p3" maxlength="2" class="w-16 p-3 border rounded-lg text-center font-bold text-lg outline-none" placeholder="14">
                    </div>
                </div>

                <button onclick="inquiryFleetLive()" id="btn_inquiry" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl transition hover:bg-blue-700 flex items-center justify-center gap-2">
                    🔍 استعلام از سازمان راهداری
                </button>
            </div>

            <div class="bg-slate-50 p-6 border-t flex justify-end gap-3">
                <button onclick="closeFleetModal()" class="px-6 py-3 rounded-xl font-bold text-slate-600">انصراف</button>
                <button onclick="saveFleet()" id="btn_save" class="bg-emerald-600 text-white px-8 py-3 rounded-xl font-bold transition hover:bg-emerald-700">💾 ذخیره نهایی اطلاعات ناوگان</button>
            </div>
        </div>
    </div>
@endsection


@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let isEditMode = false;
    let typingTimer;
    let lastInquiredId = ''; 

    function openFleetModal() { 
        isEditMode = false;
        // 🔒 تمام فیلدها به جز کارت هوشمند را قفل می‌کنیم تا دستی پر نشوند
        $('#m_smart_card').prop('disabled', false).val('');
        $('#m_loading_type, #m_transit_horse, #m_transit_trailer, #p_p1, #p_p2, #p_p3').prop('disabled', true).val('');
        $('#p_letter').prop('disabled', true).val('ع'); 
        
        $('#fleet_modal').removeClass('hidden'); 
    }
    
    function closeFleetModal() { $('#fleet_modal').addClass('hidden'); }

    function editFleet(btn) {
        isEditMode = true;
        let smart = $(btn).data('smart'); 
        let loading = $(btn).data('loading'); 
        let horse = $(btn).data('horse'); 
        let trailer = $(btn).data('trailer'); 
        let plate = $(btn).data('plate');

        // در حالت ویرایش، کارت هوشمند و پلاک قفل هستند که تغییر نکنند
        $('#m_smart_card').val(smart || '').prop('disabled', true);
        $('#m_loading_type').val(loading || '').prop('disabled', true);
        
        // اجازه ویرایش اسب و یدک
        $('#m_transit_horse').val(horse || '').prop('disabled', false);
        $('#m_transit_trailer').val(trailer || '').prop('disabled', false);
        
        if (plate && plate !== '') {
            let parts = plate.split('-');
            if(parts.length >= 4) {
                $('#p_p1').val(parts[0]); 
                $('#p_letter').val(parts[1]); 
                $('#p_p2').val(parts[2]); 
                $('#p_p3').val(parts[3]);
            }
        }
        $('#p_p1, #p_letter, #p_p2, #p_p3').prop('disabled', true);
        $('#fleet_modal').removeClass('hidden'); 
    }

    $('#m_smart_card').on('input', function() {
        clearTimeout(typingTimer);
        let smartId = $(this).val().trim();
        if (smartId.length >= 6) {
            typingTimer = setTimeout(function() {
                if (!$('#btn_inquiry').prop('disabled') && smartId !== lastInquiredId) {
                    inquiryFleetLive();
                }
            }, 800); 
        } else {
            lastInquiredId = '';
        }
    });

    function inquiryFleetLive() {
        let smartId = $('#m_smart_card').val().trim();
        let btn = $('#btn_inquiry');
        
        if (!smartId || smartId === lastInquiredId) return; 
        
        lastInquiredId = smartId; 
        btn.text('⏳ در حال استعلام...').prop('disabled', true);
        
        $.post("{{ route('web.company.fleet.inquire') }}", { 
            _token: "{{ csrf_token() }}", 
            smart_id: smartId
        }, function(res) {
            btn.text('🔍 استعلام از سازمان راهداری').prop('disabled', false);
            
            let apiData = res.data || {};
            let car = apiData; 
            if (apiData.data && apiData.data.car) {
                car = apiData.data.car; 
            } else if (apiData.car) {
                car = apiData.car;
            }
            
            // 🛑 جلوگیری از ثبت ناوگان تکراری
            if (!isEditMode && car.hasOwnProperty('id')) {
                lastInquiredId = ''; 
                Swal.fire({ icon: 'warning', title: 'ناوگان تکراری ⚠️', text: 'این ناوگان قبلاً در سیستم ثبت شده است و نیاز به ثبت مجدد ندارد.' });
                return;
            }

            // بررسی معتبر بودن یا فعال بودن در راهداری
            let isActive = (car.IsActive === true || car.is_active === true || car.IsActive === "true" || car.IsActive == 1);
            
            if (!isActive) {
                lastInquiredId = ''; 
                Swal.fire({ icon: 'error', title: 'یافت نشد / غیرفعال ⛔', text: 'این کارت هوشمند نامعتبر است یا در راهداری غیرفعال می‌باشد.' });
                return;
            }

            let loadingType = car.truck_type || car.LoadingTypeTitle || car.bargir_name || 'مشخص نشده';
            let p1 = '', letter = 'ع', p2 = '', p3 = '';

            if (car.transit_plate) {
                let parts = car.transit_plate.split('-');
                if(parts.length >= 4) {
                    p1 = parts[0]; letter = parts[1]; p2 = parts[2]; p3 = parts[3];
                }
            } else {
                p1 = car.plq3 || ''; letter = car.plq2 || 'ع'; p2 = car.plq1 || ''; p3 = car.shomare_serial_plaque || ''; 
            }

            let displayPlate = `${p1} ${letter} ${p2} | ایران ${p3}`;
            
            Swal.fire({
                title: '📋 پیش‌نمایش اطلاعات ناوگان',
                html: `
                    <div class="text-right text-sm space-y-3 border p-4 rounded-xl bg-slate-50 font-medium mt-2" dir="rtl">
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-slate-500">🔢 هوشمند ناوگان:</span>
                            <span class="font-mono font-bold text-slate-800">${smartId}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-slate-500">🚛 نوع بارگیر:</span>
                            <span class="font-black text-blue-700">${loadingType}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">💳 پلاک سامانه:</span>
                            <span class="font-mono font-black text-slate-800 bg-slate-200 px-3 py-1 rounded-lg" dir="ltr">${displayPlate}</span>
                        </div>
                    </div>
                `,
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: '✅ تایید و بنشان روی فرم',
                cancelButtonText: 'انصراف',
            }).then((result) => {
                if (result.isConfirmed) {
                    // 🔓 باز کردن قفل فرم بعد از تایید اطلاعات راهداری
                    $('#m_loading_type, #m_transit_horse, #m_transit_trailer, #p_p1, #p_letter, #p_p2, #p_p3').prop('disabled', false);
                    
                    if(loadingType !== 'مشخص نشده') $('#m_loading_type').val(loadingType);
                    $('#p_p1').val(p1); 
                    $('#p_letter').val(letter);
                    $('#p_p2').val(p2); 
                    $('#p_p3').val(p3);
                } else {
                    lastInquiredId = ''; 
                }
            });
        }).fail(function(xhr) { 
            btn.text('🔍 استعلام از سازمان راهداری').prop('disabled', false); 
            lastInquiredId = ''; 
            Swal.fire({ icon: 'warning', title: 'خطا', text: 'سرویس استعلام راهداری در حال حاضر پاسخ نمی‌دهد.' });
        });
    }
    
    function saveFleet() {
        let btn = $('#btn_save');
        
        // 🛑 جلوگیری از ثبت فرم خالی یا دور زدن استعلام
        if (!$('#p_p1').val() || ($('#p_p1').prop('disabled') && !isEditMode)) {
            Swal.fire('خطا ⚠️', 'لطفاً ابتدا کارت هوشمند معتبر وارد کرده و استعلام بگیرید.', 'warning');
            return;
        }

        let plate = $('#p_p1').val() + "-" + $('#p_letter').val() + "-" + $('#p_p2').val() + "-" + $('#p_p3').val();
        btn.prop('disabled', true).text('در حال ذخیره...');
        
        $.post("{{ route('web.company.fleet.store') }}", { 
            _token: "{{ csrf_token() }}", 
            smart_id: $('#m_smart_card').val(), 
            loading_type: $('#m_loading_type').val(),
            transit_horse: $('#m_transit_horse').val(),
            transit_trailer: $('#m_transit_trailer').val(),
            transit_plate: plate,
            is_edit: isEditMode ? 1 : 0
        }, function(res) { 
            if(res.success) { location.reload(); } 
            else { btn.prop('disabled', false).text('💾 ذخیره نهایی اطلاعات ناوگان'); Swal.fire('خطا', res.message, 'error'); }
        });
    }

    function releaseFleet(plate) {
        Swal.fire({ title: 'آیا اطمینان دارید؟', icon: 'warning', showCancelButton: true, confirmButtonText: 'بله، آزاد شود', cancelButtonText: 'انصراف' }).then((result) => {
            if (result.isConfirmed) {
                $.post("{{ route('web.company.fleet.release') }}", { _token: "{{ csrf_token() }}", transit_plate: plate }, function(res) {
                    if (res.success) { location.reload(); } 
                    else { Swal.fire('خطا', res.message, 'error'); }
                });
            }
        });
    }

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
        
        $('.fleet-row').hide(); // مخفی کردن همه ردیف‌ها
        
        // نمایش فقط ۱۰ ردیف مربوط به صفحه فعلی
        visibleRows.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage).show();
        
        // ساخت دکمه‌های صفحه‌بندی
        let paginationHTML = '';
        if (totalPages > 1) {
            for (let i = 1; i <= totalPages; i++) {
                let activeClass = (i === currentPage) 
                    ? 'bg-blue-600 text-white shadow-md border-blue-600' 
                    : 'bg-white text-slate-700 hover:bg-slate-100 border-slate-300';
                
                paginationHTML += `<button onclick="goToPage(${i})" class="px-3 py-1.5 border rounded-lg mx-1 ${activeClass} text-xs font-bold transition">${i}</button>`;
            }
        }
        $('#pagination_controls').html(paginationHTML);
    }

    window.goToPage = function(page) {
        currentPage = page;
        updatePagination();
    };

    // سیستم جستجوی زنده هماهنگ شده با صفحه‌بندی
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
        
        // ریست کردن به صفحه اول بعد از هر جستجو
        currentPage = 1;
        updatePagination();
    });

    // راه‌اندازی صفحه‌بندی در زمان لود شدن صفحه
    $(document).ready(function() {
        updatePagination();
    });
</script>
@endsection