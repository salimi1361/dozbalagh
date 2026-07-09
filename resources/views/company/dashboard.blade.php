@extends('layouts.app')

@section('header_title', 'داشبورد شرکت')

@section('content')
@php
    $totalPermits = (int) ($permitStats->total_count ?? 0);
    $issuedCount = (int) ($permitStats->issued_count ?? 0);
    $collectedCount = (int) ($permitStats->collected_count ?? 0);
    $lostCount = (int) ($permitStats->lost_count ?? 0);
    $successfulCount = $issuedCount + $collectedCount + $lostCount;
    $pendingCount = (int) ($permitStats->pending_count ?? 0);
    $approvedCount = (int) ($permitStats->approved_count ?? 0);
    $rejectedCount = (int) ($permitStats->rejected_count ?? 0);
    $returnedCount = (int) ($permitStats->returned_count ?? 0);
    $renewalCount = (int) ($permitStats->renewal_count ?? 0);
    $newCount = (int) ($permitStats->new_count ?? 0);
    $needsAttention = $pendingCount + $returnedCount + $rejectedCount;
    $availableBalance = max(0, (int) $walletBalance);
    $blockedBalanceValue = max(0, (int) $blockedBalance);
    $walletTotal = $availableBalance + $blockedBalanceValue;
    $availablePercent = $walletTotal > 0 ? round(($availableBalance / $walletTotal) * 100) : 0;

    $statusCards = [
        ['title' => 'در انتظار بررسی', 'count' => $pendingCount, 'tone' => 'amber', 'hint' => 'پرونده‌های منتظر اقدام'],
        ['title' => 'آماده صدور', 'count' => $approvedCount, 'tone' => 'emerald', 'hint' => 'تایید شده توسط انجمن'],
        ['title' => 'صادر شده / معتبر', 'count' => $issuedCount, 'tone' => 'sky', 'hint' => 'پروانه‌های فعال'],
        ['title' => 'نیاز به اصلاح', 'count' => $returnedCount, 'tone' => 'orange', 'hint' => 'برگشتی برای تکمیل'],
        ['title' => 'رد درخواست', 'count' => $rejectedCount, 'tone' => 'rose', 'hint' => 'پرونده‌های رد شده'],
        ['title' => 'لاشه تحویل شده', 'count' => $collectedCount, 'tone' => 'indigo', 'hint' => 'تحویل و ثبت نهایی'],
        ['title' => 'درخواست تمدید', 'count' => $renewalCount, 'tone' => 'teal', 'hint' => 'از کل درخواست‌ها'],
        ['title' => 'مفقودی', 'count' => $lostCount, 'tone' => 'slate', 'hint' => 'ثبت مفقودی'],
    ];

    $toneClasses = [
        'amber' => ['card' => 'border-amber-200 bg-amber-50/70 text-amber-800', 'bar' => 'bg-amber-400', 'dot' => 'bg-amber-400'],
        'emerald' => ['card' => 'border-emerald-200 bg-emerald-50/70 text-emerald-800', 'bar' => 'bg-emerald-500', 'dot' => 'bg-emerald-500'],
        'sky' => ['card' => 'border-sky-200 bg-sky-50/70 text-sky-800', 'bar' => 'bg-sky-500', 'dot' => 'bg-sky-500'],
        'orange' => ['card' => 'border-orange-200 bg-orange-50/70 text-orange-800', 'bar' => 'bg-orange-500', 'dot' => 'bg-orange-500'],
        'rose' => ['card' => 'border-rose-200 bg-rose-50/70 text-rose-800', 'bar' => 'bg-rose-500', 'dot' => 'bg-rose-500'],
        'indigo' => ['card' => 'border-indigo-200 bg-indigo-50/70 text-indigo-800', 'bar' => 'bg-indigo-500', 'dot' => 'bg-indigo-500'],
        'teal' => ['card' => 'border-teal-200 bg-teal-50/70 text-teal-800', 'bar' => 'bg-teal-500', 'dot' => 'bg-teal-500'],
        'slate' => ['card' => 'border-slate-200 bg-slate-50 text-slate-700', 'bar' => 'bg-slate-500', 'dot' => 'bg-slate-500'],
    ];

    $maxStatusCount = max(1, ...array_map(fn ($item) => max(0, (int) $item['count']), $statusCards));
@endphp

<div class="space-y-5" dir="rtl">
    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="grid gap-0 lg:grid-cols-[1.45fr_0.9fr]">
            <div class="relative p-5 sm:p-6">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-l from-emerald-500 via-sky-500 to-amber-400"></div>
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="mb-3 inline-flex items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            نمای زنده عملکرد شرکت
                        </div>
                        <h2 class="text-xl font-black leading-9 text-slate-900 sm:text-2xl">
                            {{ $company->name_fa ?? $company->name }}
                        </h2>
                        <div class="mt-3 grid gap-2 text-xs font-bold text-slate-500 sm:grid-cols-3">
                            <span class="rounded-lg bg-slate-50 px-3 py-2">مدیرعامل: <b class="text-slate-800">{{ $company->ceo_name ?? 'ثبت نشده' }}</b></span>
                            <span class="rounded-lg bg-slate-50 px-3 py-2">کد شرکت: <b class="font-mono text-slate-800">{{ $company->company_code ?? '---' }}</b></span>
                            <span class="rounded-lg bg-slate-50 px-3 py-2">کل پرونده‌ها: <b class="font-mono text-slate-800">{{ number_format($totalPermits) }}</b></span>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col gap-2 text-xs font-black">
                        @if($company->status == 'approved')
                            <div class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-emerald-800">
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                حساب کاربری فعال و تایید شده
                            </div>
                        @else
                            <div class="inline-flex items-center justify-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-amber-800">
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                در انتظار تایید مدارک مدیریت
                            </div>
                        @endif
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-slate-600">
                            آدرس: <span class="font-bold text-slate-800">{{ $company->address_fa ?? 'ثبت نشده' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 bg-slate-950 p-5 text-white lg:border-r lg:border-t-0">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black text-slate-400">موجودی کیف پول</p>
                        <p class="mt-2 font-mono text-3xl font-black tracking-wide text-emerald-300">{{ number_format($walletBalance) }}</p>
                    </div>
                    <span class="rounded-lg bg-emerald-400/15 px-3 py-1 text-[11px] font-black text-emerald-300">تومان</span>
                </div>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-white/10">
                    <div class="h-full rounded-full bg-gradient-to-l from-emerald-300 to-sky-300" style="width: {{ $availablePercent }}%"></div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-white/10 pt-4 text-xs font-bold">
                    <span class="text-slate-400">بلوکه: <b class="font-mono text-amber-300">{{ number_format($blockedBalance) }}</b></span>
                    <a href="{{ route('company.wallet.index') }}" class="rounded-lg bg-white/10 px-3 py-1.5 font-black text-sky-200 transition hover:bg-white/15">شارژ حساب</a>
                </div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-sky-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black text-slate-500">رانندگان فعال</span>
                <span class="rounded-lg bg-sky-50 p-2 text-sky-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0" /></svg>
                </span>
            </div>
            <div class="mt-4 flex items-end gap-2">
                <b class="font-mono text-4xl font-black text-slate-900">{{ number_format($driversCount) }}</b>
                <span class="mb-1 text-xs font-bold text-slate-400">نفر</span>
            </div>
            <p class="mt-3 text-xs font-bold text-slate-500">سرمایه انسانی فعال در کارتابل شرکت</p>
        </div>

        <div class="rounded-lg border border-amber-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black text-slate-500">ناوگان ملکی</span>
                <span class="rounded-lg bg-amber-50 p-2 text-amber-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13h13l3 3h2v3h-2a2 2 0 11-4 0H9a2 2 0 11-4 0H3v-6zm0-5h10v5H3V8z" /></svg>
                </span>
            </div>
            <div class="mt-4 flex items-end gap-2">
                <b class="font-mono text-4xl font-black text-slate-900">{{ number_format($fleetsCount) }}</b>
                <span class="mb-1 text-xs font-bold text-slate-400">دستگاه</span>
            </div>
            <p class="mt-3 text-xs font-bold text-slate-500">ناوگان ثبت‌شده و متصل به شرکت</p>
        </div>

        <div class="rounded-lg border border-emerald-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black text-slate-500">نرخ موافقت پروانه‌ها</span>
                <span class="rounded-lg bg-emerald-50 p-2 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 12.5l5 5L20 6" /></svg>
                </span>
            </div>
            <div class="mt-4 flex items-end gap-2">
                <b class="font-mono text-4xl font-black text-emerald-600">{{ $successRate }}%</b>
                <span class="mb-1 text-xs font-bold text-slate-400">{{ number_format($successfulCount) }} پرونده</span>
            </div>
            <div class="mt-4 h-2 rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-gradient-to-l from-emerald-500 to-teal-400" style="width: {{ $successRate }}%"></div>
            </div>
        </div>

        <div class="rounded-lg border border-rose-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black text-slate-500">نیازمند پیگیری</span>
                <span class="rounded-lg bg-rose-50 p-2 text-rose-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v5m0 4h.01M10.3 3.9L2.8 17a2 2 0 001.7 3h15a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" /></svg>
                </span>
            </div>
            <div class="mt-4 flex items-end gap-2">
                <b class="font-mono text-4xl font-black text-rose-600">{{ number_format($needsAttention) }}</b>
                <span class="mb-1 text-xs font-bold text-slate-400">مورد</span>
            </div>
            <p class="mt-3 text-xs font-bold text-slate-500">مجموع در انتظار، اصلاحی و رد شده</p>
        </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-[0.95fr_1.25fr]">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 flex items-center justify-between">
                <h3 class="text-sm font-black text-slate-900">پراکندگی وضعیت پروانه‌ها</h3>
                <span class="rounded-lg bg-slate-100 px-3 py-1 text-[11px] font-black text-slate-600">{{ number_format($totalPermits) }} کل</span>
            </div>

            <div class="space-y-4">
                @foreach($statusCards as $item)
                    @php
                        $classes = $toneClasses[$item['tone']];
                        $barWidth = $maxStatusCount > 0 ? max(3, round(((int) $item['count'] / $maxStatusCount) * 100)) : 3;
                        $share = $totalPermits > 0 ? round(((int) $item['count'] / $totalPermits) * 100) : 0;
                    @endphp
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-xs font-bold">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $classes['dot'] }}"></span>
                                <span class="truncate text-slate-700">{{ $item['title'] }}</span>
                            </div>
                            <div class="shrink-0 font-mono font-black text-slate-900">{{ number_format($item['count']) }} <span class="text-slate-400">({{ $share }}%)</span></div>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $classes['bar'] }}" style="width: {{ $barWidth }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-sm font-black text-slate-900">مانیتورینگ آنلاین وضعیت‌ها</h3>
                <span class="text-xs font-bold text-slate-500">همه اعداد از دیتابیس شرکت خوانده شده‌اند</span>
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                @foreach($statusCards as $item)
                    @php $classes = $toneClasses[$item['tone']]; @endphp
                    <div class="rounded-lg border p-4 {{ $classes['card'] }}">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-[11px] font-black leading-5">{{ $item['title'] }}</span>
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $classes['dot'] }}"></span>
                        </div>
                        <div class="mt-3 font-mono text-3xl font-black">{{ number_format($item['count']) }}</div>
                        <p class="mt-2 text-[10px] font-bold opacity-75">{{ $item['hint'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-900">آخرین درخواست‌های دوزوله صادر شده و معلق</h3>
                <p class="mt-1 text-xs font-bold text-slate-500">نمای سریع پرونده‌های جدید، بدون تغییر در نمایش پلاک</p>
            </div>
            <a href="{{ route('dozbalagh.index') }}" class="inline-flex w-max items-center justify-center rounded-lg border border-sky-200 bg-white px-3 py-2 text-xs font-black text-sky-700 shadow-sm transition hover:bg-sky-50">
                مشاهده کارتابل جامع پروانه‌ها
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] border-collapse text-right text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-white text-[11px] font-black text-slate-500">
                        <th class="p-4">کد پرونده</th>
                        <th class="p-4">راننده متقاضی</th>
                        <th class="p-4">ناوگان ملکی (پلاک ترانزیت)</th>
                        <th class="p-4">هزینه کل پروانه</th>
                        <th class="p-4">تاریخ ثبت و فرستش</th>
                        <th class="p-4 text-center">نوع / وضعیت چرخه عمر</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm font-bold text-slate-700">
                    @forelse($recentRequests->take(10) as $req)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="p-4 font-mono text-xs font-black tracking-wider text-slate-950">{{ $req->d_code }}</td>
                            <td class="p-4 text-slate-800">{{ trim($req->first_name_fa . ' ' . $req->last_name_fa) ?: 'نامشخص' }}</td>
                            <td class="p-4">
                                @php
                                    $plateRaw = $req->transit_plate ?? $req->plate_number ?? null;
                                @endphp
                                <x-iran-plate :plate="$plateRaw" size="xs" />
                                @if(false)
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
                                @endif
                            </td>
                            <td class="p-4 font-mono text-xs font-black text-slate-900">{{ number_format($req->total_amount) }} <span class="mr-0.5 text-[10px] font-bold text-slate-400">تومان</span></td>
                            <td class="p-4 font-mono text-xs font-medium text-slate-500">{{ $req->jalali_date }}</td>
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
                                        'rejected' => ['رد درخواست', 'bg-rose-50 text-rose-800 border-rose-200'],
                                        'returned' => ['نیاز به اصلاح', 'bg-orange-50 text-orange-800 border-orange-200'],
                                        'lost' => ['مفقودی', 'bg-zinc-100 text-zinc-700 border-zinc-200'],
                                    ];
                                    [$statusText, $statusClass] = $statusMap[$req->status] ?? [$req->status, 'bg-slate-100 text-slate-800 border-slate-200'];
                                    $associationNote = $req->reject_reason ?: null;
                                @endphp
                                <div class="flex flex-col items-center gap-1.5">
                                    <span class="rounded-lg border px-3 py-1 text-[10px] font-black {{ $typeClass }}">{{ $typeText }}</span>
                                    <span class="rounded-lg border px-3 py-1 text-[10px] font-black {{ $statusClass }}">{{ $statusText }}</span>
                                    @if(in_array($req->status, ['rejected', 'returned']) && $associationNote)
                                        <span class="max-w-[180px] rounded-lg border border-rose-100 bg-rose-50 px-2 py-1 text-[10px] leading-5 text-rose-700">
                                            {{ $associationNote }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center font-black text-slate-400">هیچ درخواستی تا این لحظه برای این شرکت در دیتابیس یافت نشد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
