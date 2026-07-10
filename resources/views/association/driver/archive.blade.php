@extends('layouts.admin')

@section('header_title', 'بایگانی کل پروانه‌های دوزوله')

@section('content')
<div class="space-y-6">
    <!-- هدر کارت به همراه جستجوگر هوشمند -->
    <div class="bg-gradient-to-r from-slate-950 to-slate-900 text-white rounded-2xl p-6 shadow-xl border border-slate-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-sky-500/10 text-sky-400 rounded-xl border border-sky-500/20 text-2xl">
                🗂️
            </div>
            <div>
                <h2 class="text-lg font-black tracking-wide">بایگانی کل و سوابق دوزوله</h2>
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

        <div class="px-5 py-4 border-b border-slate-100 bg-white">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h4 class="text-xs font-black text-slate-600">تفکیک بر اساس کشور مقصد</h4>
                <span class="rounded-lg bg-slate-100 px-3 py-1 text-[11px] font-black text-slate-500">
                    {{ number_format($archiveTotalCount ?? 0) }} ردیف
                </span>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('association.archive.index') }}"
                   class="rounded-lg border px-3 py-2 text-xs font-black transition {{ ($archiveCountryFilter ?? 'all') === 'all' ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    همه درخواست‌ها
                    <span class="mr-1 font-mono">{{ number_format($archiveTotalCount ?? 0) }}</span>
                </a>

                @foreach(($archiveCountries ?? collect()) as $country)
                    @php
                        $countryId = (string) ($country->country_id ?? 0);
                        $isActiveCountry = (string) ($archiveCountryFilter ?? 'all') === $countryId;
                    @endphp
                    <a href="{{ route('association.archive.index', ['country_id' => $countryId]) }}"
                       class="rounded-lg border px-3 py-2 text-xs font-black transition {{ $isActiveCountry ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                        {{ $country->country_name }}
                        <span class="mr-1 font-mono">{{ number_format($country->total) }}</span>
                    </a>
                @endforeach
            </div>
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
                            $archivePlate = optional($req->fleet)->transit_plate ?? '';
                        @endphp
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
                                <x-iran-plate :plate="$archivePlate" size="md" />
                                @if(false)
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
                                @endif
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

                                    <button onclick="viewArchiveDocuments({{ $req->id }})" class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-black transition-all border border-emerald-100">
                                        کنترل/تکمیل
                                    </button>

                                    @if(!empty($req->collected_image))
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
                <div class="text-slate-500">نمایش ردیف‌های بایگانی کل دوزوله</div>
                <div>{{ $requests->links() }}</div>
            </div>
        @endif
    </div>
</div>

@include('association.driver.partials.documents-modal')
@endsection

@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
let archiveLoadedFiles = { receipt: '', cmr: '', tir: '', declaration: '' };
let archiveActivePermitId = null;

function viewArchiveDocuments(id) {
    archiveActivePermitId = id;
    $('#documents_modal').removeClass('hidden');
    $('#mdl_d_code').text('در حال بارگذاری...');
    $('#mdl_countries_list').html('<div class="py-4 text-center text-slate-400">در حال بارگذاری کشورهای مسیر...</div>');
    $('#modal_action_buttons').html('<span class="text-[11px] font-bold text-slate-400">ویرایش اطلاعات کنترلی بایگانی</span>');

    fetch('/web/association/request/details/' + id)
        .then(res => {
            if (!res.ok) throw new Error('Server returned ' + res.status);
            return res.json();
        })
        .then(data => {
            if (!data.success) throw new Error(data.message || 'اطلاعات پرونده دریافت نشد.');

            const p = data.permit || {};
            const db = data.dbDetails || {};
            const d = data.driver || {};
            const f = data.fleet || {};

            $('#mdl_d_code').text(p.d_code || '---');
            $('#mdl_driver_name').text(`${d.first_name_fa || ''} ${d.last_name_fa || ''}`.trim() || '---');
            $('#mdl_driver_national').text(d.national_code || '---');
            $('#mdl_fleet_smart').text(f.smart_card_number || f.smart_id || '---');
            $('#mdl_fleet_plate_container').html(window.renderIranPlate(f.transit_plate || '', { size: 'sm' }));
            if (false)
            $('#mdl_fleet_plate_container').html(`<span class="bg-slate-50 text-slate-500 px-2 py-0.5 rounded border border-slate-100 font-bold text-xs">${f.transit_plate || 'بدون پلاک'}</span>`);

            $('#inp_cargo_type').val(db.operation_type || '');
            $('#inp_cits_code').val(db.cits_code || '');
            $('#inp_origin').val(db.loading_origin || '');
            $('#inp_destination').val(db.loading_destination || '');
            $('#inp_receipt_code').val(db.receipt_code || '');
            $('#inp_trip_code').val(db.trip_code || '');
            $('#inp_cmr_date').val(db.cmr_date || '');
            $('#inp_tir_number').val(db.tir_carnet_number || '');
            $('#inp_tir_date').val(db.tir_carnet_date || '');

            const amount = p.total_amount ? parseInt(p.total_amount).toLocaleString('fa-IR') : '0';
            $('#mdl_receipt_amount').text(amount + ' ریال');

            archiveLoadedFiles.receipt = db.receipt_file ? '/storage/' + db.receipt_file : '';
            archiveLoadedFiles.cmr = db.cmr_file ? '/storage/' + db.cmr_file : '';
            archiveLoadedFiles.tir = db.tir_file ? '/storage/' + db.tir_file : '';
            archiveLoadedFiles.declaration = db.declaration_file ? '/storage/' + db.declaration_file : '';

            const countries = data.destinations || [];
            $('#mdl_countries_list').html(countries.length
                ? countries.map(item => `<div class="flex justify-between items-center py-2"><span class="font-bold text-slate-800">${item.country_name || 'نامشخص'}</span><span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-[11px] font-black border border-indigo-100">${item.permit_type || '---'}</span></div>`).join('')
                : '<div class="py-2 text-slate-400 text-center">هیچ کشور مقصدی ثبت نشده است.</div>');

            switchArchiveDocumentImage('receipt');
        })
        .catch((error) => {
            closeDocumentsModal();
            Swal.fire({ title: 'خطا', text: error.message, icon: 'error', confirmButtonText: 'تایید' });
        });
}

$('#btn_save_inline_edit').on('click', function() {
    if (!archiveActivePermitId) return;

    fetch('/web/association/request/inline-update/' + archiveActivePermitId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            cargo_type: $('#inp_cargo_type').val(),
            cits_code: $('#inp_cits_code').val(),
            loading_origin: $('#inp_origin').val(),
            loading_destination: $('#inp_destination').val(),
            receipt_code: $('#inp_receipt_code').val(),
            trip_code: $('#inp_trip_code').val(),
            cmr_date: $('#inp_cmr_date').val(),
            tir_carnet_number: $('#inp_tir_number').val(),
            tir_carnet_date: $('#inp_tir_date').val(),
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ title: 'ذخیره شد', text: data.message, icon: 'success', confirmButtonText: 'تایید' });
        } else {
            Swal.fire({ title: 'خطا', text: data.message, icon: 'error', confirmButtonText: 'تایید' });
        }
    });
});

function switchArchiveDocumentImage(type) {
    $('.doc-tab').removeClass('bg-indigo-600 text-white shadow').addClass('bg-slate-100 text-slate-600 hover:bg-slate-200');
    $(`#tab-${type}`).removeClass('bg-slate-100 text-slate-600 hover:bg-slate-200').addClass('bg-indigo-600 text-white shadow');

    const viewer = document.getElementById('modal_document_viewer');
    const fileUrl = archiveLoadedFiles[type];
    if (fileUrl) {
        viewer.src = fileUrl;
        viewer.style.display = 'block';
    } else {
        viewer.src = '';
        viewer.alt = 'این سند توسط شرکت آپلود نشده است.';
    }
}

function switchDocumentImage(type) {
    switchArchiveDocumentImage(type);
}

function closeDocumentsModal() {
    $('#documents_modal').addClass('hidden');
}
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
let lastArchiveReportHtml = '';
let lastArchiveReportTitle = 'گزارش تفکیکی چرخه حیات پرونده';

function archiveValue(value, fallback = '---') {
    if (value === null || value === undefined || value === '') return fallback;
    return String(value);
}

function archiveEscape(value) {
    return archiveValue(value).replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

function archiveDate(value, fallback = '---') {
    if (!value) return fallback;
    try {
        return new Date(value).toLocaleString('fa-IR');
    } catch (e) {
        return archiveValue(value, fallback);
    }
}

function archiveStatus(req) {
    if (req.status === 'archived' || req.status === 'collected') return { text: 'تحویل لاشه سالم', cls: 'ok' };
    if (req.status === 'lost') return { text: 'مفقودی', cls: 'bad' };
    return { text: 'در جریان', cls: 'wait' };
}

function archiveInfoRow(label, value) {
    return `<div class="archive-report-item"><span>${archiveEscape(label)}</span><strong>${archiveEscape(value)}</strong></div>`;
}

function archiveInfoHtmlRow(label, html) {
    return `<div class="archive-report-item"><span>${archiveEscape(label)}</span><strong>${html}</strong></div>`;
}

function archiveStep(index, title, owner, date, body, tone) {
    return `
        <div class="archive-report-step ${tone}">
            <div class="archive-report-step-dot">${index}</div>
            <div class="archive-report-step-body">
                <div class="archive-report-step-head"><strong>${archiveEscape(title)}</strong><span>${archiveEscape(owner)}</span></div>
                <p>${archiveEscape(body)}</p>
                <small>${archiveEscape(date)}</small>
            </div>
        </div>
    `;
}

function buildArchiveReportHtml(req, countryName, printable = false) {
    const details = req.dbDetails || {};
    const driverName = req.driver ? `${archiveValue(req.driver.first_name_fa, '')} ${archiveValue(req.driver.last_name_fa, '')}`.trim() : 'نامشخص';
    const fleetPlate = req.fleet ? archiveValue(req.fleet.transit_plate || req.fleet.plate_number || req.fleet.smart_card_number) : '---';
    const fleetPlateHtml = window.renderIranPlate(fleetPlate, { size: 'sm' });
    const status = archiveStatus(req);
    const destinations = Array.isArray(req.destinations) ? req.destinations : [];
    const destinationHtml = destinations.length
        ? destinations.map((item) => `
            <tr>
                <td>${archiveEscape(item.country_name)}</td>
                <td>${archiveEscape(item.permit_type)}</td>
                <td>${archiveEscape(item.operation_type)}</td>
                <td>${archiveEscape(item.loading_origin)}</td>
                <td>${archiveEscape(item.loading_destination)}</td>
            </tr>
        `).join('')
        : `<tr><td colspan="5">مقصدی برای این پرونده ثبت نشده است.</td></tr>`;
    const lashImageUrl = req.collected_image ? `/storage/${archiveValue(req.collected_image)}` : '';
    const requestType = req.request_type === 'renewal' ? 'تمدید' : 'درخواست جدید';
    const previousRef = req.previous_serial_number || req.previous_d_code || '---';

    return `
        <style>
            .archive-report-shell{direction:rtl;text-align:right;color:#172033;font-family:inherit}
            .archive-report-head{background:linear-gradient(135deg,#0f172a,#172554);color:#fff;border-radius:18px;padding:18px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:14px}
            .archive-report-head h3{font-size:18px;font-weight:900;margin:0}.archive-report-head p{font-size:11px;color:#cbd5e1;margin:5px 0 0}
            .archive-report-badge{border-radius:999px;padding:7px 12px;font-size:11px;font-weight:900;white-space:nowrap}.archive-report-badge.ok{background:#dcfce7;color:#166534}.archive-report-badge.bad{background:#ffe4e6;color:#be123c}.archive-report-badge.wait{background:#fef3c7;color:#92400e}
            .archive-report-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px;margin-bottom:14px}.archive-report-card{border:1px solid #e2e8f0;border-radius:16px;background:#fff;padding:14px;margin-bottom:12px}
            .archive-report-card h4{margin:0 0 10px;color:#0f172a;font-size:12px;font-weight:900;border-bottom:1px solid #eef2f7;padding-bottom:8px}
            .archive-report-item{display:flex;align-items:center;justify-content:space-between;gap:10px;border-radius:10px;background:#f8fafc;padding:8px 10px;margin-top:7px;font-size:11px}
            .archive-report-item span{color:#64748b;font-weight:800}.archive-report-item strong{color:#0f172a;font-weight:900;text-align:left;direction:rtl}
            .archive-report-table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;font-size:11px}.archive-report-table th{background:#f1f5f9;color:#334155;font-weight:900;padding:9px}.archive-report-table td{padding:9px;border-top:1px solid #e2e8f0;color:#475569;font-weight:800}
            .archive-report-timeline{position:relative;margin:4px 8px 0 0;padding-right:22px;border-right:2px solid #dbeafe}.archive-report-step{position:relative;margin:0 0 13px}.archive-report-step-dot{position:absolute;right:-34px;top:2px;width:24px;height:24px;border-radius:999px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;border:3px solid #fff;box-shadow:0 6px 14px rgba(37,99,235,.22)}
            .archive-report-step.ok .archive-report-step-dot{background:#10b981}.archive-report-step.warn .archive-report-step-dot{background:#f59e0b}.archive-report-step.info .archive-report-step-dot{background:#0ea5e9}.archive-report-step-body{border:1px solid #e2e8f0;border-radius:14px;background:#fff;padding:11px 13px}.archive-report-step-head{display:flex;align-items:center;justify-content:space-between;gap:10px}.archive-report-step-head strong{font-size:12px;color:#0f172a}.archive-report-step-head span{font-size:10px;font-weight:900;background:#eef2ff;color:#3730a3;border-radius:999px;padding:4px 8px}.archive-report-step p{font-size:11px;color:#64748b;line-height:1.8;margin:7px 0}.archive-report-step small{font-size:10px;color:#94a3b8;font-weight:900}
            .archive-report-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}.archive-report-print-btn{border:0;border-radius:11px;background:#2563eb;color:#fff;font-size:12px;font-weight:900;padding:10px 16px;cursor:pointer}.archive-report-link{display:inline-flex;align-items:center;border-radius:10px;background:#eef2ff;color:#3730a3;font-size:11px;font-weight:900;padding:8px 10px;text-decoration:none;margin-top:7px}
            @media(max-width:700px){.archive-report-grid{grid-template-columns:1fr}.archive-report-head{align-items:flex-start;flex-direction:column}}@media print{body{margin:0;background:#fff}.archive-report-shell{padding:18px}.archive-report-actions,.swal2-actions{display:none!important}.archive-report-head{border-radius:0}.archive-report-card,.archive-report-step{break-inside:avoid}}
        </style>
        <div class="archive-report-shell">
            <div class="archive-report-head"><div><h3>گزارش تفکیکی چرخه حیات پرونده</h3><p>کد پرونده ${archiveEscape(req.d_code)} | سریال ${archiveEscape(req.serial_number)}</p></div><span class="archive-report-badge ${status.cls}">${archiveEscape(status.text)}</span></div>
            <div class="archive-report-grid">
                <div class="archive-report-card"><h4>شناسه پرونده</h4>${archiveInfoRow('کد پرونده', req.d_code)}${archiveInfoRow('سریال اختصاصی', req.serial_number)}${archiveInfoRow('نوع درخواست', requestType)}${archiveInfoRow('مرجع تمدید', previousRef)}${archiveInfoRow('کشور مقصد', countryName)}</div>
                <div class="archive-report-card"><h4>راننده و ناوگان</h4>${archiveInfoRow('راننده', driverName)}${archiveInfoRow('کد ملی', req.driver ? req.driver.national_code : '---')}${archiveInfoHtmlRow('ناوگان / پلاک', fleetPlateHtml)}${archiveInfoRow('کارت هوشمند', req.fleet ? (req.fleet.smart_card_number || req.fleet.smart_id || '---') : '---')}</div>
                <div class="archive-report-card"><h4>لاشه و پیک</h4>${archiveInfoRow('نام پیک', req.courier_name)}${archiveInfoRow('موبایل پیک', req.courier_mobile)}${archiveInfoRow('کد تحویل', req.courier_delivery_code)}${archiveInfoRow('زمان تحویل', archiveDate(req.courier_received_at))}${lashImageUrl ? `<a class="archive-report-link" href="${lashImageUrl}" target="_blank">مشاهده تصویر لاشه</a>` : archiveInfoRow('تصویر لاشه', 'ثبت نشده')}</div>
            </div>
            <div class="archive-report-card"><h4>اطلاعات کنترلی پرونده و مدارک</h4><div class="archive-report-grid" style="margin:0"><div>${archiveInfoRow('نوع عملیات/بار', details.operation_type)}${archiveInfoRow('کد جامع CITS', details.cits_code)}${archiveInfoRow('کد سفر', details.trip_code)}</div><div>${archiveInfoRow('مبدا بارگیری', details.loading_origin)}${archiveInfoRow('مقصد نهایی حمل', details.loading_destination)}${archiveInfoRow('شماره فیش', details.receipt_code)}</div><div>${archiveInfoRow('مبلغ فیش', details.receipt_amount)}${archiveInfoRow('تاریخ CMR', details.cmr_date)}${archiveInfoRow('کارنه تیر', details.tir_carnet_number)}${archiveInfoRow('تاریخ کارنه تیر', details.tir_carnet_date)}</div></div></div>
            <div class="archive-report-card"><h4>کشورها و مجوزهای مسیر</h4><table class="archive-report-table"><thead><tr><th>کشور</th><th>نوع مجوز</th><th>نوع عملیات</th><th>مبدا</th><th>مقصد</th></tr></thead><tbody>${destinationHtml}</tbody></table></div>
            <div class="archive-report-card"><h4>گردش کار اداری</h4><div class="archive-report-timeline">${archiveStep(1, 'ثبت درخواست شرکت', 'سمت شرکت', archiveDate(req.created_at), 'ثبت الکترونیکی اطلاعات اولیه، راننده، ناوگان، مسیر و مدارک بارنامه.', 'info')}${archiveStep(2, 'بررسی و تایید انجمن', 'سمت انجمن', archiveDate(req.approved_at), 'کنترل مدارک، اصالت‌سنجی اطلاعات و تایید اولیه برای صدور.', 'info')}${archiveStep(3, 'صدور و تخصیص سریال', 'انبار/صدور', archiveDate(req.issued_at || req.updated_at), 'ثبت سریال، چاپ پروانه و ورود ناوگان به چرخه تردد.', 'warn')}${archiveStep(4, 'تحویل لاشه و بایگانی', 'بایگانی کل', archiveDate(req.closed_at || req.courier_received_at || req.updated_at), 'تایید کد پیک، ثبت لاشه برگشتی و انتقال پرونده به بایگانی کل.', 'ok')}</div></div>
            ${printable ? '' : `<div class="archive-report-actions"><button class="archive-report-print-btn" onclick="printArchiveLifecycleReport()">چاپ گزارش</button></div>`}
        </div>
    `;
}

function printArchiveLifecycleReport() {
    if (!lastArchiveReportHtml) return;
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;
    printWindow.document.write(`<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>${archiveEscape(lastArchiveReportTitle)}</title></head><body>${lastArchiveReportHtml}</body></html>`);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => printWindow.print(), 250);
}

function showArchiveLifecycleReport(req, countryName) {
    lastArchiveReportTitle = `گزارش پرونده ${archiveValue(req.d_code)}`;
    lastArchiveReportHtml = buildArchiveReportHtml(req, countryName, true);
    Swal.fire({
        html: buildArchiveReportHtml(req, countryName, false),
        width: 'min(980px, calc(100vw - 24px))',
        padding: '16px',
        confirmButtonText: 'بستن گزارش',
        confirmButtonColor: '#4f46e5'
    });
}

function showProcessTimeline(req, countryName) {
    showArchiveLifecycleReport(req, countryName);
    return;
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
                            <span class="font-black text-slate-900">۳. تخصیص برگه فیزیکی و پرینت دوزوله</span>
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
