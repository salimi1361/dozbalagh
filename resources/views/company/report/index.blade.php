@extends('layouts.app')

@section('header_title', 'گزارش‌ها و تحلیل وضعیت')

@section('content')
@php
    $statusLabels = [
        'draft' => 'پیش نویس',
        'pending' => 'در حال بررسی',
        'under_review' => 'در حال بررسی',
        'approved' => 'تایید شده',
        'issued' => 'صادر شده',
        'rejected' => 'رد شده',
        'returned' => 'نیاز به اصلاح',
        'collected' => 'لاشه تحویل شده',
        'archived' => 'بایگانی شده',
        'lost' => 'مفقودی',
    ];
    $statusClasses = [
        'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
        'under_review' => 'bg-amber-50 text-amber-700 border-amber-100',
        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'issued' => 'bg-sky-50 text-sky-700 border-sky-100',
        'returned' => 'bg-rose-50 text-rose-700 border-rose-100',
        'rejected' => 'bg-rose-50 text-rose-700 border-rose-100',
        'collected' => 'bg-slate-100 text-slate-700 border-slate-200',
        'archived' => 'bg-slate-100 text-slate-700 border-slate-200',
        'lost' => 'bg-zinc-100 text-zinc-700 border-zinc-200',
    ];
    $summaryCards = [
        ['label' => 'صادر شده', 'key' => 'issued'],
        ['label' => 'در حال بررسی', 'key' => 'pending'],
        ['label' => 'لاشه تحویل شده', 'key' => 'collected'],
        ['label' => 'نیاز به اصلاح', 'key' => 'returned'],
        ['label' => 'تمدیدی', 'key' => 'renewal', 'count_key' => 'renewed'],
        ['label' => 'مفقودی', 'key' => 'lost'],
    ];
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        @foreach($summaryCards as $card)
            @php
                $isActive = request('status') === $card['key'];
                $countKey = $card['count_key'] ?? $card['key'];
            @endphp
            <a href="{{ route('report.index', array_merge(request()->except('page'), ['status' => $card['key']])) }}"
               class="bg-white p-4 rounded-xl border shadow-sm text-center transition hover:-translate-y-0.5 hover:shadow-md {{ $isActive ? 'border-indigo-300 ring-2 ring-indigo-100' : 'border-slate-200' }}">
                <span class="text-[10px] font-bold text-slate-400 block">{{ $card['label'] }}</span>
                <span class="text-xl font-black text-slate-800">{{ number_format($counts[$countKey] ?? 0) }}</span>
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
        <form action="{{ route('report.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-5">
                <label for="search" class="block text-xs font-black text-slate-600 mb-2">جستجو</label>
                <input type="text"
                       id="search"
                       name="search"
                       value="{{ request('search') }}"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-700 outline-none transition focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                       placeholder="کد رهگیری، سریال، راننده، کد ملی، پلاک یا کد ناوگان">
            </div>

            <div class="md:col-span-3">
                <label for="status" class="block text-xs font-black text-slate-600 mb-2">وضعیت</label>
                <select id="status"
                        name="status"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-700 outline-none transition focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">همه وضعیت‌ها</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-4 flex flex-col sm:flex-row gap-2">
                <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white transition hover:bg-slate-800">
                    اعمال فیلتر
                </button>
                <a href="{{ route('report.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-600 transition hover:bg-slate-50">
                    حذف فیلتر
                </a>
                <a href="{{ route('report.export', request()->only(['search', 'status'])) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-black text-emerald-700 transition hover:bg-emerald-100">
                    خروجی اکسل
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-100 text-slate-700 whitespace-nowrap">
                    <tr>
                        <th class="p-4">کد رهگیری</th>
                        <th class="p-4">راننده</th>
                        <th class="p-4">ناوگان / پلاک</th>
                        <th class="p-4">سریال</th>
                        <th class="p-4">نوع</th>
                        <th class="p-4">وضعیت</th>
                        <th class="p-4">مبلغ</th>
                        <th class="p-4">تاریخ ثبت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($permits as $p)
                        @php
                            $driverName = trim(($p->driver->first_name_fa ?? '') . ' ' . ($p->driver->last_name_fa ?? ''));
                            $requestType = ($p->request_type ?? 'new') === 'renewal' ? 'تمدیدی' : 'جدید';
                            $statusClass = $statusClasses[$p->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="p-4 font-mono font-bold text-slate-800">{{ $p->d_code ?? $p->id }}</td>
                            <td class="p-4">
                                <div class="font-bold text-slate-800">{{ $driverName ?: 'نامشخص' }}</div>
                                <div class="mt-1 text-[10px] text-slate-400 font-mono">{{ $p->driver->national_code ?? '' }}</div>
                            </td>
                            <td class="p-4">
                                <div class="font-mono text-slate-700">{{ $p->fleet->smart_card_number ?? $p->fleet_id }}</div>
                                <div class="mt-1 text-[10px] text-slate-400">{{ $p->fleet->transit_plate ?? '' }}</div>
                            </td>
                            <td class="p-4 font-mono text-slate-600">{{ $p->serial_number ?? '-' }}</td>
                            <td class="p-4">
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 font-black text-slate-600">{{ $requestType }}</span>
                            </td>
                            <td class="p-4">
                                <span class="rounded-full border px-2 py-1 font-black {{ $statusClass }}">{{ $statusLabels[$p->status] ?? $p->status }}</span>
                            </td>
                            <td class="p-4 font-mono text-slate-600">{{ number_format($p->total_amount ?? 0) }}</td>
                            <td class="p-4 text-slate-500 whitespace-nowrap">
                                @if($p->created_at)
                                    <div>{{ \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($p->created_at))->format('Y/m/d H:i') }}</div>
                                    <div class="mt-1 text-[10px] font-mono" dir="ltr">{{ \Carbon\Carbon::parse($p->created_at)->format('Y-m-d H:i') }}</div>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 font-black">موردی با فیلترهای انتخاب شده پیدا نشد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($permits, 'links'))
            <div class="p-4 border-t border-slate-100">
                {{ $permits->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
