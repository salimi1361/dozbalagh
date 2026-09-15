@extends('layouts.admin')

@section('header_title', 'گزارش عملکرد کاربران انجمن')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.vendor.min.css') }}">
@php
    $cards = [
        ['title'=>'کل اقدامات','value'=>$counts['total'],'color'=>'text-slate-900','bg'=>'border-slate-200'],
        ['title'=>'تأیید درخواست','value'=>$counts['approved'],'color'=>'text-emerald-700','bg'=>'border-emerald-200'],
        ['title'=>'رد درخواست','value'=>$counts['rejected'],'color'=>'text-rose-700','bg'=>'border-rose-200'],
        ['title'=>'صدور دوزوله','value'=>$counts['issued'],'color'=>'text-sky-700','bg'=>'border-sky-200'],
        ['title'=>'سایر فرایندها','value'=>$counts['other'],'color'=>'text-violet-700','bg'=>'border-violet-200'],
    ];
@endphp
<div class="space-y-6">
    <section class="rounded-3xl bg-gradient-to-l from-violet-900 via-indigo-950 to-slate-950 p-6 text-white shadow-xl">
        <h2 class="text-xl font-black">گزارش روزانه عملکرد کاربران انجمن</h2>
        <p class="mt-2 text-xs text-violet-100">ریز قابل استناد همه اقدامات ثبت‌شده در فرایند دوزوله، از بررسی و تأیید یا رد تا صدور، CITS، لاشه، پیک و بایگانی</p>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach($cards as $card)<div class="rounded-2xl border {{ $card['bg'] }} bg-white p-4 shadow-sm"><span class="text-[11px] font-bold text-slate-500">{{ $card['title'] }}</span><strong class="mt-2 block text-2xl {{ $card['color'] }}">{{ number_format($card['value']) }}</strong></div>@endforeach
    </section>

    <form method="GET" action="{{ route('association.performance-reports.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <label class="space-y-2 xl:col-span-2"><span class="text-xs font-black text-slate-600">جست‌وجو</span><input name="search" value="{{ $filters['search'] }}" class="w-full rounded-xl border-slate-200 bg-slate-50 text-xs" placeholder="کد رهگیری، سریال، شرکت، کشور یا شرح"></label>
            <label class="space-y-2"><span class="text-xs font-black text-slate-600">کاربر انجمن</span><select name="user_id" class="w-full rounded-xl border-slate-200 bg-slate-50 text-xs"><option value="">همه کاربران</option>@foreach($associationUsers as $user)<option value="{{ $user->id }}" @selected((int)$filters['user_id']===$user->id)>{{ $user->username }}</option>@endforeach</select></label>
            <label class="space-y-2"><span class="text-xs font-black text-slate-600">نوع اقدام</span><select name="event_type" class="w-full rounded-xl border-slate-200 bg-slate-50 text-xs"><option value="">همه اقدامات</option>@foreach($eventTypes as $type)<option value="{{ $type }}" @selected($filters['event_type']===$type)>{{ $eventLabels[$type] ?? $type }}</option>@endforeach</select></label>
            <label class="relative space-y-2"><span class="text-xs font-black text-slate-600">از تاریخ (شمسی)</span><input id="performance_from_view" name="date_from_view" value="{{ $filters['date_from_view'] }}" readonly class="w-full cursor-pointer rounded-xl border-slate-200 bg-slate-50 text-center text-xs font-bold"><input id="performance_from" type="hidden" name="date_from" value="{{ $filters['date_from'] }}"></label>
            <label class="relative space-y-2"><span class="text-xs font-black text-slate-600">تا تاریخ (شمسی)</span><input id="performance_to_view" name="date_to_view" value="{{ $filters['date_to_view'] }}" readonly class="w-full cursor-pointer rounded-xl border-slate-200 bg-slate-50 text-center text-xs font-bold"><input id="performance_to" type="hidden" name="date_to" value="{{ $filters['date_to'] }}"></label>
        </div>
        <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4"><button class="rounded-xl bg-slate-900 px-6 py-2.5 text-xs font-black text-white">نمایش گزارش</button><a href="{{ route('association.performance-reports.index') }}" class="rounded-xl border border-slate-200 px-5 py-2.5 text-xs font-black text-slate-600">امروز</a><a href="{{ route('association.performance-reports.export', request()->query()) }}" class="rounded-xl bg-emerald-600 px-6 py-2.5 text-xs font-black text-white">خروجی Excel</a></div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full text-right text-xs"><thead class="whitespace-nowrap bg-slate-100 text-slate-600"><tr><th class="p-4">زمان</th><th class="p-4">کاربر</th><th class="p-4">اقدام</th><th class="p-4">پرونده / سریال</th><th class="p-4">شرکت / کشور</th><th class="p-4">تغییر وضعیت</th><th class="p-4">شرح</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($events as $event)<tr class="align-top hover:bg-slate-50"><td class="whitespace-nowrap p-4"><strong class="block">{{ verta($event->created_at)->format('Y/m/d') }}</strong><span class="text-slate-400">{{ verta($event->created_at)->format('H:i:s') }}</span></td><td class="p-4"><strong class="rounded-lg bg-violet-50 px-2 py-1 text-violet-700">{{ $event->actor_name }}</strong></td><td class="p-4 font-black text-slate-700">{{ $eventLabels[$event->event_type] ?? $event->event_type }}</td><td class="p-4"><a href="{{ route('association.permits.show', $event->permit_request_item_id) }}" class="font-mono font-black text-sky-700">{{ $event->d_code }}</a><span class="mt-1 block font-mono text-slate-500">{{ $event->serial_number ?: 'بدون سریال' }}</span></td><td class="p-4"><strong class="block">{{ $event->company_name ?: '-' }}</strong><span class="text-slate-400">{{ $event->country_name ?: '-' }}</span></td><td class="whitespace-nowrap p-4"><span>{{ $statusLabels[$event->from_status] ?? $event->from_status ?: '—' }}</span><span class="mx-1 text-slate-300">←</span><strong>{{ $statusLabels[$event->to_status] ?? $event->to_status ?: '—' }}</strong></td><td class="min-w-64 p-4 leading-6 text-slate-600">{{ $event->description ?: 'بدون توضیح' }}</td></tr>
        @empty<tr><td colspan="7" class="p-12 text-center font-bold text-slate-400">در بازه و فیلتر انتخاب‌شده اقدامی ثبت نشده است.</td></tr>@endforelse
    </tbody></table></div>@if($events->hasPages())<div class="border-t border-slate-100 p-4">{{ $events->links() }}</div>@endif</section>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/persian-date.vendor.min.js') }}"></script>
<script src="{{ asset('assets/js/persian-datepicker.vendor.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !jQuery.fn.persianDatepicker) return;
    jQuery('#performance_from_view').persianDatepicker({format:'YYYY/MM/DD',altField:'#performance_from',altFormat:'YYYY-MM-DD',initialValue:false,autoClose:true,observer:true});
    jQuery('#performance_to_view').persianDatepicker({format:'YYYY/MM/DD',altField:'#performance_to',altFormat:'YYYY-MM-DD',initialValue:false,autoClose:true,observer:true});
});
</script>
@endsection
