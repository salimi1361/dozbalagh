@extends('layouts.app')

@section('header_title', 'داشبورد اصلی سیستم')

@section('content')
<div class="space-y-6" dir="rtl">
    
    {{-- هدر خوش‌آمدهای سیستم و وضعیت تایید شرکت --}}
    <div class="bg-white rounded-2xl border-2 border-slate-100 shadow-sm p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-slate-100 rounded-2xl text-2xl shadow-inner"></div>
                <div>
                    <h2 class="text-xl font-black text-slate-800 tracking-tight">خوش آمدید، {{ $company->ceo_name ?? 'مدیر محترم شرکت' }} عزیز</h2>
                    <p class="text-slate-600 text-sm font-bold mt-2">نام شرکت: <span class="text-slate-900 font-black">{{ $company->name_fa ?? $company->name }}</span> | کد اختصاصی: <span class="font-mono text-slate-900 font-black">{{ $company->company_code ?? '---' }}</span></p>
                    <p class="text-slate-500 text-xs font-bold mt-1.5">آدرس دفتر مرکزی: {{ $company->address_fa ?? 'ثبت نشده' }}</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-3 w-full sm:w-auto">
                <div>
                    @if($company->status == 'approved')
                        <div class="bg-emerald-50 text-emerald-800 px-5 py-2.5 rounded-xl text-sm font-black border-2 border-emerald-200/80 shadow-sm flex items-center gap-2">
                            <span class="text-lg"></span> حساب کاربری فعال و تایید شده
                        </div>
                    @else
                        <div class="bg-amber-50 text-amber-800 px-5 py-2.5 rounded-xl text-sm font-black border-2 border-amber-200/80 shadow-sm flex items-center gap-2">
                            <span class="text-lg"></span> در انتظار تایید مدارک مدیریت
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ردیف اول: کارت‌های آماری شاخص‌های اصلی --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        
        {{-- کارت مالی / کیف پول --}}
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 p-6 rounded-2xl text-white shadow-md flex flex-col justify-between relative overflow-hidden">
            <div>
                <div class="flex justify-between items-center mb-3">
                    <span class="text-xs font-black text-slate-400 tracking-wider">موجودی کیف پول</span>
                    <span class="bg-emerald-500/20 text-emerald-400 px-2.5 py-0.5 rounded-md text-[10px] font-black">تومان</span>
                </div>
                <div class="text-2xl font-black font-mono tracking-wide text-emerald-400">
                    {{ number_format($walletBalance) }}
                </div>
            </div>
            <div class="mt-6 pt-3 border-t border-white/10 flex justify-between items-center text-xs font-bold">
                <span class="text-slate-400">بلوکه: <strong class="text-amber-400 font-mono font-black">{{ number_format($blockedBalance) }}</strong></span>
                <a href="{{ route('company.wallet.index') }}" class="text-sky-400 hover:text-sky-300 font-black transition">
                    شارژ حساب 
                </a>
            </div>
        </div>

        {{-- کارت رانندگان --}}
        <div class="bg-white p-6 rounded-2xl border-2 border-slate-100 shadow-sm flex items-center justify-between hover:border-sky-500/30 transition-all">
            <div class="space-y-2">
                <span class="text-xs font-black text-slate-500 block">رانندگان فعال در کارتابل</span>
                <span class="text-3xl font-black text-slate-800 block font-mono tracking-tight">{{ $driversCount }} <span class="text-xs font-bold text-slate-400 mr-1">نفر</span></span>
                <span class="text-[10px] text-sky-600 font-black bg-sky-50 px-2 py-0.5 rounded border border-sky-100 block w-max">سرمایه انسانی فعال</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                
            </div>
        </div>

        {{-- کارت ناوگان ملکی --}}
        <div class="bg-white p-6 rounded-2xl border-2 border-slate-100 shadow-sm flex items-center justify-between hover:border-amber-500/30 transition-all">
            <div class="space-y-2">
                <span class="text-xs font-black text-slate-500 block">ناوگان ملکی فعال</span>
                <span class="text-3xl font-black text-slate-800 block font-mono tracking-tight">{{ $fleetsCount }} <span class="text-xs font-bold text-slate-400 mr-1">دستگاه</span></span>
                <span class="text-[10px] text-amber-600 font-black bg-amber-50 px-2 py-0.5 rounded border border-amber-100 block w-max">کدهای هوشمند ثبت‌شده</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                
            </div>
        </div>

        {{-- نرخ موفقیت عملکرد شرکت --}}
        <div class="bg-white p-6 rounded-2xl border-2 border-slate-100 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-center">
                <span class="text-xs font-black text-slate-500">نرخ موافقت پروانه‌ها</span>
                <span class="text-base font-black text-emerald-600 font-mono">{{ $successRate }}%</span>
            </div>
            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden my-3 shadow-inner">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all" style="width: {{ $successRate }}%"></div>
            </div>
            <span class="text-[10px] text-slate-400 font-bold">نسبت صادر شده به کل درخواست‌های جاری</span>
        </div>

    </div>

    {{-- ردیف دوم: وضعیت‌های تفکیک شده دوزوله با حذف گزینه نیاز به اصلاح --}}
    <div class="space-y-3">
        <h3 class="text-xs font-black text-slate-700 tracking-wide flex items-center gap-1.5">
            <span class="w-2 h-2 bg-slate-800 rounded-full"></span> مانیتورینگ آنلاین وضعیت پروانه‌ها
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-4">
            @php
                $stats = [
                    ['در انتظار بررسی', $permitStats->pending_count ?? 0, 'text-amber-700', 'bg-amber-50/60 border-amber-200/80'],
                    ['صادر شده / معتبر', $permitStats->issued_count ?? 0, 'text-sky-700', 'bg-sky-50/60 border-sky-200/80'],
                    ['رد درخواست', $permitStats->rejected_count ?? 0, 'text-rose-700', 'bg-rose-50/60 border-rose-200/80'],
                    ['نیاز به اصلاح', $permitStats->returned_count ?? 0, 'text-orange-700', 'bg-orange-50/60 border-orange-200/80'],
                    ['لاشه تحویل شده', $permitStats->collected_count ?? 0, 'text-indigo-700', 'bg-indigo-50/60 border-indigo-200/80'],
                    ['درخواست تمدید', $permitStats->renewal_count ?? 0, 'text-emerald-700', 'bg-emerald-50/60 border-emerald-200/80'],
                    ['مفقودی', $permitStats->lost_count ?? 0, 'text-slate-700', 'bg-slate-50 border-slate-300'],
                ];
            @endphp

            @foreach($stats as $item)
            <div class="bg-white rounded-xl shadow-sm p-4 text-center hover:shadow transition-all {{ $item[3] }} border-2">
                <span class="text-[11px] font-black block mb-2">{{ $item[0] }}</span>
                <span class="text-2xl font-black {{ $item[2] }} font-mono">{{ $item[1] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ردیف سوم: جدول تفصیلی فرآیندهای اخیر کاملاعریض (Full Width) --}}
    <div class="bg-white rounded-2xl border-2 border-slate-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b-2 border-slate-100 flex justify-between items-center bg-slate-50/60">
            <h3 class="font-black text-slate-800 text-sm flex items-center gap-2">‌ آخرین درخواست‌های دوزوله صادر شده و معلق</h3>
            <a href="{{ route('dozbalagh.index') }}" class="text-xs text-sky-600 font-black hover:underline bg-white border border-slate-200 px-3 py-1.5 rounded-xl shadow-sm transition">
                مشاهده کارتابل جامع پروانه‌ها 
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/60 text-slate-600 font-black border-b border-slate-200 text-[11px]">
                        <th class="p-4">کد پرونده</th>
                        <th class="p-4">راننده متقاضی</th>
                        <th class="p-4">ناوگان ملكی (پلاک ترانزیت)</th>
                        <th class="p-4">هزینه کل پروانه</th>
                        <th class="p-4">تاریخ ثبت و فرستش</th>
                        <th class="p-4 text-center">نوع / وضعیت چرخه عمر</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-bold text-slate-700 text-sm">
                    @forelse($recentRequests->take(10) as $req)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-4 font-black font-mono text-slate-900 text-xs tracking-wider">{{ $req->d_code }}</td>
                            <td class="p-4 text-slate-800">{{ trim($req->first_name_fa . ' ' . $req->last_name_fa) ?: 'نامشخص' }}</td>
                            <td class="p-4">
                                @php
                                    $plateRaw = $req->transit_plate ?? $req->plate_number ?? null;
                                    $plateParts = $plateRaw ? preg_split('/[-\s]+/', trim($plateRaw)) : [];

                                    $plateLeft   = $plateParts[0] ?? '';
                                    $plateLetter = $plateParts[1] ?? '';
                                    $plateMid    = $plateParts[2] ?? '';
                                    $plateIran   = $plateParts[3] ?? '';

                                    if ($plateRaw && count($plateParts) < 4) {
                                        $plateLeft = '';
                                        $plateLetter = '';
                                        $plateMid = $plateRaw;
                                        $plateIran = '';
                                    }
                                @endphp

                                @if($plateRaw)
                                    <div class="inline-flex items-stretch rounded border border-slate-900 bg-white overflow-hidden shadow-sm w-[110px] h-[26px] align-middle" dir="ltr">
                                        <div class="w-[14px] bg-blue-800 text-white flex flex-col items-center justify-between py-[1px] shrink-0">
                                            <span class="text-[4px] font-black leading-none mt-[1px]">I.R.</span>
                                            <span class="w-[8px] h-[4px] rounded-[1px] overflow-hidden border border-white/20">
                                                <span class="block h-[1px] bg-green-500"></span>
                                                <span class="block h-[1px] bg-white"></span>
                                                <span class="block h-[1px] bg-red-500"></span>
                                            </span>
                                            <span class="text-[4px] font-black leading-none mb-[1px]">IRAN</span>
                                        </div>

                                        <div class="flex flex-1 items-center bg-gradient-to-b from-white to-slate-100 text-slate-950">
                                            <div class="w-[24px] h-full flex items-center justify-center border-r border-slate-300">
                                                <span class="font-black text-[11px] leading-none font-mono">{{ $plateLeft ?: '---' }}</span>
                                            </div>

                                            <div class="w-[18px] h-full flex items-center justify-center border-r border-slate-300">
                                                <span class="font-black text-[11px] leading-none">{{ $plateLetter ?: '-' }}</span>
                                            </div>

                                            <div class="w-[32px] h-full flex items-center justify-center border-r border-slate-300">
                                                <span class="font-black text-[11px] leading-none font-mono">{{ $plateMid ?: '---' }}</span>
                                            </div>

                                            <div class="w-[22px] h-full flex flex-col items-center justify-center bg-white">
                                                <span class="text-[5px] font-black text-slate-500 leading-none mb-[2px]">ایران</span>
                                                <span class="font-black text-[10px] leading-none font-mono">{{ $plateIran ?: '--' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-slate-100 text-slate-400 border border-slate-200 font-black text-[10px]">بدون پلاک</span>
                                @endif
                            </td>
                            <td class="p-4 font-mono font-black text-slate-900 text-xs">{{ number_format($req->total_amount) }} <span class="text-[10px] text-slate-400 font-bold mr-0.5">تومان</span></td>
                            <td class="p-4 text-slate-500 font-mono font-medium text-xs">{{ $req->jalali_date }}</td>
                            
                            <td class="p-4 text-center">
                                @php
                                    $requestType = $req->request_type ?? 'new';
                                    $typeText = $requestType === 'renewal' ? 'تمدید' : 'جدید';
                                    $typeClass = $requestType === 'renewal' ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-slate-50 text-slate-700 border-slate-200';
                                    $statusMap = [
                                        'pending' => ['در انتظار بررسی', 'bg-amber-50 text-amber-800 border-amber-200'],
                                        'approved' => ['آماده صدور', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                                        'issued' => ['صادر شده / معتبر', 'bg-sky-50 text-sky-800 border-sky-200'],
                                        'collected' => ['لاشه تحویل شد', 'bg-slate-100 text-slate-700 border-slate-200'],
                                        'archived' => ['بایگانی شد', 'bg-slate-100 text-slate-700 border-slate-200'],
                                        'rejected' => ['‌ رد درخواست', 'bg-rose-50 text-rose-800 border-rose-200'],
                                        'returned' => ['نیاز به اصلاح', 'bg-orange-50 text-orange-800 border-orange-200'],
                                        'lost' => ['مفقودی', 'bg-zinc-100 text-zinc-700 border-zinc-200'],
                                    ];
                                    [$statusText, $statusClass] = $statusMap[$req->status] ?? [$req->status, 'bg-slate-100 text-slate-800 border-slate-200'];
                                    $associationNote = $req->reject_reason ?: null;
                                @endphp
                                <div class="flex flex-col items-center gap-1.5">
                                    <span class="px-3 py-1 rounded-lg border font-black text-[10px] {{ $typeClass }}">{{ $typeText }}</span>
                                    <span class="px-3 py-1 rounded-lg border font-black text-[10px] {{ $statusClass }}">{{ $statusText }}</span>
                                    @if(in_array($req->status, ['rejected', 'returned']) && $associationNote)
                                        <span class="max-w-[180px] text-[10px] leading-5 text-rose-700 bg-rose-50 border border-rose-100 rounded-lg px-2 py-1">
                                            {{ $associationNote }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-slate-400 font-black">هیچ درخواستی تا این لحظه برای این شرکت در دیتابیس یافت نشد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
