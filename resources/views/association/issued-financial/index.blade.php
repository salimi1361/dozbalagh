@extends('layouts.admin')

@section('header_title', 'مبالغ دوزوله‌های صادرشده')

@section('content')
<div class="space-y-5">
    <div class="rounded-2xl bg-gradient-to-l from-emerald-900 to-slate-900 p-6 text-white shadow-xl">
        <h2 class="text-xl font-black">گزارش مبلغ دوزوله‌های صادرشده</h2>
        <p class="mt-2 text-xs text-emerald-100">این صفحه فقط مبلغ دوزوله‌هایی را نشان می‌دهد که شماره سریال گرفته و صادر شده‌اند.</p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('association.issued-financial.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div><label class="mb-2 block text-xs font-black text-slate-600">از تاریخ صدور</label><input type="date" name="date_from" value="{{ $dateFrom }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">تا تاریخ صدور</label><input type="date" name="date_to" value="{{ $dateTo }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">شرکت</label><select name="company_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs"><option value="">همه شرکت‌ها</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string)$companyId === (string)$company->id)>{{ $company->name_fa ?: $company->name }}</option>@endforeach</select></div>
            <div class="flex items-end gap-2"><button class="flex-1 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white">نمایش گزارش</button><a href="{{ route('association.issued-financial.export', request()->query()) }}" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white">خروجی Excel</a></div>
        </div>
    </form>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5"><span class="text-xs font-bold text-slate-500">تعداد صادرشده در بازه</span><strong class="mt-2 block text-2xl text-slate-800">{{ number_format($totalCount) }}</strong></div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"><span class="text-xs font-bold text-emerald-700">جمع مبلغ دوزوله‌های صادرشده</span><strong class="mt-2 block text-2xl text-emerald-800">{{ number_format($totalAmount) }} <small class="text-xs">ریال</small></strong></div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="w-full text-right text-xs"><thead class="whitespace-nowrap bg-slate-100 text-slate-600"><tr><th class="p-4">کد رهگیری</th><th class="p-4">شماره دوزوله</th><th class="p-4">شرکت</th><th class="p-4">مقصد</th><th class="p-4">مبلغ دوزوله</th><th class="p-4">تاریخ صدور</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($rows as $row)<tr class="hover:bg-slate-50"><td class="p-4 font-mono font-bold">{{ $row->tracking_code }}</td><td class="p-4 font-mono">{{ $row->serial_number }}</td><td class="p-4 font-bold">{{ $row->company_name_fa ?: $row->company_name ?: 'نامشخص' }}</td><td class="p-4"><span class="block">{{ $row->country_name ?: 'نامشخص' }}</span><span class="text-[10px] text-slate-400">{{ $row->loading_destination ?: '---' }}</span></td><td class="p-4 font-mono font-black text-emerald-700">{{ number_format($row->deducted_amount) }} ریال</td><td class="p-4 whitespace-nowrap">{{ \Morilog\Jalali\Jalalian::fromCarbon(\Illuminate\Support\Carbon::parse($row->issued_at))->format('Y/m/d H:i') }}</td></tr>@empty<tr><td colspan="6" class="p-12 text-center font-bold text-slate-400">در این بازه دوزوله صادرشده‌ای وجود ندارد.</td></tr>@endforelse</tbody></table></div>
        @if($rows->hasPages())<div class="border-t border-slate-100 p-4">{{ $rows->links() }}</div>@endif
    </div>
</div>
@endsection
