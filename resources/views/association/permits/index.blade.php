@extends('layouts.admin')

@section('header_title', 'کارتابل یکپارچه دوزوله‌ها')

@section('content')
@php
    $labels = [
        'pending'=>'جدید', 'under_review'=>'در حال بررسی', 'approved'=>'تأییدشده',
        'cits_processing'=>'در حال ثبت CITS', 'cits_company_error'=>'خطای اطلاعات',
        'cits_system_error'=>'اختلال CITS', 'cits_other_error'=>'خطای CITS',
        'ready_to_issue'=>'آماده صدور', 'issued'=>'چاپ‌شده و آماده تحویل', 'company_returned'=>'لاشه در انتظار دریافت',
        'collected_for_renewal'=>'لاشه تأیید شد / آماده تمدید', 'lost_reported'=>'مفقودی اعلام‌شده',
        'rejected'=>'ردشده', 'archived'=>'بایگانی', 'lost'=>'مفقودی',
        'returned_unused'=>'بدون استفاده', 'transferred_in'=>'انتقال‌یافته', 'extended'=>'تمدیدشده', 'cancelled'=>'باطل‌شده',
    ];
    $statusSummaryClasses = [
        'rejected'=>'border-rose-200 bg-rose-100 text-rose-800', 'cancelled'=>'border-rose-200 bg-rose-100 text-rose-800',
        'lost'=>'border-rose-200 bg-rose-100 text-rose-800', 'approved'=>'border-emerald-200 bg-emerald-100 text-emerald-800',
        'issued'=>'border-sky-200 bg-sky-100 text-sky-800', 'pending'=>'border-amber-200 bg-amber-100 text-amber-800',
        'under_review'=>'border-blue-200 bg-blue-100 text-blue-800', 'company_returned'=>'border-amber-200 bg-amber-100 text-amber-800',
    ];
    $permitTypeLabels = [
        'bilateral' => 'دوجانبه',
        'transit' => 'ترانزیتی',
        'bilateral_transit' => 'دوجانبه ترانزیتی',
        'third_country' => 'کشور ثالث',
        'third_country_transit' => 'ترانزیت کشور ثالث',
    ];
    $scopeLabels = ['active' => 'در حال اقدام', 'rejected' => 'رد شده‌ها', 'circulation' => 'در گردش', 'transferred' => 'انتقال‌یافته', 'archive' => 'بایگانی'];
@endphp
<div class="space-y-5">
    <span id="associationQueueMarker" data-latest-update="{{ strtotime((string) ($latestItemsUpdate ?? '')) ?: 0 }}" class="hidden"></span>
    <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
        <div class="pointer-events-none absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
        <div class="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>

        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M6.75 3.75h7.5L18 7.5v12.75H6V4.5c0-.414.336-.75.75-.75Z"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">کارتابل یکپارچه دوزوله‌ها</h1>
                    <p class="mt-1 text-xs font-semibold text-blue-100">بررسی درخواست، CITS، صدور، پیک، لاشه و تاریخچه هر دوزوله در یک پرونده</p>
                </div>
            </div>

            <nav class="flex w-full rounded-xl border border-white border-opacity-10 bg-white bg-opacity-10 p-1 xl:w-auto" aria-label="محدوده کارتابل">
                <a href="{{ route('association.permits.index', ['scope'=>'active']) }}" class="flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-center text-xs font-black transition xl:flex-none {{ $scope === 'active' ? 'bg-white text-blue-800 shadow-sm' : 'text-blue-100 hover:bg-white hover:bg-opacity-10' }}">در حال اقدام</a>
                <a href="{{ route('association.permits.index', ['scope'=>'rejected']) }}" class="inline-flex flex-1 items-center justify-center gap-1 whitespace-nowrap rounded-lg px-3 py-2 text-center text-xs font-black transition xl:flex-none {{ $scope === 'rejected' ? 'bg-rose-100 text-rose-800 shadow-sm' : 'text-blue-100 hover:bg-white hover:bg-opacity-10' }}">
                    رد شده‌ها
                    <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] {{ ($rejectedCount ?? 0) > 0 ? 'bg-rose-500 text-white' : 'bg-white/15 text-blue-100' }}">{{ number_format($rejectedCount ?? 0) }}</span>
                </a>
                <a href="{{ route('association.permits.index', ['scope'=>'circulation']) }}" class="flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-center text-xs font-black transition xl:flex-none {{ $scope === 'circulation' ? 'bg-white text-blue-800 shadow-sm' : 'text-blue-100 hover:bg-white hover:bg-opacity-10' }}">در گردش</a>
                <a href="{{ route('association.permits.index', ['scope'=>'transferred']) }}" class="flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-center text-xs font-black transition xl:flex-none {{ $scope === 'transferred' ? 'bg-white text-blue-800 shadow-sm' : 'text-blue-100 hover:bg-white hover:bg-opacity-10' }}">انتقال‌یافته</a>
                <a href="{{ route('association.permits.index', ['scope'=>'archive']) }}" class="flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-center text-xs font-black transition xl:flex-none {{ $scope === 'archive' ? 'bg-white text-blue-800 shadow-sm' : 'text-blue-100 hover:bg-white hover:bg-opacity-10' }}">بایگانی</a>
            </nav>
        </div>

        <div class="relative mt-4 grid grid-cols-2 gap-2 lg:grid-cols-4">
            <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">بخش فعلی</span><strong class="text-sm font-black">{{ $scopeLabels[$scope] }}</strong></div>
            <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">تعداد نتایج</span><strong class="text-lg font-black">{{ number_format($items->total()) }}</strong></div>
            <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">این صفحه</span><strong class="text-lg font-black text-cyan-200">{{ number_format($items->count()) }}</strong></div>
            <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">پاسخ جدید CITS</span><strong class="text-lg font-black {{ $unreadCitsResponses > 0 ? 'text-amber-300' : 'text-emerald-300' }}">{{ number_format($unreadCitsResponses) }}</strong></div>
        </div>
    </section>

    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif
    @if($scope === 'rejected')
        <div class="flex flex-col justify-between gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-950 shadow-sm sm:flex-row sm:items-center">
            <div><strong class="block">سوابق درخواست‌های رد شده</strong><span class="mt-1 block text-xs leading-6 text-rose-800">این درخواست‌ها از کارتابل «در حال اقدام» خارج شده‌اند. دلیل رد و تمام رویدادهای هر ردیف داخل پرونده باقی می‌ماند.</span></div>
            <span class="w-fit rounded-full bg-rose-600 px-4 py-2 text-sm font-black text-white">{{ number_format($rejectedCount ?? 0) }} پرونده</span>
        </div>
    @endif
    @if($scope === 'active' && $unreadCitsResponses > 0)
        <div class="flex flex-col justify-between gap-3 rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-900 shadow-sm sm:flex-row sm:items-center">
            <div>
                <strong class="block">پاسخ جدید شرکت در فرایند CITS</strong>
                <span class="mt-1 block text-xs leading-6">{{ $unreadCitsResponses }} پرونده پاسخ جدید دارد و نیازمند بررسی مجدد انجمن است.</span>
            </div>
            <span class="w-fit rounded-full bg-emerald-600 px-4 py-2 text-sm font-black text-white">{{ $unreadCitsResponses }} پاسخ جدید</span>
        </div>
    @endif
    @if($scope === 'circulation')
        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900"><strong>دوزوله‌های در گردش</strong><span class="mr-2 text-xs text-sky-700">فقط مسیرهای صادرشده و تحویل‌شده به پیک شرکت در این بخش هستند؛ مسیرهای ردشده در تب «رد شده‌ها» نمایش داده می‌شوند.</span></div>
    @endif
    @if($scope === 'transferred')
        <div class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-900"><strong>گزارش دوزوله‌های انتقال‌یافته</strong><span class="mr-2 text-xs text-violet-700">شرکت مبدأ و مقصد، شماره دوزوله، علت و زمان انتقال در این بخش نمایش داده می‌شود. این موارد از کارتابل «در حال اقدام» خارج شده‌اند.</span></div>
    @endif

    <form class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_220px_auto]">
        <input type="hidden" name="scope" value="{{ $scope }}">
        <input name="search" value="{{ request('search') }}" class="rounded-xl border-slate-200" placeholder="کد پرونده، سریال، شرکت، شماره کشنده یا بارگیر">
        <select name="status" class="rounded-xl border-slate-200">
            <option value="">همه وضعیت‌ها</option>
            @foreach($labels as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach
        </select>
        <button class="rounded-xl bg-slate-900 px-6 py-2 font-black text-white">جست‌وجو</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500"><tr>
                    <th class="p-4">پرونده مادر / درخواست‌ها</th><th class="p-4">شرکت / کشنده و بارگیر</th>
                    <th class="p-4">کشور / نوع</th><th class="p-4">وضعیت</th>
                    @if($scope === 'circulation')<th class="p-4">مهلت اعتبار</th>@endif
                    <th class="p-4">آخرین تغییر</th><th class="p-4">مسیرها / عملیات</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($items as $group)
                    @php
                        $children = $group->children->sortBy('id')->values();
                        $parent = $children->first();
                        $groupKey = 'association-permit-group-'.$group->parent_id;
                        $statuses = $children->pluck('item_status')->filter()->unique()->values();
                        $statusSummary = $children->filter(fn ($child) => !empty($child->item_status))->groupBy('item_status')->map(fn ($statusItems, $status) => (object) [
                            'status' => $status,
                            'count' => $statusItems->count(),
                            'label' => $labels[$status] ?? $status,
                        ])->sortByDesc('count')->values();
                        $hasRejected = $children->contains(fn ($child) => in_array($child->item_status, ['rejected', 'cancelled'], true));
                        $hasUnreadCitsResponse = $children->contains(fn ($child) => $child->company_cits_responded_at
                            && (!$child->association_cits_response_seen_at || $child->company_cits_responded_at > $child->association_cits_response_seen_at));
                        $hasPendingPickup = $children->contains(fn ($child) => (int) ($child->pending_pickup_count ?? 0) > 0);
                        $hasReturned = $children->contains(fn ($child) => $child->item_status === 'company_returned');
                        $latestChild = $children->sortByDesc('updated_at')->first();
                        $countries = $children->pluck('country_name')->filter()->unique()->values();
                        $types = $children->map(fn ($child) => $permitTypeLabels[$child->permit_type] ?? $child->permit_type)->filter()->unique()->values();
                        $groupStatus = $statuses->count() === 1 ? ($labels[$statuses->first()] ?? $statuses->first()) : 'بررسی چندمرحله‌ای';
                        $groupStatusClass = $hasPendingPickup ? 'bg-indigo-200 text-indigo-900' : ($hasReturned ? 'bg-amber-200 text-amber-900' : ($statuses->count() > 1 ? 'bg-violet-100 text-violet-800' : 'bg-sky-100 text-sky-700'));
                        $groupRowClass = $hasRejected ? 'bg-rose-50 ring-1 ring-inset ring-rose-200 hover:bg-rose-100' : ($hasUnreadCitsResponse ? 'bg-emerald-50 ring-1 ring-inset ring-emerald-200' : ($hasPendingPickup ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200' : ($hasReturned ? 'bg-amber-50 hover:bg-amber-100' : 'hover:bg-sky-50/40')));
                        $canRejectAll = $scope === 'active' && ($bulkRejectableParentIds ?? collect())->has((int) $group->parent_id);
                    @endphp
                    @if($children->count() === 1)
                        @php
                            $item = $parent;
                            $pickupCompleted = !empty($item->pickup_completed_at) && $item->item_status === 'issued';
                        @endphp
                        <tr class="{{ $hasRejected ? 'bg-rose-50 ring-1 ring-inset ring-rose-200 hover:bg-rose-100' : ($hasUnreadCitsResponse ? 'bg-emerald-50 ring-1 ring-inset ring-emerald-200' : ($hasPendingPickup ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200' : ($item->item_status === 'company_returned' ? 'bg-amber-50 hover:bg-amber-100' : 'hover:bg-sky-50/40'))) }}">
                            <td class="p-4">
                                <strong class="block font-mono">{{ $item->d_code }}-{{ $item->id }}</strong>
                                <span class="text-xs text-slate-500">سریال: {{ $item->d_serial_number ?: 'تخصیص نیافته' }}</span>
                                @if($hasUnreadCitsResponse)<span class="mt-2 block w-fit rounded-full bg-emerald-600 px-3 py-1 text-[10px] font-black text-white">پاسخ جدید شرکت</span>@endif
                                @if($hasPendingPickup)<span class="mt-2 block w-fit rounded-full bg-indigo-600 px-3 py-1 text-[10px] font-black text-white">پیک در راه دریافت دوزوله</span>@endif
                            </td>
                            <td class="p-4">
                                @if($scope === 'transferred')
                                    <strong class="block text-rose-700">از: {{ $item->transfer_source_company_name }}</strong>
                                    <strong class="mt-1 block text-emerald-700">به: {{ $item->transfer_destination_company_name }}</strong>
                                    <span class="mt-1 block text-[10px] text-slate-500">علت: {{ $item->transfer_reason }}</span>
                                @else
                                    <strong class="block">{{ $item->company_name }}</strong><span class="mt-1 block text-[10px] text-slate-400">شماره ترانزیت اسب (کشنده): <b class="font-mono text-slate-700">{{ $item->transit_horse ?: 'ثبت نشده' }}</b></span><span class="block text-[10px] text-slate-400">شماره ترانزیت یدک (بارگیر): <b class="font-mono text-slate-700">{{ $item->transit_trailer ?: 'ثبت نشده' }}</b></span>
                                @endif
                            </td>
                            <td class="p-4"><strong>{{ $item->country_name ?: 'نامشخص' }}</strong><span class="block text-xs text-slate-500">{{ $permitTypeLabels[$item->permit_type] ?? $item->permit_type }}</span></td>
                            <td class="p-4"><span class="rounded-full px-3 py-1 text-xs font-black {{ $hasPendingPickup ? 'bg-indigo-200 text-indigo-900' : ($pickupCompleted ? 'bg-emerald-100 text-emerald-800' : ($item->item_status === 'company_returned' ? 'bg-amber-200 text-amber-900' : 'bg-sky-100 text-sky-700')) }}">{{ $hasPendingPickup ? 'در انتظار تحویل به پیک' : ($pickupCompleted ? 'تحویل پیک شرکت شد' : ($item->item_status === 'company_returned' && $item->disposition_type === 'unused' ? 'برگشت بدون استفاده در انتظار دریافت' : ($labels[$item->item_status] ?? $item->item_status))) }}</span>@if($hasPendingPickup)<span class="mt-2 block text-[10px] font-bold text-indigo-700">{{ $item->pending_pickup_courier_name }} — {{ $item->pending_pickup_courier_mobile }}</span>@elseif($pickupCompleted)<span class="mt-2 block text-[10px] font-bold text-emerald-700">{{ $item->pickup_completed_courier_name ?: 'پیک معرفی‌شده' }} — {{ verta($item->pickup_completed_at)->format('Y/m/d H:i') }}</span>@elseif($item->item_status === 'company_returned')<span class="mt-2 block text-[10px] font-bold text-amber-700">{{ $item->disposition_type === 'unused' ? 'مسیر مستقل بدون CITS لاشه' : 'نیازمند کنترل کد پیک' }}</span>@endif</td>
                            @if($scope === 'circulation')
                                <td class="p-4">
                                    @if((int) ($item->validity_days ?? $item->country_validity_days) === 0)
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">بدون محدودیت</span>
                                    @elseif(!$item->permit_valid_until)
                                        <span class="text-xs font-bold text-rose-600">تاریخ پایان ثبت نشده</span>
                                    @else
                                        @php $remaining = (int) $item->expiry_days_remaining; @endphp
                                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $remaining < 0 ? 'bg-rose-100 text-rose-800' : ($remaining <= 10 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">{{ $remaining < 0 ? abs($remaining).' روز از مهلت گذشته' : ($remaining === 0 ? 'مهلت امروز تمام می‌شود' : $remaining.' روز باقی مانده') }}</span>
                                        <span class="mt-2 block text-[10px] font-bold text-slate-500">پایان: {{ verta($item->permit_valid_until)->format('Y/m/d') }}</span>
                                    @endif
                                </td>
                            @endif
                            <td class="whitespace-nowrap p-4 text-xs text-slate-500">
                                {{ verta($scope === 'transferred' && $item->transferred_at ? $item->transferred_at : $item->updated_at)->format('Y/m/d H:i') }}
                                @if($hasUnreadCitsResponse)<span class="mt-1 block font-bold text-emerald-700">پاسخ شرکت: {{ verta($item->company_cits_responded_at)->format('H:i') }}</span>@endif
                            </td>
                            <td class="p-4"><a href="{{ route('association.permits.show',$item->id) }}" class="rounded-xl px-4 py-2 text-xs font-black text-white {{ $hasUnreadCitsResponse ? 'bg-emerald-600 shadow-sm shadow-emerald-200' : ($hasPendingPickup ? 'bg-indigo-600 shadow-sm shadow-indigo-200' : 'bg-slate-900') }}">{{ $hasUnreadCitsResponse ? 'بررسی پاسخ شرکت' : ($hasPendingPickup ? 'ثبت تحویل به پیک' : 'باز کردن پرونده') }}</a></td>
                        </tr>
                    @else
                    <tr data-permit-group-row="{{ $groupKey }}" tabindex="0" role="button" aria-expanded="false" aria-controls="{{ $groupKey }}" class="cursor-pointer {{ $groupRowClass }}">
                        <td class="p-4">
                            <div class="flex items-start gap-3">
                                <button type="button" data-permit-group-toggle="{{ $groupKey }}" aria-expanded="false" aria-controls="{{ $groupKey }}" class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-lg font-black text-white transition hover:bg-blue-700" title="نمایش درخواست‌های این پرونده"><span>+</span></button>
                                <div>
                                    <strong class="block font-mono">{{ $parent->d_code }}</strong>
                                    <span class="mt-1 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-black text-blue-700">{{ number_format($children->count()) }} مسیر درخواست</span>
                                    <span class="mt-1 block text-[10px] text-slate-500">ردیف‌ها: {{ $children->map(fn ($child) => $child->d_code.'-'.$child->id)->implode('، ') }}</span>
                                    @if($hasUnreadCitsResponse)<span class="mt-2 block w-fit rounded-full bg-emerald-600 px-3 py-1 text-[10px] font-black text-white">پاسخ جدید شرکت</span>@endif
                                    @if($hasPendingPickup)<span class="mt-2 block w-fit rounded-full bg-indigo-600 px-3 py-1 text-[10px] font-black text-white">پیک در راه دریافت دوزوله</span>@endif
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            @if($scope === 'transferred')
                                <strong class="block text-rose-700">از: {{ $parent->transfer_source_company_name }}</strong>
                                <strong class="mt-1 block text-emerald-700">به: {{ $parent->transfer_destination_company_name }}</strong>
                                <span class="mt-1 block text-[10px] text-slate-500">علت: {{ $parent->transfer_reason }}</span>
                            @else
                                <strong class="block">{{ $parent->company_name }}</strong><span class="mt-1 block text-[10px] text-slate-400">شماره ترانزیت اسب (کشنده): <b class="font-mono text-slate-700">{{ $parent->transit_horse ?: 'ثبت نشده' }}</b></span><span class="block text-[10px] text-slate-400">شماره ترانزیت یدک (بارگیر): <b class="font-mono text-slate-700">{{ $parent->transit_trailer ?: 'ثبت نشده' }}</b></span>
                            @endif
                        </td>
                        <td class="p-4"><strong>{{ $countries->count() > 1 ? $countries->count().' مقصد' : ($countries->first() ?: 'نامشخص') }}</strong><span class="block text-xs text-slate-500">{{ $types->implode('، ') ?: 'نوع نامشخص' }}</span></td>
                        <td class="p-4">
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $groupStatusClass }}">{{ $hasPendingPickup ? 'در انتظار تحویل به پیک' : $groupStatus }}</span>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach($statusSummary as $summary)
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-black {{ $statusSummaryClasses[$summary->status] ?? 'border-slate-200 bg-slate-100 text-slate-700' }}">{{ number_format($summary->count) }} {{ $summary->label }}</span>
                                @endforeach
                            </div>
                            <span class="mt-2 block text-[10px] font-bold text-slate-500">خلاصه وضعیت {{ $children->count() }} مسیر</span>
                        </td>
                        @if($scope === 'circulation')
                            <td class="p-4">
                                @if((int) ($parent->validity_days ?? $parent->country_validity_days) === 0)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">بدون محدودیت</span>
                                @elseif(!$parent->permit_valid_until)
                                    <span class="text-xs font-bold text-rose-600">تاریخ پایان ثبت نشده</span>
                                @else
                                    @php $remaining = (int) $parent->expiry_days_remaining; @endphp
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $remaining < 0 ? 'bg-rose-100 text-rose-800' : ($remaining <= 10 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">{{ $remaining < 0 ? abs($remaining).' روز از مهلت گذشته' : ($remaining === 0 ? 'مهلت امروز تمام می‌شود' : $remaining.' روز باقی مانده') }}</span>
                                    <span class="mt-2 block text-[10px] font-bold text-slate-500">پایان: {{ verta($parent->permit_valid_until)->format('Y/m/d') }}</span>
                                @endif
                            </td>
                        @endif
                        <td class="whitespace-nowrap p-4 text-xs text-slate-500">
                            {{ verta($scope === 'transferred' && $latestChild->transferred_at ? $latestChild->transferred_at : $latestChild->updated_at)->format('Y/m/d H:i') }}
                            @if($hasUnreadCitsResponse)<span class="mt-1 block font-bold text-emerald-700">پاسخ جدید در یکی از ردیف‌ها</span>@endif
                        </td>
                        <td class="p-4">
                            <div class="flex flex-col items-start gap-2">
                                <span class="inline-flex rounded-xl bg-blue-50 px-4 py-2 text-xs font-black text-blue-700">درخواست در {{ number_format($children->count()) }} مسیر ثبت شده</span>
                                @if($canRejectAll)
                                    <details class="w-full max-w-[290px] rounded-xl border border-rose-200 bg-rose-50">
                                        <summary class="cursor-pointer list-none px-3 py-2 text-xs font-black text-rose-700">رد کامل این درخواست</summary>
                                        <form action="{{ route('association.permits.reject-all', $group->parent_id) }}" method="POST" class="border-t border-rose-200 p-3" onsubmit="return confirm('همه مسیرهای این درخواست رد شود؟')">
                                            @csrf
                                            <textarea name="reason" required maxlength="2000" rows="3" class="w-full rounded-lg border-rose-200 bg-white text-xs focus:border-rose-500 focus:ring-rose-500" placeholder="دلیل رد درخواست"></textarea>
                                            <button type="submit" class="mt-2 w-full rounded-lg bg-rose-600 px-3 py-2 text-xs font-black text-white transition hover:bg-rose-700">تأیید رد {{ number_format($children->count()) }} مسیر</button>
                                        </form>
                                    </details>
                                @endif
                                <span class="text-[10px] font-bold text-slate-500">برای نمایش درخواست‌ها روی + یا ردیف کلیک کنید</span>
                            </div>
                        </td>
                    </tr>
                    <tr id="{{ $groupKey }}" class="hidden bg-slate-50">
                        <td colspan="{{ $scope === 'circulation' ? 7 : 6 }}" class="p-4">
                            <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-inner">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <strong class="text-sm text-slate-800">درخواست‌های پرونده {{ $parent->d_code }}</strong>
                                    <span class="text-xs font-bold text-slate-500">تصمیم هر مسیر مستقل ثبت می‌شود.</span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[760px] text-right text-xs">
                                        <thead class="border-b border-slate-200 text-slate-500"><tr><th class="p-3">ردیف / شماره</th><th class="p-3">کشور / نوع</th><th class="p-3">راننده / ناوگان</th><th class="p-3">وضعیت</th><th class="p-3">آخرین تغییر</th><th class="p-3"></th></tr></thead>
                                        <tbody class="divide-y divide-slate-100">
                                        @foreach($children as $child)
                                            @php
                                                $childUnread = $child->company_cits_responded_at && (!$child->association_cits_response_seen_at || $child->company_cits_responded_at > $child->association_cits_response_seen_at);
                                                $childPickup = (int) ($child->pending_pickup_count ?? 0) > 0;
                                            @endphp
                                            <tr class="hover:bg-sky-50/50">
                                                <td class="p-3"><strong class="block font-mono">{{ $child->d_code }}-{{ $child->id }}</strong><span class="text-[10px] text-slate-500">سریال: {{ $child->d_serial_number ?: 'تخصیص نیافته' }}</span></td>
                                                <td class="p-3"><strong>{{ $child->country_name ?: 'نامشخص' }}</strong><span class="block text-[10px] text-slate-500">{{ $permitTypeLabels[$child->permit_type] ?? $child->permit_type }}</span></td>
                                                <td class="p-3"><strong class="block">{{ trim(($child->first_name_fa ?? '').' '.($child->last_name_fa ?? '')) ?: 'ثبت نشده' }}</strong><span class="block text-[10px] text-slate-500">اسب: {{ $child->transit_horse ?: '—' }} | یدک: {{ $child->transit_trailer ?: '—' }}</span></td>
                                                <td class="p-3"><span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $childPickup ? 'bg-indigo-100 text-indigo-800' : ($child->item_status === 'company_returned' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-700') }}">{{ $childPickup ? 'در انتظار تحویل به پیک' : ($labels[$child->item_status] ?? $child->item_status) }}</span>@if($childUnread)<span class="mt-1 block font-bold text-emerald-700">پاسخ جدید شرکت</span>@endif</td>
                                                <td class="whitespace-nowrap p-3 text-slate-500">{{ verta($child->updated_at)->format('Y/m/d H:i') }}</td>
                                                <td class="p-3"><a href="{{ route('association.permits.show',$child->id) }}" class="rounded-lg bg-slate-900 px-3 py-2 text-[10px] font-black text-white">باز کردن</a></td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="{{ $scope === 'circulation' ? 7 : 6 }}" class="p-16 text-center font-bold text-slate-400">موردی در این کارتابل وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t p-4">{{ $items->links() }}</div>
    </div>
</div>
<script>
(() => {
    const toggleGroup = (toggle) => {
        const detail = document.getElementById(toggle.dataset.permitGroupToggle);
        if (!detail) return;

        const isOpening = detail.classList.contains('hidden');
        detail.classList.toggle('hidden', !isOpening);
        toggle.setAttribute('aria-expanded', isOpening ? 'true' : 'false');
        const row = toggle.closest('[data-permit-group-row]');
        if (row) row.setAttribute('aria-expanded', isOpening ? 'true' : 'false');
        const icon = toggle.querySelector('span');
        if (icon) icon.textContent = isOpening ? '−' : '+';
    };

    document.querySelectorAll('[data-permit-group-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleGroup(toggle);
        });
    });

    document.querySelectorAll('[data-permit-group-row]').forEach((row) => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('button, a, input, select, textarea, label, details, summary, form')) return;
            const toggle = row.querySelector('[data-permit-group-toggle]');
            if (toggle) toggleGroup(toggle);
        });

        row.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            if (event.target.closest('button, a, input, select, textarea, label, details, summary, form')) return;
            event.preventDefault();
            const toggle = row.querySelector('[data-permit-group-toggle]');
            if (toggle) toggleGroup(toggle);
        });
    });

    const marker=document.getElementById('associationQueueMarker');
    let latest=Number(marker?.dataset.latestUpdate||0), dirty=false;
    document.querySelectorAll('form input, form select').forEach(field=>{
        field.addEventListener('input',()=>dirty=true,{once:true});
        field.addEventListener('change',()=>dirty=true,{once:true});
    });
    setInterval(async()=>{
        if(document.hidden||dirty)return;
        try{
            const response=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'},cache:'no-store'});
            if(!response.ok)return;
            const doc=new DOMParser().parseFromString(await response.text(),'text/html');
            const next=Number(doc.getElementById('associationQueueMarker')?.dataset.latestUpdate||0);
            if(next>latest)location.reload();
        }catch(_){}
    },10000);
})();
</script>
@endsection
