@extends('layouts.app')
@section('header_title', 'دوزوله‌های صادرشده')
@section('content')
<div dir="rtl" class="mx-auto max-w-6xl space-y-5">
    <div class="rounded-2xl bg-gradient-to-l from-emerald-800 to-emerald-600 p-6 text-white shadow-lg">
        <h1 class="text-xl font-black">دوزوله‌های صادرشده شرکت</h1>
        <p class="mt-2 text-xs text-emerald-100">مشاهده و چاپ نسخه تصویری دوزوله‌های تخصیص‌یافته</p>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="w-full text-right text-xs"><thead class="whitespace-nowrap bg-slate-100 text-slate-600"><tr><th class="p-4">سریال / کد رهگیری</th><th class="p-4">کشور و نوع مجوز</th><th class="p-4">راننده و ناوگان</th><th class="p-4">مسیر</th><th class="p-4">تاریخ صدور</th><th class="p-4 text-center">نسخه چاپی</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($items as $item)<tr class="hover:bg-slate-50"><td class="p-4"><strong class="block font-mono text-sm text-slate-800">{{ $item->d_serial_number }}</strong><span class="mt-1 block font-mono text-[10px] text-slate-400">{{ $item->d_code }}</span></td><td class="p-4"><strong class="block text-slate-700">{{ $item->country_name ?: '---' }}</strong><span class="mt-1 block text-slate-500">{{ str_replace('_',' ',$item->permit_type) }}</span></td><td class="p-4"><strong class="block">{{ trim(($item->first_name_fa ?? '').' '.($item->last_name_fa ?? '')) ?: '---' }}</strong><span class="mt-1 block font-mono text-slate-500">{{ $item->transit_plate ?: '---' }}</span></td><td class="p-4"><span>{{ $item->loading_origin ?: '---' }}</span><span class="mx-1 text-slate-300">←</span><span>{{ $item->loading_destination ?: '---' }}</span></td><td class="whitespace-nowrap p-4">{{ $item->issued_at ? verta($item->issued_at)->format('Y/m/d') : '---' }}</td><td class="p-4 text-center"><a href="{{ route('company.dozbalagh.copy',$item->id) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2 font-black text-white shadow hover:bg-sky-700">👁 مشاهده و چاپ</a></td></tr>@empty<tr><td colspan="6" class="p-14 text-center font-bold text-slate-400">هنوز دوزوله‌ای برای شرکت صادر نشده است.</td></tr>@endforelse</tbody></table></div>
        @if($items->hasPages())<div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>@endif
    </div>
</div>
@endsection
