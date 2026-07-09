@extends('layouts.admin')

@section('header_title')
    انبار کل / <span class="text-indigo-600 font-black">مدیریت سریال های دوزوله</span>
@endsection

@section('content')
    <div class="max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-900 p-6 text-white flex justify-between items-center">
            <div>
                <h1 class="text-base font-black flex items-center gap-2">🎫 لیست پارت های دوزوله (انبار کل )</h1>
                <p class="text-slate-400 text-[11px] mt-1">در این بخش می توانید پارت های جدید دوزوله را تولید و به انبار مرکزی سیستم اضافه کنید .</p>
            </div>
            <button onclick="openInventoryModal()" class="bg-indigo-600 hover:bg-indigo-500 text-white font-black px-5 py-2.5 rounded-xl text-xs shadow-lg shadow-indigo-900/50 transition">
                ➕ تولید سریال جدید
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                        <th class="p-4 w-16 text-center">پارت</th>
                        <th class="p-4">کشور مقصد</th>
                        <th class="p-4 text-center">از سریال</th>
                        <th class="p-4 text-center">تا سریال</th>
                        <th class="p-4 text-center">تعداد کل</th>
                        <th class="p-4 text-center">مانده خام</th>
                        <th class="p-4 text-center">مصرف شده</th>
                        <th class="p-4 text-center">تاریخ ثبت</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                    @forelse($batches as $batch)
                        <tr class="hover:bg-indigo-50/50 transition">
                            <td class="p-4 text-center text-slate-400 font-mono">#{{ $batch->id }}</td>
                            <td class="p-4 font-bold text-slate-700">{{ $batch->country ? $batch->country->name : '-' }}</td>
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
                            <td class="p-4 text-center text-slate-500 text-xs font-mono">
                                {{ $batch->created_at ? $batch->created_at->format('Y/m/d') : '-' }}
                            </td>
                            <td class="p-4 text-center flex items-center justify-center gap-2">
                                {{-- 🟢 اصلاح دکمه: تولید مستقیم و بدون باگِ آدرسِ فیزیکی با هلپر action اتمیک لاراول --}}
                                <button type="button" 
                                        onclick="showAvailableSerials(this, '{{ $batch->id }}', '{{ $batch->country ? $batch->country->name : '' }}')" 
                                        data-route="{{ action([App\Http\Controllers\Admin\InventoryController::class, 'getAvailableSerials'], $batch->id) }}"
                                        class="text-indigo-600 font-bold hover:text-indigo-800 transition text-xs bg-indigo-50 px-2.5 py-1.5 rounded-lg border border-indigo-200 cursor-pointer">
                                    👁️ سریال‌های موجود
                                </button>

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
                        <tr><td colspan="9" class="p-10 text-center text-slate-400 font-bold">موردی یافت نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
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
                        <select name="country_id" required class="w-full p-3 border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white">
                            <option value="">انتخاب کشور...</option>
                            @foreach(App\Models\Country::where('is_active', true)->get() as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
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

                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">تاریخ انقضا (اختیاری)</label>
                        <input type="date" name="expiry_date" class="w-full p-3 border border-slate-300 rounded-xl text-left focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
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

    // 🟢 تابع جاوااسکریپت نهایی و هوشمند (دریافت آدرس امن تولید شده توسط کنترلر)
    function showAvailableSerials(btnElement, batchId, countryName) {
        $('#report_country_title').text(countryName + ' - پارت #' + batchId);
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