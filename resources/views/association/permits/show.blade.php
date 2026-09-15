@extends('layouts.admin')

@section('header_title', 'پرونده دوزوله '.$permit->d_code.'-'.$permit->id)

@section('content')
@php
    $statusLabels=['pending'=>'در انتظار بررسی','under_review'=>'در حال بررسی','approved'=>'تأیید انجمن','cits_processing'=>'در حال ثبت در CITS','cits_company_error'=>'خطای اطلاعات پرونده','cits_system_error'=>'اختلال CITS','cits_other_error'=>'خطای CITS','ready_to_issue'=>'آماده چاپ و صدور','issued'=>'چاپ‌شده و آماده تحویل','company_returned'=>'لاشه در راه انجمن','collected_for_renewal'=>'لاشه دریافت شد','rejected'=>'رد کامل','archived'=>'بایگانی','lost'=>'مفقودی','lost_reported'=>'مفقودی اعلام‌شده','returned_unused'=>'بدون استفاده','extended'=>'تمدیدشده'];
    $isUnusedReturn=$permit->disposition_type === 'unused';
    if($permit->item_status === 'company_returned' && $isUnusedReturn) $statusLabels['company_returned']='دوزوله استفاده‌نشده در راه انجمن';
    $citsLabels=['not_started'=>'هنوز شروع نشده','processing'=>'در حال اقدام','success'=>'ثبت موفق','company_error'=>'خطای اطلاعات شرکت','system_error'=>'اختلال سامانه راهداری','other_error'=>'سایر خطاها'];
    $permitTypeLabels=['bilateral'=>'دو جانبه','transit'=>'ترانزیت','bilateral_transit'=>'دو جانبه ترانزیت','third_country'=>'کشور ثالث','third_country_transit'=>'ترانزیت کشور ثالث'];
    $eventLabels=['request_submitted'=>'ثبت درخواست','item_approved'=>'تأیید انجمن','item_rejected'=>'رد کامل','association_edit'=>'ویرایش انجمن','cits_result'=>'نتیجه CITS','permit_issued'=>'چاپ و صدور','permit_serial_corrected'=>'اصلاح شماره دوزوله','disposition_changed'=>'تغییر تعیین تکلیف','outbound_courier_assigned'=>'معرفی پیک دریافت','outbound_courier_completed'=>'تحویل به پیک','lash_received'=>'دریافت لاشه از پیک','unused_permit_transferred'=>'انتقال دوزوله استفاده‌نشده','unused_return_received'=>'دریافت دوزوله استفاده‌نشده'];
    $eventLabels['company_cits_response']='پاسخ شرکت به خطای CITS';
    $citsConversation=$events->whereIn('event_type',['cits_result','company_cits_response'])->sortByDesc('id');
    $isReturnCits=!empty($permit->courier_received_at) && !empty($permit->company_return_image);
    $pickupCompleted=$permit->item_status === 'issued' && $missions->contains(fn($mission)=>$mission->direction === 'association_to_company' && $mission->status === 'completed');
    $pickupPending=$missions->contains(fn($mission)=>$mission->direction === 'association_to_company' && $mission->status === 'pending');
    $canCorrectSerial=!empty($permit->d_serial_number)
        && ($permit->item_status ?? null) === 'issued'
        && ($permit->request_type ?? null) !== 'renewal'
        && empty($permit->company_return_submitted_at)
        && empty($permit->courier_received_at)
        && empty($permit->cits_attempted_at)
        && empty($permit->disposition_type)
        && empty($permit->closed_at)
        && !$pickupCompleted;
    $step=match(true){
        in_array($permit->item_status,['pending','under_review'])=>1,
        $permit->item_status==='ready_to_issue'=>2,
        in_array($permit->item_status,['issued','company_returned','lost_reported'])=>3,
        default=>4,
    };
    $nextAction=match($step){
        1=>'مدارک و سوابق ناوگان را کنترل کنید؛ سپس فقط همین دوزوله را تأیید یا رد کنید.',
        2=>'شماره سریال و مدت اعتبار را وارد کنید تا مبلغ قطعی کسر و دوزوله صادر شود.',
        3=>$isUnusedReturn ? 'دوزوله به‌عنوان استفاده‌نشده برگشت داده شده است؛ کد پیک را کنترل و دریافت آن را تأیید کنید.' : 'دوزوله صادر شده است؛ تحویل به پیک یا تعیین تکلیف لاشه را در همین پرونده پیگیری کنید.',
        4=>$isReturnCits
            ? 'لاشه تحویل شده است؛ نتیجه ثبت تحویل در CITS را وارد کنید. بایگانی فقط پس از موفقیت CITS انجام می‌شود.'
            : 'فرآیند اصلی این پرونده پایان یافته است. جزئیات کامل در تاریخچه پایین صفحه باقی می‌ماند.',
        default=>'فرآیند اصلی این پرونده پایان یافته است.',
    };
    $workflowSteps=[
        ['title'=>'کنترل و تصمیم','caption'=>'بررسی اطلاعات و مدارک'],
        ['title'=>'چاپ و صدور','caption'=>'تخصیص سریال و چاپ'],
        ['title'=>$isUnusedReturn ? 'تحویل برگشتی' : 'تحویل و لاشه','caption'=>$isUnusedReturn ? 'دریافت بدون ورود به CITS' : 'تحویل و تعیین تکلیف'],
        ['title'=>'CITS و بایگانی','caption'=>'کنترل نهایی و پایان پرونده'],
    ];
@endphp
<style>
    .association-header-back-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.75rem;
        padding: .65rem 1rem;
        border: 1px solid rgba(255, 255, 255, .3);
        border-radius: .8rem;
        background: rgba(255, 255, 255, .13);
        color: #d1fae5 !important;
        font-size: .9375rem !important;
        font-weight: 900 !important;
        line-height: 1.5;
        text-decoration: none !important;
        white-space: nowrap;
        box-shadow: 0 3px 8px rgba(2, 6, 23, .12);
        transition: background-color .2s ease, border-color .2s ease, color .2s ease, transform .2s ease;
    }
    .association-header-back-link:hover {
        border-color: rgba(255, 255, 255, .55);
        background: rgba(255, 255, 255, .23);
        color: #fff !important;
        transform: translateY(-1px);
    }
    .association-header-back-link:focus-visible {
        outline: 3px solid rgba(167, 243, 208, .95);
        outline-offset: 2px;
    }
    @media (max-width: 640px) {
        .association-header-back-link { min-height: 2.6rem; padding: .55rem .85rem; font-size: .875rem !important; }
    }
</style>
<div class="space-y-4">
    <div class="relative flex flex-col justify-between gap-4 overflow-hidden rounded-3xl bg-gradient-to-l from-emerald-900 to-emerald-600 p-6 text-white shadow-xl lg:flex-row lg:items-center">
        <div class="pointer-events-none absolute left-0 top-0 h-36 w-36 rounded-full bg-emerald-300/10 blur-2xl"></div>
        <div class="relative"><a href="{{ route('association.permits.index') }}" class="association-header-back-link">← بازگشت به کارتابل</a><p class="mt-2 text-base font-black leading-7 text-white">{{ $permit->company_name }} — {{ $permit->country_name }} — {{ $permitTypeLabels[$permit->permit_type] ?? ($permit->permit_type ?: 'مجوز ثبت نشده') }}</p><h1 class="mt-1 text-sm font-bold text-emerald-100">{{ $permit->d_code }} / ردیف {{ $permit->id }}</h1></div>
        <div class="relative flex flex-wrap gap-2"><span class="rounded-xl bg-white/15 px-4 py-2 text-sm font-black">{{ $pickupCompleted ? 'تحویل پیک شرکت شد' : ($statusLabels[$permit->item_status] ?? $permit->item_status) }}</span><span class="rounded-xl bg-emerald-950/35 px-4 py-2 text-sm font-black text-emerald-50">{{ number_format($permit->reserved_amount ?: $permit->price) }} ریال / {{ $permit->financial_status }}</span></div>
    </div>
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 font-bold text-rose-700">{{ $errors->first() }}</div>@endif
    @if($permit->item_status === 'rejected')
        <section class="rounded-2xl border-2 border-rose-200 bg-gradient-to-l from-rose-50 to-white p-5 shadow-sm">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                <div><h2 class="font-black text-rose-950">این درخواست رد شده است</h2><p class="mt-1 text-xs leading-6 text-rose-800">درخواست از کارتابل «در حال اقدام» خارج شده و در بخش «رد شده‌ها» قابل پیگیری است. این تصمیم در تاریخچه دائمی پرونده نیز ثبت شده است.</p></div>
                <span class="w-fit rounded-full bg-rose-600 px-4 py-2 text-xs font-black text-white">رد کامل</span>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-[1fr_auto]">
                <div class="rounded-xl border border-rose-200 bg-white p-4"><span class="block text-xs font-black text-rose-900">دلیل رد</span><p class="mt-2 text-sm leading-7 text-slate-700">{{ $permit->rejection_reason ?: 'دلیل رد در رویداد ثبت‌شده در تاریخچه پرونده قابل مشاهده است.' }}</p></div>
                <div class="rounded-xl border border-rose-100 bg-rose-100/60 p-4 text-xs text-rose-900"><span class="block text-rose-700">تاریخ رد</span><strong class="mt-2 block">{{ verta($permit->rejected_at ?: $permit->updated_at)->format('Y/m/d H:i') }}</strong></div>
            </div>
        </section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-sky-200 bg-gradient-to-l from-sky-50 to-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-sky-100 px-4 py-3 md:flex-row md:items-center">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-lg font-black text-white shadow-sm">{{ $step }}</span>
                <div><div class="flex items-center gap-2"><h2 class="font-black text-sky-950">اقدام بعدی اپراتور</h2><span class="rounded-full bg-sky-100 px-2 py-1 text-[10px] font-black text-sky-700">مرحله جاری</span></div><p class="mt-1 text-sm leading-6 text-sky-800">{{ $nextAction }}</p></div>
            </div>
        </div>
        <div class="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($workflowSteps as $number=>$workflowStep)
                @php $stageNumber=$number+1; $isCurrent=$step===$stageNumber; $isDone=$step>$stageNumber; @endphp
                <div class="flex items-center gap-3 rounded-xl border px-3 py-3 text-right {{ $isCurrent ? 'border-sky-500 bg-sky-600 text-white shadow-md' : ($isDone ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white text-slate-500') }}">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full font-black {{ $isCurrent ? 'bg-white text-sky-700' : ($isDone ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500') }}">{{ $isDone ? '✓' : $stageNumber }}</span>
                    <div class="min-w-0"><strong class="block text-xs">{{ $workflowStep['title'] }}</strong><small class="mt-1 block truncate text-[10px] {{ $isCurrent ? 'text-sky-100' : ($isDone ? 'text-emerald-700' : 'text-slate-400') }}">{{ $workflowStep['caption'] }}</small></div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="space-y-4">
        <div class="grid items-start gap-4 xl:grid-cols-2">
            <section id="controlDecisionSection" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="flex flex-col justify-between gap-3 border-b border-slate-100 bg-gradient-to-l from-slate-50 to-white px-5 py-4 sm:flex-row sm:items-center"><div><h2 class="font-black text-slate-900">اطلاعات پرونده</h2><p class="mt-1 text-xs text-slate-500">مشخصات راننده، کشنده، بارگیر و وضعیت جاری این دوزوله</p></div><button type="button" id="fleetHistoryBtn" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-indigo-700"><span>◷</span> سوابق ناوگان در همه شرکت‌ها</button></div>
                <div class="grid bg-slate-100 text-sm sm:grid-cols-2 xl:grid-cols-4">
                    <div class="m-px rounded-xl bg-white p-4"><span class="text-xs text-slate-400">راننده</span><strong class="mt-1 block text-slate-900">{{ trim($permit->first_name_fa.' '.$permit->last_name_fa) }}</strong><small class="mt-1 block font-mono text-slate-400">{{ $permit->driver_national_code }}</small></div>
                    <div class="m-px rounded-xl bg-white p-4"><span class="text-xs text-slate-400">شماره ترانزیت اسب (کشنده)</span><strong class="mt-1 block cursor-pointer select-all font-mono text-base text-slate-900 transition-colors hover:text-slate-700" title="کلیک برای کپی" data-copy-dynamic>{{ $permit->transit_horse ?: 'ثبت نشده' }}</strong></div>
                    <div class="m-px rounded-xl bg-white p-4"><span class="text-xs text-slate-400">شماره ترانزیت یدک (بارگیر)</span><strong class="mt-1 block cursor-pointer select-all font-mono text-base text-slate-900 transition-colors hover:text-slate-700" title="کلیک برای کپی" data-copy-dynamic>{{ $permit->transit_trailer ?: 'ثبت نشده' }}</strong></div>
                    <div class="m-px rounded-xl bg-white p-4"><span class="text-xs text-slate-400">مسیر بارگیری</span><strong class="mt-1 block text-slate-900">{{ $permit->loading_origin }} ← {{ $permit->loading_destination }}</strong></div>
                    <div class="m-px rounded-xl bg-white p-4"><span class="text-xs text-slate-400">شماره درخواست CITS</span><strong class="mt-1 block cursor-pointer select-all font-mono text-slate-900 transition-colors hover:text-slate-700" title="کلیک برای کپی" data-copy-dynamic>{{ $permit->cits_code ?: 'ثبت نشده' }}</strong></div>
                    <div class="m-px rounded-xl bg-white p-4"><span class="text-xs text-slate-400">وضعیت CITS</span><strong class="mt-1 inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs text-sky-700">{{ $citsLabels[$permit->cits_status] ?? $permit->cits_status }}</strong></div>
                    <div class="m-px rounded-xl bg-white p-4"><span class="text-xs text-slate-400">سریال دوزوله</span><strong class="mt-1 block font-mono text-base text-slate-900">{{ $permit->d_serial_number ?: 'تخصیص نیافته' }}</strong></div>
                    <div class="m-px rounded-xl bg-white p-4"><span class="text-xs text-slate-400">وضعیت پرونده</span><strong class="mt-1 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs text-emerald-700">{{ $statusLabels[$permit->item_status] ?? $permit->item_status }}</strong></div>
                </div>
            </section>

            @if(in_array($permit->item_status,['pending','under_review']))
            <section class="rounded-2xl border border-slate-200 bg-white p-4 xl:col-span-2">
                <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 md:flex-row md:items-center">
                    <div><h2 class="font-black">تصمیم مرحله کنترل</h2><p class="mt-1 text-xs text-slate-500">تصمیم فقط روی همین دوزوله اعمال می‌شود و در تاریخچه دائمی می‌ماند.</p></div>
                    <span class="w-fit rounded-full bg-sky-50 px-3 py-1 text-[11px] font-black text-sky-700">مرحله ۱ از ۴</span>
                </div>
                <div class="mt-4 grid items-start gap-3 lg:grid-cols-2">
                    <form method="POST" action="{{ route('association.permits.decision',$permit->id) }}" class="flex min-h-[150px] flex-col justify-between rounded-2xl border border-emerald-200 bg-gradient-to-l from-emerald-50 to-white p-4 shadow-sm">@csrf
                        <input type="hidden" name="decision" value="approved">
                        <div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-600 font-black text-white">✓</span><div><h3 class="font-black text-emerald-900">مدارک صحیح است</h3><p class="mt-1 text-xs text-emerald-700">پس از تأیید، این دوزوله مستقیماً آماده تخصیص سریال و صدور می‌شود.</p></div></div>
                        <button class="mt-4 w-full rounded-xl bg-emerald-600 py-3 font-black text-white shadow-sm hover:bg-emerald-700">تأیید و ارسال به صدور</button>
                    </form>
                    <details class="group min-h-[150px] rounded-2xl border border-rose-200 bg-gradient-to-l from-rose-50 to-white p-4 shadow-sm">
                        <summary class="flex min-h-[116px] cursor-pointer list-none flex-col justify-between gap-4">
                            <div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100 font-black text-rose-700">×</span><div><h3 class="font-black text-rose-900">رد کامل دوزوله</h3><p class="mt-1 text-xs text-rose-700">مبلغ همین ردیف آزاد و به کیف پول شرکت برمی‌گردد.</p></div></div>
                            <span class="w-full rounded-xl border border-rose-200 bg-white px-4 py-3 text-center text-xs font-black text-rose-700 shadow-sm group-open:hidden">ثبت دلیل رد کامل</span>
                        </summary>
                        <form method="POST" action="{{ route('association.permits.decision',$permit->id) }}" class="mt-4 border-t border-rose-200 pt-4">@csrf
                            <input type="hidden" name="decision" value="rejected">
                            <label class="text-xs font-black text-rose-900">دلیل رد کامل برای شرکت</label>
                            <textarea required name="reason" rows="2" class="mt-2 w-full rounded-xl border-rose-200 bg-white" placeholder="دلیل دقیق رد را بنویسید؛ این متن برای شرکت نمایش داده می‌شود."></textarea>
                            <button class="mt-3 w-full rounded-xl bg-rose-600 py-3 font-black text-white hover:bg-rose-700">تأیید رد و آزادسازی مبلغ</button>
                        </form>
                    </details>
                </div>
            </section>
            @endif

            <details id="associationCorrectionSection" class="group rounded-2xl border-2 border-indigo-200 bg-white p-4 xl:col-span-2">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                    <span><strong class="block text-indigo-950">کنترل و اصلاح اطلاعات ثبت‌شده شرکت</strong><small class="mt-1 block text-slate-500">تمام اطلاعات ثبت اولیه همین ردیف را کنترل کنید؛ هر تغییر همراه دلیل، نسخه قبل و بعد و نام اپراتور در تاریخچه ثبت می‌شود.</small></span>
                    <span class="shrink-0 rounded-lg bg-indigo-50 px-3 py-2 text-xs font-black text-indigo-700 group-open:hidden">نمایش اطلاعات و اصلاح</span>
                    <span class="hidden shrink-0 rounded-lg bg-indigo-700 px-3 py-2 text-xs font-black text-white group-open:inline-block">بستن بخش کنترل</span>
                </summary>
                <form method="POST" enctype="multipart/form-data" action="{{ route('association.permits.update',$permit->id) }}" class="mt-4 space-y-4 border-t border-indigo-100 pt-4">@csrf @method('PUT')
                    @php
                        $correctionControl = 'mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-bold text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100';
                    @endphp
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="text-xs font-bold text-slate-700">راننده شرکت
                            <select name="driver_id" class="{{ $correctionControl }}">
                                <option value="">انتخاب نشده</option>
                                @foreach($companyDrivers as $driver)<option value="{{ $driver->id }}" @selected($permit->driver_id == $driver->id)>{{ trim($driver->first_name_fa.' '.$driver->last_name_fa) }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-xs font-bold text-slate-700">شماره پلاک ناوگان
                            <select id="associationCorrectionFleet" name="fleet_id" class="{{ $correctionControl }}">
                                @foreach($companyFleets as $fleet)<option value="{{ $fleet->id }}" data-horse="{{ $fleet->transit_horse ?: '' }}" data-trailer="{{ $fleet->transit_trailer ?: '' }}" @selected($permit->fleet_id == $fleet->id)>{{ $fleet->transit_plate ?: 'پلاک ثبت‌نشده' }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-xs font-bold text-slate-700">شماره ترانزیت اسب (کشنده)
                            <input id="associationCorrectionHorse" type="text" value="{{ $permit->transit_horse ?: 'ثبت نشده' }}" readonly class="{{ $correctionControl }} cursor-not-allowed bg-slate-50 font-mono">
                        </label>
                        <label class="text-xs font-bold text-slate-700">شماره ترانزیت یدک (بارگیر)
                            <input id="associationCorrectionTrailer" type="text" value="{{ $permit->transit_trailer ?: 'ثبت نشده' }}" readonly class="{{ $correctionControl }} cursor-not-allowed bg-slate-50 font-mono">
                        </label>
                        <label class="text-xs font-bold text-slate-600">کشور مقصد
                            <select name="country_id" class="{{ $correctionControl }}">@foreach($countries as $country)<option value="{{ $country->id }}" @selected($permit->country_id == $country->id)>{{ $country->name }}</option>@endforeach</select>
                        </label>
                        <label class="text-xs font-bold text-slate-600">نوع مجوز
                            <select name="permit_type" class="{{ $correctionControl }}">
                                @foreach(['bilateral'=>'دوجانبه','transit'=>'ترانزیت','bilateral_transit'=>'دوجانبه ترانزیت','third_country'=>'کشور ثالث','third_country_transit'=>'ترانزیت کشور ثالث'] as $value=>$label)<option value="{{ $value }}" @selected($permit->permit_type === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-xs font-bold text-slate-600">نوع عملیات/بار<input name="operation_type" value="{{ $permit->operation_type }}" class="{{ $correctionControl }}"></label>
                        <label class="text-xs font-bold text-slate-600">مبدأ بارگیری<input name="loading_origin" value="{{ $permit->loading_origin }}" class="{{ $correctionControl }}"></label>
                        <label class="text-xs font-bold text-slate-600">مقصد بارگیری<input name="loading_destination" value="{{ $permit->loading_destination }}" class="{{ $correctionControl }}"></label>
                        <label class="text-xs font-bold text-slate-600">کد درخواست سامانه جامع (CITS)<input name="cits_code" value="{{ $permit->cits_code }}" class="{{ $correctionControl }} font-mono"></label>
                        <label class="text-xs font-bold text-slate-600">کد سفر (شروع با J)<input name="trip_code" value="{{ $permit->trip_code }}" class="{{ $correctionControl }} font-mono"></label>
                        <label class="text-xs font-bold text-slate-600">شماره فیش سازمان<input name="receipt_code" value="{{ $permit->receipt_code }}" class="{{ $correctionControl }} border-rose-300 font-mono text-slate-900 focus:border-rose-500 focus:ring-rose-100"></label>
                        <label class="text-xs font-bold text-slate-600">مبلغ واریزی (ریال)<input type="number" min="0" name="receipt_amount" value="{{ $permit->receipt_amount }}" class="{{ $correctionControl }} font-mono"></label>
                        <label class="text-xs font-bold text-slate-600">تاریخ بارگیری CMR<input type="date" name="cmr_date" value="{{ $permit->cmr_date }}" class="{{ $correctionControl }}"></label>
                        <label class="text-xs font-bold text-slate-600">شماره کارنه تیر<input name="tir_carnet_number" value="{{ $permit->tir_carnet_number }}" class="{{ $correctionControl }}"></label>
                        <label class="text-xs font-bold text-slate-600">تاریخ کارنه تیر<input type="date" name="tir_carnet_date" value="{{ $permit->tir_carnet_date }}" class="{{ $correctionControl }}"></label>
                    </div>
                    <div class="grid gap-3 rounded-2xl bg-slate-50 p-3 md:grid-cols-2 xl:grid-cols-4">
                        @foreach(['receipt_file'=>'تصویر فیش','cmr_file'=>'تصویر CMR','tir_file'=>'تصویر کارنه تیر','declaration_file'=>'تصویر اظهارنامه'] as $field=>$label)
                            <label class="rounded-xl border bg-white p-3 text-xs font-bold text-slate-600">{{ $label }}
                                @if($permit->{$field})<button type="button" data-current-file-preview="{{ asset('storage/'.$permit->{$field}) }}" data-current-file-title="{{ $label }}" class="mr-2 text-sky-700 underline">مشاهده فایل فعلی</button>@else<span class="mr-2 text-amber-600">ثبت نشده</span>@endif
                                <input type="file" name="{{ $field }}" accept="image/*" class="mt-2 block w-full text-[11px]">
                            </label>
                        @endforeach
                    </div>
                    <div class="grid items-end gap-3 md:grid-cols-[1fr_auto]">
                        <label class="text-xs font-black text-slate-700">دلیل اصلاح (اجباری و قابل مشاهده در تاریخچه)<textarea required name="reason" rows="2" class="mt-1 block w-full rounded-xl border-indigo-200 text-sm" placeholder="دقیقاً بنویسید چه چیزی و به چه دلیل اصلاح شد."></textarea></label>
                        <button class="rounded-xl bg-indigo-700 px-8 py-3 font-black text-white shadow-sm hover:bg-indigo-800">ذخیره اصلاحات و ثبت تاریخچه</button>
                    </div>
                </form>
                <script>
                    (() => {
                        const fleetSelect = document.getElementById('associationCorrectionFleet');
                        const horseField = document.getElementById('associationCorrectionHorse');
                        const trailerField = document.getElementById('associationCorrectionTrailer');
                        if (!fleetSelect || !horseField || !trailerField) return;

                        const syncFleetDetails = () => {
                            const option = fleetSelect.options[fleetSelect.selectedIndex];
                            horseField.value = option?.dataset.horse || 'ثبت نشده';
                            trailerField.value = option?.dataset.trailer || 'ثبت نشده';
                        };

                        fleetSelect.addEventListener('change', syncFleetDetails);
                        syncFleetDetails();
                    })();
                </script>
            </details>

            @if($citsConversation->isNotEmpty())
            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-3">
                    <div><h2 class="font-black">گفتگوی CITS با شرکت</h2><p class="mt-1 text-xs text-slate-500">تمام پیام‌ها و مستندات این رفت‌وبرگشت به‌ترتیب زمان نگهداری می‌شوند.</p></div>
                    <span class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-600">{{ $citsConversation->count() }} پیام</span>
                </div>
                <div class="mt-4 max-h-[430px] space-y-3 overflow-y-auto pl-1">
                    @foreach($citsConversation as $message)
                        @php
                            $isCompany=$message->event_type === 'company_cits_response';
                            $messageData=json_decode($message->after_data ?: '{}', true) ?: [];
                            $attachment=$isCompany ? ($messageData['response_file'] ?? null) : ($messageData['error_image'] ?? null);
                        @endphp
                        <article class="flex {{ $isCompany ? 'justify-start' : 'justify-end' }}">
                            <div class="w-full max-w-3xl rounded-2xl border p-4 {{ $isCompany ? 'border-emerald-200 bg-emerald-50' : 'border-orange-200 bg-orange-50' }}">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <strong class="text-sm {{ $isCompany ? 'text-emerald-900' : 'text-orange-900' }}">{{ $isCompany ? 'شرکت' : 'انجمن' }}</strong>
                                    <time class="text-[11px] text-slate-500">{{ verta($message->created_at)->format('Y/m/d H:i') }}</time>
                                </div>
                                <p class="mt-3 whitespace-pre-line text-sm font-bold leading-7 text-slate-800">{{ $message->description ?: 'پیام متنی ثبت نشده است.' }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                    <span>{{ $message->actor_name ?: ($isCompany ? 'کاربر شرکت' : 'کاربر انجمن') }}</span>
                                    @if($attachment)<a target="_blank" href="{{ asset('storage/'.$attachment) }}" class="rounded-lg bg-white px-3 py-2 font-black text-sky-700 shadow-sm">مشاهده فایل پیوست</a>@endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
            @endif
            @if($isReturnCits && in_array($permit->item_status,['cits_processing','cits_company_error','cits_system_error','cits_other_error']))
            <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                <h2 class="font-black text-indigo-950">ثبت نتیجه تحویل در CITS</h2>
                <p class="mt-2 text-xs leading-6 text-indigo-800">اگر سایت سازمان اعلام کرد اطلاعات ورود ثبت نشده یا خطای دیگری وجود دارد، همان خطا را برای شرکت ارسال کنید. هیچ کد مرزی در این سامانه وارد نمی‌شود.</p>
                @if($permit->company_cits_response)<p class="mt-2 rounded-xl bg-white p-3 text-xs leading-6 text-slate-600">پاسخ جدید شرکت در ستون گفتگو ثبت شده است. پس از کنترل سایت راهداری نتیجه را وارد کنید.</p>@endif
                <form id="citsResultForm" method="POST" enctype="multipart/form-data" action="{{ route('association.permits.cits',$permit->id) }}" class="mt-4 grid items-start gap-3 md:grid-cols-2">@csrf
                    <div class="rounded-xl border border-indigo-100 bg-white p-3">
                        <label for="citsResultStatus" class="block text-xs font-black text-slate-700">نتیجه کنترل در سامانه CITS</label>
                        <select id="citsResultStatus" name="status" class="mt-2 block h-11 w-full rounded-xl border-slate-200 py-2 text-sm"><option value="processing">در حال اقدام</option><option value="success">ثبت موفق</option><option value="company_error">خطای اطلاعات پرونده</option><option value="system_error">اختلال سامانه راهداری</option><option value="other_error">سایر خطاها</option></select>
                    </div>
                    <div class="rounded-xl border border-indigo-100 bg-white p-3">
                        <label for="citsErrorImageInput" class="block text-xs font-black text-slate-700">تصویر خطای سامانه (اختیاری)</label>
                        <input id="citsErrorImageInput" type="file" name="error_image" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-xl border border-slate-200 p-2 text-xs">
                        <p id="citsImageStatus" class="mt-2 text-[11px] leading-5 text-slate-500">تصاویر پرحجم پیش از ارسال به‌صورت خودکار کم‌حجم می‌شوند.</p>
                        <div id="citsImagePreviewWrap" class="mt-3 hidden items-center justify-between gap-2 rounded-xl bg-slate-50 p-2">
                            <span id="citsImagePreviewName" class="min-w-0 truncate text-[11px] font-bold text-slate-600"></span>
                            <div class="flex shrink-0 gap-2">
                                <button id="citsImagePreviewButton" type="button" class="rounded-lg bg-indigo-100 px-3 py-1.5 text-[11px] font-black text-indigo-700">پیش‌نمایش</button>
                                <button id="citsImageRemove" type="button" class="rounded-lg bg-rose-50 px-3 py-1.5 text-[11px] font-black text-rose-700">حذف</button>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-indigo-100 bg-white p-3 md:col-span-2">
                        <label for="citsResultMessage" class="block text-xs font-black text-slate-700">توضیحات نتیجه یا متن خطا</label>
                        <textarea id="citsResultMessage" name="message" rows="3" class="mt-2 block w-full resize-y rounded-xl border-slate-200 text-sm leading-7" placeholder="شرح خطای نمایش‌داده‌شده در سامانه یا توضیحات نتیجه کنترل را وارد کنید."></textarea>
                    </div>
                    <button id="citsSubmitButton" class="rounded-xl bg-indigo-600 py-3 font-black text-white disabled:cursor-wait disabled:opacity-60 md:col-span-2">ثبت تلاش CITS</button>
                </form>
            </section>
            @endif

            @if($permit->item_status === 'ready_to_issue' && !$permit->d_serial_number)
            <section class="rounded-2xl border border-amber-200 bg-gradient-to-l from-amber-50 to-white p-5 xl:col-span-2">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                    <div>
                        <h2 class="font-black text-amber-950">صدور و تخصیص سریال فیزیکی</h2>
                        <p class="mt-1 text-xs leading-6 text-slate-500">شماره درج‌شده روی برگه دوزوله را ثبت کنید؛ موجودی انبار بر اساس کشور و نوع مجوز به‌صورت سیستمی کنترل می‌شود.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-center text-xs sm:grid-cols-3">
                        <div class="rounded-xl border border-amber-100 bg-white px-4 py-3"><span class="block text-slate-400">{{ $issuance?->is_renewal ? 'شماره قبلی' : 'پیشنهاد انبار' }}</span><strong class="mt-1 block font-mono text-base text-amber-800">{{ $issuance?->suggested_serial ?: 'بدون موجودی' }}</strong></div>
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3"><span class="block text-emerald-600">مدت اعتبار</span><strong class="mt-1 block text-base text-emerald-800">{{ $issuance?->validity_days }} روز</strong></div>
                        <div class="col-span-2 rounded-xl border border-sky-100 bg-sky-50 px-4 py-3 sm:col-span-1"><span class="block text-sky-600">پایان اعتبار</span><strong class="mt-1 block font-mono text-base text-sky-900">{{ $issuance?->expires_at_fa }}</strong></div>
                    </div>
                    <button type="button" id="openSerialAllocation" class="shrink-0 rounded-xl bg-amber-600 px-8 py-4 font-black text-white shadow-lg shadow-amber-200 transition hover:bg-amber-700">تخصیص سریال و چاپ</button>
                </div>
            </section>
            @endif

            @if($permit->d_serial_number)
            <section class="rounded-2xl border border-sky-200 bg-gradient-to-l from-sky-50 to-white p-5 xl:col-span-2">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                    <div>
                        <h2 class="font-black text-sky-950">چاپ دوزوله صادرشده</h2>
                        <p class="mt-2 text-xs leading-6 text-sky-700">سریال {{ $permit->d_serial_number }} برای این پرونده ثبت شده است؛ نسخه انجمن یا نسخه قابل ارائه به شرکت و راننده را از همین بخش باز کنید.</p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="{{ url('/web/association/request/print-item/'.$permit->id) }}?mode=original" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-xl bg-sky-700 px-5 py-3 text-xs font-black text-white shadow-sm transition hover:bg-sky-800">
                            چاپ نسخه انجمن
                        </a>
                        <a href="{{ url('/web/association/request/print-item/'.$permit->id) }}?mode=copy" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-xl border border-sky-200 bg-white px-5 py-3 text-xs font-black text-sky-800 shadow-sm transition hover:bg-sky-100">
                            مشاهده و چاپ نسخه شرکت و راننده
                        </a>
                    </div>
                </div>
            </section>
            @if($canCorrectSerial)
            <section class="rounded-2xl border border-amber-200 bg-gradient-to-l from-amber-50 to-white p-5 shadow-sm xl:col-span-2">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                    <div>
                        <h2 class="font-black text-amber-950">اصلاح شماره دوزوله قبل از تحویل</h2>
                        <p class="mt-2 text-xs leading-6 text-amber-800">شماره فعلی <strong class="font-mono">{{ $permit->d_serial_number }}</strong> هنوز به پیک تحویل نشده است. در اصلاح، شماره قبلی به موجودی برمی‌گردد و مبلغ پرونده دوباره کسر نمی‌شود.</p>
                    </div>
                    <button type="button" id="openSerialCorrection" class="shrink-0 rounded-xl border border-amber-300 bg-amber-100 px-6 py-3 font-black text-amber-900 shadow-sm transition hover:bg-amber-200">اصلاح شماره تخصیص‌یافته</button>
                </div>
            </section>
            @endif
            @if($permit->item_status === 'returned_unused' && $transferEnabled)
            <section class="rounded-2xl border border-violet-200 bg-gradient-to-l from-violet-50 to-white p-5 xl:col-span-2">
                <h2 class="font-black text-violet-950">انتقال دوزوله برگشتی با حفظ شماره</h2>
                <p class="mt-2 text-xs leading-6 text-violet-700">شماره {{ $permit->d_serial_number }} آزاد یا تغییر نمی‌کند. شرکت، راننده و ناوگان مقصد باید با هم تعیین شوند و عملیات در تاریخچه ثبت می‌شود.</p>
                @if($transferFinancial['enabled'])
                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-800">هزینه انتقال بر اساس تعرفه عضو یا غیرعضو کشور دوزوله، فقط از کیف پول شرکت مقصد کسر می‌شود. برای شرکت مبدأ هیچ تراکنشی انجام نمی‌شود.</div>
                @else
                    <div class="mt-3 rounded-xl bg-emerald-50 p-3 text-xs font-bold text-emerald-700">هزینه انتقال در تنظیمات ادمین غیرفعال است.</div>
                @endif
                <form method="POST" action="{{ route('association.permits.transfer',$permit->id) }}" class="mt-4 grid gap-3 md:grid-cols-3" id="unusedPermitTransferForm">@csrf
                    <input type="hidden" name="confirm_active_assignments" id="confirmActiveAssignments" value="0">
                    <select required name="destination_company_id" id="transferCompany" class="rounded-xl border-violet-200"><option value="">شرکت مقصد</option>@foreach($transferCompanies as $company)<option value="{{ $company->id }}">{{ $company->name_fa }} — {{ $company->company_code }}</option>@endforeach</select>
                    <select required name="destination_fleet_id" id="transferFleet" class="rounded-xl border-violet-200"><option value="">ناوگان مقصد</option>@foreach($transferFleets as $fleet)<option value="{{ $fleet->id }}" data-company="{{ $fleet->company_id }}">{{ $fleet->transit_plate ?: ($fleet->transit_horse.' / '.$fleet->transit_trailer) }}</option>@endforeach</select>
                    <select required name="destination_driver_id" id="transferDriver" class="rounded-xl border-violet-200"><option value="">راننده مقصد</option>@foreach($transferDrivers as $driver)<option value="{{ $driver->id }}" data-company="{{ $driver->current_company_id }}">{{ $driver->first_name_fa }} {{ $driver->last_name_fa }} — {{ $driver->national_code }}</option>@endforeach</select>
                    <input required name="reason" maxlength="1000" class="rounded-xl border-violet-200 md:col-span-3" placeholder="دلیل انتقال">
                    <button class="rounded-xl bg-violet-700 py-3 font-black text-white md:col-span-3">تأیید انتقال شرکت، ناوگان و راننده با حفظ شماره</button>
                </form>
            </section>
            @endif
            @if($permit->item_status === 'company_returned')
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <h2 class="font-black text-emerald-900">{{ $isUnusedReturn ? 'دریافت دوزوله استفاده‌نشده از پیک شرکت' : 'دریافت لاشه از پیک شرکت' }}</h2>
                <p class="mt-2 text-xs text-emerald-700">{{ $isUnusedReturn ? 'شرکت این دوزوله را بدون استفاده برگشت داده است. پس از کنترل کد پیک، شماره حفظ و امکان انتقال یا تمدید فعال می‌شود؛ این مسیر وارد CITS لاشه نمی‌شود.' : 'تصویر لاشه و مشخصات پیک ثبت شده است. پس از دریافت، کنترل CITS انجام می‌شود.' }}</p>
                <form method="POST" action="{{ route('association.permits.receive-lash',$permit->id) }}" class="mt-4 flex gap-2">@csrf
                    <input required name="courier_code" class="min-w-0 flex-1 rounded-xl border-emerald-200" placeholder="کد تحویل پیک">
                    <button class="rounded-xl bg-emerald-700 px-6 font-black text-white">{{ $isUnusedReturn ? 'تأیید دریافت بدون استفاده' : 'تأیید دریافت لاشه' }}</button>
                </form>
            </section>
            @endif
            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-black">تعیین تکلیف پس از صدور</h2>
                <form method="POST" action="{{ route('association.permits.disposition',$permit->id) }}" class="mt-4 grid gap-3 md:grid-cols-2">@csrf
                    <select name="type" class="rounded-xl border-slate-200"><option value="return_archive">ثبت لاشه و بایگانی</option><option value="unused">برگشت بدون استفاده</option><option value="lost">مفقودی</option></select>
                    <input required name="reason" class="rounded-xl border-slate-200" placeholder="دلیل انتخاب یا تغییر">
                    <button class="rounded-xl bg-violet-600 py-3 font-black text-white md:col-span-2">ثبت/تغییر همراه تاریخچه</button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-black">پیک معرفی‌شده توسط شرکت</h2>
                @forelse($missions->where('direction','association_to_company')->where('status','pending') as $mission)
                    <div class="mt-4 rounded-xl bg-sky-50 p-3 text-sm">
                        <div class="grid gap-2 md:grid-cols-2">
                            <span>نام: <strong>{{ $mission->courier_name }}</strong></span>
                            <span>موبایل: <strong dir="ltr">{{ $mission->courier_mobile }}</strong></span>
                        </div>
                        <form method="POST" action="{{ route('association.courier.complete',$mission->id) }}" class="mt-3 flex gap-2">@csrf
                            <input name="otp" required inputmode="numeric" pattern="[0-9]{5}" minlength="5" maxlength="5" class="min-w-0 flex-1 rounded-xl border-sky-200" placeholder="کد ۵ رقمی پیک">
                            <button class="rounded-xl bg-sky-700 px-5 font-black text-white">تأیید تحویل</button>
                        </form>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-slate-500">هنوز شرکتی برای دریافت این دوزوله پیک معرفی نکرده است.</p>
                @endforelse
            </section>
            @endif
        </div>

        <aside class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <details class="group">
            <summary class="cursor-pointer list-none bg-gradient-to-l from-slate-50 to-white px-5 py-4"><div class="flex items-center justify-between gap-3"><span><strong class="block text-slate-900">گردش کار و مکاتبات پرونده</strong><small class="mt-1 block text-slate-500">{{ $events->count() }} رویداد؛ رفت‌وبرگشت شرکت، انجمن و عملیات سیستمی</small></span><span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-black text-slate-700 group-open:hidden">نمایش تاریخچه</span><span class="hidden rounded-lg bg-slate-800 px-3 py-2 text-xs font-black text-white group-open:inline-block">بستن تاریخچه</span></div></summary>
            <div class="space-y-3 border-t border-slate-100 bg-slate-50/60 p-5">
                @forelse($events as $event)
                @php
                    $isCompanyEvent=$event->actor_role === 'company';
                    $isAssociationEvent=in_array($event->actor_role,['association','admin']);
                    $actorLabel=$isCompanyEvent ? 'شرکت' : ($isAssociationEvent ? 'انجمن' : 'سیستم');
                    $eventData=json_decode($event->after_data ?: '{}', true) ?: [];
                    $courierName=$eventData['courier_name'] ?? null;
                    $courierMobile=$eventData['courier_mobile'] ?? null;
                    if($event->event_type === 'outbound_courier_completed'){
                        $courierName=$courierName ?: ($permit->pickup_completed_courier_name ?? null);
                        $courierMobile=$courierMobile ?: ($permit->pickup_completed_courier_mobile ?? null);
                    }
                    if(in_array($event->event_type, ['lash_received','unused_return_received'], true)){
                        $courierName=$courierName ?: ($permit->courier_name ?? null);
                        $courierMobile=$courierMobile ?: ($permit->courier_mobile ?? null);
                    }
                    $showCourierInfo=in_array($event->event_type, ['outbound_courier_assigned','outbound_courier_completed','lash_received','unused_return_received'], true) && ($courierName || $courierMobile);
                @endphp
                <article class="flex {{ $isCompanyEvent ? 'justify-start' : ($isAssociationEvent ? 'justify-end' : 'justify-center') }}">
                    <div class="w-full rounded-2xl border p-4 md:max-w-[78%] {{ $isCompanyEvent ? 'border-emerald-200 bg-emerald-50' : ($isAssociationEvent ? 'border-orange-200 bg-orange-50' : 'border-slate-200 bg-white') }}">
                        <div class="flex flex-wrap items-center justify-between gap-2"><div class="flex items-center gap-2"><span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $isCompanyEvent ? 'bg-emerald-600 text-white' : ($isAssociationEvent ? 'bg-orange-500 text-white' : 'bg-slate-600 text-white') }}">{{ $actorLabel }}</span><strong class="text-sm">{{ $eventLabels[$event->event_type] ?? $event->event_type }}</strong></div><time class="text-[10px] text-slate-400">{{ verta($event->created_at)->format('Y/m/d H:i') }}</time></div>
                        <p class="mt-3 text-xs leading-7 text-slate-700">{{ $event->description }}</p>
                        @if($showCourierInfo)
                            <div class="mt-3 grid gap-2 rounded-xl border border-white/70 bg-white/70 p-3 text-[11px] font-bold text-slate-700 sm:grid-cols-2">
                                <span>نام پیک: <strong>{{ $courierName ?: 'ثبت نشده' }}</strong></span>
                                <span>موبایل پیک: <strong dir="ltr">{{ $courierMobile ?: 'ثبت نشده' }}</strong></span>
                            </div>
                        @endif
                        <p class="mt-2 text-[10px] font-bold text-slate-400">ثبت‌کننده: {{ $event->actor_name ?: $actorLabel }}</p>
                    </div>
                </article>
                @empty<p class="text-sm text-slate-400">هنوز رویدادی ثبت نشده است.</p>@endforelse
            </div>
            </details>
        </aside>
    </div>
</div>

<div id="fleetHistoryModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4"><div class="max-h-[80vh] w-full max-w-5xl overflow-auto rounded-2xl bg-white p-5 shadow-2xl"><div class="flex justify-between"><div><h2 class="font-black">سوابق ناوگان در همه شرکت‌ها</h2><p class="mt-1 text-xs text-slate-500">سوابق دارای شماره کشنده یا بارگیر مشترک با پرونده جاری نمایش داده می‌شوند.</p></div><button type="button" id="fleetHistoryClose" class="text-2xl">×</button></div><div id="fleetHistoryContent" class="mt-4 text-sm">در حال دریافت...</div></div></div>
<div id="currentFilePreviewModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/80 p-4">
    <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3"><div><h2 class="font-black">پیش‌نمایش فایل فعلی</h2><p id="currentFilePreviewTitle" class="mt-1 text-xs text-slate-500"></p></div><button id="currentFilePreviewClose" type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl font-black text-slate-700">×</button></div>
        <div class="min-h-0 flex-1 overflow-auto bg-slate-100 p-4 text-center"><img id="currentFilePreviewImage" alt="پیش‌نمایش فایل فعلی" class="mx-auto max-h-[78vh] max-w-full rounded-xl bg-white object-contain shadow"></div>
    </div>
</div>
<div id="citsImageModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/80 p-4">
    <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div><h2 class="font-black">پیش‌نمایش تصویر خطای CITS</h2><p id="citsImageModalName" class="mt-1 text-[11px] text-slate-500"></p></div>
            <button id="citsImageModalClose" type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl font-black text-slate-700">×</button>
        </div>
        <div class="min-h-0 flex-1 overflow-auto bg-slate-100 p-4 text-center">
            <img id="citsImagePreview" alt="پیش‌نمایش تصویر خطای CITS" class="mx-auto max-h-[75vh] max-w-full rounded-xl bg-white object-contain shadow">
        </div>
    </div>
</div>
<script src="{{ asset('assets/js/compressor.min.js') }}"></script>
<script>
const transferCompany = document.getElementById('transferCompany');
if (transferCompany) {
    const transferForm = document.getElementById('unusedPermitTransferForm');
    const transferFleet = document.getElementById('transferFleet');
    const transferDriver = document.getElementById('transferDriver');
    const confirmation = document.getElementById('confirmActiveAssignments');
    let approvedSelection = '';
    const selectionKey = () => `${transferCompany.value}:${transferFleet.value}:${transferDriver.value}`;
    const resetTransferConfirmation = () => { approvedSelection = ''; confirmation.value = '0'; };
    const filterTransferOptions = () => {
        const companyId = transferCompany.value;
        ['transferFleet', 'transferDriver'].forEach(id => {
            const select = document.getElementById(id);
            select.value = '';
            Array.from(select.options).forEach((option, index) => {
                option.hidden = index > 0 && option.dataset.company !== companyId;
            });
        });
    };
    transferCompany.addEventListener('change', filterTransferOptions);
    transferCompany.addEventListener('change', resetTransferConfirmation);
    transferFleet.addEventListener('change', resetTransferConfirmation);
    transferDriver.addEventListener('change', resetTransferConfirmation);
    filterTransferOptions();
    transferForm.addEventListener('submit', async event => {
        const key = selectionKey();
        if (approvedSelection === key) return;
        event.preventDefault();
        try {
            const url = new URL(@json(route('association.permits.transfer-conflicts', $permit->id)), window.location.origin);
            url.searchParams.set('driver_id', transferDriver.value);
            url.searchParams.set('fleet_id', transferFleet.value);
            const response = await fetch(url, {headers: {'Accept': 'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'بررسی دوزوله‌های فعال انجام نشد.');

            if (data.has_conflict) {
                const rows = [];
                if (data.driver.length) rows.push(`<b>راننده دارای دوزوله فعال است:</b><br>${data.driver.map(item => `شماره ${item.d_serial_number} ـ ${item.country_name || ''}`).join('<br>')}`);
                if (data.fleet.length) rows.push(`<b>ناوگان دارای دوزوله فعال است:</b><br>${data.fleet.map(item => `شماره ${item.d_serial_number} ـ ${item.country_name || ''}`).join('<br>')}`);
                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'تداخل دوزوله فعال',
                    html: `${rows.join('<hr class="my-3">')}<br><br>آیا با اطلاع از موارد بالا، انتقال انجام شود؟`,
                    showCancelButton: true,
                    confirmButtonText: 'بله، آگاهانه انتقال بده',
                    cancelButtonText: 'خیر، بازگشت',
                    confirmButtonColor: '#7c3aed'
                });
                if (!result.isConfirmed) return;
            }
            confirmation.value = '1';
            approvedSelection = key;
            transferForm.requestSubmit();
        } catch (error) {
            await Swal.fire({icon: 'error', title: 'امکان بررسی وجود ندارد', text: error.message, confirmButtonText: 'بستن'});
        }
    });
}
const associationCorrectionSection = document.getElementById('associationCorrectionSection');
const controlDecisionSection = document.getElementById('controlDecisionSection');
if (associationCorrectionSection && controlDecisionSection) {
    controlDecisionSection.parentNode.insertBefore(associationCorrectionSection, controlDecisionSection);
}

(() => {
    const modal = document.getElementById('currentFilePreviewModal');
    const image = document.getElementById('currentFilePreviewImage');
    const title = document.getElementById('currentFilePreviewTitle');
    const closeButton = document.getElementById('currentFilePreviewClose');
    if (!modal || !image || !title || !closeButton) return;
    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        image.removeAttribute('src');
    };
    document.querySelectorAll('[data-current-file-preview]').forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();
            image.src = button.dataset.currentFilePreview;
            title.textContent = button.dataset.currentFileTitle || '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });
    closeButton.addEventListener('click', close);
    modal.addEventListener('click', event => { if (event.target === modal) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.classList.contains('hidden')) close(); });
})();

(() => {
    const form = document.getElementById('citsResultForm');
    const input = document.getElementById('citsErrorImageInput');
    if (!form || !input) return;

    const status = document.getElementById('citsImageStatus');
    const previewWrap = document.getElementById('citsImagePreviewWrap');
    const preview = document.getElementById('citsImagePreview');
    const previewName = document.getElementById('citsImagePreviewName');
    const previewButton = document.getElementById('citsImagePreviewButton');
    const removeButton = document.getElementById('citsImageRemove');
    const modal = document.getElementById('citsImageModal');
    const modalName = document.getElementById('citsImageModalName');
    const modalClose = document.getElementById('citsImageModalClose');
    const submitButton = document.getElementById('citsSubmitButton');
    const maxDirectSize = 500 * 1024;
    let isProcessing = false;
    let previewUrl = null;

    const formatSize = bytes => bytes < 1024 * 1024
        ? `${Math.ceil(bytes / 1024)} کیلوبایت`
        : `${(bytes / 1024 / 1024).toFixed(1)} مگابایت`;

    const replaceFile = file => {
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
    };

    const showPreview = file => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        previewName.textContent = `${file.name} — ${formatSize(file.size)}`;
        modalName.textContent = `${file.name} — ${formatSize(file.size)}`;
        previewWrap.classList.remove('hidden');
        previewWrap.classList.add('flex');
    };

    const clearImage = () => {
        input.value = '';
        preview.removeAttribute('src');
        previewWrap.classList.add('hidden');
        previewWrap.classList.remove('flex');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        previewName.textContent = '';
        status.textContent = 'تصاویر پرحجم پیش از ارسال به‌صورت خودکار کم‌حجم می‌شوند.';
        status.className = 'mt-2 text-[11px] leading-5 text-slate-500';
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
    };

    const setProcessing = processing => {
        isProcessing = processing;
        input.disabled = processing;
        submitButton.disabled = processing;
        submitButton.textContent = processing ? 'در حال کم‌حجم‌سازی تصویر...' : 'ثبت تلاش CITS';
    };

    input.addEventListener('change', () => {
        const originalFile = input.files?.[0];
        if (!originalFile) {
            clearImage();
            return;
        }

        if (!originalFile.type.startsWith('image/')) {
            clearImage();
            status.textContent = 'فایل انتخاب‌شده تصویر معتبر نیست.';
            status.className = 'mt-2 text-[11px] font-bold leading-5 text-rose-600';
            return;
        }

        if (originalFile.size <= maxDirectSize || typeof Compressor === 'undefined') {
            showPreview(originalFile);
            status.textContent = `آماده ارسال — حجم تصویر ${formatSize(originalFile.size)}`;
            status.className = 'mt-2 text-[11px] font-bold leading-5 text-emerald-700';
            return;
        }

        setProcessing(true);
        status.textContent = `در حال کاهش حجم از ${formatSize(originalFile.size)}...`;
        status.className = 'mt-2 text-[11px] font-bold leading-5 text-indigo-700';

        new Compressor(originalFile, {
            quality: 0.88,
            maxWidth: 2200,
            maxHeight: 2200,
            convertSize: maxDirectSize,
            success(result) {
                const compressedFile = new File([result], result.name || originalFile.name, {
                    type: result.type,
                    lastModified: Date.now()
                });
                replaceFile(compressedFile);
                showPreview(compressedFile);
                status.textContent = `حجم تصویر از ${formatSize(originalFile.size)} به ${formatSize(compressedFile.size)} کاهش یافت.`;
                status.className = 'mt-2 text-[11px] font-bold leading-5 text-emerald-700';
                setProcessing(false);
            },
            error() {
                input.value = '';
                status.textContent = 'کاهش حجم این تصویر انجام نشد؛ لطفاً تصویر دیگری انتخاب کنید.';
                status.className = 'mt-2 text-[11px] font-bold leading-5 text-rose-600';
                setProcessing(false);
            }
        });
    });

    removeButton.addEventListener('click', clearImage);
    previewButton.addEventListener('click', () => {
        if (!previewUrl) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });
    const closePreview = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };
    modalClose.addEventListener('click', closePreview);
    modal.addEventListener('click', event => {
        if (event.target === modal) closePreview();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closePreview();
    });
    form.addEventListener('submit', event => {
        if (!isProcessing) return;
        event.preventDefault();
        status.textContent = 'لطفاً تا پایان کم‌حجم‌سازی تصویر صبر کنید.';
    });
})();

document.getElementById('fleetHistoryBtn').addEventListener('click', async function () {
    const modal=document.getElementById('fleetHistoryModal'), content=document.getElementById('fleetHistoryContent');
    modal.classList.remove('hidden'); modal.classList.add('flex'); content.textContent='در حال دریافت...';
    const response=await fetch(@json(route('association.permits.fleet-history',$permit->id)),{headers:{'Accept':'application/json'}});
    const data=await response.json();
    const labels=@json($statusLabels);
    const esc=value=>String(value??'-').replace(/[&<>'"]/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    content.innerHTML=data.items.length ? `<div class="overflow-x-auto rounded-xl border border-slate-200"><table class="w-full text-right"><thead><tr class="bg-slate-50 text-xs text-slate-500"><th class="p-3">پرونده</th><th class="p-3">شرکت</th><th class="p-3">کشنده / بارگیر</th><th class="p-3">کشور</th><th class="p-3">سریال</th><th class="p-3">وضعیت</th><th class="p-3">تاریخ</th></tr></thead><tbody>${data.items.map(x=>`<tr class="border-t hover:bg-slate-50"><td class="p-3 font-mono font-bold">${esc(x.d_code)}-${esc(x.id)}</td><td class="p-3">${esc(x.company_name)}</td><td class="p-3 font-mono">${esc(x.transit_horse)} / ${esc(x.transit_trailer)}</td><td class="p-3">${esc(x.country_name)}</td><td class="p-3 font-mono">${esc(x.d_serial_number)}</td><td class="p-3">${esc(labels[x.item_status]||x.item_status)}</td><td class="p-3 text-xs text-slate-500">${esc(x.created_at)}</td></tr>`).join('')}</tbody></table></div>` : '<div class="rounded-xl bg-slate-50 p-6 text-center text-slate-500">سابقه دیگری برای این کشنده یا بارگیر در شرکت‌ها یافت نشد.</div>';
});
document.getElementById('fleetHistoryClose').addEventListener('click',()=>{const m=document.getElementById('fleetHistoryModal');m.classList.add('hidden');m.classList.remove('flex')});
</script>
@if(($permit->item_status === 'ready_to_issue' && !$permit->d_serial_number) || $canCorrectSerial)
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
@endif
@if($permit->item_status === 'ready_to_issue' && !$permit->d_serial_number)
<script>
document.getElementById('openSerialAllocation')?.addEventListener('click', async function () {
    const isRenewal=@json((bool) $issuance?->is_renewal);
    const suggested=@json((string) ($issuance?->suggested_serial ?? ''));
    const result=await Swal.fire({
        title:isRenewal?'صدور تمدید دوزوله':'ثبت سریال دوزوله کشور',
        html:`<div dir="rtl" class="text-right">
            <div class="mb-4 rounded-2xl bg-slate-50 p-4 text-sm leading-7">پرونده <strong>{{ $permit->d_code }}-{{ $permit->id }}</strong><br>${isRenewal?'در تمدید همان شماره قبلی استفاده می‌شود.':'سریال باید در انبار خام کشور {{ $permit->country_name }} و نوع مجوز {{ $permitTypeLabels[$permit->permit_type] ?? $permit->permit_type }} موجود باشد.'}</div>
            <label class="mb-2 block text-sm font-black">${isRenewal?'شماره دوزوله قبلی':'شماره سریال درج‌شده روی برگه'} <span class="text-rose-600">*</span></label>
            <input id="serialAllocationInput" value="${suggested}" ${isRenewal?'readonly':''} inputmode="numeric" dir="ltr" class="w-full rounded-xl border border-slate-200 p-3 text-center font-mono text-xl font-black">
            <div class="mt-4 grid grid-cols-2 gap-2 text-center text-xs"><div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">اعتبار<br><strong>{{ $issuance?->validity_days }} روز</strong></div><div class="rounded-xl bg-sky-50 p-3 text-sky-900">پایان اعتبار<br><strong>{{ $issuance?->expires_at_fa }}</strong></div></div>
            <p class="mt-4 text-xs text-rose-600">پس از ثبت، سریال مصرف و هزینه از مبلغ بلوکه‌شده قطعی کسر می‌شود.</p>
        </div>`,
        showCancelButton:true, confirmButtonText:'ثبت سریال و چاپ پروانه', cancelButtonText:'انصراف',
        reverseButtons:true, focusConfirm:false,
        preConfirm:()=>{
            const serial=document.getElementById('serialAllocationInput').value.trim();
            if(!serial){Swal.showValidationMessage('شماره سریال را وارد کنید.');return false}
            if(!isRenewal&&!/^\d+$/.test(serial)){Swal.showValidationMessage('شماره سریال فقط باید عدد باشد.');return false}
            return serial;
        }
    });
    if(!result.isConfirmed)return;
    Swal.fire({title:'در حال صدور...',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
    try{
        const response=await fetch(@json(route('association.permit.assign_serial',$permit->id)),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify({serial_number:result.value,validity_days:@json((int) $issuance?->validity_days)})});
        const data=await response.json();
        if(!response.ok||!data.success)throw new Error(data.message||'صدور انجام نشد.');
        await Swal.fire({icon:'success',title:'دوزوله صادر شد',text:`سریال ${data.serial} ثبت شد. پایان اعتبار: ${data.valid_until}`,confirmButtonText:'مشاهده پرونده'});
        window.open(@json(url('/web/association/request/print-item/'.$permit->id)),'_blank');
        window.location.reload();
    }catch(error){Swal.fire({icon:'error',title:'خطا در تخصیص سریال',text:error.message,confirmButtonText:'متوجه شدم'})}
});
</script>
@endif
@if($canCorrectSerial)
<script>
document.getElementById('openSerialCorrection')?.addEventListener('click', async function () {
    const currentSerial=@json((string) $permit->d_serial_number);
    const result=await Swal.fire({
        title:'اصلاح شماره دوزوله',
        html:`<div dir="rtl" class="text-right">
            <div class="mb-4 rounded-2xl bg-amber-50 p-4 text-sm leading-7 text-amber-900">شماره فعلی: <strong class="font-mono">${currentSerial}</strong><br>شماره قبلی به موجودی برمی‌گردد و مبلغ پرونده دوباره کسر نمی‌شود.</div>
            <label class="mb-2 block text-sm font-black">شماره صحیح درج‌شده روی برگه</label>
            <input id="serialCorrectionInput" inputmode="numeric" dir="ltr" class="w-full rounded-xl border border-slate-200 p-3 text-center font-mono text-xl font-black" placeholder="شماره جدید">
            <label class="mt-4 block text-sm font-black">دلیل اصلاح</label>
            <textarea id="serialCorrectionReason" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 p-3 text-sm" placeholder="مثلاً شماره هنگام ثبت اشتباه وارد شد"></textarea>
            <label class="mt-4 flex items-start gap-2 rounded-xl bg-rose-50 p-3 text-xs leading-6 text-rose-800"><input id="serialCorrectionConfirm" type="checkbox" class="mt-1" value="1"> تأیید می‌کنم شماره قبلی به پیک یا شرکت تحویل نشده و برگه قبلی استفاده نشده است.</label>
        </div>`,
        showCancelButton:true, confirmButtonText:'ثبت اصلاح شماره', cancelButtonText:'انصراف',
        reverseButtons:true, focusConfirm:false,
        preConfirm:()=>{
            const serial=document.getElementById('serialCorrectionInput').value.trim();
            const reason=document.getElementById('serialCorrectionReason').value.trim();
            const confirmed=document.getElementById('serialCorrectionConfirm').checked;
            if(!serial){Swal.showValidationMessage('شماره صحیح را وارد کنید.');return false}
            if(!/^\d+$/.test(serial)){Swal.showValidationMessage('شماره جدید باید فقط عدد باشد.');return false}
            if(serial===currentSerial){Swal.showValidationMessage('شماره جدید با شماره فعلی یکسان است.');return false}
            if(!reason){Swal.showValidationMessage('دلیل اصلاح را وارد کنید.');return false}
            if(!confirmed){Swal.showValidationMessage('تأیید تحویل‌نشدن شماره قبلی الزامی است.');return false}
            return {serial,reason};
        }
    });
    if(!result.isConfirmed)return;
    Swal.fire({title:'در حال اصلاح شماره...',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
    try{
        const response=await fetch(@json(route('association.permit.correct_serial',$permit->id)),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify({serial_number:result.value.serial,reason:result.value.reason,confirm_not_delivered:true})});
        const data=await response.json();
        if(!response.ok||!data.success)throw new Error(data.message||'اصلاح شماره انجام نشد.');
        await Swal.fire({icon:'success',title:'شماره اصلاح شد',text:`شماره ${data.old_serial} به ${data.serial} تغییر کرد.`,confirmButtonText:'مشاهده پرونده'});
        window.location.reload();
    }catch(error){Swal.fire({icon:'error',title:'خطا در اصلاح شماره',text:error.message,confirmButtonText:'متوجه شدم'})}
});
</script>
@endif
@include('partials.permit-live-refresh')
@endsection
