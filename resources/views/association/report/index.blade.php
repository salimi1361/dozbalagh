@extends('layouts.admin')

@section('header_title', 'گزارش دوزوله‌ها')

@section('content')
@php
    $statusColors = [
        'pending' => 'bg-amber-50 text-amber-700 border-amber-200', 'under_review' => 'bg-amber-50 text-amber-700 border-amber-200',
        'approved' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'issued' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'company_returned' => 'bg-violet-50 text-violet-700 border-violet-200',
        'returned' => 'bg-orange-50 text-orange-700 border-orange-200', 'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
        'collected' => 'bg-sky-50 text-sky-700 border-sky-200', 'archived' => 'bg-slate-100 text-slate-700 border-slate-200',
        'lost' => 'bg-red-50 text-red-700 border-red-200',
    ];
@endphp
<div class="space-y-6">
    <div class="rounded-2xl bg-gradient-to-l from-slate-900 to-indigo-950 p-6 text-white shadow-xl">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div><h2 class="text-xl font-black">گزارش جامع دوزوله‌ها</h2><p class="mt-2 text-xs text-slate-300">مشاهده، فیلتر و دریافت خروجی اکسل از پرونده‌ها در همه وضعیت‌ها</p></div>
            <div class="rounded-xl border border-white/10 bg-white/5 px-5 py-3 text-center"><span class="block text-[10px] text-slate-300">تعداد نتیجه</span><strong class="text-2xl">{{ number_format($permits->total()) }}</strong></div>
        </div>
    </div>

    <form method="GET" action="{{ route('association.reports.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div><label class="mb-2 block text-xs font-black text-slate-600">جست‌وجو</label><input name="search" value="{{ request('search') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs outline-none focus:border-indigo-500" placeholder="کد رهگیری، سریال، شرکت، راننده، پلاک..."></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">وضعیت</label><select name="status" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"><option value="">همه وضعیت‌ها</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }} ({{ number_format($statusCounts[$value] ?? 0) }})</option>@endforeach</select></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">شرکت</label><select name="company_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"><option value="">همه شرکت‌ها</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name_fa ?: $company->name }}</option>@endforeach</select></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">کشور مقصد</label><select name="country_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"><option value="">همه کشورها</option>@foreach($countries as $country)<option value="{{ $country->id }}" @selected((string) request('country_id') === (string) $country->id)>{{ $country->name }}</option>@endforeach</select></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">نوع درخواست</label><select name="request_type" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"><option value="">همه انواع</option><option value="new" @selected(request('request_type') === 'new')>جدید</option><option value="renewal" @selected(request('request_type') === 'renewal')>تمدید</option></select></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">از تاریخ صدور</label><input type="date" name="date_from" value="{{ request('date_from') }}" dir="ltr" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs"></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">تا تاریخ صدور</label><input type="date" name="date_to" value="{{ request('date_to') }}" dir="ltr" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs"></div>
            <div class="flex items-end gap-2"><button class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white">اعمال فیلتر</button><a href="{{ route('association.reports.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-600">پاک‌کردن</a></div>
        </div>
        <div class="mt-4 flex justify-end border-t border-slate-100 pt-4"><a href="{{ route('association.reports.export', request()->query()) }}" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-black text-white shadow-sm hover:bg-emerald-700">خروجی اکسل از نتایج فیلترشده</a></div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full text-right text-xs"><thead class="whitespace-nowrap bg-slate-100 text-slate-600"><tr><th class="p-4">کد رهگیری / سریال</th><th class="p-4">شرکت</th><th class="p-4">راننده</th><th class="p-4">ناوگان</th><th class="p-4">کشور</th><th class="p-4">نوع</th><th class="p-4">وضعیت</th><th class="p-4">تاریخ ثبت / صدور</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($permits as $permit)<tr class="hover:bg-slate-50"><td class="p-4"><strong class="block font-mono text-slate-800">{{ $permit->d_code ?: '#' . $permit->id }}</strong><span class="mt-1 block font-mono text-[10px] text-slate-400">{{ $permit->serial_number }}</span></td><td class="p-4 font-bold text-slate-700">{{ $permit->company_name ?: '-' }}</td><td class="p-4"><strong class="block text-slate-700">{{ trim(($permit->driver_first_name ?? '').' '.($permit->driver_last_name ?? '')) ?: '-' }}</strong><span class="text-[10px] text-slate-400">{{ $permit->driver_national_code }}</span></td><td class="p-4"><span class="block font-mono">{{ $permit->fleet_smart_card ?: '-' }}</span><span class="text-[10px] text-slate-400">{{ $permit->fleet_plate }}</span></td><td class="p-4">{{ $permit->country_name ?: '-' }}</td><td class="p-4">{{ ($permit->request_type ?? 'new') === 'renewal' ? 'تمدید' : 'جدید' }}</td><td class="p-4"><span class="whitespace-nowrap rounded-full border px-2 py-1 font-black {{ $statusColors[$permit->report_status] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">{{ $statusOptions[$permit->report_status] ?? $permit->report_status }}</span></td><td class="whitespace-nowrap p-4 text-slate-500"><span class="block">ثبت: {{ $permit->created_at ? verta($permit->created_at)->format('Y/m/d H:i') : '-' }}</span><span class="mt-1 block text-[10px]">صدور: {{ $permit->issued_at ? verta($permit->issued_at)->format('Y/m/d H:i') : '-' }}</span></td></tr>@empty<tr><td colspan="8" class="p-12 text-center font-bold text-slate-400">نتیجه‌ای با فیلترهای انتخاب‌شده پیدا نشد.</td></tr>@endforelse</tbody></table></div>
        @if($permits->hasPages())<div class="border-t border-slate-100 p-4">{{ $permits->links() }}</div>@endif
    </div>
</div>
@endsection
