@extends('layouts.app')

@section('header_title', 'پرونده دوزوله '.$permit->d_code.'-'.$permit->id)

@section('content')
@php
    $labels=[
        'pending'=>'در انتظار بررسی','under_review'=>'در حال بررسی','approved'=>'تأیید اولیه',
        'cits_processing'=>'در حال ثبت CITS','cits_company_error'=>'خطای اطلاعات CITS',
        'cits_system_error'=>'اختلال CITS','cits_other_error'=>'خطای CITS',
        'ready_to_issue'=>'آماده چاپ','issued'=>'چاپ‌شده و آماده تحویل',
        'company_returned'=>'لاشه در مسیر انجمن','collected_for_renewal'=>'لاشه دریافت‌شده برای تمدید',
        'rejected'=>'رد کامل','archived'=>'بایگانی','returned_unused'=>'بدون استفاده','transferred_in'=>'دریافت‌شده از انتقال',
        'extended'=>'تمدیدشده','lost_reported'=>'مفقودی اعلام‌شده','lost'=>'مفقودی تأییدشده',
    ];
    $permitTypeLabels=[
        'bilateral'=>'دوجانبه',
        'transit'=>'ترانزیت',
        'bilateral_transit'=>'دوجانبه ترانزیت',
        'third_country_transit'=>'ترانزیت کشور ثالث',
        'third_country'=>'کشور ثالث',
    ];
    $citsLabels=[
        'not_started'=>'هنوز شروع نشده',
        'processing'=>'در حال بررسی',
        'recheck_requested'=>'در انتظار بررسی مجدد',
        'success'=>'ثبت موفق',
        'company_error'=>'خطای اطلاعات پرونده',
        'system_error'=>'اختلال سامانه راهداری',
        'other_error'=>'سایر خطاهای CITS',
    ];
    $financialLabels=['reserved'=>'مبلغ بلوکه‌شده','settled'=>'کسر قطعی','released'=>'مبلغ آزادشده'];
    $pendingPickupMission = $missions->where('direction','association_to_company')->where('status','pending')->first();
    $completedPickupMission = $missions->where('direction','association_to_company')->where('status','completed')->first();
@endphp
<div class="mx-auto max-w-7xl space-y-5">
    <section class="company-page-header rounded-3xl p-6 text-white shadow-xl">
        <a href="{{ route('dozbalagh.index') }}" class="company-header-back-link">← بازگشت به پرونده‌ها</a>
        <div class="mt-3 flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-2xl font-black">{{ $permit->d_code }}-{{ $permit->id }}</h1>
                <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-300">
                    <span>{{ $permit->country_name ?: 'کشور نامشخص' }}</span>
                    <span class="text-slate-600">/</span>
                    <span class="rounded-lg bg-white/10 px-2 py-1 font-bold text-sky-200">{{ $permitTypeLabels[$permit->permit_type] ?? str_replace('_',' ',$permit->permit_type) }}</span>
                    <span class="text-slate-600">/</span>
                    <span>سریال {{ $permit->d_serial_number ?: 'تخصیص نیافته' }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2"><span class="rounded-xl bg-sky-500/20 px-4 py-2 text-sm font-black text-sky-200">{{ $labels[$permit->item_status] ?? $permit->item_status }}</span><span class="rounded-xl bg-emerald-500/20 px-4 py-2 text-sm font-black text-emerald-200">{{ number_format($permit->reserved_amount ?: $permit->price) }} ریال</span></div>
        </div>
    </section>
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 font-bold text-rose-700">{{ $errors->first() }}</div>@endif
    @if($completedPickupMission)
        <section class="rounded-2xl border-2 border-emerald-300 bg-emerald-50 px-5 py-4 text-emerald-950 shadow-sm">
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                <div>
                    <h2 class="font-black">دوزوله به پیک شرکت تحویل شد</h2>
                    <p class="mt-1 text-sm text-emerald-800">پیک <strong>{{ $completedPickupMission->courier_name }}</strong> دوزوله را از انجمن دریافت کرده است.</p>
                </div>
                @if($completedPickupMission->completed_at)<time class="whitespace-nowrap rounded-lg bg-white px-3 py-2 text-xs font-bold text-emerald-700">{{ verta($completedPickupMission->completed_at)->format('Y/m/d H:i') }}</time>@endif
            </div>
        </section>
    @endif

    @if(in_array($permit->cits_status,['company_error','system_error','other_error']))
    <section class="rounded-2xl border-2 {{ $permit->cits_status === 'company_error' ? 'border-rose-300 bg-rose-50' : 'border-amber-300 bg-amber-50' }} p-5 shadow-sm">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-xl">!</span>
                    <div>
                        <h2 class="font-black {{ $permit->cits_status === 'company_error' ? 'text-rose-900' : 'text-amber-900' }}">
                            {{ $permit->cits_status === 'company_error' ? 'خطای اطلاعات پرونده در سامانه CITS' : 'خطا هنگام ثبت در سامانه CITS' }}
                        </h2>
                        @if($permit->cits_attempted_at)<p class="mt-1 text-xs text-slate-500">ثبت‌شده توسط انجمن در {{ verta($permit->cits_attempted_at)->format('Y/m/d H:i') }}</p>@endif
                    </div>
                </div>
                <div class="mt-4 rounded-xl bg-white/80 p-4">
                    <span class="text-xs font-bold text-slate-500">پیام انجمن</span>
                    <p class="mt-2 whitespace-pre-line text-sm font-bold leading-7 text-slate-800">{{ $permit->cits_message ?: 'برای این خطا پیام تکمیلی ثبت نشده است؛ لطفاً با انجمن پیگیری کنید.' }}</p>
                </div>
            </div>
            @if($permit->cits_error_image)
                <a href="{{ asset('storage/'.$permit->cits_error_image) }}" target="_blank" class="shrink-0 rounded-xl bg-slate-900 px-5 py-3 text-center text-xs font-black text-white">مشاهده تصویر خطا</a>
            @endif
        </div>
        <form method="POST" enctype="multipart/form-data" action="{{ route('company.dozbalagh.cits_response',$permit->id) }}" class="mt-5 grid gap-3 border-t border-current/10 pt-5 md:grid-cols-2">@csrf
            <div class="md:col-span-2">
                <label class="text-sm font-black text-slate-800">پاسخ شرکت به انجمن</label>
                <p class="mt-1 text-xs text-slate-500">شرح دهید چه اقدامی برای رفع خطا انجام شده است. بعد از ارسال، انجمن CITS را دوباره کنترل می‌کند.</p>
            </div>
            <textarea required name="message" rows="3" class="rounded-xl border-slate-200 md:col-span-2" placeholder="مثلاً: اطلاعات بررسی و اصلاح شد؛ لطفاً ثبت در سامانه راهداری مجدداً کنترل شود.">{{ old('message') }}</textarea>
            <label class="rounded-xl bg-white p-3 text-xs font-bold text-slate-600">تصویر یا PDF مستند اصلاح
                <input type="file" name="response_file" accept="image/*,.pdf" class="mt-2 block w-full">
            </label>
            <button class="rounded-xl bg-sky-700 px-5 py-3 font-black text-white">ارسال پاسخ و درخواست بررسی مجدد</button>
        </form>
    </section>
    @endif

    @if($permit->cits_status === 'recheck_requested')
    <section class="rounded-2xl border border-sky-200 bg-sky-50 p-5">
        <h2 class="font-black text-sky-900">پاسخ شما برای انجمن ارسال شده است</h2>
        <p class="mt-2 text-sm leading-7 text-sky-800">{{ $permit->company_cits_response }}</p>
        <p class="mt-2 text-xs text-slate-500">پرونده در انتظار بررسی مجدد سامانه CITS توسط انجمن است.</p>
    </section>
    @endif

    <div class="grid gap-5 xl:grid-cols-[1.15fr_.85fr]">
        <div class="space-y-5">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 bg-gradient-to-l from-slate-50 to-white px-5 py-4">
                    <div><h2 class="font-black">مشخصات این دوزوله</h2><p class="mt-1 text-xs text-slate-400">اطلاعات راننده، مجوز، کشنده و بارگیر همین پرونده</p></div>
                    <span class="rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">{{ $labels[$permit->item_status] ?? $permit->item_status }}</span>
                </div>
                <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="flex min-h-24 flex-col justify-center bg-white p-4"><span class="text-xs text-slate-400">راننده</span><strong class="mt-2 block">{{ trim(($permit->first_name_fa ?? '').' '.($permit->last_name_fa ?? '')) ?: '—' }}</strong><small class="mt-1 block font-mono text-slate-400">{{ $permit->driver_national_code ?: 'بدون کد ملی' }}</small></div>
                    <div class="flex min-h-24 flex-col justify-center bg-white p-4"><span class="text-xs text-slate-400">کشور و نوع مجوز</span><strong class="mt-2 block">{{ $permit->country_name ?: '—' }}</strong><small class="mt-1 block font-bold text-indigo-600">{{ $permitTypeLabels[$permit->permit_type] ?? str_replace('_',' ',$permit->permit_type) }}</small></div>
                    <div class="flex min-h-24 flex-col justify-center bg-white p-4"><span class="text-xs text-slate-400">شماره ترانزیت اسب (کشنده)</span><bdi dir="ltr" class="mt-2 block text-right font-mono text-base font-black text-slate-800">{{ $permit->transit_horse ?: $permit->transit_plate ?: 'ثبت نشده' }}</bdi></div>
                    <div class="flex min-h-24 flex-col justify-center bg-white p-4"><span class="text-xs text-slate-400">شماره ترانزیت یدک (بارگیر)</span><bdi dir="ltr" class="mt-2 block text-right font-mono text-base font-black text-slate-800">{{ $permit->transit_trailer ?: 'ثبت نشده' }}</bdi></div>
                    <div class="flex min-h-24 flex-col justify-center bg-white p-4"><span class="text-xs text-slate-400">مسیر بارگیری</span><strong class="mt-2 block">{{ $permit->loading_origin ?: '—' }} ← {{ $permit->loading_destination ?: '—' }}</strong></div>
                    <div class="flex min-h-24 flex-col justify-center bg-white p-4"><span class="text-xs text-slate-400">وضعیت CITS</span><strong class="mt-2 block text-sky-700">{{ $citsLabels[$permit->cits_status] ?? $permit->cits_status }}</strong></div>
                    <div class="flex min-h-24 flex-col justify-center bg-white p-4"><span class="text-xs text-slate-400">وضعیت مالی</span><strong class="mt-2 block text-emerald-700">{{ $financialLabels[$permit->financial_status] ?? $permit->financial_status }}</strong></div>
                    <div class="flex min-h-24 flex-col justify-center bg-white p-4"><span class="text-xs text-slate-400">مبلغ همین دوزوله</span><strong class="mt-2 flex items-baseline gap-1 text-base"><bdi dir="ltr" class="font-mono">{{ number_format($permit->reserved_amount ?: $permit->price) }}</bdi><small class="text-xs">ریال</small></strong></div>
                </div>
                @if($permit->d_serial_number)<div class="border-t bg-slate-50 px-5 py-3"><a href="{{ route('company.dozbalagh.copy',$permit->id) }}" target="_blank" class="inline-flex rounded-xl bg-sky-600 px-5 py-2.5 text-xs font-black text-white">مشاهده و چاپ نسخه شرکت</a></div>@endif
            </section>

            @if($permit->d_serial_number && in_array($permit->item_status, ['issued','transferred_in'], true) && !$completedPickupMission)
            <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                <h2 class="text-sm font-black text-indigo-900">معرفی پیک برای دریافت دوزوله از انجمن</h2>
                @if($pendingPickupMission)
                    <div class="mt-3 rounded-xl bg-white p-3 text-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2"><span>پیک: <strong>{{ $pendingPickupMission->courier_name }}</strong></span><span>موبایل: <strong dir="ltr">{{ $pendingPickupMission->courier_mobile }}</strong></span></div>
                        @if($pendingPickupMission->delivery_code)
                            <div class="mt-3 flex flex-col items-center justify-between gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 sm:flex-row">
                                <div class="text-center sm:text-right">
                                    <span class="block text-[11px] font-black text-amber-800">کد تحویل فعال پیک</span>
                                    <strong class="mt-1 block font-mono text-2xl tracking-[.25em] text-amber-950" dir="ltr">{{ $pendingPickupMission->delivery_code }}</strong>
                                </div>
                                <button type="button" data-copy-code="{{ $pendingPickupMission->delivery_code }}" data-copy-status="copyPickupCodeStatus" class="js-copy-code rounded-lg bg-slate-900 px-4 py-2 text-xs font-black text-white">کپی کد</button>
                            </div>
                            <p id="copyPickupCodeStatus" class="mt-1 hidden text-[11px] font-bold text-emerald-700">کد کپی شد.</p>
                        @endif
                        @if(!$pendingPickupMission->otp_sent_at)
                            <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">پیامک این کد ارسال نشده است؛ کد بالا را مستقیماً در اختیار پیک قرار دهید یا پس از شارژ پنل پیامک، ارسال مجدد را بزنید.</p>
                        @endif
                        <form method="POST" action="{{ route('company.dozbalagh.pickup_courier.resend',$pendingPickupMission->id) }}" class="mt-2 text-left">@csrf
                            <button class="rounded-lg bg-amber-600 px-4 py-2 text-xs font-black text-white">تولید و ارسال مجدد کد</button>
                        </form>
                    </div>
                @else
                <form method="POST" action="{{ route('company.dozbalagh.pickup_courier',$permit->id) }}" class="mt-4 grid gap-3 md:grid-cols-2">@csrf
                    <input required name="courier_name" class="rounded-xl border-indigo-200" placeholder="نام و نام خانوادگی پیک">
                    <input required name="courier_mobile" class="rounded-xl border-indigo-200" placeholder="موبایل پیک">
                    <button class="rounded-xl bg-indigo-700 py-3 font-black text-white md:col-span-2">ثبت پیک و ارسال کد تحویل</button>
                </form>
                @endif
            </section>
            @endif

            @if($permit->d_serial_number && in_array($permit->item_status, ['issued','transferred_in'], true) && $completedPickupMission)
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <h2 class="font-black text-emerald-900">تعیین تکلیف دوزوله صادرشده</h2>
                <form id="lashForm" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-2">
                    @csrf
                    <select name="disposition_type" class="rounded-xl border-emerald-200"><option value="return_archive">ثبت لاشه و پایان فرایند</option><option value="unused">برگشت بدون استفاده</option></select>
                    <div class="rounded-xl border border-emerald-200 bg-white p-3">
                        <input id="returnLashImage" required type="file" name="return_image" accept="image/jpeg,image/png" class="block w-full text-xs">
                        <p id="returnLashImageStatus" class="mt-2 text-[11px] text-slate-500">تصویر JPG یا PNG؛ فایل‌های بیشتر از ۵۰۰ کیلوبایت خودکار کم‌حجم می‌شوند.</p>
                        <div id="returnLashImagePreview" class="mt-2 hidden items-center justify-between gap-2 rounded-lg bg-emerald-50 p-2">
                            <span id="returnLashImageName" class="min-w-0 truncate text-[11px] font-bold text-emerald-800"></span>
                            <button id="openReturnLashPreview" type="button" class="shrink-0 rounded-lg bg-emerald-700 px-3 py-2 text-[11px] font-black text-white">پیش‌نمایش</button>
                        </div>
                    </div>
                    <input required name="courier_name" class="rounded-xl border-emerald-200" placeholder="نام پیک ارسال‌کننده لاشه">
                    <input required name="courier_mobile" class="rounded-xl border-emerald-200" placeholder="موبایل پیک">
                    <button id="lashSubmitButton" class="rounded-xl bg-emerald-700 py-3 font-black text-white disabled:cursor-not-allowed disabled:opacity-60 md:col-span-2">ثبت تصویر لاشه و مشخصات پیک</button>
                </form>
                <div class="mt-5 border-t border-emerald-200 pt-5">
                    <form id="lostForm" class="flex flex-col gap-2 sm:flex-row">@csrf<input name="lost_reason" class="min-w-0 flex-1 rounded-xl border-rose-200" placeholder="توضیح مفقودی"><button class="rounded-xl bg-rose-600 px-5 py-3 text-sm font-black text-white">اعلام مفقودی</button></form>
                </div>
            </section>
            @endif

            @if($permit->item_status === 'company_returned')
            <section class="rounded-2xl border-2 border-amber-300 bg-amber-50 p-5">
                <h2 class="font-black text-amber-950">در انتظار تحویل لاشه به انجمن</h2>
                <p class="mt-2 text-sm leading-7 text-amber-900">انتخاب شما ثبت شد و عملیات دیگر این دوزوله تا تعیین تکلیف انجمن قفل است. پیک باید کد پیامک‌شده را به اپراتور انجمن اعلام کند.</p>
                <div class="mt-4 grid gap-3 rounded-xl bg-white p-4 text-sm sm:grid-cols-3">
                    <span>نوع: <strong>{{ $permit->disposition_type === 'unused' ? 'برگشت بدون استفاده' : 'ثبت لاشه و پایان فرایند' }}</strong></span>
                    <span>پیک: <strong>{{ $permit->courier_name ?: '—' }}</strong></span>
                    <span>موبایل: <strong dir="ltr">{{ $permit->courier_mobile ?: '—' }}</strong></span>
                </div>
                @if($permit->courier_delivery_code)
                    <div class="mt-3 flex flex-col items-center justify-between gap-3 rounded-xl border border-amber-300 bg-white px-4 py-3 sm:flex-row">
                        <div class="text-center sm:text-right">
                            <span class="block text-[11px] font-black text-amber-700">کد تحویل لاشه به انجمن</span>
                            <strong class="mt-1 block font-mono text-2xl tracking-[.25em] text-amber-950" dir="ltr">{{ $permit->courier_delivery_code }}</strong>
                        </div>
                        <button type="button" data-copy-code="{{ $permit->courier_delivery_code }}" data-copy-status="copyReturnCodeStatus" class="js-copy-code rounded-lg bg-slate-900 px-4 py-2 text-xs font-black text-white">کپی کد</button>
                    </div>
                    <p id="copyReturnCodeStatus" class="mt-1 hidden text-[11px] font-bold text-emerald-700">کد کپی شد.</p>
                @endif
            </section>
            @elseif($permit->item_status === 'collected_for_renewal')
            <section class="rounded-2xl border-2 border-emerald-300 bg-emerald-50 p-5">
                <h2 class="font-black text-emerald-950">لاشه توسط انجمن تأیید شد؛ آماده ثبت تمدید</h2>
                <p class="mt-2 text-sm leading-7 text-emerald-900">از ثبت درخواست جدید، گزینه تمدید را انتخاب و شماره سریال همین دوزوله را وارد کنید.</p>
                <a href="{{ route('dozbalagh.create') }}" class="mt-4 inline-flex rounded-xl bg-emerald-700 px-6 py-3 text-sm font-black text-white">شروع درخواست تمدید</a>
            </section>
            @elseif($permit->item_status === 'cits_processing' && !empty($permit->courier_received_at))
            <section class="rounded-2xl border-2 border-sky-300 bg-sky-50 p-5">
                <h2 class="font-black text-sky-950">لاشه تحویل انجمن شد؛ در انتظار ثبت نتیجه CITS</h2>
                <p class="mt-2 text-sm leading-7 text-sky-900">انجمن در حال ثبت تحویل در سایت سازمان راهداری است. اگر CITS خطایی اعلام کند، پیام و مستند آن در همین پرونده نمایش داده می‌شود.</p>
            </section>
            @elseif(in_array($permit->item_status,['lost_reported','archived','returned_unused','extended','lost']))
            <section class="rounded-2xl border border-slate-200 bg-slate-100 p-5 text-sm font-bold text-slate-600">این دوزوله تعیین تکلیف شده و عملیات جدید شرکت روی آن بسته است.</section>
            @endif
        </div>

        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-black">گزارش کامل عملکرد</h2>
            <p class="mt-1 text-xs text-slate-400">همه تغییرات این دوزوله به‌ترتیب زمان ثبت شده‌اند.</p>
            <div class="mt-5 space-y-4 border-r-2 border-emerald-100 pr-5">
                @forelse($events as $event)
                    @php
                        $eventData = json_decode($event->after_data ?: '{}', true) ?: [];
                        $courierName = $eventData['courier_name'] ?? null;
                        $courierMobile = $eventData['courier_mobile'] ?? null;
                        if ($event->event_type === 'outbound_courier_completed') {
                            $courierName = $courierName ?: ($permit->pickup_completed_courier_name ?? null);
                            $courierMobile = $courierMobile ?: ($permit->pickup_completed_courier_mobile ?? null);
                        }
                        if (in_array($event->event_type, ['company_lash_submitted', 'lash_received', 'unused_return_received'], true)) {
                            $courierName = $courierName ?: ($permit->courier_name ?? null);
                            $courierMobile = $courierMobile ?: ($permit->courier_mobile ?? null);
                        }
                        $showCourierInfo = ($courierName || $courierMobile) && in_array($event->event_type, ['outbound_courier_assigned', 'outbound_courier_completed', 'company_lash_submitted', 'lash_received', 'unused_return_received'], true);
                    @endphp
                    <article class="relative rounded-xl bg-slate-50 p-4 before:absolute before:-right-[30px] before:top-4 before:h-4 before:w-4 before:rounded-full before:bg-emerald-500 before:ring-4 before:ring-white">
                        <div class="flex justify-between gap-2"><strong class="text-xs">{{ $event->event_type === 'unused_permit_transferred' ? 'دوزوله شماره '.$permit->d_serial_number.' انتقال یافت.' : ($event->description ?: $event->event_type) }}</strong><time class="whitespace-nowrap text-[9px] text-slate-400">{{ verta($event->created_at)->format('Y/m/d H:i') }}</time></div>
                        @if($event->from_status || $event->to_status)<p class="mt-2 text-[10px] text-slate-500">{{ $event->from_status ?: 'شروع' }} ← {{ $event->to_status ?: 'بدون تغییر وضعیت' }}</p>@endif
                        @if($showCourierInfo)
                            <div class="mt-3 grid gap-2 rounded-lg border border-emerald-100 bg-white px-3 py-2 text-[10px] font-bold text-slate-600 sm:grid-cols-2">
                                <span>نام پیک: <strong class="text-slate-900">{{ $courierName ?: 'ثبت نشده' }}</strong></span>
                                <span>موبایل پیک: <strong class="text-slate-900" dir="ltr">{{ $courierMobile ?: 'ثبت نشده' }}</strong></span>
                            </div>
                        @endif
                        <p class="mt-1 text-[9px] font-bold text-slate-400">{{ $event->actor_role }}</p>
                    </article>
                @empty<p class="text-sm text-slate-400">هنوز رویدادی ثبت نشده است.</p>@endforelse
            </div>
        </aside>
    </div>
</div>
<div id="returnLashPreviewModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/75 p-4" role="dialog" aria-modal="true" aria-labelledby="returnLashPreviewTitle">
    <div class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-4 py-3"><h3 id="returnLashPreviewTitle" class="text-sm font-black">پیش‌نمایش تصویر لاشه</h3><button id="closeReturnLashPreview" type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl">×</button></div>
        <div class="flex max-h-[75vh] items-center justify-center bg-slate-100 p-4"><img id="returnLashPreviewImage" class="max-h-[70vh] max-w-full rounded-lg object-contain" alt="پیش‌نمایش تصویر لاشه"></div>
        <p id="returnLashPreviewName" class="truncate border-t px-4 py-3 text-center text-xs text-slate-500"></p>
    </div>
</div>
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
@if($permit->d_serial_number)
<script>
const csrfToken=document.querySelector('meta[name="csrf-token"]').content;
document.querySelectorAll('.js-copy-code').forEach(button=>button.addEventListener('click',async function(){
    const code=this.dataset.copyCode||'';
    try{
        await navigator.clipboard.writeText(code);
    }catch(error){
        const input=document.createElement('textarea');
        input.value=code;input.style.position='fixed';input.style.opacity='0';
        document.body.appendChild(input);input.select();document.execCommand('copy');input.remove();
    }
    const status=document.getElementById(this.dataset.copyStatus||'');
    if(status){status.classList.remove('hidden');setTimeout(()=>status.classList.add('hidden'),2000);}
}));

const returnImageInput=document.getElementById('returnLashImage');
const returnImageStatus=document.getElementById('returnLashImageStatus');
const returnImagePreview=document.getElementById('returnLashImagePreview');
const returnImageName=document.getElementById('returnLashImageName');
const returnPreviewModal=document.getElementById('returnLashPreviewModal');
const returnPreviewImage=document.getElementById('returnLashPreviewImage');
const returnPreviewName=document.getElementById('returnLashPreviewName');
let returnImageObjectUrl=null;

function canvasToBlob(canvas,type,quality){return new Promise((resolve,reject)=>canvas.toBlob(blob=>blob?resolve(blob):reject(new Error('فشرده‌سازی تصویر انجام نشد.')),type,quality));}
function loadLocalImage(file){return new Promise((resolve,reject)=>{const image=new Image(),url=URL.createObjectURL(file);image.onload=()=>{URL.revokeObjectURL(url);resolve(image)};image.onerror=()=>{URL.revokeObjectURL(url);reject(new Error('تصویر قابل خواندن نیست.'))};image.src=url;});}
async function compressReturnImage(file){
    const image=await loadLocalImage(file),canvas=document.createElement('canvas'),context=canvas.getContext('2d');
    let scale=Math.min(1,2200/Math.max(image.naturalWidth,image.naturalHeight));
    let quality=file.type==='image/jpeg'?0.86:undefined,blob=file;
    for(let attempt=0;attempt<12;attempt++){
        canvas.width=Math.max(1,Math.round(image.naturalWidth*scale));canvas.height=Math.max(1,Math.round(image.naturalHeight*scale));
        context.clearRect(0,0,canvas.width,canvas.height);context.drawImage(image,0,0,canvas.width,canvas.height);
        blob=await canvasToBlob(canvas,file.type,quality);
        if(blob.size<=490*1024)break;
        if(file.type==='image/jpeg'&&quality>0.55)quality=Math.max(0.55,quality-0.1);else scale*=0.82;
    }
    if(blob.size>500*1024)throw new Error('حجم این تصویر تا ۵۰۰ کیلوبایت قابل کاهش نیست؛ تصویر دیگری انتخاب کنید.');
    return new File([blob],file.name,{type:file.type,lastModified:Date.now()});
}
function replaceReturnImage(file){const transfer=new DataTransfer();transfer.items.add(file);returnImageInput.files=transfer.files;}
returnImageInput?.addEventListener('change',async function(){
    let file=this.files?.[0];
    if(returnImageObjectUrl){URL.revokeObjectURL(returnImageObjectUrl);returnImageObjectUrl=null;}
    returnImagePreview?.classList.add('hidden');returnImagePreview?.classList.remove('flex');
    if(!file)return;
    if(!['image/jpeg','image/png'].includes(file.type)){this.value='';alert('فقط تصویر JPG یا PNG مجاز است.');return;}
    try{
        if(file.size>500*1024){
            const originalSize=Math.ceil(file.size/1024);returnImageStatus.textContent='در حال کاهش حجم تصویر...';returnImageStatus.className='mt-2 text-[11px] font-bold text-amber-600';
            file=await compressReturnImage(file);replaceReturnImage(file);
            returnImageStatus.textContent=`حجم تصویر از ${originalSize} به ${Math.ceil(file.size/1024)} کیلوبایت کاهش یافت.`;returnImageStatus.className='mt-2 text-[11px] font-bold text-emerald-700';
        }else{returnImageStatus.textContent=`حجم تصویر: ${Math.ceil(file.size/1024)} کیلوبایت`;returnImageStatus.className='mt-2 text-[11px] font-bold text-emerald-700';}
        returnImageObjectUrl=URL.createObjectURL(file);returnImageName.textContent=`${file.name} — ${Math.ceil(file.size/1024)} کیلوبایت`;returnImagePreview.classList.remove('hidden');returnImagePreview.classList.add('flex');
    }catch(error){this.value='';returnImageStatus.textContent=error.message;returnImageStatus.className='mt-2 text-[11px] font-bold text-rose-700';}
});
document.getElementById('openReturnLashPreview')?.addEventListener('click',()=>{if(!returnImageObjectUrl)return;returnPreviewImage.src=returnImageObjectUrl;returnPreviewName.textContent=returnImageName.textContent;returnPreviewModal.classList.remove('hidden');returnPreviewModal.classList.add('flex');document.body.classList.add('overflow-hidden');});
function closeReturnPreview(){returnPreviewModal?.classList.add('hidden');returnPreviewModal?.classList.remove('flex');if(returnPreviewImage)returnPreviewImage.removeAttribute('src');document.body.classList.remove('overflow-hidden');}
document.getElementById('closeReturnLashPreview')?.addEventListener('click',closeReturnPreview);
returnPreviewModal?.addEventListener('click',event=>{if(event.target===returnPreviewModal)closeReturnPreview();});
document.addEventListener('keydown',event=>{if(event.key==='Escape')closeReturnPreview();});
async function submitItemAction(form,url){
    const response=await fetch(url,{method:'POST',headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},body:new FormData(form)});
    const data=await response.json();
    if(!response.ok||!data.success){throw new Error(data.message||'عملیات انجام نشد');}
    return data;
}
document.getElementById('lashForm')?.addEventListener('submit',async function(e){
    e.preventDefault();
    const button=document.getElementById('lashSubmitButton');
    if(button?.disabled)return;
    if(button){button.disabled=true;button.textContent='در حال ثبت و تولید کد...';}
    try{
        const data=await submitItemAction(this,@json(route('company.dozbalagh.return_lash',$permit->id)));
        const deliveryCode=String(data.delivery_code||'');
        await Swal.fire({
            icon:data.sms_sent?'success':'warning',
            title:data.sms_sent?'درخواست تحویل لاشه ثبت شد':'درخواست ثبت شد؛ پیامک ارسال نشد',
            html:`
                <div style="font-family:inherit;text-align:center">
                    <p style="margin:0 0 12px;color:#475569;font-size:14px">${data.sms_sent?'کد تحویل برای پیک پیامک شد.':'این کد را مستقیماً در اختیار پیک قرار دهید.'}</p>
                    <div style="border:1px solid #fbbf24;background:#fffbeb;border-radius:14px;padding:14px">
                        <span style="display:block;color:#92400e;font-size:12px;font-weight:800">کد تحویل لاشه به انجمن</span>
                        <strong dir="ltr" style="display:block;margin-top:6px;color:#451a03;font-family:monospace;font-size:30px;letter-spacing:.25em">${deliveryCode}</strong>
                    </div>
                    <p style="margin:12px 0 0;color:#64748b;font-size:12px">این کد تا زمان تأیید انجمن داخل پرونده شرکت باقی می‌ماند.</p>
                </div>`,
            confirmButtonText:'متوجه شدم',
            confirmButtonColor:data.sms_sent?'#059669':'#d97706',
            allowOutsideClick:false
        });
        window.location.reload();
    }catch(error){
        await Swal.fire({icon:'error',title:'ثبت درخواست انجام نشد',text:error.message,confirmButtonText:'بستن',confirmButtonColor:'#e11d48'});
        if(button){button.disabled=false;button.textContent='ثبت تصویر لاشه و مشخصات پیک';}
    }
});
document.getElementById('lostForm')?.addEventListener('submit',async function(e){e.preventDefault();try{await submitItemAction(this,@json(route('company.dozbalagh.report_lost',$permit->id)));await Swal.fire({icon:'success',title:'مفقودی ثبت شد',text:'اعلام مفقودی برای بررسی به انجمن ارسال شد.',confirmButtonText:'متوجه شدم',confirmButtonColor:'#e11d48'});window.location.reload()}catch(error){await Swal.fire({icon:'error',title:'ثبت مفقودی انجام نشد',text:error.message,confirmButtonText:'بستن',confirmButtonColor:'#e11d48'})}});
</script>
@endif
@include('partials.permit-live-refresh')
@endsection
