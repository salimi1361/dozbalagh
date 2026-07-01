@extends('layouts.admin')

@section('header_title', 'بایگانی کل پروانه‌های دوزبِلاغ')

@section('content')
<div class="space-y-6">
    <!-- هدر کارت به همراه جستجوگر هوشمند -->
    <div class="bg-gradient-to-r from-slate-950 to-slate-900 text-white rounded-2xl p-6 shadow-xl border border-slate-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-sky-500/10 text-sky-400 rounded-xl border border-sky-500/20 text-2xl">
                🗂️
            </div>
            <div>
                <h2 class="text-lg font-black tracking-wide">بایگانی کل و سوابق دوزبِلاغ</h2>
                <p class="text-slate-400 text-xs mt-1">بانک اطلاعاتی پروانه‌های ابطال‌شده، تحویل‌گرفته‌شده و مفقودی به همراه گزارش زمان‌بندی فرآیندها.</p>
            </div>
        </div>
        
        <!-- 🔍 جستجوگر هدر هوشمند -->
        <div class="relative w-full md:w-80">
            <input type="text" id="archiveSearch" onkeyup="filterArchiveTable()" placeholder="جستجو بر اساس سریال، پرونده یا راننده..." class="w-full bg-slate-800/80 border border-slate-700/60 rounded-xl px-4 py-2.5 text-xs font-bold text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 transition-all text-right" dir="rtl">
            <span class="absolute left-3 top-3 text-slate-500 text-sm">🔍</span>
        </div>
    </div>

    <!-- جدول آرشیو -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-sm text-slate-700 flex items-center gap-2">
                📁 کل پرونده‌های مختومه (<span id="request-count">{{ $requests->total() }}</span>)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse" id="archiveTable">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-100 text-slate-600 text-xs font-bold whitespace-nowrap">
                        <th class="p-4">سریال پروانه</th>
                        <th class="p-4">کد پرونده</th>
                        <th class="p-4">راننده متقاضی</th>
                        <th class="p-4 text-center">ناوگان / پلاک ایران</th>
                        <th class="p-4">کشور مقصد</th>
                        <th class="p-4 text-center">وضعیت نهایی</th>
                        <th class="p-4 text-center">عملیات گزارش و مستندات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 text-sm text-slate-700">
                    @forelse($requests as $index => $req)
                        @php
                            // استخراج بخش‌های مختلف پلاک ترانزیت یا معمولی برای ساخت گرافیک پلاک ایران
                            // فرض می‌کنیم فرمت پلاک در دیتابیس شما شبیه: "32ع93984" یا مشابه است، در غیر این صورت مقدار فیک را با داده مچ می‌کنیم.
                            $plateStr = optional($req->fleet)->transit_plate ?? '۳۲ع۹۳۹۸۴';
                            preg_match_all('/(\d+)|([^\d\s]+)/u', $plateStr, $matches);
                            $pPart1 = $matches[0][0] ?? '84';
                            $pAlphabet = $matches[0][1] ?? 'ع';
                            $pPart2 = $matches[0][2] ?? '939';
                            $pCityCode = $matches[0][3] ?? '32';
                        @endphp
                        <tr class="searchable-row transition-all duration-200 {{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} hover:bg-slate-100">
                            
                            <td class="p-4 font-mono font-black text-slate-700 text-sm target-serial">🎫 {{ $req->serial_number }}</td>
                            <td class="p-4 font-mono text-xs font-bold text-slate-400 target-code">{{ $req->d_code }}</td>
                            
                            <td class="p-4 target-driver">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-900">
                                        {{ $req->driver ? trim(($req->driver->first_name_fa ?? '') . ' ' . ($req->driver->last_name_fa ?? '')) : 'نامشخص' }}
                                    </span>
                                    <span class="text-slate-400 text-[11px]">کد ملی: {{ $req->driver->national_code ?? '---' }}</span>
                                </div>
                            </td>

                            <!-- 🟨 ساخت پلاک فیزیکی استاندارد زرد عمومی زنده (طراحی کاملاً هماهنگ با تصویر image_9d29e4.png) -->
                            <td class="p-4 flex justify-center">
                                <div class="w-[180px] h-[38px] bg-[#fab800] border-2 border-slate-900 rounded-md flex items-center overflow-hidden font-sans font-black text-slate-950 shadow-sm relative select-none" dir="ltr">
                                    <!-- نوار آبی سمت چپ پلاک ایران -->
                                    <div class="w-[18px] h-full bg-[#044391] flex flex-col items-center justify-between py-0.5 text-white text-[7px]">
                                        <div class="flex flex-col items-center gap-0.5 leading-none">
                                            <div class="w-2.5 h-1.5 flex flex-col justify-between">
                                                <div class="h-[33%] bg-[#ff0000]"></div>
                                                <div class="h-[33%] bg-white"></div>
                                                <div class="h-[33%] bg-[#008000]"></div>
                                            </div>
                                            <span class="scale-[0.8] tracking-tighter">I.R.</span>
                                        </div>
                                        <span class="scale-[0.7] font-mono tracking-tighter opacity-80">IRAN</span>
                                    </div>
                                    <!-- بدنه اصلی شماره پلاک -->
                                    <div class="flex-1 flex items-center justify-center gap-2 text-base font-black px-1 tracking-wide">
                                        <span>{{ $pPart1 }}</span>
                                        <span class="text-lg font-serif">{{ $pAlphabet }}</span>
                                        <span>{{ $pPart2 }}</span>
                                    </div>
                                    <!-- کادر سمت راست کد شهر -->
                                    <div class="w-[32px] h-full border-l border-slate-900/60 flex flex-col items-center justify-center leading-none text-center">
                                        <span class="text-[8px] font-bold text-slate-800 tracking-tighter">ایران</span>
                                        <span class="text-xs font-black mt-0.5">{{ $pCityCode }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="p-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ $req->country_name }}
                                </span>
                            </td>

                            <td class="p-4 text-center">
                                @if($req->status === 'archived' || $req->status === 'collected')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        ✅ تحویل لاشه سالم
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-700 border border-rose-100">
                                        ⚠️ مفقود شده
                                    </span>
                                @endif
                            </td>

                            <!-- ستون گزارش کامل زنجیره زمانی و مستندات لاشه فیزیکی -->
                            <td class="p-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- دکمه گزارش تایم‌لاین کامل فرآیند -->
                                    <button onclick="showProcessTimeline({{ json_encode($req) }}, '{{ $req->country_name }}')" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-black transition-all border border-slate-200">
                                        📊 گزارش فرآیند
                                    </button>

                                    @if(isset($req->collected_image))
                                        <a href="{{ asset('storage/' . $req->collected_image) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-black transition-all border border-indigo-100">
                                            👁️ تصویر لاشه
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400 font-bold px-3">---</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-slate-400 bg-white">
                                <div class="text-4xl mb-3">🗂️</div>
                                <p class="font-bold text-sm">هیچ پرونده‌ای هنوز به آرشیو کل منتقل نشده است.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 📄 سیستم صفحه‌بندی هوشمند دقیقاً هر ۱۰ تا گزارش برود صفحه بعدی -->
        @if($requests->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between font-bold text-xs">
                <div class="text-slate-500">نمایش ردیف‌های بایگانی کل دوزبِلاغ</div>
                <div>{{ $requests->links() }}</div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
// 🔍 موتور جستجوگر در هدر برای فیلتر آنی سطرها بدون نیاز به رفرش
function filterArchiveTable() {
    const input = document.getElementById('archiveSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.searchable-row');
    
    rows.forEach(row => {
        const serial = row.querySelector('.target-serial').innerText.toLowerCase();
        const code = row.querySelector('.target-code').innerText.toLowerCase();
        const driver = row.querySelector('.target-driver').innerText.toLowerCase();
        
        if (serial.includes(input) || code.includes(input) || driver.includes(input)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

// 📊 تولید گزارش ارتقا یافته تفکیکی (رفت‌وآمد بین شرکت و انجمن) با نشانگرهای رنگی حرارتی
function showProcessTimeline(req, countryName) {
    const dName = req.driver ? (req.driver.first_name_fa + ' ' + req.driver.last_name_fa) : 'نامشخص';
    
    // فرمت‌دهی دقیق و بررسی تاریخ رویدادها
    const timeStart = req.created_at ? new Date(req.created_at).toLocaleString('fa-IR') : '---';
    const timeApprove = req.approved_at ? new Date(req.approved_at).toLocaleString('fa-IR') : 'درج نشده';
    const timeIssued = req.updated_at ? new Date(req.updated_at).toLocaleString('fa-IR') : 'درج نشده';
    const timeEnd = req.closed_at ? new Date(req.closed_at).toLocaleString('fa-IR') : 'جاری در ترانزیت';

    // تعیین وضعیت حرارتی گام نهایی بر اساس فیلد دیتابیس
    let finalStepBadge = '';
    if (req.status === 'collected' || req.status === 'archived') {
        finalStepBadge = `<span class="px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-500 font-bold text-[10px]">✅ موفق (ابطال شد)</span>`;
    } else if (req.status === 'lost') {
        finalStepBadge = `<span class="px-2 py-0.5 rounded-md bg-rose-500/10 text-rose-500 font-bold text-[10px]">⚠️ بحرانی (مفقودی)</span>`;
    } else {
        finalStepBadge = `<span class="px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-500 font-bold text-[10px]">⏳ در حال حرکت</span>`;
    }

    Swal.fire({
        title: `📝 گزارش تفکیکی چرخه حیات پرونده`,
        html: `
            <div class="text-right space-y-4 max-h-[70vh] overflow-y-auto p-1 text-slate-800" dir="rtl">
                
                <!-- باکس مینی‌مال خلاصه مشخصات با کادربندی تفکیک شده -->
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/70 text-xs space-y-2 font-bold text-slate-600">
                    <div class="flex justify-between items-center"><span>کد پرونده:</span> <span class="text-slate-900 font-mono text-sm bg-white px-2 py-1 rounded-md border border-slate-200">${req.d_code}</span></div>
                    <div class="flex justify-between items-center"><span>سریال اختصاصی:</span> <span class="text-indigo-600 font-mono font-black text-sm bg-indigo-50 px-2 py-1 rounded-md border border-indigo-100">${req.serial_number ?? 'تخصیص نیافته'}</span></div>
                    <div class="flex justify-between items-center"><span>راننده متقاضی:</span> <span class="text-slate-900">${dName}</span></div>
                    <div class="flex justify-between items-center"><span>کشور مقصد:</span> <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">${countryName}</span></div>
                </div>

                <h4 class="text-xs font-black text-slate-500 flex items-center gap-1.5 pt-2">
                    <span>⏳</span> گردش کار و رفت‌وآمد اداری پرونده بین سطوح:
                </h4>

                <!-- ساختار گرافیکی تایم‌لاین مراحل ارتقا یافته با نشانگرهای سمتی -->
                <div class="relative border-r-2 border-indigo-100 mr-2 space-y-4 py-1 text-xs">
                    
                    <!-- گام ۱: مبدا شرکت -->
                    <div class="relative pr-6">
                        <div class="absolute -right-[7px] top-1 w-3 h-3 rounded-full bg-indigo-600 border-2 border-white shadow-sm"></div>
                        <div class="flex items-center justify-between">
                            <span class="font-black text-slate-900">۱. اقدام شرکت حمل‌ونقل (مبدا)</span>
                            <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 text-[10px] font-black border border-indigo-100">🏢 سمت شرکت</span>
                        </div>
                        <p class="text-slate-500 text-[11px] mt-1 leading-relaxed">ثبت الکترونیکی پورتال، الحاق ناوگان و قفل موقت اعتبار کیف پول.</p>
                        <div class="text-[10px] text-slate-400 font-mono mt-1 bg-slate-50 inline-block px-1.5 py-0.5 rounded">⏰ رویداد: ${timeStart}</div>
                    </div>

                    <!-- گام ۲: بررسی انجمن -->
                    <div class="relative pr-6">
                        <div class="absolute -right-[7px] top-1 w-3 h-3 rounded-full bg-sky-500 border-2 border-white shadow-sm"></div>
                        <div class="flex items-center justify-between">
                            <span class="font-black text-slate-900">۲. کارشناسی مدارک و اصالت سنجی</span>
                            <span class="px-1.5 py-0.5 rounded bg-sky-50 text-sky-700 text-[10px] font-black border border-sky-100">⚖️ سمت انجمن</span>
                        </div>
                        <p class="text-slate-500 text-[11px] mt-1 leading-relaxed">بررسی پیوست‌ها، کارت هوشمند راننده و تایید اولیه برای صدور نهایی.</p>
                        <div class="text-[10px] text-slate-400 font-mono mt-1 bg-slate-50 inline-block px-1.5 py-0.5 rounded">⏰ رویداد: ${timeApprove}</div>
                    </div>

                    <!-- گام ۳: کسر انبار انجمن -->
                    <div class="relative pr-6">
                        <div class="absolute -right-[7px] top-1 w-3 h-3 rounded-full bg-amber-500 border-2 border-white shadow-sm"></div>
                        <div class="flex items-center justify-between">
                            <span class="font-black text-slate-900">۳. تخصیص برگه فیزیکی و پرینت دوزبِلاغ</span>
                            <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 text-[10px] font-black border border-amber-100">⚙️ انبار کل</span>
                        </div>
                        <p class="text-slate-500 text-[11px] mt-1 leading-relaxed">کسر قطعی سریال خام از پارت فعال کشور مربوطه، لود و کالیبراسیون مختصات میلی‌متری برگه پرینت و آزادسازی فیزیکی کامیون.</p>
                        <div class="text-[10px] text-slate-400 font-mono mt-1 bg-slate-50 inline-block px-1.5 py-0.5 rounded">⏰ رویداد: ${timeIssued}</div>
                    </div>

                    <!-- گام ۴: فرجام فیزیکی -->
                    <div class="relative pr-6">
                        <div class="absolute -right-[7px] top-1 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white shadow-sm"></div>
                        <div class="flex items-center justify-between">
                            <span class="font-black text-slate-900">۴. ثبت لاشه برگشتی و خاتمه فرآیند اداری</span>
                            ${finalStepBadge}
                        </div>
                        <p class="text-slate-500 text-[11px] mt-1 leading-relaxed">بازگشت ناوگان، آپلود و بهینه‌سازی فشرده تصویر فیزیکی سند و باز شدن دائمی کدهای قفل ناوگان و راننده.</p>
                        <div class="text-[10px] text-slate-400 font-mono mt-1 bg-slate-50 inline-block px-1.5 py-0.5 rounded">⏰ رویداد: ${timeEnd}</div>
                    </div>

                </div>
            </div>
        `,
        confirmButtonText: 'بستن گزارش',
        confirmButtonColor: '#4f46e5'
    });
}
</script>
@endsection