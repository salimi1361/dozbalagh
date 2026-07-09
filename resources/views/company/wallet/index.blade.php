@extends('layouts.app')

@section('header_title')
    داشبورد مالی / <span class="text-blue-600 font-black">کیف پول شرکت</span>
@endsection

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        
        {{-- بخش کارت‌های موجودی --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gradient-to-l from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg shadow-emerald-200">
                <div class="text-emerald-100 text-sm font-bold mb-2">موجودی قابل استفاده</div>
                <div class="text-3xl font-black font-mono" dir="ltr">
                    {{ number_format($wallet->available_balance ?? 0) }} <span class="text-base font-normal">تومان</span>
                </div>
                <div class="text-xs text-emerald-100 mt-2">این مبلغ برای ثبت درخواست‌های جدید قابل استفاده است.</div>
            </div>

            <div class="bg-gradient-to-l from-amber-500 to-orange-500 rounded-2xl p-6 text-white shadow-lg shadow-orange-200">
                <div class="text-orange-100 text-sm font-bold mb-2">موجودی مسدود شده</div>
                <div class="text-3xl font-black font-mono" dir="ltr">
                    {{ number_format($wallet->blocked_balance ?? 0) }} <span class="text-base font-normal">تومان</span>
                </div>
                <div class="text-xs text-orange-100 mt-2">مبالغ بلوکه شده بابت درخواست‌های در انتظار تایید.</div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-6 text-slate-800 shadow-sm relative overflow-hidden">
                <div class="text-slate-500 text-sm font-bold mb-2">موجودی کل (دفتر کل)</div>
                <div class="text-3xl font-black font-mono text-slate-800" dir="ltr">
                    {{ number_format($wallet->balance ?? 0) }} <span class="text-base font-normal text-slate-400">تومان</span>
                </div>
                <div class="text-xs text-slate-400 mt-2">مجموع موجودی قابل استفاده و مسدود شده.</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            
            {{-- بخش فرم شارژ کیف پول --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="bg-slate-950 p-5 text-white">
                        <h3 class="font-black text-lg">💳 شارژ آنلاین حساب</h3>
                    </div>
                    <form action="{{ route('company.wallet.charge') }}" method="POST" id="chargeForm" class="p-6">
                        @csrf
                        <div class="mb-5">
                            <label class="block text-sm font-bold text-slate-700 mb-2">مبلغ شارژ (تومان) <span class="text-rose-500">*</span></label>
                            
                            {{-- فیلد نمایشی (با جداکننده ۳ رقم) --}}
                            <input type="text" id="amount_display" required class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none font-mono text-lg" dir="ltr" placeholder="حداقل 5.000.000 ریال">
                            
                            {{-- فیلد واقعی (اعداد خام که به سمت سرور می‌رود) --}}
                            <input type="hidden" name="amount" id="amount_real" required>
                            
                            <p class="text-xs text-slate-400 mt-2">حداقل مبلغ شارژ ۵۰۰,۰۰۰ تومان (۵ میلیون ریال) می‌باشد.</p>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition shadow-lg shadow-blue-200 flex justify-center items-center gap-2">
                            انتقال به درگاه پرداخت زرین‌پال
                        </button>
                    </form>
                </div>
            </div>

            {{-- بخش جدول تاریخچه تراکنش‌ها --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden h-full">
                    
                    {{-- هدر اصلاح شده به همراه دکمه خروجی اکسل --}}
                    <div class="bg-slate-50 p-5 border-b border-slate-200 flex justify-between items-center">
                        <h3 class="font-black text-slate-800 text-lg">🧾 تاریخچه تراکنش‌ها</h3>
                        
                        {{-- فرم مخفی برای ارسال آیدی‌ها --}}
                        <form id="exportForm" action="{{ route('company.wallet.export') }}" method="POST" class="hidden">
                            @csrf
                            <input type="hidden" name="transaction_ids" id="export_ids">
                        </form>
                        
                        {{-- دکمه خروجی --}}
                        <button type="button" onclick="exportSelected()" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-700 font-bold py-1.5 px-4 rounded-lg transition-colors text-xs flex items-center gap-1 border border-emerald-300">
                            <span>📊</span> خروجی اکسل از موارد انتخابی
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-sm border-collapse">
                            <thead>
                                <tr class="bg-slate-100/50 text-slate-600 font-bold border-b border-slate-200">
                                    {{-- چک‌باکس انتخاب همه --}}
                                    <th class="p-4 text-center w-10">
                                        <input type="checkbox" id="selectAllTrx" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 cursor-pointer">
                                    </th>
                                    <th class="p-4 text-center">شناسه</th>
                                    <th class="p-4">شرح تراکنش</th>
                                    <th class="p-4 text-center">مبلغ (تومان)</th>
                                    <th class="p-4 text-center">نوع</th>
                                    <th class="p-4 text-center">تاریخ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                                @forelse($transactions as $trx)
                                    <tr class="hover:bg-slate-50 transition">
                                        {{-- چک‌باکس هر ردیف --}}
                                        <td class="p-4 text-center">
                                            <input type="checkbox" class="trx-checkbox w-4 h-4 rounded text-blue-600 focus:ring-blue-500 cursor-pointer" value="{{ $trx->id }}">
                                        </td>
                                        <td class="p-4 text-center text-slate-400 font-mono text-xs">
                                            #{{ $trx->ref_id ?? $trx->id }}
                                        </td>
                                        <td class="p-4">
                                            <div class="font-bold text-slate-800">{{ $trx->description ?? 'بدون شرح' }}</div>
                                            <div class="text-xs text-slate-400 mt-1">
                                                @switch($trx->action_type)
                                                    @case('online_charge') شارژ آنلاین @break
                                                    @case('dozbalagh_reserve') مسدودی موقت @break
                                                    @case('dozbalagh_purchase') کسر قطعی دوزوله @break
                                                    @case('dozbalagh_release') آزادسازی وجه @break
                                                    @case('manual_adjustment') اصلاحیه سیستمی @break
                                                @endswitch
                                            </div>
                                        </td>
                                        <td class="p-4 text-center font-mono font-bold" dir="ltr">
                                            @if($trx->type === 'credit' || $trx->type === 'wallet_charge')
                                                <span class="text-emerald-600">+ {{ number_format($trx->amount) }}</span>
                                            @else
                                                <span class="text-rose-500">- {{ number_format($trx->amount) }}</span>
                                            @endif
                                        </td>
                                        <td class="p-4 text-center">
                                            @if($trx->status === 'success' || $trx->type === 'credit')
                                                <span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded text-[10px] font-black">موفق / بستانکار</span>
                                            @elseif($trx->status === 'failed')
                                                <span class="bg-rose-100 text-rose-700 px-2 py-1 rounded text-[10px] font-black">ناموفق</span>
                                            @else
                                                <span class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-[10px] font-black">در انتظار</span>
                                            @endif
                                        </td>
                                        <td class="p-4 text-center">
                                            {{-- تاریخ شمسی --}}
                                            <div class="text-slate-700 font-bold text-xs flex items-center justify-center gap-1" dir="rtl">
                                                <span class="text-blue-500">📅</span> {{ \Morilog\Jalali\Jalalian::fromCarbon($trx->created_at)->format('Y/m/d H:i') }}
                                            </div>
                                            {{-- تاریخ میلادی --}}
                                            <div class="text-slate-400 font-mono text-[10px] mt-1" dir="ltr">
                                                {{ \Carbon\Carbon::parse($trx->created_at)->format('Y-m-d H:i') }}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-slate-400 font-bold">هیچ تراکنشی در سیستم ثبت نشده است.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($transactions, 'links'))
                        <div class="p-4 border-t border-slate-100">
                            {{ $transactions->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // ۱. اسکریپت جداکننده ۳ رقم ۳ رقم برای مبلغ شارژ
        const displayInput = document.getElementById('amount_display');
        const realInput = document.getElementById('amount_real');
        
        if (displayInput) {
            displayInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, "");
                displayInput.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                realInput.value = value;
            });
        }

        // بررسی حداقل مبلغ قبل از ارسال
        document.getElementById('chargeForm').addEventListener('submit', function(e) {
            if(!realInput.value || parseInt(realInput.value) < 500000) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'مبلغ نامعتبر', text: 'حداقل مبلغ جهت شارژ حساب ۵۰۰,۰۰۰ تومان می‌باشد.', confirmButtonText: 'اصلاح مبلغ' });
            }
        });

        // ۲. اسکریپت چک‌باکس‌ها و خروجی اکسل
        const selectAllBtn = document.getElementById('selectAllTrx');
        if(selectAllBtn) {
            selectAllBtn.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.trx-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        }

        window.exportSelected = function() {
            const selected = Array.from(document.querySelectorAll('.trx-checkbox:checked')).map(cb => cb.value);
            
            if (selected.length === 0) {
                Swal.fire({ icon: 'warning', title: 'توجه', text: 'لطفاً حداقل یک تراکنش را برای خروجی گرفتن انتخاب کنید.', confirmButtonText: 'باشه' });
                return;
            }

            document.getElementById('export_ids').value = selected.join(',');
            document.getElementById('exportForm').submit();
        };

        // ۳. نمایش پیام‌های موفقیت یا خطای سرور
        @if(session('success'))
            Swal.fire({ icon: 'success', title: 'عملیات موفق', text: @json(session('success')), confirmButtonText: 'متوجه شدم' });
        @endif

        @if($errors->any())
            Swal.fire({ icon: 'error', title: 'خطا', text: @json($errors->first()), confirmButtonText: 'متوجه شدم' });
        @endif
    });
</script>
@endsection