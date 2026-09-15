@extends('layouts.admin')

@section('header_title')
    انبار کل / <span class="text-indigo-600 font-black">مدیریت سریال های دوزوله</span>
@endsection

@section('content')
    @php($permitTypeLabels = config('permit_types.labels', []))
    <div class="mx-auto max-w-7xl space-y-4">
        <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
            <div class="pointer-events-none absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
            <div class="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>
            <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 8.25 12 3 3 8.25m18 0-9 5.25m9-5.25v7.5L12 21m0-7.5L3 8.25m9 5.25V21m-9-12.75v7.5L12 21"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-black sm:text-2xl">مدیریت انبار دوزوله</h1>
                        <p class="mt-1 text-xs font-semibold text-blue-100">تولید پارت، کنترل سریال‌ها و پایش موجودی انبار مرکزی سامانه</p>
                    </div>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div class="rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 text-center"><span class="block text-[9px] font-bold text-blue-100">پارت‌ها</span><strong class="mt-0.5 block text-lg font-black">{{ number_format($batches->count()) }}</strong></div>
                        <div class="rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 text-center"><span class="block text-[9px] font-bold text-blue-100">کل سریال‌ها</span><strong class="mt-0.5 block text-lg font-black text-cyan-200">{{ number_format($stats->sum()) }}</strong></div>
                        <div class="rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 text-center"><span class="block text-[9px] font-bold text-blue-100">موجودی خام</span><strong class="mt-0.5 block text-lg font-black text-emerald-300">{{ number_format($stats->get('raw', 0)) }}</strong></div>
                        <div class="rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 text-center"><span class="block text-[9px] font-bold text-blue-100">صادرشده</span><strong class="mt-0.5 block text-lg font-black text-amber-300">{{ number_format($stats->get('issued', 0)) }}</strong></div>
                    </div>
                    <button onclick="openInventoryModal()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-black text-blue-800 shadow-md transition hover:bg-blue-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        تولید سریال جدید
                    </button>
                </div>
            </div>
        </section>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div><h2 class="font-black text-slate-800">فهرست پارت‌های انبار مرکزی</h2><p class="mt-1 text-xs text-slate-500">هر پارت فقط برای ترکیب مشخص «کشور + نوع مجوز» قابل تخصیص است.</p></div>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full text-right text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                        <th class="p-4 w-16 text-center">پارت</th>
                        <th class="p-4">کشور مقصد</th>
                        <th class="p-4">نوع مجوز</th>
                        <th class="p-4 text-center">از سریال</th>
                        <th class="p-4 text-center">تا سریال</th>
                        <th class="p-4 text-center">تعداد کل</th>
                        <th class="p-4 text-center">مانده خام</th>
                        <th class="p-4 text-center">مصرف شده</th>
                        <th class="p-4 text-center">تاریخ ثبت/تحویل</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                    @forelse($batches as $batch)
                        <tr class="hover:bg-indigo-50/50 transition">
                            <td class="p-4 text-center text-slate-400 font-mono">#{{ $batch->id }}</td>
                            <td class="p-4 font-bold text-slate-700">{{ $batch->country ? $batch->country->name : ($batch->country_name ?: '-') }}</td>
                            <td class="p-4">
                                @if($batch->permit_type)
                                    <span class="inline-flex rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-black text-indigo-700">{{ $permitTypeLabels[$batch->permit_type] ?? $batch->permit_type }}</span>
                                @else
                                    <span class="inline-flex rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-black text-rose-700">نیازمند تعیین نوع مجوز</span>
                                @endif
                            </td>
                            <td class="p-4 text-center font-mono text-indigo-600 font-bold">{{ $batch->serial_start }}</td>
                            <td class="p-4 text-center font-mono text-indigo-600 font-bold">{{ $batch->serial_end }}</td>
                            <td class="p-4 text-center">
                                <span class="bg-slate-100 px-3 py-1 rounded-md text-xs font-bold text-slate-700">{{ $batch->total_quantity }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-emerald-50 text-emerald-700 px-3 py-1 rounded-md text-xs font-black border border-emerald-200">{{ number_format($batch->remaining_count) }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-amber-50 text-amber-700 px-3 py-1 rounded-md text-xs font-black border border-amber-200">{{ number_format($batch->consumed_count) }}</span>
                            </td>
                            <td class="p-4 text-center text-slate-500 text-xs">
                                @if($batch->received_date)
                                    <strong dir="ltr" class="block font-mono text-slate-700">{{ $batch->received_date->format('Y/m/d') }}</strong>
                                    <span class="mt-1 block text-[10px] text-slate-400">شمسی: {{ verta($batch->received_date)->format('Y/m/d') }}</span>
                                @elseif($batch->created_at)
                                    <strong dir="ltr" class="block font-mono text-slate-700">{{ $batch->created_at->format('Y/m/d') }}</strong>
                                    <span class="mt-1 block text-[10px] text-amber-600">تاریخ قدیمی سیستم</span>
                                @else
                                    -
                                @endif
                                @if($batch->expiry_date)
                                    <span dir="ltr" class="mt-1 block text-[10px] font-bold text-amber-600">پایان: {{ $batch->expiry_date->format('Y/m/d') }}</span>
                                @endif
                            </td>
                            <td class="p-4 text-center flex items-center justify-center gap-2">
                                {{-- 🟢 اصلاح دکمه: تولید مستقیم و بدون باگِ آدرسِ فیزیکی با هلپر action اتمیک لاراول --}}
                                <button type="button"
                                        onclick="showAvailableSerials(this, '{{ $batch->id }}')"
                                        data-country="{{ $batch->country ? $batch->country->name : ($batch->country_name ?: '') }}"
                                        data-permit="{{ $permitTypeLabels[$batch->permit_type] ?? ($batch->permit_type ?: 'نوع مجوز نامشخص') }}"
                                        data-route="{{ route('admin.inventory.available-serials', $batch->id) }}"
                                        class="text-indigo-600 font-bold hover:text-indigo-800 transition text-xs bg-indigo-50 px-2.5 py-1.5 rounded-lg border border-indigo-200 cursor-pointer">
                                    👁️ سریال‌های موجود
                                </button>

                                <a href="{{ route('admin.inventory.edit', $batch->id) }}" class="text-slate-600 font-bold hover:text-slate-900 transition text-xs bg-slate-100 px-2.5 py-1.5 rounded-lg border border-slate-200">✏️ ویرایش</a>

                                <form action="{{ route('admin.inventory.destroy', $batch->id) }}" method="POST" class="inline-block m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDelete(this)" class="text-rose-500 font-bold hover:text-rose-700 transition text-xs flex items-center justify-center gap-1 mx-auto cursor-pointer">
                                        🗑️ حذف
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="p-10 text-center text-slate-400 font-bold">موردی یافت نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- 📊 مودال نمایش کدهای مصرف‌نشده انبار --}}
    <div id="serials_report_modal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl overflow-hidden border border-slate-200">
            <div class="bg-slate-950 p-5 flex justify-between items-center text-white">
                <h3 class="font-black text-base">📊 لیست شماره‌های مصرف‌نشده (<span id="report_country_title"></span>)</h3>
                <button onclick="closeReportModal()" class="text-slate-400 hover:text-white text-xl">✕</button>
            </div>
            <div class="p-6">
                <p class="text-xs text-slate-500 mb-4 font-bold">تمام شماره‌های نمایش داده شده در زیر، در انبار خام موجود و آماده تخصیص هستند:</p>
                <div id="serials_container" class="grid grid-cols-5 gap-2 max-h-60 overflow-y-auto p-3 border border-slate-200 rounded-xl bg-slate-50 font-mono text-center text-xs font-bold text-indigo-700">
                </div>
            </div>
            <div class="bg-slate-50 p-4 border-t flex justify-end">
                <button type="button" onclick="closeReportModal()" class="bg-slate-900 text-white px-5 py-2 rounded-xl font-bold text-xs transition hover:bg-slate-800">بستن گزارش</button>
            </div>
        </div>
    </div>

    <div id="inventory_modal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden border border-slate-200">
            <div class="bg-slate-900 p-5 flex justify-between items-center text-white">
                <h3 class="font-black text-lg">🎫 تولید انبوه سریال دوزوله</h3>
                <button onclick="closeInventoryModal()" class="text-slate-400 hover:text-white text-2xl">✕</button>
            </div>
            
            <form action="{{ route('admin.inventory.store') }}" method="POST">
                @csrf
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">کشور مقصد <span class="text-rose-500">*</span></label>
                        <select name="country_id" id="inventory_country_id" required class="w-full p-3 border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white">
                            <option value="">انتخاب کشور...</option>
                            @foreach($countries as $c)
                                <option value="{{ $c->id }}" data-permits='@json($c->allowed_permit_types ?? [])'>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">نوع مجوز <span class="text-rose-500">*</span></label>
                        <select name="permit_type" id="inventory_permit_type" required disabled class="w-full p-3 border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="">ابتدا کشور را انتخاب کنید...</option>
                        </select>
                        <p class="mt-2 text-xs font-semibold text-slate-500">سریال‌های این پارت فقط برای همین کشور و همین نوع مجوز در دسترس خواهند بود.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 font-bold mb-2 text-sm">شروع سریال <span class="text-rose-500">*</span></label>
                            <input type="number" name="serial_start" id="serial_start" oninput="calculateTotal()" required min="1" class="w-full p-3 border border-slate-300 rounded-xl font-mono text-left focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" placeholder="مثال: 1000">
                        </div>
                        <div>
                            <label class="block text-slate-700 font-bold mb-2 text-sm">پایان سریال <span class="text-rose-500">*</span></label>
                            <input type="number" name="serial_end" id="serial_end" oninput="calculateTotal()" required min="1" class="w-full p-3 border border-slate-300 rounded-xl font-mono text-left focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" placeholder="مثال: 5000">
                        </div>
                    </div>

                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex justify-between items-center">
                        <span class="text-indigo-800 font-bold text-sm">تعداد کل دوزوله تولیدی:</span>
                        <span id="total_display" class="text-indigo-900 font-black text-xl font-mono">0</span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="inventory_received_date" class="block text-slate-700 font-bold mb-2 text-sm">تاریخ تحویل/ثبت (میلادی) <span class="text-rose-500">*</span></label>
                            <input type="date" name="received_date" id="inventory_received_date" value="{{ old('received_date', now()->format('Y-m-d')) }}" required lang="en" dir="ltr" autocomplete="off" class="w-full p-2.5 border border-slate-300 rounded-xl text-left font-mono focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="inventory_expiry_date" class="block text-slate-700 font-bold mb-2 text-sm">تاریخ پایان/انقضا (اختیاری)</label>
                            <input type="date" name="expiry_date" id="inventory_expiry_date" value="{{ old('expiry_date') }}" lang="en" dir="ltr" autocomplete="off" class="w-full p-2.5 border border-slate-300 rounded-xl text-left font-mono focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-100">
                        </div>
                    </div>
                    <p class="text-[10px] font-semibold leading-5 text-slate-500">تاریخ تحویل/ثبت برای گزارش‌گیری ذخیره می‌شود؛ تاریخ پایان در صورت داشتن مهلت اعتبار اختیاری است.</p>
                </div>

                <div class="bg-slate-50 p-5 border-t flex justify-end gap-3">
                    <button type="button" onclick="closeInventoryModal()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 transition hover:bg-slate-200">انصراف</button>
                    <button type="submit" id="submit_btn" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold transition hover:bg-indigo-700 flex items-center gap-2">
                        <span>🚀 تولید و ثبت در انبار</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
    function openInventoryModal() { $('#inventory_modal').removeClass('hidden'); }
    function closeInventoryModal() { $('#inventory_modal').addClass('hidden'); }
    
    // بستن مودال گزارش
    function closeReportModal() { $('#serials_report_modal').addClass('hidden'); }

    const permitTypeLabels = @json($permitTypeLabels);

    // 🟢 تابع جاوااسکریپت نهایی و هوشمند (دریافت آدرس امن تولید شده توسط کنترلر)
    function showAvailableSerials(btnElement, batchId) {
        const countryName = $(btnElement).attr('data-country') || 'کشور نامشخص';
        const permitName = $(btnElement).attr('data-permit') || 'نوع مجوز نامشخص';
        $('#report_country_title').text(countryName + ' / ' + permitName + ' - پارت #' + batchId);
        const container = $('#serials_container');
        container.html('<div class="col-span-5 text-center text-slate-400 py-4">در حال بارگذاری لیست انبار...</div>');
        $('#serials_report_modal').removeClass('hidden');

        // 🛡️ استخراج مستقیم آدرس فیزیکیِ بدون خطای کامپایل لاراول از المنت دکمه
        let fetchUrl = $(btnElement).attr('data-route');

        fetch(fetchUrl)
            .then(res => {
                if (!res.ok) {
                    throw new Error('Server returned ' + res.status);
                }
                return res.json();
            })
            .then(data => {
                if(data.success && data.serials.length > 0) {
                    let html = '';
                    data.serials.forEach(num => {
                        html += `<div class="bg-white border border-slate-200 p-2 rounded-lg shadow-sm hover:border-indigo-400 transition">${num}</div>`;
                    });
                    container.html(html);
                } else {
                    container.html('<div class="col-span-5 text-center text-rose-500 py-4">هیچ شماره خامِ باقی‌مانده‌ای در این پارت وجود ندارد.</div>');
                }
            })
            .catch((error) => {
                console.error('Fetch Error:', error);
                container.html('<div class="col-span-5 text-center text-rose-500 py-4 text-xs">خطا در ارتباط با وب‌سرور پروژه.<br><small class="text-slate-400">علت: ' + error.message + '</small></div>');
            });
    }

    function refreshInventoryPermitTypes() {
        const countrySelect = document.getElementById('inventory_country_id');
        const permitSelect = document.getElementById('inventory_permit_type');
        if (!countrySelect || !permitSelect) return;

        const option = countrySelect.options[countrySelect.selectedIndex];
        let permitTypes = [];
        try {
            permitTypes = option && option.dataset.permits ? JSON.parse(option.dataset.permits) : [];
        } catch (error) {
            permitTypes = [];
        }

        permitSelect.innerHTML = '<option value="">انتخاب نوع مجوز...</option>';
        permitTypes.forEach((type) => {
            const permitOption = document.createElement('option');
            permitOption.value = type;
            permitOption.textContent = permitTypeLabels[type] || type;
            permitSelect.appendChild(permitOption);
        });
        permitSelect.disabled = permitTypes.length === 0;
        if (permitTypes.length === 0 && countrySelect.value) {
            permitSelect.innerHTML = '<option value="">برای این کشور نوع مجوز ثبت نشده است</option>';
        }
    }

    $('#inventory_country_id').on('change', refreshInventoryPermitTypes);
    refreshInventoryPermitTypes();

    // محاسبه آنلاین فرم تولید انبوه
    function calculateTotal() {
        let start = parseInt(document.getElementById('serial_start').value) || 0;
        let end = parseInt(document.getElementById('serial_end').value) || 0;
        let total = 0;
        if (end >= start && start > 0) {
            total = end - start + 1;
        }
        document.getElementById('total_display').innerText = total.toLocaleString('en-US');
    }

    function confirmDelete(button) {
        Swal.fire({
            title: 'آیا از حذف مطمئن هستید؟',
            text: "در صورتی که هیچ دوزبلاغی از این پارت مصرف نشده باشد، کل پارت پاک خواهد شد.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'بله، حذف کن!',
            cancelButtonText: 'انصراف'
        }).then((result) => {
            if (result.isConfirmed) {
                button.closest('form').submit();
            }
        });
    }

    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'عملیات موفق', text: '{{ session('success') }}', confirmButtonText: 'عالیه' });
    @endif

    @if($errors->any())
        Swal.fire({ icon: 'error', title: 'خطا', text: '{{ $errors->first() }}', confirmButtonText: 'متوجه شدم' });
    @endif
</script>
@endsection
