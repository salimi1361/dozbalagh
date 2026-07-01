@extends('layouts.admin')

@section('header_title', 'درخواست‌های معلق دوزبِلاغ')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-2xl p-6 shadow-xl border border-slate-800">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-500/10 text-amber-500 rounded-xl border border-amber-500/20 text-2xl">
                ⏳
            </div>
            <div>
                <h2 class="text-lg font-black tracking-wide">بررسی و اصالت‌سنجی درخواست‌های معلق</h2>
                <p class="text-slate-400 text-xs mt-1">لیست مجوزهای ثبت شده توسط شرکت‌ها که منتظر تایید اولیه یا رد توسط انجمن هستند.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/50">
            <h3 class="font-bold text-sm text-slate-700 flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                درخواست‌های در انتظار بررسی (<span id="request-count">{{ $requests->total() }}</span>)
            </h3>
            
            <div class="relative w-full sm:w-72">
                <input type="text" id="tableSearch" onkeyup="filterTable()" placeholder="جستجو در راننده، ناوگان، کد رهگیری..." class="w-full pl-3 pr-9 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-slate-700">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-sm">
                    🔍
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="permitsTable" class="w-full text-right border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-100 text-slate-600 text-xs font-bold whitespace-nowrap">
                        <th class="p-4">کد سیستم</th>
                        <th class="p-4">کد رهگیری سامانه</th>
                        <th class="p-4">مشخصات راننده</th>
                        <th class="p-4 text-center">ناوگان / پلاک</th>
                        <th class="p-4">کشور مقصد</th>
                        <th class="p-4">نوع پروانه</th>
                        <th class="p-4 text-center">عملیات بررسی انجمن</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 text-sm text-slate-700">
                    @forelse($requests as $index => $req)
                        <!-- 🎨 تفکیک حرفه‌ای و لایت ردیف‌ها با استفاده از کلاس‌های فرد و زوج تیل‌ویند -->
                        <tr id="req-row-{{ $req->id }}" class="permit-row transition-all duration-200 {{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} hover:bg-indigo-50/40">
                            
                            <!-- کد سیستم -->
                            <td class="p-4 font-bold text-slate-400">#{{ $req->id }}</td>
                            
                            <!-- کد رهگیری سامانه -->
                            <td class="p-4 font-mono font-bold text-slate-900">
                                @if(isset($req->d_code))
                                    <span onclick="copyToClipboard('{{ $req->d_code }}', this)" class="cursor-pointer bg-white hover:bg-indigo-50 text-slate-700 hover:text-indigo-600 px-2.5 py-1.5 rounded-xl border border-slate-200 hover:border-indigo-200 transition-all inline-flex items-center gap-1.5 group shadow-sm" title="کلیک جهت کپی کدرهگیری">
                                        <span class="search-target">{{ $req->d_code }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-500 transition-colors"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5" /></svg>
                                    </span>
                                @else
                                    <span class="text-slate-400">---</span>
                                @endif
                            </td>

                            <!-- مشخصات راننده -->
                            <td class="p-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-900 search-target">
                                        {{ $req->driver ? trim(($req->driver->first_name_fa ?? '') . ' ' . ($req->driver->last_name_fa ?? '')) : 'نامشخص' }}
                                    </span>
                                    <span class="text-slate-400 text-[11px] mt-0.5 search-target">کد ملی: {{ $req->driver->national_code ?? $req->driver_id ?? '---' }}</span>
                                </div>
                            </td>

                            <!-- ناوگان / پلاک -->
                            <td class="p-4 flex flex-col items-center justify-center gap-1.5 whitespace-nowrap">
                                <span class="text-xs font-bold text-slate-600 search-target">
                                    💳 کارت هوشمند: <strong class="font-mono text-slate-900">{{ $req->fleet->smart_card_number ?? $req->fleet_id ?? '---' }}</strong>
                                </span>
                                
                                @php
                                    $rawPlate = optional($req->fleet)->transit_plate ?? '';
                                    $plateParts = !empty($rawPlate) ? explode('-', $rawPlate) : [];
                                @endphp
                                @if(count($plateParts) == 4)
                                    <div dir="ltr" class="inline-flex items-stretch border border-slate-900 rounded-md bg-[#fab800] text-slate-950 font-black h-7 overflow-hidden shadow-sm search-target" style="width: 130px; max-width: 130px;">
                                        <div class="bg-[#0033a0] flex flex-col items-center justify-between py-0.5 px-0.5 text-white border-r border-slate-900" style="width: 14px; min-width: 14px;">
                                            <div class="w-full rounded-sm overflow-hidden flex flex-col" style="height: 3px;">
                                                <div class="bg-[#228B22]" style="height: 33.33%;"></div><div class="bg-white" style="height: 33.33%;"></div><div class="bg-[#DA291C]" style="height: 33.33%;"></div>
                                            </div>
                                            <div class="flex flex-col items-center text-[3px] font-sans font-bold tracking-tighter" style="line-height: 1;">
                                                <span>I.R.</span><span>AN</span>
                                            </div>
                                        </div>
                                        <div class="flex-1 flex items-center justify-center gap-1 px-1 text-xs font-mono font-black tracking-wide">
                                            <span>{{ $plateParts[0] }}</span>
                                            <span class="font-sans font-black text-[10px]">{{ $plateParts[1] }}</span>
                                            <span>{{ $plateParts[2] }}</span>
                                        </div>
                                        <div class="border-l border-slate-900 flex flex-col items-center justify-center bg-[#fab800] text-slate-950 font-bold text-center" style="width: 26px; min-width: 26px; line-height: 1;">
                                            <span class="text-[6px]">ایران</span>
                                            <div class="w-full border-t border-slate-900 my-px"></div>
                                            <span class="text-[9px] font-mono">{{ $plateParts[3] }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="bg-slate-50 text-slate-500 px-2 py-0.5 rounded border border-slate-100 text-[11px] font-bold w-fit search-target">{{ $rawPlate ?: 'بدون پلاک' }}</span>
                                @endif
                            </td>

                            <!-- کشور مقصد -->
                            <td class="p-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-sky-50 text-sky-700 border border-sky-100 search-target">
                                    {{ $req->country_name ?? 'نامشخص' }}
                                </span>
                            </td>

                            <!-- نوع پروانه -->
                            <td class="p-4 font-medium text-slate-600">{{ $req->permit_type ?? $req->type ?? 'پروانه ترانزیت' }}</td>

                            <!-- عملیات بررسی انجمن -->
                            <td class="p-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="updateStatus({{ $req->id }}, 'approved')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-sm hover:shadow transition-all flex items-center gap-1 cursor-pointer">
                                        ✨ تایید اولیه
                                    </button>
                                    <button onclick="updateStatus({{ $req->id }}, 'returned')" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-lg shadow-sm hover:shadow transition-all flex items-center gap-1 cursor-pointer">
                                        🔄 برگشت اصلاحی
                                    </button>
                                    <button onclick="updateStatus({{ $req->id }}, 'rejected')" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-lg shadow-sm hover:shadow transition-all flex items-center gap-1 cursor-pointer">
                                        ❌ رد کامل درخواست
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-placeholder">
                            <td colspan="7" class="p-12 text-center text-slate-400 bg-white">
                                <div class="text-4xl mb-3">📁</div>
                                <p class="font-bold text-sm">هیچ درخواست معلقی در کارتابل انجمن صنفی یافت نشد.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between font-bold text-xs">
                <div class="text-slate-500">
                    نمایش {{ $requests->firstItem() }} تا {{ $requests->lastItem() }} از کل {{ $requests->total() }} درخواست معلق
                </div>
                <div class="flex items-center gap-1">
                    @if($requests->onFirstPage())
                        <span class="px-3 py-2 bg-slate-100 text-slate-400 rounded-xl cursor-not-allowed">قبلی</span>
                    @else
                        <a href="{{ $requests->previousPageUrl() }}" class="px-3 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl transition">قبلی</a>
                    @endif

                    @if($requests->hasMorePages())
                        <a href="{{ $requests->nextPageUrl() }}" class="px-3 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl transition">بعدی</a>
                    @else
                        <span class="px-3 py-2 bg-slate-100 text-slate-400 rounded-xl cursor-not-allowed">بعدی</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<div id="copy-toast" class="fixed top-5 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-2xl transition-all duration-300 opacity-0 pointer-events-none z-[200] flex items-center gap-2">
    <span>📋</span> کد رهگیری با موفقیت کپی شد.
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// 📋 تابع کپی بومی و انیمیشنی دقیقاً مطابق منطق پنل شرکت شما
function copyToClipboard(text, element) {
    navigator.clipboard.writeText(text).then(function() {
        const toast = document.getElementById('copy-toast');
        const originalBg = element.className;
        
        element.className = element.className.replace('bg-slate-5', 'bg-emerald-50').replace('border-slate-20', 'border-emerald-300');
        toast.classList.remove('opacity-0');
        toast.classList.add('opacity-100', 'top-8');
        
        setTimeout(function() {
            toast.classList.remove('opacity-100', 'top-8');
            toast.classList.add('opacity-0', 'top-5');
            element.className = originalBg;
        }, 2000);
    }).catch(function(err) {
        console.error('خطا در کپی: ', err);
    });
}

function filterTable() {
    const input = document.getElementById('tableSearch');
    const filter = input.value.toLowerCase();
    const rows = document.getElementsByClassName('permit-row');

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const targets = row.getElementsByClassName('search-target');
        let found = false;

        const systemId = row.cells[0].innerText.toLowerCase();
        const dCode = row.cells[1].innerText.toLowerCase();
        if (systemId.includes(filter) || dCode.includes(filter)) {
            found = true;
        }

        for (let j = 0; j < targets.length; j++) {
            if (targets[j].innerText.toLowerCase().includes(filter)) {
                found = true;
                break;
            }
        }

        if (found) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    }
}

function updateStatus(id, status) {
    let actionText = 'تایید اولیه';
    let confirmBtnColor = '#059669';
    let hasInput = false;
    let inputLabel = '';

    if (status === 'rejected') {
        actionText = 'رد کامل و عودت وجه بلوکه‌شده';
        confirmBtnColor = '#dc2626';
        hasInput = true;
        inputLabel = 'علت رد قطعی درخواست را وارد کنید:';
    } else if (status === 'returned') {
        actionText = 'برگشت پرونده جهت اصلاح مدارک';
        confirmBtnColor = '#d97706';
        hasInput = true;
        inputLabel = 'موارد دارای نقص و نیازمند ویرایش شرکت را بنویسید:';
    }

    Swal.fire({
        title: `آیا از ${actionText} مطمئن هستید؟`,
        icon: 'warning',
        input: hasInput ? 'textarea' : undefined,
        inputLabel: hasInput ? inputLabel : undefined,
        inputPlaceholder: hasInput ? 'توضیحات تکمیلی را اینجا وارد کنید...' : undefined,
        showCancelButton: true,
        confirmButtonColor: confirmBtnColor,
        cancelButtonColor: '#64748b',
        confirmButtonText: 'بله، انجام شود',
        cancelButtonText: 'انصراف',
        inputValidator: (value) => {
            if (hasInput && !value) {
                return 'نوشتن دلیل برای این وضعیت الزامی است!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            let payload = { status: status };
            if (hasInput) {
                payload.reject_reason = result.value;
            }

            fetch(`/web/association/request/process/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ title: 'موفقیت‌آمیز', text: data.message, icon: 'success', confirmButtonText: 'تایید' });
                    document.getElementById(`req-row-${id}`).remove();
                } else {
                    Swal.fire({ title: 'خطا', text: data.message, icon: 'error', confirmButtonText: 'تایید' });
                }
            })
            .catch(err => {
                Swal.fire({ title: 'خطا', text: 'ارتباط با سرور برقرار نشد.', icon: 'error', confirmButtonText: 'تایید' });
            });
        }
    });
}
</script>
@endsection