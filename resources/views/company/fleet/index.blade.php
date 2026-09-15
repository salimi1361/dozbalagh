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
        <div class="company-page-header p-6 text-white flex justify-between items-center">
            <div>
                <h1 class="text-base font-black">لیست ناوگان فعال شرکت</h1>
                <p class="text-emerald-100 text-[11px] mt-0.5">آزادسازی ناوگان منوط به تسویه کامل دوزبلاغ‌های فعال می‌باشد.</p>
            </div>
            <button onclick="openFleetModal()" class="bg-white/95 hover:bg-white text-emerald-800 font-black px-5 py-2.5 rounded-xl text-xs shadow-lg shadow-emerald-950/20 transition">
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
                        <th class="p-4 min-w-[190px]">نوع بارگیر (یدک)</th>
                        <th class="p-4 min-w-[220px]">مشخصات ترانزیتی</th>
                        <th class="p-4 text-center">دوزبلاغ فعال</th> 
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                    @forelse($fleets as $f)
                        <tr class="hover:bg-slate-50 transition fleet-row">
                            
                            <td class="p-4 text-center search-target" dir="ltr">
                                <x-iran-plate :plate="$f->transit_plate" size="sm" />
                                @if(false)
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
                                @endif
                            </td>

                            <td class="p-4 font-mono text-center search-target">{{ $f->smart_card_number ?? '---' }}</td>
                            
                            @php
                                $trailerLoadingType = trim((string) ($f->fleet_loading_type_name ?? ''));
                                $organizationLoadingType = trim((string) ($f->truck_type ?? ''));
                                $transitHorse = trim((string) ($f->transit_horse ?? ''));
                                $transitTrailer = trim((string) ($f->transit_trailer ?? ''));
                            @endphp
                            <td class="p-4 text-center search-target align-middle">
                                <div class="mx-auto flex max-w-[220px] flex-col items-center gap-1 rounded-xl border border-indigo-100 bg-indigo-50/60 px-3 py-2">
                                    <span class="text-[9px] font-black text-indigo-500">نوع یدک</span>
                                    <span class="font-black text-slate-800">{{ $trailerLoadingType !== '' ? $trailerLoadingType : 'ثبت نشده' }}</span>
                                </div>
                                @if($organizationLoadingType !== '' && $organizationLoadingType !== $trailerLoadingType)
                                    <div class="mt-1 text-[9px] text-slate-400">عنوان استعلام: {{ $organizationLoadingType }}</div>
                                @endif
                                <span class="hidden">{{ $organizationLoadingType }}</span>
                            </td>

                            <td class="p-4 text-center search-target align-middle">
                                <div class="mx-auto flex max-w-[250px] flex-col gap-1 rounded-xl border border-sky-100 bg-sky-50/60 px-3 py-2 text-right" dir="rtl">
                                    <div class="flex items-center justify-between gap-3 border-b border-sky-100 pb-1">
                                        <span class="text-[9px] font-black text-sky-600">اسب (کشنده)</span>
                                        <span class="font-mono text-[11px] font-black tracking-wide text-slate-800" dir="ltr">{{ $transitHorse !== '' ? $transitHorse : 'ثبت نشده' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-[9px] font-black text-sky-600">یدک (بارگیر)</span>
                                        <span class="font-mono text-[11px] font-black tracking-wide text-slate-800" dir="ltr">{{ $transitTrailer !== '' ? $transitTrailer : 'ثبت نشده' }}</span>
                                    </div>
                                </div>
                                <span class="hidden">{{ $transitHorse }} {{ $transitTrailer }}</span>
                            </td>
                            
                            <td class="p-4 text-center">
                                @if(isset($f->active_dozbalagh_count) && $f->active_dozbalagh_count > 0)
                                    <span class="inline-flex flex-col items-center bg-emerald-100 text-emerald-800 font-black px-3 py-1 rounded-lg text-[11px] shadow-sm">
                                        <span>فعلاً دوزبلاغ فعال دارد</span>
                                        <span class="mt-0.5 text-[10px] text-emerald-600">{{ $f->active_dozbalagh_count }} عدد</span>
                                    </span>
                                @else
                                    <span class="bg-slate-100 text-slate-400 px-3 py-1 rounded-full font-bold inline-block">
                                        ندارد
                                    </span>
                                @endif
                            </td>

                            <td class="p-4 text-center flex justify-center items-center gap-2">
                                <button 
                                    type="button"
                                    onclick="editFleet(this)"
                                    data-smart="{{ $f->smart_card_number }}"
                                    data-loading="{{ $f->truck_type }}"
                                    data-loading-id="{{ $f->fleet_loading_type_id ?? '' }}"
                                    data-loading-code="{{ $f->loading_type_code ?? '' }}"
                                    data-horse="{{ $f->transit_horse }}"
                                    data-trailer="{{ $f->transit_trailer }}"
                                    data-plate="{{ $f->transit_plate }}"
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition">
                                    ✏️ ویرایش
                                </button>
                                
                                @if(!isset($f->active_dozbalagh_count) || $f->active_dozbalagh_count == 0)
                                    <button onclick="releaseFleet('{{ $f->transit_plate }}')" 
                                            class="bg-rose-500 hover:bg-rose-600 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition shadow-sm">
                                        🔓 آزادسازی
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="no_records_row"><td colspan="6" class="p-8 text-center text-slate-400 font-bold">هیچ ناوگانی یافت نشد.</td></tr>
                    @endforelse
                    
                    <tr id="search_empty_row" class="hidden">
                        <td colspan="6" class="p-8 text-center text-slate-400 font-bold">هیچ ناوگانی با مشخصات جستجو شده یافت نشد.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        {{-- 🟢 نوار ابزار صفحه‌بندی ۱۰ تایی --}}
        <div id="pagination_controls" class="flex justify-center items-center py-4 bg-slate-50 border-t border-slate-200 flex-wrap gap-1">
            </div>
    </div>

    <div id="fleet_modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/75 backdrop-blur-sm p-3 sm:p-5">
        <div class="mx-auto my-1 flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="company-page-header flex items-start justify-between gap-3 p-4 text-white sm:p-5">
                <div class="min-w-0">
                    <p class="mb-1 text-[10px] font-bold text-sky-100">ناوگان و رانندگان شرکت</p>
                    <h3 class="text-base font-black sm:text-lg">ثبت و استعلام ناوگان ترانزیتی جدید</h3>
                    <p class="mt-1 text-[10px] font-medium text-sky-100">اطلاعات استعلامی به‌صورت خودکار تکمیل می‌شود؛ در صورت نیاز می‌توانید آن را دستی وارد کنید.</p>
                </div>
                <button type="button" onclick="closeFleetModal()" aria-label="بستن" class="shrink-0 rounded-lg p-1 text-2xl leading-none text-sky-100 transition hover:bg-white/10 hover:text-white">✕</button>
            </div>

            <div class="space-y-3 p-4 sm:p-5">
                <section class="rounded-xl border border-sky-100 bg-sky-50/60 p-3 sm:p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-sky-600 text-xs font-black text-white">۱</span>
                        <div>
                            <h4 class="text-sm font-black text-slate-800">استعلام و شناسایی ناوگان</h4>
                            <p class="text-[10px] text-slate-500">کارت هوشمند برای دریافت اطلاعات از سازمان راهداری استفاده می‌شود.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="m_smart_card" class="mb-1.5 block text-xs font-black text-slate-700">شماره کارت هوشمند <span class="text-rose-500">*</span> <span class="font-medium text-slate-400">(۷ رقم، برای استعلام)</span></label>
                            <input type="text" id="m_smart_card" required minlength="7" maxlength="7" pattern="[0-9]{7}" inputmode="numeric" autocomplete="off" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center font-mono text-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100" placeholder="مثال: 1768168">
                        </div>
                        <div>
                            <label for="m_organization_loading_type" class="mb-1.5 block text-xs font-black text-slate-700">عنوان بارگیر در استعلام راهداری</label>
                            <input type="text" id="m_organization_loading_type" readonly class="mb-2 w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-center text-xs text-slate-500 outline-none" placeholder="پس از استعلام نمایش داده می‌شود">
                            <label for="m_loading_type_id" class="mb-1.5 block text-xs font-black text-slate-700">نوع بارگیر (یدک) <span class="text-rose-500">*</span> <span class="font-medium text-slate-400">(مجاز برای ترانزیت)</span></label>
                            <select id="m_loading_type_id" required disabled class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center text-xs outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100 disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="">ابتدا استعلام بگیرید یا ورود دستی را انتخاب کنید</option>
                                @foreach(($loadingTypes ?? collect()) as $type)
                                    <option value="{{ $type->id }}" data-code="{{ $type->rmto_code }}">{{ $type->name_fa }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="m_loading_type" value="">
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-600 text-xs font-black text-white">۲</span>
                        <div>
                            <h4 class="text-sm font-black text-slate-800">مشخصات ترانزیتی و پلاک</h4>
                            <p class="text-[10px] text-slate-500">همه موارد این بخش برای ذخیره نهایی الزامی است.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="m_transit_horse" class="mb-1.5 block text-xs font-black text-slate-700">شماره ترانزیت اسب (کشنده) <span class="text-rose-500">*</span> <span class="font-medium text-slate-400">(فقط انگلیسی بزرگ و عدد لاتین)</span></label>
                            <input type="text" id="m_transit_horse" required maxlength="100" pattern="[A-Z0-9]+(?:-[A-Z0-9]+)*" lang="en" dir="ltr" autocomplete="off" autocapitalize="characters" spellcheck="false" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center font-mono text-sm uppercase outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100 disabled:bg-slate-100 disabled:text-slate-400" placeholder="مثال: TR-12345">
                        </div>
                        <div>
                            <label for="m_transit_trailer" class="mb-1.5 block text-xs font-black text-slate-700">شماره ترانزیت یدک (بارگیر) <span class="text-rose-500">*</span> <span class="font-medium text-slate-400">(فقط انگلیسی بزرگ و عدد لاتین)</span></label>
                            <input type="text" id="m_transit_trailer" required maxlength="100" pattern="[A-Z0-9]+(?:-[A-Z0-9]+)*" lang="en" dir="ltr" autocomplete="off" autocapitalize="characters" spellcheck="false" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center font-mono text-sm uppercase outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100 disabled:bg-slate-100 disabled:text-slate-400" placeholder="مثال: TR-98765">
                        </div>
                    </div>

                    <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <label class="text-xs font-black text-slate-700">شماره پلاک ایران خودرو <span class="text-rose-500">*</span></label>
                            <span class="text-[10px] font-medium text-slate-400">همهٔ اجزا را کامل کنید</span>
                        </div>
                        <div class="flex justify-center overflow-x-auto py-1" dir="ltr">
                            <div class="inline-flex h-14 min-w-[248px] overflow-hidden rounded-lg border-2 border-slate-800 bg-[#f7b915] text-slate-950 shadow-[0_8px_16px_rgba(15,23,42,0.16)]">
                                <span class="flex w-8 shrink-0 flex-col items-center justify-between border-r-2 border-slate-800 bg-gradient-to-b from-blue-700 to-blue-950 px-1 py-1 text-white" aria-hidden="true">
                                    <span class="flex h-2 w-4 flex-col overflow-hidden rounded-[2px] shadow-sm"><i class="h-1/3 bg-[#239f40]"></i><i class="h-1/3 bg-white"></i><i class="h-1/3 bg-[#da0000]"></i></span>
                                    <span class="flex flex-col text-[5px] font-black leading-none"><b>I.R.</b><b>IRAN</b></span>
                                </span>
                                <span class="flex h-full min-w-[168px] flex-1 items-center justify-center gap-1 px-2">
                                    <span class="flex h-full shrink-0 items-center border-r-2 border-slate-800/70 pr-1">
                                        <input type="text" id="p_p1" required maxlength="2" inputmode="numeric" aria-label="دو رقم اول پلاک" class="h-10 w-11 border-0 bg-transparent px-0 text-center font-black text-xl outline-none transition placeholder:text-slate-500/50 focus:bg-white/30 disabled:cursor-not-allowed disabled:opacity-50 sm:w-12" placeholder="">
                                    </span>
                                    <span class="flex h-full shrink-0 items-center border-r-2 border-slate-800/70 pr-1">
                                        <input type="text" id="p_letter" required maxlength="1" aria-label="حرف ثابت پلاک" class="h-10 w-9 border-0 bg-transparent px-0 text-center font-black text-xl outline-none transition disabled:cursor-not-allowed disabled:opacity-70" value="ع" readonly>
                                    </span>
                                    <span class="flex h-full shrink-0 items-center">
                                        <input type="text" id="p_p2" required maxlength="3" inputmode="numeric" aria-label="سه رقم پلاک" class="h-10 w-14 border-0 bg-transparent px-0 text-center font-black text-xl outline-none transition placeholder:text-slate-500/50 focus:bg-white/30 disabled:cursor-not-allowed disabled:opacity-50 sm:w-16" placeholder="">
                                    </span>
                                </span>
                                <span class="flex w-12 shrink-0 flex-col items-center justify-center border-l-2 border-slate-800 bg-gradient-to-b from-[#ffc928] to-[#f3b300]">
                                    <small class="border-b border-slate-800 pb-0.5 text-[8px] font-black leading-none">ایران</small>
                                    <input type="text" id="p_p3" required maxlength="2" inputmode="numeric" aria-label="کد ایران پلاک" class="h-7 w-10 border-0 bg-transparent px-0 text-center font-black text-lg outline-none transition placeholder:text-slate-500/50 focus:bg-white/30 disabled:cursor-not-allowed disabled:opacity-50" placeholder="">
                                </span>
                            </div>
                        </div>
                        <div class="mt-1 flex justify-center gap-4 text-[9px] font-medium text-slate-400" dir="ltr">
                            <span>دو رقم</span><span>حرف ثابت ع</span><span>سه رقم</span><span>کد ایران</span>
                        </div>
                    </div>
                </section>

                <button type="button" onclick="inquiryFleetLive()" id="btn_inquiry" class="w-full rounded-lg bg-sky-600 py-2.5 text-sm font-black text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60 flex items-center justify-center gap-2">
                    🔍 استعلام از سازمان راهداری
                </button>
            </div>

            <div class="flex flex-col-reverse items-stretch justify-between gap-2 border-t border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-center sm:p-4">
                <p class="text-[10px] font-medium text-slate-500">* تکمیل همه موارد برای ذخیره ناوگان الزامی است.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeFleetModal()" class="rounded-lg px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-200">انصراف</button>
                    <button type="button" onclick="saveFleet()" id="btn_save" class="rounded-lg bg-sky-700 px-5 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-60">💾 ذخیره نهایی اطلاعات ناوگان</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>

<script>
    let isEditMode = false;
    let typingTimer;
    let lastInquiredId = ''; 
    let lastInquiredCar = null;
    let lastLoadingTypeSource = 'manual';

    function openFleetModal() { 
        isEditMode = false;
        lastInquiredCar = null;
        lastLoadingTypeSource = 'manual';
        // 🔒 تمام فیلدها به جز کارت هوشمند را قفل می‌کنیم تا دستی پر نشوند
        $('#m_smart_card').prop('disabled', false).val('');
        $('#m_organization_loading_type').val('').prop('readonly', true).removeClass('bg-white').addClass('bg-slate-100');
        $('#m_loading_type, #m_loading_type_id, #m_transit_horse, #m_transit_trailer, #p_p1, #p_p2, #p_p3').prop('disabled', true).val('');
        $('#p_letter').prop('disabled', true).val('ع'); 
        
        $('#fleet_modal').removeClass('hidden');
        window.DozoleAssistant?.emit('fleet_registration', 'form_opened');
    }
    
    function closeFleetModal() { $('#fleet_modal').addClass('hidden'); }

    function editFleet(btn) {
        isEditMode = true;
        let smart = $(btn).data('smart'); 
        let loading = $(btn).data('loading'); 
        let loadingId = $(btn).data('loading-id');
        let loadingCode = $(btn).data('loading-code');
        let horse = $(btn).data('horse'); 
        let trailer = $(btn).data('trailer'); 
        let plate = $(btn).data('plate');

        // در حالت ویرایش، کارت هوشمند و پلاک قفل هستند که تغییر نکنند
        $('#m_smart_card').val(smart || '').prop('disabled', true);
        lastInquiredCar = null;
        lastLoadingTypeSource = 'manual';
        $('#m_organization_loading_type').val(loading || '').prop('readonly', true).removeClass('bg-white').addClass('bg-slate-100');
        $('#m_loading_type').val(loading || '');
        // نوع بارگیر (یدک) در ویرایش باید قابل تغییر باشد؛ عنوان خام استعلام راهداری ثابت می‌ماند.
        $('#m_loading_type_id').val(loadingId || '').prop('disabled', false);
        if (!loadingId && loadingCode) $('#m_loading_type_id option[data-code="' + loadingCode + '"]').prop('selected', true);
        
        // اجازه ویرایش اسب و یدک
        $('#m_transit_horse').val(normalizeTransitCode(horse || '')).prop('disabled', false);
        $('#m_transit_trailer').val(normalizeTransitCode(trailer || '')).prop('disabled', false);
        
        if (plate && plate !== '') {
            let parts = plate.split('-');
            if(parts.length >= 4) {
                $('#p_p1').val(parts[0]); 
                $('#p_p2').val(parts[2]); 
                $('#p_p3').val(parts[3]);
            }
        }
        $('#p_letter').val('ع').prop('disabled', true).prop('readonly', true);
        $('#p_p1, #p_p2, #p_p3').prop('disabled', true);
        $('#fleet_modal').removeClass('hidden'); 
    }

    function normalizeFleetSmartCard(value) {
        const persianDigits = '۰۱۲۳۴۵۶۷۸۹';
        return String(value || '')
            .replace(/[۰-۹]/g, digit => String(persianDigits.indexOf(digit)))
            .replace(/\D/g, '')
            .slice(0, 7);
    }

    // کدهای ترانزیت فقط با حروف انگلیسی بزرگ، عدد لاتین و خط تیره ثبت می‌شوند.
    // حروف فارسی و ارقام فارسی عمداً تبدیل نمی‌شوند و از ورودی حذف می‌گردند.
    function normalizeTransitCode(value) {
        return String(value || '')
            .toUpperCase()
            .replace(/[^A-Z0-9-]/g, '')
            .replace(/-{2,}/g, '-')
            .slice(0, 100);
    }

    $('#m_transit_horse, #m_transit_trailer').on('input', function() {
        $(this).val(normalizeTransitCode($(this).val()));
    });

    $('#m_smart_card').on('input', function() {
        clearTimeout(typingTimer);
        let smartId = normalizeFleetSmartCard($(this).val());
        $(this).val(smartId);
        if (smartId.length === 7) {
            typingTimer = setTimeout(function() {
                if (!$('#btn_inquiry').prop('disabled') && smartId !== lastInquiredId) {
                    inquiryFleetLive();
                }
            }, 800); 
        } else {
            lastInquiredId = '';
        }
    });

    function enableFleetManualMode() {
        lastLoadingTypeSource = 'manual';
        $('#m_organization_loading_type').prop('readonly', false).removeClass('bg-slate-100').addClass('bg-white');
        $('#m_loading_type_id, #m_transit_horse, #m_transit_trailer, #p_p1, #p_p2, #p_p3').prop('disabled', false);
        $('#p_letter').val('ع').prop('disabled', true).prop('readonly', true);
        $('#btn_inquiry').prop('disabled', false).text('🔍 استعلام از سازمان راهداری');
    }

    function switchFleetToManualMode(noticeMessage, iconType = 'warning') {
        enableFleetManualMode();
        Swal.fire({
            icon: iconType,
            title: 'ورود دستی اطلاعات ناوگان',
            text: `${noticeMessage} فیلدهای فرم باز شد؛ اطلاعات را دستی و دقیق وارد کنید.`,
            confirmButtonText: 'تایید و ادامه',
            confirmButtonColor: '#1e293b',
        }).then(() => $('#m_loading_type_id').focus());
    }

    function inquiryFleetLive() {
        let smartId = normalizeFleetSmartCard($('#m_smart_card').val());
        $('#m_smart_card').val(smartId);
        let btn = $('#btn_inquiry');
        
        if (!/^\d{7}$/.test(smartId)) {
            Swal.fire({ icon: 'warning', title: 'شماره کارت ناقص است', text: 'شماره کارت هوشمند باید دقیقاً ۷ رقم باشد.' });
            return;
        }
        if (smartId === lastInquiredId) return;
        window.DozoleAssistant?.emit('fleet_registration', 'inquiry_started');
        
        lastInquiredId = smartId; 
        btn.text('⏳ در حال استعلام...').prop('disabled', true);
        
        $.post("{{ route('web.company.fleet.inquire') }}", { 
            _token: "{{ csrf_token() }}", 
            smart_id: smartId
        }, function(res) {
            btn.text('🔍 استعلام از سازمان راهداری').prop('disabled', false);

            if (!res || res.success === false) {
                lastInquiredId = '';
                window.DozoleAssistant?.emit('fleet_registration', 'inquiry_failed');
                switchFleetToManualMode(
                    (res && res.message) || 'ارتباط با سازمان راهداری برقرار نشد.',
                    'error'
                );
                return;
            }
            
            let apiData = res.data || {};
            let car = apiData; 
            if (apiData.data && apiData.data.car) {
                car = apiData.data.car; 
            } else if (apiData.car) {
                car = apiData.car;
            }
            lastInquiredCar = car;

            const hasActivityFlag = Object.prototype.hasOwnProperty.call(car, 'IsActive')
                || Object.prototype.hasOwnProperty.call(car, 'is_active');
            if (!hasActivityFlag && !Object.prototype.hasOwnProperty.call(car, 'id')) {
                lastInquiredId = '';
                window.DozoleAssistant?.emit('fleet_registration', 'inquiry_failed');
                switchFleetToManualMode('پاسخ سازمان راهداری کامل دریافت نشد.', 'warning');
                return;
            }
            
            // 🛑 جلوگیری از ثبت ناوگان تکراری
            if (!isEditMode && car.hasOwnProperty('id') && !car.can_register) {
                lastInquiredId = ''; 
                window.DozoleAssistant?.emit('fleet_registration', 'duplicate');
                Swal.fire({ icon: 'warning', title: 'ناوگان تکراری ⚠️', text: 'این ناوگان قبلاً در سیستم ثبت شده است و نیاز به ثبت مجدد ندارد.' });
                return;
            }

            // بررسی معتبر بودن یا فعال بودن در راهداری
            let isActive = (car.IsActive === true || car.is_active === true || car.IsActive === "true" || car.IsActive == 1);
            
            if (!isActive) {
                lastInquiredId = ''; 
                window.DozoleAssistant?.emit('fleet_registration', 'inquiry_failed');
                switchFleetToManualMode('استعلام کارت هوشمند ناموفق بود یا کارت در پاسخ سازمان فعال نبود.', 'warning');
                return;
            }

            let loadingType = car.fleet_loading_type_name || car.LoadingTypeTitle || car.bargir_name || car.truck_type || 'مشخص نشده';
            const loadingTypeId = car.fleet_loading_type_id || '';
            const loadingTypeAllowed = (car.fleet_loading_type_allowed === true || car.fleet_loading_type_allowed === 1 || car.fleet_loading_type_allowed === '1') && !!loadingTypeId;
            $('#m_organization_loading_type').val(loadingType !== 'مشخص نشده' ? loadingType : 'در پاسخ سازمان عنوانی اعلام نشد');

            // عنوان سازمان ممکن است نوع خودِ کشنده باشد (مثل کامیون کشنده)،
            // بنابراین استعلام موفق است اما نوع یدک باید جداگانه انتخاب شود.
            if (loadingTypeAllowed) {
                $('#m_loading_type_id').val(String(loadingTypeId));
            } else {
                lastLoadingTypeSource = 'manual';
                $('#m_loading_type_id').val('');
            }
            let p1 = '', letter = 'ع', p2 = '', p3 = '';

            if (car.transit_plate) {
                let parts = car.transit_plate.split('-');
                if(parts.length >= 4) {
                    p1 = parts[0]; letter = parts[1]; p2 = parts[2]; p3 = parts[3];
                }
            } else {
                p1 = car.plq3 || ''; letter = car.plq2 || 'ع'; p2 = car.plq1 || ''; p3 = car.shomare_serial_plaque || ''; 
            }

            const fixedPlateLetter = 'ع';
            let displayPlate = `${p1} ${fixedPlateLetter} ${p2} | ایران ${p3}`;
            let previewPlate = `${p1}-${fixedPlateLetter}-${p2}-${p3}`;
            let platePreviewHtml = typeof window.renderIranPlate === 'function'
                ? window.renderIranPlate(previewPlate, { size: 'md', className: 'mx-auto' })
                : `<span class="font-mono font-black text-slate-800 bg-slate-200 px-3 py-1 rounded-lg" dir="ltr">${displayPlate}</span>`;
            
            const loadingTypeNotice = loadingTypeAllowed
                ? ''
                : `<div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-6 text-amber-800">استعلام سازمان موفق بود. عنوان «${loadingType}» نوع وسیله/کشنده است؛ نوع بارگیر یدک را از فهرست فرم انتخاب کنید.</div>`;

            Swal.fire({
                title: '📋 پیش‌نمایش اطلاعات ناوگان',
                html: `
                    <div class="text-right text-sm space-y-3 border p-4 rounded-xl bg-slate-50 font-medium mt-2" dir="rtl">
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-slate-500">🔢 هوشمند ناوگان:</span>
                            <span class="font-mono font-bold text-slate-800">${smartId}</span>
                        </div>
                         <div class="flex justify-between border-b border-slate-200 pb-2">
                             <span class="text-slate-500">🚛 نوع بارگیر (یدک):</span>
                            <span class="font-black text-blue-700">${loadingType}</span>
                        </div>
                         <div class="border-t border-slate-200 pt-3">
                            <div class="mb-2 text-slate-500">💳 پلاک سامانه:</div>
                             <div class="flex justify-center rounded-xl bg-slate-100 p-2" dir="ltr">${platePreviewHtml}</div>
                         </div>
                         ${loadingTypeNotice}
                     </div>
                 `,
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: loadingTypeAllowed ? '✅ تایید و بنشان روی فرم' : '✅ تایید و انتخاب نوع یدک',
                cancelButtonText: 'انصراف',
            }).then((result) => {
                if (result.isConfirmed) {
                    // 🔓 باز کردن قفل فرم بعد از تایید اطلاعات راهداری
                    lastLoadingTypeSource = loadingTypeAllowed ? 'rmto' : 'manual';
                    $('#m_loading_type_id').val(loadingTypeAllowed ? String(loadingTypeId) : '').prop('disabled', loadingTypeAllowed);
                    $('#m_organization_loading_type').prop('readonly', loadingTypeAllowed).toggleClass('bg-slate-100', loadingTypeAllowed).toggleClass('bg-white', !loadingTypeAllowed);
                    $('#m_loading_type, #m_transit_horse, #m_transit_trailer, #p_p1, #p_p2, #p_p3').prop('disabled', false);
                    $('#p_letter').val(fixedPlateLetter).prop('disabled', true).prop('readonly', true);
                    
                    if(loadingType !== 'مشخص نشده') $('#m_loading_type').val(loadingType);
                    $('#p_p1').val(p1); 
                    $('#p_p2').val(p2); 
                    $('#p_p3').val(p3);
                    window.DozoleAssistant?.emit('fleet_registration', 'inquiry_succeeded');
                } else {
                    lastInquiredId = ''; 
                }
            });
        }).fail(function(xhr) { 
            btn.text('🔍 استعلام از سازمان راهداری').prop('disabled', false); 
            lastInquiredId = ''; 
            window.DozoleAssistant?.emit('fleet_registration', 'inquiry_failed');
            let message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'سرویس استعلام راهداری در حال حاضر پاسخ نمی‌دهد.';
            if (xhr.status === 409 || xhr.status === 403) {
                Swal.fire({
                    icon: 'warning',
                    title: 'امکان ثبت وجود ندارد',
                    text: message
                });
                return;
            }
            switchFleetToManualMode(message, 'error');
        });
    }
    
    function saveFleet() {
        let btn = $('#btn_save');
        let smartId = normalizeFleetSmartCard($('#m_smart_card').val());
        $('#m_smart_card').val(smartId);

        if (!/^\d{7}$/.test(smartId)) {
            window.DozoleAssistant?.emit('fleet_registration', 'validation_failed');
            Swal.fire({ icon: 'warning', title: 'شماره کارت نامعتبر است', text: 'شماره کارت هوشمند باید دقیقاً ۷ رقم باشد.' });
            return;
        }
        
        // 🛑 جلوگیری از ثبت فرم خالی یا دور زدن استعلام
        if (!$('#p_p1').val() || ($('#p_p1').prop('disabled') && !isEditMode)) {
            window.DozoleAssistant?.emit('fleet_registration', 'validation_failed');
            Swal.fire('خطا ⚠️', 'لطفاً ابتدا کارت هوشمند معتبر وارد کرده و استعلام بگیرید.', 'warning');
            return;
        }

        const requiredFields = [
            ['#m_smart_card', 'شماره کارت هوشمند'],
            ['#m_transit_horse', 'شماره ترانزیت اسب (کشنده)'],
            ['#m_transit_trailer', 'شماره ترانزیت یدک (بارگیر)'],
            ['#p_p1', 'بخش اول پلاک'],
            ['#p_letter', 'حرف پلاک'],
            ['#p_p2', 'بخش دوم پلاک'],
            ['#p_p3', 'کد ایران پلاک'],
        ];
        if (!isEditMode || String($('#m_loading_type_id').val() || '').trim()) {
            requiredFields.splice(1, 0, ['#m_loading_type_id', 'نوع بارگیر (یدک)']);
        }
        const missingFields = requiredFields
            .filter(([selector]) => !String($(selector).val() || '').trim())
            .map(([, label]) => label);
        if (missingFields.length) {
            window.DozoleAssistant?.emit('fleet_registration', 'validation_failed');
            Swal.fire({
                icon: 'warning',
                title: 'تکمیل اطلاعات الزامی است',
                text: `لطفاً این موارد را تکمیل کنید: ${missingFields.join('، ')}`,
            });
            return;
        }

        let plate = $('#p_p1').val() + "-" + $('#p_letter').val() + "-" + $('#p_p2').val() + "-" + $('#p_p3').val();
        btn.prop('disabled', true).text('در حال ذخیره...');
        
        const selectedType = $('#m_loading_type_id option:selected');
        const selectedTypeId = String($('#m_loading_type_id').val() || '').trim();
        const selectedTypeName = selectedType.text().trim();
        const selectedTypeCode = selectedType.data('code') || '';
        const organizationPayload = lastInquiredCar ? JSON.stringify(lastInquiredCar) : '';

        $.post("{{ route('web.company.fleet.store') }}", { 
            _token: "{{ csrf_token() }}", 
            smart_id: smartId,
            loading_type: $('#m_loading_type').val(),
            loading_type_id: selectedTypeId,
            loading_type_code: selectedTypeCode,
            loading_type_source: lastLoadingTypeSource,
            organization_payload: organizationPayload,
            transit_horse: $('#m_transit_horse').val(),
            transit_trailer: $('#m_transit_trailer').val(),
            transit_plate: plate,
            is_edit: isEditMode ? 1 : 0
        }, function(res) { 
            if(res.success) {
                window.DozoleAssistant?.emit('fleet_registration', 'saved');
                location.reload();
            }
            else {
                window.DozoleAssistant?.emit('fleet_registration', 'save_failed', { message: res.message || '' });
                btn.prop('disabled', false).text('💾 ذخیره نهایی اطلاعات ناوگان');
                Swal.fire('خطا', res.message, 'error');
            }
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
