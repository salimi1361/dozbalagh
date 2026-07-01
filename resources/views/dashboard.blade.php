@extends('layouts.app')

@section('header_title', 'داشبورد اصلی سیستم')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-sky-50 rounded-xl text-xl">👤</div>
            <div>
                <h2 class="text-lg font-black text-slate-800">خوش آمدید، {{ $company->ceo_name ?? 'مدیر محترم شرکت' }} عزیز</h2>
                <p class="text-slate-500 text-sm mt-2">🏢 نام شرکت: {{ $company->name_fa ?? $company->name }} | کد اختصاصی: {{ $company->company_code ?? '---' }}</p>
                <p class="text-slate-500 text-sm mt-1">📍 آدرس: {{ $company->address_fa ?? 'ثبت نشده' }}</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-end sm:items-center gap-3 w-full sm:w-auto">
            <div>
                @if($company->status == 'approved')
                    <div class="bg-emerald-50 text-emerald-700 px-4 py-2 rounded-xl text-sm font-bold border border-emerald-200 shadow-sm flex items-center gap-2">
                        <span class="text-lg">✅</span> حساب کاربری فعال
                    </div>
                @else
                    <div class="bg-amber-50 text-amber-700 px-4 py-2 rounded-xl text-sm font-bold border border-amber-200 shadow-sm flex items-center gap-2">
                        <span class="text-lg">⏳</span> در انتظار تایید مدیریت
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-6 gap-4">
    @php
        $stats = [
            ['صادر شده', $counts['issued'] ?? 0, 'text-blue-600'],
            ['استفاده شده', $counts['used'] ?? 0, 'text-emerald-600'],
            ['لاشه', $counts['returned'] ?? 0, 'text-indigo-600'],
            ['منقضی', $counts['expired'] ?? 0, 'text-slate-600'],
            ['تمدیدی', $counts['renewed'] ?? 0, 'text-amber-600'],
            ['مفقودی', $counts['lost'] ?? 0, 'text-rose-600'],
        ];
    @endphp

    @foreach($stats as $item)
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 text-center hover:shadow-md transition">
        <span class="text-[10px] font-bold text-slate-400 block mb-2">{{ $item[0] }}</span>
        <span class="text-2xl font-black {{ $item[2] }}">{{ $item[1] }}</span>
    </div>
    @endforeach
</div>
@endsection