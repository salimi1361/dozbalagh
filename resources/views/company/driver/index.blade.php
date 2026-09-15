@extends('layouts.app')

@section('header_title')
    مدیریت رانندگان / <span class="text-blue-600 font-black">پرونده‌های تحت پوشش شرکت</span>
@endsection

@section('header_actions')
    <button onclick="openDriverModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 shadow-sm transition">
        ➕ ثبت راننده جدید
    </button>
@endsection

@section('content')
    <div class="max-w-6xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="company-page-header p-6 text-white flex justify-between items-center">
            <div>
                <h1 class="text-base font-black">لیست کارتابل رانندگان فعال</h1>
                <p class="text-emerald-100 text-[11px] mt-0.5">سیستم فینگلیش خودکار و کنترل محدودیت‌های بین‌المللی فعال است.</p>
            </div>
            
            <button onclick="openDriverModal()"
                    class="bg-white/95 hover:bg-white text-emerald-800 font-black px-5 py-2.5 rounded-xl text-xs shadow-lg shadow-emerald-950/20 transition">
                ➕ ثبت راننده جدید
            </button>
        </div>

        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row gap-4 justify-between items-center">
            <div class="relative w-full sm:w-80">
                <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 text-sm">🔍</span>
                <input type="text" id="table_search" placeholder="جستجو بر اساس کد ملی، نام، موبایل یا فینگلیش..." 
                       class="w-full pr-9 pl-4 py-2.5 text-xs border border-slate-200 rounded-xl bg-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition shadow-sm font-medium">
            </div>
            <div class="text-[11px] text-slate-500 font-bold flex gap-2">
                <span>تعداد کل پرونده‌ها:</span>
                <span class="text-slate-800 font-black bg-slate-200 px-2 py-0.5 rounded-full">{{ count($drivers) }} راننده</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs border-collapse" id="drivers_table">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <th class="p-4">کد ملی</th>
                        <th class="p-4">هویت فارسی</th>
                        <th class="p-4">شماره موبایل</th>
                        <th class="p-4 text-blue-600">هویت فینگلیش</th>
                        <th class="p-4 text-center">دوزبلاغ فعال</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                    @forelse($drivers as $d)
                        <tr class="hover:bg-slate-50 transition driver-row">
                            <td class="p-4 font-mono text-slate-600 search-target">{{ $d->national_code }}</td>
                            <td class="p-4 font-bold search-target">{{ $d->first_name_fa }} {{ $d->last_name_fa }}</td>
                            <td class="p-4 font-mono text-slate-600 bg-slate-50/50 search-target">{{ $d->mobile }}</td>
                            <td class="p-4 font-mono font-bold uppercase text-blue-700 search-target">{{ $d->first_name_en }} {{ $d->last_name_en }}</td>
                            
                            <td class="p-4 text-center">
                                @if($d->active_dozbalaghs > 0)
                                    <span class="inline-flex flex-col items-center bg-emerald-100 text-emerald-800 font-black px-3 py-1 rounded-lg text-[11px] shadow-sm">
                                        <span>فعلاً دوزبلاغ فعال دارد</span>
                                        <span class="mt-0.5 text-[10px] text-emerald-600">{{ $d->active_dozbalaghs }} عدد</span>
                                    </span>
                                @else
                                    <span class="bg-slate-100 text-slate-400 font-bold px-3 py-1 rounded-full text-xs">ندارد</span>
                                @endif
                            </td>

                            <td class="p-4 text-center flex justify-center gap-2 flex-wrap">
                                <button onclick="editDriver('{{ $d->national_code }}', '{{ $d->first_name_fa }}', '{{ $d->last_name_fa }}', '{{ $d->first_name_en }}', '{{ $d->last_name_en }}', '{{ $d->passport_number }}', '{{ $d->mobile }}')" 
                                        class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold px-3 py-1.5 rounded-lg text-xs transition">✏️ ویرایش</button>
                                
                                @if($d->active_dozbalaghs == 0)
                                    <button onclick="deleteDriver('{{ $d->national_code }}', 0)"
                                            class="bg-rose-500 hover:bg-rose-600 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition">🗑️ حذف</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="no_records_row"><td colspan="6" class="p-8 text-center text-slate-400 font-bold">رکوردی یافت نشد.</td></tr>
                    @endforelse
                    
                    <tr id="search_empty_row" class="hidden">
                        <td colspan="6" class="p-8 text-center text-slate-400 font-bold">هیچ راننده‌ای با مشخصات جستجو شده یافت نشد.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        {{-- نوار ابزار صفحه‌بندی ۱۰ تایی --}}
        <div id="pagination_controls" class="flex justify-center items-center py-4 bg-slate-50 border-t border-slate-200 flex-wrap gap-1">
            </div>
    </div>

    <div id="driver_modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/70 p-3 backdrop-blur-sm sm:p-4">
        <div class="mx-auto my-2 w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="driver_modal_title">
            <div class="company-page-header flex items-start justify-between gap-3 p-4 text-white">
                <div class="min-w-0">
                    <p class="text-xs font-bold text-emerald-100">ناوگان و رانندگان شرکت</p>
                    <h3 id="driver_modal_title" class="mt-1 text-base font-black">ثبت و استعلام راننده جدید</h3>
                    <p class="mt-1 max-w-2xl text-[11px] leading-5 text-blue-100">استعلام خودکار؛ در صورت عدم پاسخ، ورود دستی اطلاعات.</p>
                </div>
                <div class="flex shrink-0 items-start gap-3">
                    <span id="status_badge" class="rounded-full bg-white/15 px-3 py-1.5 text-[10px] font-black text-white ring-1 ring-white/20">در انتظار استعلام</span>
                    <button type="button" onclick="closeDriverModal()" class="rounded-xl p-1 text-2xl leading-none text-blue-100 transition hover:bg-white/10 hover:text-white" aria-label="بستن فرم">✕</button>
                </div>
            </div>

            <form id="driver_form" class="space-y-3 p-4 sm:p-5" onsubmit="saveDriver(event); return false;">
                <section class="rounded-xl border border-sky-100 bg-sky-50/60 p-3 sm:p-4">
                    <div class="mb-3 flex items-start gap-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-600 text-xs font-black text-white">۱</span>
                        <div>
                            <h4 class="text-sm font-black text-slate-800">استعلام اولیه راننده</h4>
                            <p class="mt-0.5 text-[10px] leading-4 text-slate-500">کد ملی و موبایل الزامی است.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="block text-right text-xs font-black text-slate-700">
                            کد ملی راننده <span class="text-rose-600">*</span>
                            <span class="mr-1 inline text-[10px] font-normal text-slate-500">(۱۰ رقم)</span>
                            <input type="text" id="m_national_code" required maxlength="10" minlength="10" inputmode="numeric" autocomplete="off" placeholder="مثال: ۰۰۱۲۳۴۵۶۷۸" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-center font-mono text-sm tracking-widest outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                        </label>
                        <label class="block text-right text-xs font-black text-slate-700">
                            شماره موبایل راننده <span class="text-rose-600">*</span>
                            <span class="mr-1 inline text-[10px] font-normal text-slate-500">(با ۰۹، ۱۱ رقم)</span>
                            <input type="text" id="m_mobile" required maxlength="11" minlength="11" inputmode="tel" autocomplete="tel" placeholder="مثال: ۰۹۱۲۱۲۳۴۵۶۷" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-center font-mono text-sm tracking-widest outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                            <span class="mt-1 block text-[10px] font-normal text-slate-500">هر شماره موبایل فقط برای یک راننده قابل ثبت است.</span>
                        </label>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <button type="button" onclick="inquiryDriverLive()" id="btn_inquiry" class="rounded-lg bg-sky-600 px-5 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60">🔍 استعلام از راهداری</button>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
                    <div class="mb-3 flex items-start gap-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-xs font-black text-white">۲</span>
                        <div>
                            <h4 class="text-sm font-black text-slate-800">مشخصات مورد نیاز پرونده</h4>
                            <p class="mt-0.5 text-[10px] leading-4 text-slate-500">استعلامی یا ورود دستی؛ همه موارد الزامی است.</p>
                        </div>
                    </div>
                    <div class="mb-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-[10px] leading-5 text-amber-900">
                        <span class="shrink-0 text-sm">⚠️</span>
                        <p><strong>تذکر مهم برای ثبت‌کننده:</strong> این اطلاعات مستقیماً روی دوزوله درج می‌شود. نام و نام خانوادگی لاتین را دقیقاً مطابق پاسپورت و فقط با حروف انگلیسی بزرگ وارد کنید؛ مسئولیت صحت اطلاعات با شرکت ثبت‌کننده است.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="block text-right text-xs font-black text-slate-700">
                            نام فارسی <span class="text-rose-600">*</span>
                            <span class="mr-1 inline text-[10px] font-normal text-slate-500">(مطابق مدرک)</span>
                            <input type="text" id="m_first_name_fa" required maxlength="100" placeholder="نام راننده" oninput="$('#m_first_name_en').val(toFinglish(this.value))" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        </label>
                        <label class="block text-right text-xs font-black text-slate-700">
                            نام خانوادگی فارسی <span class="text-rose-600">*</span>
                            <span class="mr-1 inline text-[10px] font-normal text-slate-500">(مطابق مدرک)</span>
                            <input type="text" id="m_last_name_fa" required maxlength="100" placeholder="نام خانوادگی راننده" oninput="$('#m_last_name_en').val(toFinglish(this.value))" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        </label>
                        <label class="block text-right text-xs font-black text-slate-700">
                            نام لاتین / پاسپورت <span class="text-rose-600">*</span>
                            <span class="mr-1 inline text-[10px] font-normal text-slate-500">(انگلیسی بزرگ)</span>
                            <input type="text" id="m_first_name_en" required maxlength="100" placeholder="مثال: MOHAMMAD" dir="ltr" class="mt-1.5 w-full rounded-lg border border-indigo-200 bg-indigo-50/60 p-2.5 text-left font-mono text-sm uppercase outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        </label>
                        <label class="block text-right text-xs font-black text-slate-700">
                            نام خانوادگی لاتین / پاسپورت <span class="text-rose-600">*</span>
                            <span class="mr-1 inline text-[10px] font-normal text-slate-500">(انگلیسی بزرگ)</span>
                            <input type="text" id="m_last_name_en" required maxlength="100" placeholder="مثال: AHMADI" dir="ltr" class="mt-1.5 w-full rounded-lg border border-indigo-200 bg-indigo-50/60 p-2.5 text-left font-mono text-sm uppercase outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        </label>
                        <label class="block text-right text-xs font-black text-slate-700 md:col-span-2">
                            شماره پاسپورت <span class="text-rose-600">*</span>
                            <span class="mr-1 inline text-[10px] font-normal text-slate-500">(حروف و عدد)</span>
                            <input type="text" id="m_passport" required maxlength="30" placeholder="مثال: A12345678" dir="ltr" class="mt-1.5 w-full rounded-lg border border-amber-300 bg-amber-50/50 p-2.5 text-left font-mono text-sm uppercase outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-100">
                        </label>
                    </div>
                    <p class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-2.5 text-[10px] leading-5 text-slate-600">اطلاعات ثبت‌شده برای صدور و چاپ دوزوله استفاده می‌شود؛ پیش از ذخیره، نام لاتین و شماره پاسپورت را دوباره کنترل کنید.</p>
                </section>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                    <p class="text-[11px] font-bold text-slate-400"><span class="text-rose-600">*</span> تکمیل همهٔ موارد الزامی است.</p>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeDriverModal()" class="rounded-lg bg-slate-100 px-5 py-2.5 text-xs font-black text-slate-700 transition hover:bg-slate-200">انصراف</button>
                        <button type="submit" id="btn_save_driver" class="rounded-lg bg-sky-700 px-6 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-60">💾 ذخیره پرونده</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="notify_modal" class="hidden fixed inset-0 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden">
            <div class="bg-slate-950 p-5 text-white flex justify-between items-center">
                <div>
                    <h3 class="font-black">ارسال پیام به راننده</h3>
                    <p id="notify_recipient_label" class="text-slate-400 text-[11px] mt-1">انتخاب مخاطب</p>
                </div>
                <button onclick="closeNotifyModal()" class="text-slate-400 hover:text-white text-xl">✕</button>
            </div>

            <div class="p-6 space-y-4">
                <input type="hidden" id="notify_scope" value="selected">
                <input type="hidden" id="notify_driver_id" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-black text-slate-600 mb-2">عنوان پیام</label>
                        <input type="text" id="notify_title" maxlength="120" placeholder="مثلا: تغییر برنامه سفر"
                               class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-600 mb-2">نوع پیام</label>
                        <select id="notify_category" class="w-full p-3 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:border-blue-500">
                            <option value="general">اطلاعیه عادی</option>
                            <option value="warning">هشدار مهم</option>
                            <option value="trip_change">تغییر برنامه سفر</option>
                            <option value="action_required">نیازمند اقدام راننده</option>
                            <option value="document">مدارک و مستندات</option>
                            <option value="settlement">مالی و تسویه</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-600 mb-2">اولویت</label>
                        <select id="notify_priority" class="w-full p-3 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:border-blue-500">
                            <option value="normal">عادی</option>
                            <option value="important">مهم</option>
                            <option value="urgent">فوری</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-black text-slate-600 mb-2">متن پیام</label>
                        <textarea id="notify_message" rows="5" maxlength="1000" placeholder="متن قابل مشاهده برای راننده را وارد کنید..."
                                  class="w-full p-3 border border-slate-200 rounded-xl text-sm leading-7 resize-none focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <label class="md:col-span-2 flex items-center gap-2 bg-blue-50 border border-blue-100 p-3 rounded-xl text-xs font-bold text-blue-900">
                        <input type="checkbox" id="notify_requires_ack" class="w-4 h-4">
                        راننده باید پیام را مشاهده/تایید کند.
                    </label>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button onclick="closeNotifyModal()" class="bg-slate-200 text-slate-800 hover:bg-slate-300 px-6 py-3 rounded-xl text-xs font-bold transition">انصراف</button>
                    <button onclick="sendDriverNotification(event)" id="btn_send_notification" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl text-xs font-bold transition">ارسال پیام</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>

@php
    $companyDriversForNotification = $drivers->map(function ($driver) {
        return [
            'id' => $driver->id,
            'name' => trim(($driver->first_name_fa ?? '') . ' ' . ($driver->last_name_fa ?? '')),
            'national_code' => $driver->national_code,
        ];
    })->values();
@endphp

<script>
    const companyDrivers = @json($companyDriversForNotification);

    const finglishDictionary = {
        'محمد': 'Mohammad', 'علی': 'Ali', 'حسن': 'Hassan', 'حسین': 'Hossein', 'رضا': 'Reza', 
        'صادق': 'Sadegh', 'میلاد': 'Milad', 'احمد': 'Ahmad', 'محمود': 'Mahmoud', 'مصطفی': 'Mostafa', 
        'مرتضی': 'Morteza', 'مهدی': 'Mehdi', 'سعید': 'Saeed', 'مسعود': 'Masoud', 'علیرضا': 'Alireza', 
        'محمدرضا': 'Mohammadreza', 'امیر': 'Amir', 'عباس': 'Abbas', 'اکبر': 'Akbar', 'اصغر': 'Asghar', 
        'ابراهیم': 'Ebrahim', 'قاسم': 'Ghasem', 'جواد': 'Javad', 'وحید': 'Vahid', 'حمید': 'Hamid',
        'امین': 'Amin', 'آرش': 'Arash', 'امید': 'Omid', 'هادی': 'Hadi', 'رسول': 'Rasoul',
        'سلیمی': 'Salimi', 'ذاکری': 'Zakeri', 'کریمی': 'Karimi', 'حسینی': 'Hosseini', 'رضایی': 'Rezaei', 
        'محمدی': 'Mohammadi', 'احمدی': 'Ahmadi', 'رحیمی': 'Rahimi', 'امینی': 'Amini', 'حیدری': 'Heidari',
        'مرادی': 'Moradi', 'صالحی': 'Salehi', 'نظری': 'Nazari', 'غفاری': 'Ghaffari', 'ابراهیمی': 'Ebrahimi',
        'پور': 'Pour', 'زاده': 'Zadeh', 'فر': 'Far', 'نژاد': 'Nejad', 'وند': 'Vand', 'نیا': 'Nia'
    };

    const advancedCharMap = {
        'آ': 'A', 'ا': 'A', 'ب': 'B', 'پ': 'P', 'ت': 'T', 'ث': 'S', 'ج': 'J', 'چ': 'Ch', 
        'ح': 'H', 'خ': 'Kh', 'د': 'D', 'ذ': 'Z', 'ر': 'R', 'ز': 'Z', 'ژ': 'Zh', 'س': 'S', 
        'ش': 'Sh', 'ص': 'S', 'ض': 'Z', 'ط': 'T', 'ظ': 'Z', 'ع': 'A', 'غ': 'Gh', 'ف': 'F', 
        'ق': 'Gh', 'ک': 'K', 'گ': 'G', 'ل': 'L', 'م': 'M', 'ن': 'N', 'و': 'O', 'ه': 'H', 
        'ی': 'i', 'ئ': 'Y', 'ء': 'A', 'ي': 'i', 'ك': 'K'
    };

    function toFinglish(str) {
        if (!str) return "";
        str = str.replace(/ي/g, 'ی').replace(/ك/g, 'ک');
        let words = str.trim().split(/\s+/);
        
        let finglishWords = words.map(word => {
            if (finglishDictionary[word]) return finglishDictionary[word];
            
            for (let key in finglishDictionary) {
                if (word.endsWith(key) && word.length > key.length) {
                    let baseWord = word.substring(0, word.length - key.length);
                    return toFinglish(baseWord) + finglishDictionary[key];
                }
            }

            let result = "";
            for (let i = 0; i < word.length; i++) {
                let char = word[i];
                if (char === 'خ' && word[i+1] === 'و' && word[i+2] === 'ا') {
                    result += "Kha";
                    i += 2;
                    continue;
                }
                result += advancedCharMap[char] || char;
            }
            return result;
        });
        
        return finglishWords.join(" ").toUpperCase();
    }
    
    function resetDriverModal() {
        const modal = $('#driver_modal');
        modal.find('input').val('').prop('disabled', false);
        $('#btn_inquiry').prop('disabled', false).text('🔍 استعلام از راهداری').removeClass('bg-amber-600').addClass('bg-sky-600');
        $('#btn_save_driver').prop('disabled', false).text('💾 ذخیره پرونده');
        $('#status_badge').text('در انتظار استعلام').attr('class', 'rounded-full bg-white/15 px-3 py-1.5 text-[10px] font-black text-white ring-1 ring-white/20');
    }

    function openDriverModal() {
        resetDriverModal();
        $('#driver_modal').removeClass('hidden');
        $('#m_national_code').trigger('focus');
    }

    function closeDriverModal() {
        $('#driver_modal').addClass('hidden');
        resetDriverModal();
    }

    function normalizeDigits(value) {
        return String(value || '').replace(/[۰-۹]/g, digit => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)));
    }

    $('#m_national_code, #m_mobile').on('input', function () {
        this.value = normalizeDigits(this.value).replace(/\D/g, '');
    });

    $('#m_first_name_en, #m_last_name_en').on('input', function () {
        this.value = this.value.replace(/[^a-zA-Z ]/g, '').toUpperCase();
    });

    $('#m_passport').on('input', function () {
        this.value = this.value.replace(/[^a-zA-Z0-9 -]/g, '').toUpperCase();
    });

    function openNotifyModal(scope = 'selected', driverId = null) {
        const driver = companyDrivers.find(item => Number(item.id) === Number(driverId));
        $('#notify_scope').val(scope);
        $('#notify_driver_id').val(driverId || '');
        $('#notify_title').val('');
        $('#notify_message').val('');
        $('#notify_category').val('general');
        $('#notify_priority').val(scope === 'all' ? 'important' : 'normal');
        $('#notify_requires_ack').prop('checked', scope === 'all');

        const label = scope === 'all'
            ? `ارسال برای همه رانندگان فعال شرکت (${companyDrivers.length.toLocaleString('fa-IR')} نفر)`
            : `ارسال برای ${driver ? driver.name || driver.national_code : 'راننده انتخاب‌شده'}`;

        $('#notify_recipient_label').text(label);
        $('#notify_modal').removeClass('hidden');
        $('#notify_title').focus();
    }

    function closeNotifyModal() {
        $('#notify_modal').addClass('hidden');
    }

    function sendDriverNotification(event) {
        const btn = $(event.target);
        const originalText = btn.text();
        const scope = $('#notify_scope').val();
        const driverId = $('#notify_driver_id').val();

        const data = {
            _token: "{{ csrf_token() }}",
            scope: scope,
            driver_ids: scope === 'selected' ? [driverId] : [],
            title: $('#notify_title').val(),
            message: $('#notify_message').val(),
            category: $('#notify_category').val(),
            priority: $('#notify_priority').val(),
            requires_acknowledgement: $('#notify_requires_ack').is(':checked') ? 1 : 0,
        };

        if (!data.title.trim() || !data.message.trim()) {
            Swal.fire({ icon: 'warning', title: 'پیام ناقص است', text: 'عنوان و متن پیام را وارد کنید.', confirmButtonText: 'باشه' });
            return;
        }

        btn.prop('disabled', true).text('در حال ارسال...');

        $.post("{{ route('web.company.driver.notify') }}", data, function(res) {
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'پیام ثبت شد',
                    text: res.message,
                    confirmButtonText: 'بروزرسانی جدول',
                    confirmButtonColor: '#2563eb'
                }).then(() => location.reload());
            } else {
                btn.prop('disabled', false).text(originalText);
                Swal.fire({ icon: 'error', title: 'خطا', text: res.message || 'ارسال پیام انجام نشد.', confirmButtonText: 'تایید' });
            }
        }).fail(function(xhr) {
            btn.prop('disabled', false).text(originalText);
            let errMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'خطا در ارتباط با سرور';
            Swal.fire({ icon: 'error', title: 'عدم ارسال پیام', text: errMsg, confirmButtonText: 'تایید', confirmButtonColor: '#e11d48' });
        });
    }
    
    function editDriver(nc, fnf, lnf, fne, lne, pass, mobile) {
        openDriverModal();
        $('#m_national_code').val(nc).prop('disabled', true);
        $('#m_mobile').val(mobile);
        $('#m_first_name_fa').val(fnf); $('#m_last_name_fa').val(lnf);
        $('#m_first_name_en').val(fne); $('#m_last_name_en').val(lne);
        $('#m_passport').val(pass);
    }

    $('#m_national_code, #m_mobile').on('input', function() {
        let natId = $('#m_national_code').val();
        let mobile = $('#m_mobile').val();
        
        if (natId.length === 10 && mobile.length === 11) {
            if (!$('#btn_inquiry').prop('disabled') && $('#m_national_code').is(':enabled')) {
                inquiryDriverLive();
            }
        }
    });

    function inquiryDriverLive() {
        let natId = normalizeDigits($('#m_national_code').val()).replace(/\D/g, '');
        let mobile = normalizeDigits($('#m_mobile').val()).replace(/\D/g, '');
        $('#m_national_code').val(natId);
        $('#m_mobile').val(mobile);
        let btn = $('#btn_inquiry');
        if (!/^\d{10}$/.test(natId) || !/^09\d{9}$/.test(mobile)) {
            Swal.fire({ icon: 'warning', title: 'اطلاعات استعلام ناقص است', text: 'کد ملی باید ۱۰ رقم و موبایل باید ۱۱ رقم و با ۰۹ شروع شود.', confirmButtonText: 'متوجه شدم' });
            return;
        }
        
        btn.text('⏳ استعلام زنده...').addClass('bg-amber-600').removeClass('bg-sky-600').prop('disabled', true);

        $.post("{{ route('web.company.driver.inquire') }}", { _token: "{{ csrf_token() }}", national_id: natId, mobile_number: mobile }, function(res) {
            btn.text('🔍 استعلام از راهداری').addClass('bg-sky-600').removeClass('bg-amber-600').prop('disabled', false);
            
            if (res.success && res.data) {
                let driver = null;
                try {
                    let root = res.data;
                    if (typeof root === 'string') root = JSON.parse(root);
                    
                    if (root) {
                        if (root.driver) {
                            driver = root.driver;
                        } else if (root.data) {
                            let inner = root.data;
                            if (typeof inner === 'string') inner = JSON.parse(inner);
                            
                            if (inner && inner.driver) {
                                driver = inner.driver;
                            } else if (inner && inner.data && inner.data.driver) {
                                driver = inner.data.driver;
                            }
                        }
                    }
                } catch(err) {
                    console.error("خطا در مفسر جی‌سون استعلام:", err);
                }

                if (driver) {
                    let firstName = driver.NAME || '';
                    let lastName = driver.FAMILY || '';

                    if(firstName || lastName) {
                        $('#m_first_name_fa').val(firstName).trigger('input'); 
                        $('#m_last_name_fa').val(lastName).trigger('input');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'اطلاعات هویت یافت شد',
                            text: `مشخصات آقای ${firstName} ${lastName} با موفقیت جایگذاری شد.`,
                            confirmButtonText: 'عالی',
                            confirmButtonColor: '#059669'
                        });
                        return;
                    }
                }
                switchToManualMode("اطلاعات نام و فامیل در پاسخ سازمان یافت نشد. لطفاً دستی وارد کنید.", "warning");
            } else { 
                switchToManualMode("پاسخی از سازمان دریافت نشده است. لطفاً مشخصات را دستی وارد کنید.", "error"); 
            }
        }).fail(function(xhr) {
            btn.text('🔍 استعلام از راهداری').addClass('bg-sky-600').removeClass('bg-amber-600').prop('disabled', false);
            
            let actualErrorMessage = "سرویس استعلام راهداری موقتاً پاسخگو نیست (Timeout). لطفاً دستی وارد کنید.";
            if (xhr.status === 419) {
                actualErrorMessage = "نشست کاری یا توکن امنیت صفحه منقضی شده است. لطفاً یک‌بار صفحه را رفرش (F5) کنید.";
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                actualErrorMessage = xhr.responseJSON.message;
            }
            
            switchToManualMode(actualErrorMessage, "error");
        });
    }

    function switchToManualMode(noticeMessage, iconType = "warning") {
        Swal.fire({
            icon: iconType,
            title: 'وضعیت استعلام',
            text: noticeMessage,
            confirmButtonText: 'تایید و ادامه',
            confirmButtonColor: '#1e293b'
        }).then(() => {
            $('#m_first_name_fa').focus();
        });
    }
    
    function saveDriver(event) {
        event.preventDefault();

        const form = document.getElementById('driver_form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const nationalId = normalizeDigits($('#m_national_code').val()).replace(/\D/g, '');
        const mobile = normalizeDigits($('#m_mobile').val()).replace(/\D/g, '');
        const firstNameFa = $('#m_first_name_fa').val().trim();
        const lastNameFa = $('#m_last_name_fa').val().trim();
        const firstNameEn = $('#m_first_name_en').val().trim().toUpperCase();
        const lastNameEn = $('#m_last_name_en').val().trim().toUpperCase();
        const passportNumber = $('#m_passport').val().trim().toUpperCase();
        const latinNamePattern = /^[A-Z]+(?:[ '-][A-Z]+)*$/;
        const passportPattern = /^[A-Z0-9][A-Z0-9 -]*$/;

        $('#m_national_code').val(nationalId);
        $('#m_mobile').val(mobile);
        $('#m_first_name_en').val(firstNameEn);
        $('#m_last_name_en').val(lastNameEn);
        $('#m_passport').val(passportNumber);

        if (!/^\d{10}$/.test(nationalId) || !/^09\d{9}$/.test(mobile)) {
            Swal.fire({ icon: 'warning', title: 'اطلاعات ناقص است', text: 'کد ملی باید ۱۰ رقم و موبایل باید ۱۱ رقم و با ۰۹ شروع شود.', confirmButtonText: 'متوجه شدم' });
            return;
        }

        if (!latinNamePattern.test(firstNameEn) || !latinNamePattern.test(lastNameEn)) {
            Swal.fire({ icon: 'warning', title: 'نام لاتین نامعتبر است', text: 'نام و نام خانوادگی لاتین را فقط با حروف انگلیسی بزرگ وارد کنید.', confirmButtonText: 'متوجه شدم' });
            return;
        }

        if (!passportPattern.test(passportNumber)) {
            Swal.fire({ icon: 'warning', title: 'شماره پاسپورت نامعتبر است', text: 'شماره پاسپورت باید فقط شامل حروف انگلیسی، عدد، فاصله یا خط تیره باشد.', confirmButtonText: 'متوجه شدم' });
            return;
        }

        const btn = $('#btn_save_driver');
        const originalText = btn.text();
        btn.prop('disabled', true).text('در حال ذخیره...');

        const isEditMode = $('#m_national_code').is(':disabled') ? 1 : 0;
        const data = {
            _token: "{{ csrf_token() }}",
            national_id: nationalId,
            mobile: mobile,
            first_name: firstNameFa,
            last_name: lastNameFa,
            first_name_en: firstNameEn,
            last_name_en: lastNameEn,
            passport_number: passportNumber,
            is_edit: isEditMode
        };

        $.post("/web/company/driver/store", data, function(res) { 
            if(res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'موفقیت‌آمیز',
                    text: res.message,
                    confirmButtonText: 'باشه',
                    confirmButtonColor: '#059669'
                }).then(() => {
                    location.reload();
                });
            } else { 
                btn.prop('disabled', false).text(originalText);
                Swal.fire({ icon: 'error', title: 'خطا', text: res.message, confirmButtonText: 'تایید', confirmButtonColor: '#e11d48' });
            }
        }).fail(function(xhr) {
            btn.prop('disabled', false).text(originalText);
            const response = xhr.responseJSON || {};
            const validationErrors = response.errors ? Object.values(response.errors).flat().join('\n') : '';
            let errMsg = validationErrors || response.message || "سرور خطا داد";
            Swal.fire({ icon: 'error', title: 'عدم ثبت رکورد', text: errMsg, confirmButtonText: 'اصلاح مشخصات', confirmButtonColor: '#e11d48' });
        });
    }

    function deleteDriver(nc, activeCount) {
        if (activeCount > 0) {
            Swal.fire({
                icon: 'error',
                title: 'غیر قابل حذف',
                text: "❌ خطا: این راننده " + activeCount + " دوزبلاغ فعال دارد. ابتدا باید وضعیت آن‌ها تعیین تکلیف شود.",
                confirmButtonText: 'متوجه شدم',
                confirmButtonColor: '#0f172a'
            });
            return;
        }

        Swal.fire({
            title: 'آیا اطمینان دارید؟',
            text: "از شرکت شما حذف گردید آیا اطمینان دارید؟",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'بله، حذف و تایید شود',
            cancelButtonText: 'انصراف',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("{{ route('web.company.driver.delete') }}", { 
                    _token: "{{ csrf_token() }}", 
                    national_code: nc 
                }, function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'عملیات موفق',
                            text: res.message,
                            confirmButtonText: 'بروزرسانی جدول',
                            confirmButtonColor: '#059669'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'خطا', text: res.message, confirmButtonText: 'تایید' });
                    }
                }).fail(function(xhr) {
                    let errMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : "خطای سرور";
                    Swal.fire({ icon: 'error', title: 'خطا در ارتباط', text: errMsg, confirmButtonText: 'تایید', confirmButtonColor: '#e11d48' });
                });
            }
        });
    }

    // =========================================
    // 🛠️ منطق صفحه‌بندی ۱۰ تایی + جستجوی زنده
    // =========================================
    let currentPage = 1;
    const rowsPerPage = 10;

    function updatePagination() {
        let visibleRows = $('.driver-row:not(.search-hidden)');
        let totalRows = visibleRows.length;
        let totalPages = Math.ceil(totalRows / rowsPerPage);
        
        if (totalPages === 0) totalPages = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        
        $('.driver-row').hide(); // مخفی کردن همه
        
        // نمایش فقط ده تاییِ مربوط به صفحه فعلی
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

    $('#table_search').on('keyup input', function() {
        let query = $(this).val().toLowerCase().trim();
        let matchesFound = 0;

        $('.driver-row').each(function() {
            let rowText = $(this).text().toLowerCase();
            let isMatch = rowText.indexOf(query) > -1;
            
            if (isMatch) {
                $(this).removeClass('search-hidden');
                matchesFound++;
            } else {
                $(this).addClass('search-hidden');
            }
        });

        // مدیریت ردیف‌های "یافت نشد" جدول به صورت هوشمند
        if (matchesFound === 0) {
            $('#search_empty_row').removeClass('hidden');
            if($('#no_records_row').length) $('#no_records_row').addClass('hidden');
        } else {
            $('#search_empty_row').addClass('hidden');
        }
        
        // بعد از هر جستجو، برمی‌گردیم به صفحه اول نتایج
        currentPage = 1;
        updatePagination();
    });

    // راه‌اندازی صفحه‌بندی در زمان لود شدن اولیه صفحه
    $(document).ready(function() {
        updatePagination();
    });

</script>
@endsection
