@extends('layouts.app')

@section('header_title', 'پرونده‌های دوزوله')

@section('content')
@php
    $labels = [
        'pending'=>'در انتظار بررسی انجمن', 'under_review'=>'در حال بررسی انجمن',
        'approved'=>'تأیید اولیه', 'cits_processing'=>'در حال ثبت در CITS',
        'cits_company_error'=>'خطای اطلاعات در CITS', 'cits_system_error'=>'اختلال سامانه CITS',
        'cits_other_error'=>'خطای CITS', 'ready_to_issue'=>'آماده چاپ',
        'issued'=>'چاپ‌شده و آماده تحویل', 'company_returned'=>'لاشه در مسیر انجمن',
        'collected_for_renewal'=>'لاشه دریافت‌شده برای تمدید', 'rejected'=>'رد کامل',
        'archived'=>'بایگانی‌شده', 'returned_unused'=>'برگشت بدون استفاده',
        'extended'=>'تمدیدشده', 'lost_reported'=>'مفقودی اعلام‌شده',
        'lost'=>'مفقودی تأییدشده', 'cancelled'=>'باطل‌شده',
        'processing'=>'در حال بررسی چند ردیف', 'partially_issued'=>'بخشی صادر شده',
    ];
    $classes = [
        'pending'=>'bg-amber-50 text-amber-700 border-amber-200',
        'under_review'=>'bg-blue-50 text-blue-700 border-blue-200',
        'approved'=>'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cits_processing'=>'bg-blue-50 text-blue-700 border-blue-200',
        'cits_company_error'=>'bg-rose-50 text-rose-700 border-rose-200',
        'cits_system_error'=>'bg-orange-50 text-orange-700 border-orange-200',
        'cits_other_error'=>'bg-orange-50 text-orange-700 border-orange-200',
        'ready_to_issue'=>'bg-violet-50 text-violet-700 border-violet-200',
        'issued'=>'bg-sky-50 text-sky-700 border-sky-200',
        'rejected'=>'bg-rose-50 text-rose-700 border-rose-200',
        'lost'=>'bg-rose-50 text-rose-700 border-rose-200',
        'lost_reported'=>'bg-orange-50 text-orange-700 border-orange-200',
        'archived'=>'bg-slate-100 text-slate-700 border-slate-200',
        'processing'=>'bg-violet-50 text-violet-700 border-violet-200',
        'partially_issued'=>'bg-indigo-50 text-indigo-700 border-indigo-200',
    ];
    $permitTypeLabels=['bilateral'=>'دوجانبه','transit'=>'ترانزیت','bilateral_transit'=>'دوجانبه ترانزیت','third_country_transit'=>'ترانزیت کشور ثالث','third_country'=>'کشور ثالث'];
    $statusSummaryClasses = [
        'rejected'=>'border-rose-200 bg-rose-100 text-rose-800', 'cancelled'=>'border-rose-200 bg-rose-100 text-rose-800',
        'lost'=>'border-rose-200 bg-rose-100 text-rose-800', 'lost_reported'=>'border-orange-200 bg-orange-100 text-orange-800',
        'approved'=>'border-emerald-200 bg-emerald-100 text-emerald-800', 'issued'=>'border-sky-200 bg-sky-100 text-sky-800',
        'pending'=>'border-amber-200 bg-amber-100 text-amber-800', 'under_review'=>'border-blue-200 bg-blue-100 text-blue-800',
        'company_returned'=>'border-amber-200 bg-amber-100 text-amber-800',
    ];
    $permitStats = [
        'total' => (int) ($companyPermitStats->total ?? 0),
        'in_progress' => (int) ($companyPermitStats->in_progress ?? 0),
        'issued' => (int) ($companyPermitStats->issued ?? 0),
        'renewals' => (int) ($companyPermitStats->renewals ?? 0),
    ];
    $primaryFilters = [
        '' => ['label' => 'در حال اقدام', 'active' => 'border-slate-900 bg-slate-900 text-white shadow-sm', 'idle' => 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 hover:bg-slate-100', 'dot' => 'bg-slate-500'],
        'pending' => ['label' => 'در جریان بررسی', 'active' => 'border-amber-500 bg-amber-500 text-white shadow-sm', 'idle' => 'border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-300 hover:bg-amber-100', 'dot' => 'bg-amber-500'],
        'approved' => ['label' => 'تأیید اولیه', 'active' => 'border-emerald-600 bg-emerald-600 text-white shadow-sm', 'idle' => 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100', 'dot' => 'bg-emerald-500'],
        'issued' => ['label' => 'صادرشده', 'active' => 'border-sky-600 bg-sky-600 text-white shadow-sm', 'idle' => 'border-sky-200 bg-sky-50 text-sky-700 hover:border-sky-300 hover:bg-sky-100', 'dot' => 'bg-sky-500'],
        'collected' => ['label' => 'لاشه / تمدید', 'active' => 'border-violet-600 bg-violet-600 text-white shadow-sm', 'idle' => 'border-violet-200 bg-violet-50 text-violet-700 hover:border-violet-300 hover:bg-violet-100', 'dot' => 'bg-violet-500'],
    ];
    $secondaryFilters = [
        'rejected' => ['label' => 'ردشده', 'active' => 'border-rose-600 bg-rose-600 text-white shadow-sm', 'idle' => 'border-rose-200 bg-rose-50 text-rose-700 hover:border-rose-300 hover:bg-rose-100', 'dot' => 'bg-rose-500'],
        'archived' => ['label' => 'بایگانی', 'active' => 'border-slate-600 bg-slate-600 text-white shadow-sm', 'idle' => 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50', 'dot' => 'bg-slate-400'],
        'renewal' => ['label' => 'تمدیدها', 'active' => 'border-indigo-600 bg-indigo-600 text-white shadow-sm', 'idle' => 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:border-indigo-300 hover:bg-indigo-100', 'dot' => 'bg-indigo-500'],
        'lost' => ['label' => 'مفقودی', 'active' => 'border-orange-600 bg-orange-600 text-white shadow-sm', 'idle' => 'border-orange-200 bg-orange-50 text-orange-700 hover:border-orange-300 hover:bg-orange-100', 'dot' => 'bg-orange-500'],
    ];
@endphp
<div class="mx-auto max-w-7xl space-y-5" dir="rtl">
    <div class="space-y-0">
        <span id="companyPermitListMarker" data-latest-update="{{ strtotime((string) ($latestCompanyItemUpdate ?? '')) ?: 0 }}" class="hidden"></span>
        <section class="company-page-header rounded-t-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5">
        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.75 5.75A1.75 1.75 0 0 1 6.5 4h3l1.5 2h6.5a1.75 1.75 0 0 1 1.75 1.75v9.75a1.75 1.75 0 0 1-1.75 1.75h-11a1.75 1.75 0 0 1-1.75-1.75V5.75Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 11h8M8 14.5h5"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">پرونده‌های دوزوله شرکت</h1>
                    <p class="mt-1 text-xs font-semibold text-emerald-100">پیگیری مستقل وضعیت بررسی، CITS، صدور، پیک، لاشه و تاریخچه هر دوزوله</p>
                </div>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <div class="flex min-w-[7.5rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                        <span class="text-[10px] font-bold text-emerald-100">کل پرونده‌ها</span>
                        <strong class="text-lg font-black">{{ number_format($permitStats['total']) }}</strong>
                    </div>
                    <div class="flex min-w-[7.5rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                        <span class="text-[10px] font-bold text-emerald-100">در جریان بررسی</span>
                        <strong class="text-lg font-black text-amber-200">{{ number_format($permitStats['in_progress']) }}</strong>
                    </div>
                    <div class="flex min-w-[7.5rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                        <span class="text-[10px] font-bold text-emerald-100">صادرشده</span>
                        <strong class="text-lg font-black text-cyan-100">{{ number_format($permitStats['issued']) }}</strong>
                    </div>
                    <div class="flex min-w-[7.5rem] items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                        <span class="text-[10px] font-bold text-emerald-100">تمدیدها</span>
                        <strong class="text-lg font-black text-violet-200">{{ number_format($permitStats['renewals']) }}</strong>
                    </div>
                </div>
                @if($requestWindowStatus['allowed'])
                    <a href="{{ route('dozbalagh.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-black text-emerald-800 shadow-sm transition hover:bg-emerald-50">
                        <span class="text-lg leading-none">+</span>
                        ثبت درخواست جدید
                    </a>
                @else
                    <button type="button" disabled class="inline-flex cursor-not-allowed items-center justify-center rounded-xl border border-white border-opacity-10 bg-emerald-950 bg-opacity-40 px-5 py-3 text-sm font-black text-emerald-100">
                        ثبت درخواست بسته است
                    </button>
                @endif
            </div>
        </div>
        </section>

        <section class="overflow-hidden rounded-b-2xl border border-t-0 border-slate-200 bg-white shadow-sm">
            <div class="space-y-5 p-4 sm:p-5">
            <form action="{{ route('dozbalagh.index') }}" method="GET" class="flex flex-col gap-3 lg:flex-row lg:items-end">
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                <label class="block min-w-0 flex-1">
                    <span class="mb-2 flex items-center justify-between gap-3">
                        <span class="text-xs font-black text-slate-700">جست‌وجوی پرونده</span>
                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-black text-emerald-700">{{ number_format($items->total()) }} نتیجه</span>
                    </span>
                    <span class="relative block">
                        <svg class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke-width="1.8"></circle>
                            <path stroke-linecap="round" stroke-width="1.8" d="m20 20-3.5-3.5"></path>
                        </svg>
                        <input name="search" value="{{ request('search') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-11 pr-12 text-sm font-bold text-slate-800 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100" placeholder="کد پرونده، سریال، نام راننده، ناوگان یا کشور را وارد کنید">
                        @if(request('search'))
                            <a href="{{ route('dozbalagh.index', array_filter(['status' => request('status')])) }}" class="absolute left-3 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg bg-slate-200 text-slate-500 transition hover:bg-rose-100 hover:text-rose-600" title="پاک‌کردن جست‌وجو">×</a>
                        @endif
                    </span>
                </label>
                <button class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-7 text-sm font-black text-white shadow-sm transition hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="2"></circle><path stroke-linecap="round" stroke-width="2" d="m20 20-3.5-3.5"></path></svg>
                    اعمال جست‌وجو
                </button>
            </form>

            <div class="grid gap-4 border-t border-slate-100 pt-4 xl:grid-cols-3">
                <div class="xl:col-span-2">
                    <div class="mb-2 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-100 text-xs font-black text-emerald-700">۱</span>
                        <h3 class="text-xs font-black text-slate-700">روند اصلی پرونده</h3>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-black">
                        @foreach($primaryFilters as $value => $filter)
                            @php $filterIsActive = request('status', '') === $value; @endphp
                            <a href="{{ route('dozbalagh.index', array_filter(['status' => $value, 'search' => request('search')], fn ($item) => $item !== '')) }}"
                               class="inline-flex items-center gap-2 rounded-xl border px-3 py-2.5 transition {{ $filterIsActive ? $filter['active'] : $filter['idle'] }}">
                                <span class="h-2 w-2 rounded-full {{ $filterIsActive ? 'bg-white' : $filter['dot'] }}"></span>
                                {{ $filter['label'] }}
                            </a>
                            @if(!$loop->last)<span class="hidden text-slate-300 lg:inline">←</span>@endif
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="mb-2 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-xs font-black text-slate-600">۲</span>
                        <h3 class="text-xs font-black text-slate-700">وضعیت‌های تکمیلی</h3>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs font-black">
                        @foreach($secondaryFilters as $value => $filter)
                            @php $filterIsActive = request('status', '') === $value; @endphp
                            <a href="{{ route('dozbalagh.index', array_filter(['status' => $value, 'search' => request('search')], fn ($item) => $item !== '')) }}"
                               class="inline-flex items-center gap-2 rounded-xl border px-3 py-2.5 transition {{ $filterIsActive ? $filter['active'] : $filter['idle'] }}">
                                <span class="h-2 w-2 rounded-full {{ $filterIsActive ? 'bg-white' : $filter['dot'] }}"></span>
                                {{ $filter['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            </div>
        </section>
    </div>

    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif
    @if(request('status') === 'rejected')
        <div class="flex flex-col justify-between gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-950 shadow-sm sm:flex-row sm:items-center">
            <div><strong class="block">سوابق مسیرهای ردشده</strong><span class="mt-1 block text-xs leading-6 text-rose-800">در این بخش فقط مسیرهایی نمایش داده می‌شوند که انجمن رد کرده است؛ مسیرهای تأییدشده یا صادرشده در تب وضعیت خودشان باقی می‌مانند.</span></div>
            <span class="w-fit rounded-full bg-rose-600 px-4 py-2 text-sm font-black text-white">فقط ردشده‌ها</span>
        </div>
    @elseif(request('status') === 'issued')
        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900"><strong>مسیرهای صادرشده</strong><span class="mr-2 text-xs text-sky-700">فقط دوزوله‌های صادرشده در این تب هستند؛ مسیرهای ردشده در تب «ردشده» نمایش داده می‌شوند.</span></div>
    @endif

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1050px] text-right text-sm">
                <thead class="border-b bg-slate-50 text-xs text-slate-500">
                <tr>
                    <th class="p-4">پرونده مادر / درخواست‌ها</th><th class="p-4">کشور / نوع</th>
                    <th class="p-4">راننده / ناوگان</th><th class="p-4">مبلغ کل پرونده</th>
                    <th class="p-4">وضعیت کلی</th><th class="p-4">آخرین اقدام</th><th class="p-4">مسیرها / عملیات</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($items as $group)
                    @php
                        $children = $group->children->sortBy('id')->values();
                        $parent = $children->first();
                        $groupKey = 'company-permit-group-'.$group->parent_id;
                        $statuses = $children->pluck('item_status')->filter()->unique()->values();
                        $groupStatus = $statuses->count() === 1
                            ? $statuses->first()
                            : ($statuses->contains('issued') ? 'partially_issued' : 'processing');
                        $groupStatus = $groupStatus ?: ($parent->parent_status ?: 'processing');
                        $groupStatusLabel = $labels[$groupStatus] ?? $groupStatus;
                        $groupStatusClass = $classes[$groupStatus] ?? 'border-violet-200 bg-violet-50 text-violet-700';
                        $statusSummary = $children->filter(fn ($child) => !empty($child->item_status))->groupBy('item_status')->map(fn ($statusItems, $status) => (object) [
                            'status' => $status,
                            'count' => $statusItems->count(),
                            'label' => $labels[$status] ?? $status,
                        ])->sortByDesc('count')->values();
                        $hasRejected = $children->contains(fn ($child) => in_array($child->item_status, ['rejected', 'cancelled', 'lost'], true));
                        $hasAttentionStatus = $children->contains(fn ($child) => in_array($child->item_status, ['rejected', 'cancelled', 'lost', 'lost_reported', 'company_returned'], true));
                        $groupRowClass = $hasRejected ? 'bg-rose-50 ring-1 ring-inset ring-rose-200 hover:bg-rose-100' : ($hasAttentionStatus ? 'bg-amber-50 ring-1 ring-inset ring-amber-200 hover:bg-amber-100' : 'hover:bg-emerald-50/30');
                        $countries = $children->pluck('country_name')->filter()->unique()->values();
                        $types = $children->map(fn ($child) => $permitTypeLabels[$child->permit_type] ?? str_replace('_',' ',$child->permit_type))->filter()->unique()->values();
                        $groupAmount = $children->sum(fn ($child) => (float) ($child->reserved_amount ?: $child->price));
                        $latestChild = $children->sortByDesc('updated_at')->first();
                        $lastEvent = \Illuminate\Support\Facades\DB::table('permit_item_events')
                            ->where('permit_request_item_id',$latestChild->id)
                            ->where('permit_request_id',$latestChild->permit_request_id)
                            ->where('created_at','>=',$latestChild->created_at)
                            ->latest('id')->first();
                    @endphp
                    @if($children->count() === 1)
                        @php
                            $item = $parent;
                            $pickupCompleted = !empty($item->pickup_completed_at);
                        @endphp
                        <tr class="transition {{ $groupRowClass }}">
                            <td class="p-4">
                                <strong class="block font-mono text-slate-800">{{ $item->d_code }}-{{ $item->id }}</strong>
                                <span class="mt-1 block text-[10px] text-slate-400">درخواست مادر: {{ $item->d_code }}</span>
                                <span class="mt-1 block font-mono text-xs text-sky-700">سریال: {{ $item->d_serial_number ?: 'هنوز تخصیص نیافته' }}</span>
                            </td>
                            <td class="p-4"><strong>{{ $item->country_name ?: 'نامشخص' }}</strong><span class="mt-1 inline-flex rounded-lg bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700">{{ $permitTypeLabels[$item->permit_type] ?? str_replace('_',' ',$item->permit_type) }}</span></td>
                            <td class="p-4"><strong class="block">{{ trim(($item->first_name_fa ?? '').' '.($item->last_name_fa ?? '')) }}</strong><span class="mt-1 block text-[10px] text-slate-400">اسب: <b class="font-mono text-slate-600">{{ $item->transit_horse ?: $item->transit_plate ?: '—' }}</b></span><span class="block text-[10px] text-slate-400">یدک: <b class="font-mono text-slate-600">{{ $item->transit_trailer ?: '—' }}</b></span></td>
                            <td class="p-4"><strong class="font-mono">{{ number_format($item->reserved_amount ?: $item->price) }}</strong><span class="mt-1 block text-[10px] text-slate-400">{{ ['reserved'=>'بلوکه‌شده','settled'=>'کسر قطعی','released'=>'آزادشده'][$item->financial_status] ?? $item->financial_status }}</span></td>
                            <td class="p-4">
                                @if($pickupCompleted && $item->item_status === 'issued')
                                    <span class="inline-flex rounded-full border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">تحویل پیک شرکت شد</span>
                                    <span class="mt-2 block text-[10px] font-bold text-emerald-600">{{ $item->pickup_completed_courier_name ?: 'پیک معرفی‌شده' }} — {{ verta($item->pickup_completed_at)->format('Y/m/d H:i') }}</span>
                                @else
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-black {{ $classes[$item->item_status] ?? 'border-indigo-200 bg-indigo-50 text-indigo-700' }}">{{ $labels[$item->item_status] ?? $item->item_status }}</span>
                                    <span class="mt-2 block text-[10px] text-slate-400">{{ $item->request_type === 'renewal' ? 'درخواست تمدید' : 'درخواست جدید' }}</span>
                                @endif
                            </td>
                            <td class="max-w-[260px] p-4"><p class="line-clamp-2 text-xs leading-6 text-slate-600">{{ $lastEvent?->description ?: 'درخواست ثبت شده و منتظر اقدام است.' }}</p><time class="text-[10px] text-slate-400">{{ $lastEvent ? verta($lastEvent->created_at)->format('Y/m/d H:i') : verta($item->created_at)->format('Y/m/d H:i') }}</time></td>
                            <td class="p-4"><a href="{{ route('company.dozbalagh.item.show',$item->id) }}" class="inline-flex whitespace-nowrap rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-black text-white shadow">مشاهده پرونده</a></td>
                        </tr>
                    @else
                    <tr data-permit-group-row="{{ $groupKey }}" tabindex="0" role="button" aria-expanded="false" aria-controls="{{ $groupKey }}" class="cursor-pointer transition {{ $groupRowClass }}">
                        <td class="p-4">
                            <div class="flex items-start gap-3">
                                <button type="button" data-permit-group-toggle="{{ $groupKey }}" aria-expanded="false" aria-controls="{{ $groupKey }}" class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-lg font-black text-white transition hover:bg-indigo-700" title="نمایش مسیرهای این پرونده"><span>+</span></button>
                                <div>
                                    <strong class="block font-mono text-slate-800">{{ $parent->d_code }}</strong>
                                    <span class="mt-1 inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-black text-indigo-700">{{ number_format($children->count()) }} مسیر درخواست</span>
                                    <span class="mt-1 block text-[10px] text-slate-400">ردیف‌ها: {{ $children->map(fn ($child) => $child->d_code.'-'.$child->id)->implode('، ') }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-4"><strong>{{ $countries->count() > 1 ? $countries->count().' مقصد' : ($countries->first() ?: 'نامشخص') }}</strong><span class="mt-1 block text-xs text-slate-500">{{ $types->implode('، ') ?: 'نوع نامشخص' }}</span></td>
                        <td class="p-4"><strong class="block">{{ trim(($parent->first_name_fa ?? '').' '.($parent->last_name_fa ?? '')) ?: 'ثبت نشده' }}</strong><span class="mt-1 block text-[10px] text-slate-400">اسب: <b class="font-mono text-slate-600">{{ $parent->transit_horse ?: $parent->transit_plate ?: '—' }}</b></span><span class="block text-[10px] text-slate-400">یدک: <b class="font-mono text-slate-600">{{ $parent->transit_trailer ?: '—' }}</b></span></td>
                        <td class="p-4"><strong class="font-mono">{{ number_format($groupAmount) }}</strong><span class="mt-1 block text-[10px] text-slate-400">جمع مبلغ {{ $children->count() }} ردیف</span></td>
                        <td class="p-4">
    <span class="inline-flex min-h-[36px] max-w-full items-center justify-center whitespace-nowrap rounded-full border px-3 py-1 text-xs font-black leading-5 text-center {{ $groupStatusClass }}">{{ $groupStatusLabel }}</span>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach($statusSummary as $summary)
            <span class="inline-flex min-h-[36px] max-w-full items-center justify-center whitespace-nowrap rounded-full border px-3 py-1 text-xs font-black leading-5 text-center {{ $statusSummaryClasses[$summary->status] ?? 'border-slate-200 bg-slate-100 text-slate-700' }}">{{ number_format($summary->count) }} {{ $summary->label }}</span>
                                @endforeach
                            </div>
                            <span class="mt-2 block text-[10px] text-slate-500">خلاصه وضعیت {{ $children->count() }} مسیر</span>
                        </td>
                        <td class="max-w-[260px] p-4"><p class="line-clamp-2 text-xs leading-6 text-slate-600">{{ $lastEvent?->description ?: 'درخواست ثبت شده و منتظر اقدام است.' }}</p><time class="text-[10px] text-slate-400">{{ $lastEvent ? verta($lastEvent->created_at)->format('Y/m/d H:i') : verta($latestChild->created_at)->format('Y/m/d H:i') }}</time></td>
                        <td class="p-4">
                            <span class="inline-flex rounded-xl bg-indigo-50 px-4 py-2 text-xs font-black text-indigo-700">درخواست در {{ number_format($children->count()) }} مسیر ثبت شده</span>
                            <span class="mt-2 block text-[10px] font-bold text-slate-500">برای نمایش درخواست‌ها روی + یا ردیف کلیک کنید</span>
                        </td>
                    </tr>
                    <tr id="{{ $groupKey }}" class="hidden bg-slate-50">
                        <td colspan="7" class="p-4">
                            <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-inner">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <strong class="text-sm text-slate-800">درخواست‌های پرونده {{ $parent->d_code }}</strong>
                                    <span class="text-xs font-bold text-slate-500">وضعیت و جزئیات هر مسیر جداگانه پیگیری می‌شود.</span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[900px] text-right text-xs">
                                        <thead class="border-b border-slate-200 text-slate-500"><tr><th class="p-3">ردیف / شماره</th><th class="p-3">کشور / نوع</th><th class="p-3">راننده / ناوگان</th><th class="p-3">مبلغ</th><th class="p-3">وضعیت</th><th class="p-3">آخرین اقدام</th><th class="p-3"></th></tr></thead>
                                        <tbody class="divide-y divide-slate-100">
                                        @foreach($children as $child)
                                            @php
                                                $childEvent = \Illuminate\Support\Facades\DB::table('permit_item_events')
                                                    ->where('permit_request_item_id',$child->id)
                                                    ->where('permit_request_id',$child->permit_request_id)
                                                    ->where('created_at','>=',$child->created_at)
                                                    ->latest('id')->first();
                                                $pickupCompleted = !empty($child->pickup_completed_at);
                                            @endphp
                                            <tr class="hover:bg-emerald-50/40">
                                                <td class="p-3"><strong class="block font-mono text-slate-800">{{ $child->d_code }}-{{ $child->id }}</strong><span class="block text-[10px] text-slate-400">سریال: {{ $child->d_serial_number ?: 'هنوز تخصیص نیافته' }}</span></td>
                                                <td class="p-3"><strong>{{ $child->country_name ?: 'نامشخص' }}</strong><span class="mt-1 inline-flex rounded-lg bg-indigo-50 px-2 py-1 text-[10px] font-bold text-indigo-700">{{ $permitTypeLabels[$child->permit_type] ?? str_replace('_',' ',$child->permit_type) }}</span></td>
                                                <td class="p-3"><strong class="block">{{ trim(($child->first_name_fa ?? '').' '.($child->last_name_fa ?? '')) ?: 'ثبت نشده' }}</strong><span class="mt-1 block text-[10px] text-slate-400">اسب: {{ $child->transit_horse ?: $child->transit_plate ?: '—' }} | یدک: {{ $child->transit_trailer ?: '—' }}</span></td>
                                                <td class="p-3"><strong class="font-mono">{{ number_format($child->reserved_amount ?: $child->price) }}</strong><span class="mt-1 block text-[10px] text-slate-400">{{ ['reserved'=>'بلوکه‌شده','settled'=>'کسر قطعی','released'=>'آزادشده'][$child->financial_status] ?? $child->financial_status }}</span></td>
                                                <td class="p-3">@if($pickupCompleted && $child->item_status === 'issued')<span class="inline-flex rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-[10px] font-black text-emerald-700">تحویل پیک شرکت شد</span>@else<span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-black {{ $classes[$child->item_status] ?? 'border-indigo-200 bg-indigo-50 text-indigo-700' }}">{{ $labels[$child->item_status] ?? $child->item_status }}</span>@endif</td>
                                                <td class="max-w-[210px] p-3"><p class="line-clamp-2 text-[10px] leading-5 text-slate-600">{{ $childEvent?->description ?: 'درخواست ثبت شده و منتظر اقدام است.' }}</p><time class="text-[10px] text-slate-400">{{ $childEvent ? verta($childEvent->created_at)->format('Y/m/d H:i') : verta($child->created_at)->format('Y/m/d H:i') }}</time></td>
                                                <td class="p-3"><a href="{{ route('company.dozbalagh.item.show',$child->id) }}" class="inline-flex whitespace-nowrap rounded-lg bg-indigo-600 px-3 py-2 text-[10px] font-black text-white">مشاهده مسیر</a></td>
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
                    <tr><td colspan="7" class="p-16 text-center font-bold text-slate-400">دوزوله‌ای با این شرایط یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t p-4">{{ $items->links() }}</div>
    </section>
</div>
<button type="button" id="companyPermitListNotice" class="fixed bottom-5 left-5 z-50 hidden rounded-2xl bg-rose-600 px-5 py-3 text-sm font-black text-white shadow-2xl">وضعیت جدید پرونده دریافت شد — مشاهده</button>
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
            if (event.target.closest('button, a, input, select, textarea, label')) return;
            const toggle = row.querySelector('[data-permit-group-toggle]');
            if (toggle) toggleGroup(toggle);
        });

        row.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            const toggle = row.querySelector('[data-permit-group-toggle]');
            if (toggle) toggleGroup(toggle);
        });
    });

    const marker=document.getElementById('companyPermitListMarker');
    const notice=document.getElementById('companyPermitListNotice');
    let latest=Number(marker?.dataset.latestUpdate||0), dirty=false;
    document.querySelectorAll('form input, form select').forEach(field=>{
        field.addEventListener('input',()=>dirty=true,{once:true});
        field.addEventListener('change',()=>dirty=true,{once:true});
    });
    notice?.addEventListener('click',()=>location.reload());
    setInterval(async()=>{
        if(document.hidden)return;
        try{
            const response=await fetch(location.href,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'},cache:'no-store'});
            if(!response.ok)return;
            const doc=new DOMParser().parseFromString(await response.text(),'text/html');
            const next=Number(doc.getElementById('companyPermitListMarker')?.dataset.latestUpdate||0);
            if(next>latest){
                latest=next;
                if(dirty)notice?.classList.remove('hidden'); else location.reload();
            }
        }catch(_){}
    },10000);
})();
</script>
@endsection
