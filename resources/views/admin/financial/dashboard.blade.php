@extends('layouts.admin')

@section('header_title')
    مدیریت کلان مالی / <span class="text-indigo-600 font-black">داشبورد حسابداری پلتفرم</span>
@endsection

@section('content')
{{-- 🚀 استایل استاندارد و بومی تقویم بدون کدهای مزاحم پوزیشن دستی --}}
<style>
     .datepicker-container td.today {
       background: #e0e7ff !important;
       color: #4f46e5 !important;
       border-radius: 8px !important;
       border: 1px dashed #4f46e5 !important;
   }
    .datepicker-container {
        position: absolute !important;
        z-index: 999999 !important;
        direction: rtl !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        padding: 12px !important;
        width: 260px !important;
    }
    .datepicker-container table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    .datepicker-container th {
        font-size: 11px !important;
        color: #64748b !important;
        padding: 6px 0 !important;
        font-weight: bold !important;
    }
    .datepicker-container td {
        text-align: center !important;
        padding: 6px !important;
        font-size: 12px !important;
        font-weight: bold !important;
        color: #334155 !important;
        cursor: pointer !important;
    }
    .datepicker-container td:hover {
        background: #f1f5f9 !important;
        border-radius: 8px !important;
    }
    .datepicker-container td.selected {
        background: #4f46e5 !important;
        color: #ffffff !important;
        border-radius: 8px !important;
    }
    .datepicker-header {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        margin-bottom: 8px !important;
        font-weight: 900 !important;
        color: #1e293b !important;
        font-size: 13px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        padding-bottom: 6px !important;
    }
</style>

<div class="max-w-7xl mx-auto space-y-8 pb-10">

    {{-- نمایش خطاهای احتمالی ولیدیشن در بالای صفحه --}}
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl text-xs font-bold space-y-1 shadow-sm">
            @foreach($errors->all() as $error)
                <p>⚠️ {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ======================================= --}}
    {{-- بخش اول: نمای کلی صندوق (ترازنامه سیستم) --}}
    {{-- ======================================= --}}
    <div>
        <h2 class="text-lg font-black text-slate-800 mb-4 flex items-center gap-2">
            <span class="text-indigo-500">🏦</span> نمای کلی صندوق پلتفرم
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            {{-- موجودی کل در گردش --}}
            <div class="bg-gradient-to-br from-indigo-800 to-slate-900 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
                <div class="absolute -right-4 -top-4 text-white/10 text-7xl font-black">💰</div>
                <div class="text-indigo-200 text-sm font-bold mb-2 relative z-10">مجموع نقدینگی سیستم</div>
                <div class="text-3xl font-black font-mono relative z-10" dir="ltr">
                    {{ number_format($totalSystemBalance ?? 0) }} <span class="text-base font-normal">ریال</span>
                </div>
                <div class="text-xs text-indigo-300 mt-2 relative z-10">کل پولی که هم‌اکنون در پلتفرم وجود دارد.</div>
            </div>

            {{-- موجودی آزاد شرکت‌ها --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-6 text-slate-800 shadow-sm relative border-r-4 border-r-emerald-500">
                <div class="text-slate-500 text-sm font-bold mb-2">موجودی آزاد شرکت‌ها</div>
                <div class="text-2xl font-black font-mono text-slate-800" dir="ltr">
                    {{ number_format($totalAvailable ?? 0) }} <span class="text-sm font-normal text-slate-400">ریال</span>
                </div>
                <div class="text-xs text-slate-400 mt-2">امانت قابل برداشت/خرج.</div>
            </div>

            {{-- موجودی بلوکه شده --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-6 text-slate-800 shadow-sm relative border-r-4 border-r-amber-500">
                <div class="text-slate-500 text-sm font-bold mb-2">مبالغ بلوکه شده (درگیر)</div>
                <div class="text-2xl font-black font-mono text-slate-800" dir="ltr">
                    {{ number_format($totalBlocked ?? 0) }} <span class="text-sm font-normal text-slate-400">ریال</span>
                </div>
                <div class="text-xs text-slate-400 mt-2">درگیر در درخواست‌های باز.</div>
            </div>

            {{-- شارژ موفق این ماه --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-6 text-slate-800 shadow-sm relative border-r-4 border-r-blue-500">
                <div class="text-slate-500 text-sm font-bold mb-2">ورودی درگاه (این ماه)</div>
                <div class="text-2xl font-black font-mono text-blue-600" dir="ltr">
                    +{{ number_format($chargedThisMonth ?? 0) }} <span class="text-sm font-normal text-slate-400">ریال</span>
                </div>
                <div class="text-xs text-slate-400 mt-2">جمع شارژهای موفق کل سیستم.</div>
            </div>

        </div>
    </div>

    {{-- ======================================= --}}
    {{-- بخش دوم: تسویه حساب‌های انجمن و عملیات سریع --}}
    {{-- ======================================= --}}
    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-1 shadow-sm">
        <div class="bg-white rounded-[22px] p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h2 class="text-lg font-black text-slate-800 flex items-center gap-2">
                    <span class="text-rose-500">🏛️</span> وضعیت مالی و تسویه با انجمن
                </h2>
                
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="openModal('manualChargeModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1 shadow-md shadow-indigo-100">
                        <span>✏️</span> شارژ / اصلاح دستی شرکت‌ها
                    </button>
                    <button type="button" onclick="openModal('associationSettlementModal')" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 px-5 rounded-xl transition-all text-xs flex items-center gap-1 shadow-md shadow-slate-300">
                        <span>➕</span> ثبت فیش واریزی به انجمن
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- بدهی جاری به انجمن --}}
                <div class="bg-rose-50 border border-rose-100 rounded-xl p-5 flex items-center justify-between">
                    <div>
                        <div class="text-rose-600 text-sm font-bold mb-1">بدهی جاری (تسویه نشده)</div>
                        <div class="text-xs text-rose-400">سهم انجمن از دوزوله‌های تایید شده تا این لحظه</div>
                    </div>
                    <div class="text-2xl font-black font-mono text-rose-600" dir="ltr">
                        {{ number_format($associationPendingDebt ?? 0) }} <span class="text-sm font-normal">ریال</span>
                    </div>
                </div>

                {{-- کل مبالغ تسویه شده --}}
                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-5 flex items-center justify-between">
                    <div>
                        <div class="text-emerald-700 text-sm font-bold mb-1">کل مبالغ تسویه شده با انجمن</div>
                        <div class="text-xs text-emerald-500">مجموع واریزی‌های شما به حساب انجمن</div>
                    </div>
                    <div class="text-2xl font-black font-mono text-emerald-600" dir="ltr">
                        {{ number_format($associationTotalSettled ?? 0) }} <span class="text-sm font-normal">ریال</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================= --}}
    {{-- بخش سوم: دفتر کل تراکنش‌ها (Master Ledger) --}}
    {{-- ======================================= --}}
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-5 mb-5">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div>
                <h3 class="font-black text-slate-800 text-lg">گزارش مالی ماهانه دوزوله‌های صادرشده</h3>
                <p class="text-xs text-slate-500 mt-1">شامل شرکت صادرکننده، شماره دوزوله، کد رهگیری، مقصد و مبلغ کسرشده؛ با جمع کنترل نهایی.</p>
            </div>
            <form action="{{ route('admin.financial.dozbalaghMonthlyReport') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">از تاریخ</label>
                    <input type="date" name="date_from" required value="{{ now()->startOfMonth()->format('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">تا تاریخ</label>
                    <input type="date" name="date_to" required value="{{ now()->endOfMonth()->format('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">شرکت (اختیاری)</label>
                    <select name="company_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                        <option value="">همه شرکت‌ها</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-xl text-xs">خروجی Excel ماهانه</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-50 p-5 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <h3 class="font-black text-slate-800 text-lg flex items-center gap-2">
                    <span>📑</span> دفتر کل تراکنش‌های پلتفرم
                </h3>
                
                <a href="{{ route('admin.financial.exportExcel', request()->all()) }}" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-700 font-bold py-2 px-4 rounded-xl transition-colors text-xs flex items-center gap-1 border border-emerald-300 shadow-sm self-end lg:self-auto">
                    <span>📊</span> خروجی اکسل فیلتر شده
                </a>
            </div>

            <form action="{{ url()->current() }}" method="GET" class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                
                <div class="relative">
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">جستجوی آزاد</label>
                    <div class="relative flex items-center">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="کد پیگیری، شرح یا شناسه..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pr-10 pl-3 py-2 text-xs font-medium outline-none focus:border-indigo-500 focus:bg-white transition-all text-slate-700">
                        <div class="absolute right-3 text-slate-400">🔍</div>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">فیلتر بر اساس شرکت</label>
                    <select name="company_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium outline-none focus:border-indigo-500 focus:bg-white transition-all text-slate-700 cursor-pointer">
                        <option value="">-- همه شرکت‌ها --</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- کادر فیلتر تاریخ شروع --}}
                <div class="relative">
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">از تاریخ (شمسی)</label>
                    <div class="relative flex items-center">
                        <input type="text" id="date_from_view" value="{{ request('date_from_view') }}" placeholder="انتخاب تاریخ شروع..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pr-10 pl-3 py-2 text-xs font-semibold outline-none focus:border-indigo-500 focus:bg-white text-slate-700 cursor-pointer" readonly>
                        <div class="absolute right-3 text-slate-400">📅</div>
                    </div>
                    <input type="hidden" name="date_from" id="date_from_gregorian" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_from_view" id="date_from_view_hidden" value="{{ request('date_from_view') }}">
                </div>

                {{-- کادر فیلتر تاریخ پایان --}}
                <div class="flex gap-2 items-center relative">
                    <div class="w-full">
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">تا تاریخ (شمسی)</label>
                        <div class="relative flex items-center">
                            <input type="text" id="date_to_view" value="{{ request('date_to_view') }}" placeholder="انتخاب تاریخ پایان..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pr-10 pl-3 py-2 text-xs font-semibold outline-none focus:border-indigo-500 focus:bg-white text-slate-700 cursor-pointer" readonly>
                            <div class="absolute right-3 text-slate-400">📅</div>
                        </div>
                        <input type="hidden" name="date_to" id="date_to_gregorian" value="{{ request('date_to') }}">
                        <input type="hidden" name="date_to_view" id="date_to_view_hidden" value="{{ request('date_to_view') }}">
                    </div>
                    
                    <div class="flex gap-1 shadow-sm rounded-xl overflow-hidden mt-5">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-4 transition-colors">
                             اعمال
                        </button>
                        @if(request()->anyFilled(['search', 'company_id', 'date_from', 'date_to']))
                            <a href="{{ route('admin.financial.dashboard') }}" class="bg-rose-500 hover:bg-rose-600 text-white text-xs font-bold py-2 px-2 transition-colors flex items-center justify-center" title="پاکسازی فیلترها">
                                ✕
                            </a>
                        @endif
                    </div>
                </div>

            </form>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-100/50 text-slate-600 font-bold border-b border-slate-200">
                        <th class="p-4 text-center">شناسه</th>
                        <th class="p-4">نام شرکت</th>
                        <th class="p-4">شرح تراکنش</th>
                        <th class="p-4 text-center">مبلغ (ریال)</th>
                        <th class="p-4 text-center">وضعیت</th>
                        <th class="p-4 text-center">تاریخ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($allTransactions as $trx)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4 text-center text-slate-400 font-mono text-xs">
                                #{{ $trx->ref_id ?? $trx->id }}
                            </td>
                            <td class="p-4">
                                <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded text-xs font-black border border-indigo-100">
                                    {{ $trx->wallet->company->name ?? 'نامشخص' }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-slate-800">{{ $trx->description ?? 'بدون شرح' }}</div>
                            </td>
                            <td class="p-4 text-center font-mono font-bold" dir="ltr">
                                @if($trx->type === 'credit' || $trx->type === 'wallet_charge')
                                    <span class="text-emerald-600">+ {{ number_format($trx->amount) }}</span>
                                @else
                                    <span class="text-rose-500">- {{ number_format($trx->amount) }}</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                @if($trx->type === 'credit' || $trx->type === 'wallet_charge')
                                    <span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded text-[10px] font-black">موفق</span>
                                @else
                                    <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-[10px] font-black">خروجی</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <div class="text-slate-700 font-bold text-xs flex flex-col items-center justify-center gap-1">
                                    <span dir="rtl">{{ \Morilog\Jalali\Jalalian::fromCarbon($trx->created_at)->format('Y/m/d H:i') }}</span>
                                    
                                    @if($trx->action_type === 'manual_adjustment')
                                        <form action="{{ route('admin.financial.destroyAdjustment', $trx->id) }}" method="POST" onsubmit="return confirm('آیا از ابطال و حذف این سند اصلاحی دستی و بازگشت مبلغ به حساب شرکت مطمئن هستید؟')" class="mt-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[10px] text-rose-600 hover:text-rose-800 font-black bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200 transition-colors">
                                                🗑️ ابطال سند
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 font-bold">هیچ تراکنشی با فیلترهای انتخابی شما یافت نشد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(method_exists($allTransactions, 'links'))
            <div class="p-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-xs text-slate-500 font-medium">
                    نمایش تراکنش‌های {{ $allTransactions->firstItem() ?? 0 }} تا {{ $allTransactions->lastItem() ?? 0 }} از کل {{ $allTransactions->total() }} تراکنش پلتفرم
                </div>
                <div class="pagination-indigo">
                    {{ $allTransactions->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ======================================= --}}
{{-- مُدال شارژ دستی --}}
{{-- ======================================= --}}
<div id="manualChargeModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300">
        <div class="bg-indigo-900 p-4 text-white flex justify-between items-center">
            <h4 class="font-black text-sm">✏️ سند اصلاحی و شارژ دستی کیف پول</h4>
            <button onclick="closeModal('manualChargeModal')" class="text-white/70 hover:text-white text-lg font-bold">✕</button>
        </div>
        <form id="formManualCharge" action="{{ route('admin.financial.manualAdjustment') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">انتخاب شرکت مقصد <span class="text-rose-500">*</span></label>
                <select name="company_id" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-medium focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">-- انتخاب شرکت مقصد --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع عملیات مالی <span class="text-rose-500">*</span></label>
                <div class="flex gap-4 bg-slate-50 p-2 rounded-xl border border-slate-200">
                    <label class="flex items-center gap-1.5 text-xs font-bold text-emerald-700 cursor-pointer">
                        <input type="radio" name="action_type" value="charge" checked class="text-emerald-600 focus:ring-emerald-500"> افزایش موجودی (+)
                    </label>
                    <label class="flex items-center gap-1.5 text-xs font-bold text-rose-700 cursor-pointer">
                        <input type="radio" name="action_type" value="deduct" class="text-rose-600 focus:ring-rose-500"> کسر موجودی (-)
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">مبلغ اصلاحیه (ریال) <span class="text-rose-500">*</span></label>
                <input type="text" id="modal_amount_display" inputmode="numeric" required class="w-full border border-slate-300 rounded-xl px-3 py-2 font-mono text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="مثلاً: 1,000,000">
                <input type="hidden" name="amount" id="modal_amount_real">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">شرح و بابت سند <span class="text-rose-500">*</span></label>
                <textarea name="description" required rows="3" class="w-full border border-slate-300 rounded-xl p-3 text-xs focus:ring-2 focus:ring-indigo-500 outline-none font-medium placeholder:text-slate-400" placeholder="علت و جزئیات سند دستی را بنویسید..."></textarea>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-xl transition text-xs shadow-md">
                ثبت و اعمال تغییرات در دیتابیس
            </button>
        </form>
    </div>
</div>

{{-- مُدال تسویه انجمن --}}
<div id="associationSettlementModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300">
        <div class="bg-slate-950 p-4 text-white flex justify-between items-center">
            <h4 class="font-black text-sm">🏛️ ثبت فیش واریزی و تسویه‌حساب با انجمن</h4>
            <button onclick="closeModal('associationSettlementModal')" class="text-white/70 hover:text-white text-lg font-bold">✕</button>
        </div>
        <form id="formSettlement" action="{{ route('admin.financial.storeSettlement') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">مبلغ واریز شده به حساب انجمن (ریال) <span class="text-rose-500">*</span></label>
                <input type="text" id="settle_amount_display" required class="w-full border border-slate-300 rounded-xl px-3 py-2 font-mono text-sm focus:ring-2 focus:ring-slate-950 outline-none" placeholder="مثلاً: 15,000,000">
                <input type="hidden" name="amount" id="settle_amount_real">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره فیش / پیگیری <span class="text-rose-500">*</span></label>
                    <input type="text" name="ref_number" required class="w-full border border-slate-300 rounded-xl px-3 py-2 font-mono text-xs focus:ring-2 focus:ring-slate-950 outline-none" placeholder="123456">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">نام بانک مقصد <span class="text-rose-500">*</span></label>
                    <input type="text" name="bank_name" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-slate-950 outline-none font-medium" placeholder="مثلاً ملی، تجارت">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">بارگذاری تصویر فیش واریزی <span class="text-slate-400">(اختیاری)</span></label>
                <input type="file" name="receipt_file" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
            </div>
            <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 rounded-xl transition text-xs shadow-md">
                ثبت قطعی تسویه حساب
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
{{-- فراخوانی فایل هسته جاوااسکریپت تقویم آفلاین --}}
<script src="{{ asset('assets/js/persian-datepicker.min.js') }}"></script>
<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.querySelector('.transform').classList.remove('scale-95');
        }, 10);
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        modal.querySelector('.transform').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 150);
    }

    document.addEventListener("DOMContentLoaded", function() {
        // 🚀 راه‌اندازی کاملاً مستقل تقویم بدون ریست کردن المان‌های Select2 و درگاه
        if (typeof $.fn.persianDatepicker !== 'undefined') {
            $('#date_from_view').persianDatepicker({
                observer: false, // غیرفعال کردن اورزرور عمومی برای جلوگیری از تداخل با بقیه فرم‌ها
                format: 'YYYY/MM/DD',
                altField: '#date_from_gregorian',
                altFormat: 'gregorian',
                autoClose: true,
                onSelect: function(unixTimestamp){
                    var pDate = new persianDate(unixTimestamp);
                    var formatted = pDate.format('YYYY/MM/DD');
                    $('#date_from_view').val(formatted);
                    $('#date_from_view_hidden').val(formatted);
                }
            });

            $('#date_to_view').persianDatepicker({
                observer: false,
                format: 'YYYY/MM/DD',
                altField: '#date_to_gregorian',
                altFormat: 'gregorian',
                autoClose: true,
                onSelect: function(unixTimestamp){
                    var pDate = new persianDate(unixTimestamp);
                    var formatted = pDate.format('YYYY/MM/DD');
                    $('#date_to_view').val(formatted);
                    $('#date_to_view_hidden').val(formatted);
                }
            });
        }

        // ۱. فرمت ۳ رقم ۳ رقم برای مدال شارژ دستی
        const modalDisplay = document.getElementById('modal_amount_display');
        const modalReal = document.getElementById('modal_amount_real');
        if (modalDisplay) {
            modalDisplay.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, "");
                modalDisplay.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                modalReal.value = value;
            });
        }

        document.getElementById('formManualCharge').addEventListener('submit', function() {
            let cleanVal = modalDisplay.value.replace(/\D/g, "");
            modalReal.value = cleanVal;
        });

        // ۲. فرمت ۳ رقم ۳ رقم برای مدال تسویه انجمن
        const settleDisplay = document.getElementById('settle_amount_display');
        const settleReal = document.getElementById('settle_amount_real');
        if (settleDisplay) {
            settleDisplay.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, "");
                settleDisplay.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                settleReal.value = value;
            });
        }

        document.getElementById('formSettlement').addEventListener('submit', function() {
            let cleanVal = settleDisplay.value.replace(/\D/g, "");
            settleReal.value = cleanVal;
        });

        // نمایش نوتیفیکیشن خوش‌رنگ SweetAlert پس از ثبت موفقیت‌آمیز
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'موفقیت‌آمیز',
                text: "{{ session('success') }}",
                confirmButtonText: 'تایید',
                customClass: {
                    confirmButton: 'bg-indigo-600 text-white px-4 py-2 rounded-xl text-xs font-bold'
                }
            });
        @endif
    });
</script>
@endsection
