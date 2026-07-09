@extends('layouts.app')
@php
    $requestWindowStatus = $requestWindowStatus ?? ['allowed' => true, 'message' => null];
@endphp

@section('header_title')
    مدیریت پروانه‌ها / <span class="text-slate-600 font-black">لیست درخواست‌های دوزوله</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    
    {{-- هدر سرمه‌ای/مشکی شیک --}}
    <div class="bg-slate-950 rounded-3xl p-6 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border border-slate-800 shadow-lg relative overflow-hidden">
        <div class="absolute left-0 top-0 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="z-10">
            <h1 class="text-xl font-black text-white">تاریخچه درخواست‌های دوزوله</h1>
            <p class="text-xs text-slate-400 font-medium mt-1.5">لیست تمام مجوزهای ثبت شده به همراه آخرین وضعیت بررسی و تخصیص</p>
        </div>
        
        @if($requestWindowStatus['allowed'])
            <a href="{{ route('dozbalagh.create') }}" class="z-10 flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-emerald-900/20 transition shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                ثبت درخواست جدید
            </a>
        @else
            <button type="button" disabled class="z-10 flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-700 text-slate-300 font-bold text-sm rounded-xl cursor-not-allowed shrink-0">
                ثبت درخواست بسته است
            </button>
        @endif
    </div>

    @if(!$requestWindowStatus['allowed'])
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm font-bold leading-7">
            {{ $requestWindowStatus['message'] }}
        </div>
    @endif

    {{-- نمایش پیام موفقیت سیستم --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-bold flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            {{ session('success') }}
        </div>
    @endif

    {{-- نوار فیلترهای وضعیت و باکس جستجوی یکپارچه جدول --}}
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-5 bg-white p-3 rounded-2xl border border-slate-200/60 shadow-sm">
        
        {{-- دکمه‌های فیلتر وضعیت به ترتیب درخواستی شما --}}
        <div class="flex flex-wrap gap-1.5 text-xs font-bold">
            <a href="{{ route('dozbalagh.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}" 
               class="px-3 py-2 rounded-xl transition-all {{ request('status') === 'pending' ? 'bg-blue-600 text-white shadow-sm' : 'bg-blue-50/60 text-blue-600 hover:bg-blue-50' }}">
                در حال بررسی
            </a>
            <a href="{{ route('dozbalagh.index', array_merge(request()->except('page'), ['status' => 'approved'])) }}" 
               class="px-3 py-2 rounded-xl transition-all {{ request('status') === 'approved' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50/60 text-emerald-600 hover:bg-emerald-50' }}">
                تایید شده
            </a>
            <a href="{{ route('dozbalagh.index', array_merge(request()->except('page'), ['status' => 'issued'])) }}"
               class="px-3 py-2 rounded-xl transition-all {{ request('status') === 'issued' ? 'bg-sky-600 text-white shadow-sm' : 'bg-sky-50/60 text-sky-600 hover:bg-sky-50' }}">
                صادر شده
            </a>
            <a href="{{ route('dozbalagh.index', array_merge(request()->except('page'), ['status' => 'rejected'])) }}" 
               class="px-3 py-2 rounded-xl transition-all {{ request('status') === 'rejected' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50/60 text-rose-600 hover:bg-rose-50' }}">
                ‌ رد شده
            </a>
            <a href="{{ route('dozbalagh.index', array_merge(request()->except('page'), ['status' => 'collected'])) }}"
               class="px-3 py-2 rounded-xl transition-all {{ request('status') === 'collected' ? 'bg-slate-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                لاشه تحویل شده
            </a>
            <a href="{{ route('dozbalagh.index', array_merge(request()->except('page'), ['status' => 'archived'])) }}"
               class="px-3 py-2 rounded-xl transition-all {{ request('status') === 'archived' ? 'bg-zinc-700 text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' }}">
                بایگانی
            </a>
            <a href="{{ route('dozbalagh.index', array_merge(request()->except('page'), ['status' => 'renewal'])) }}" 
               class="px-3 py-2 rounded-xl transition-all {{ request('status') === 'renewal' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-indigo-50/60 text-indigo-600 hover:bg-indigo-50' }}">
                تمدیدها
            </a>
            <a href="{{ route('dozbalagh.index', array_merge(request()->except('page'), ['status' => 'lost'])) }}" 
               class="px-3 py-2 rounded-xl transition-all {{ request('status') === 'lost' ? 'bg-slate-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                مفقودی
            </a>
            <a href="{{ route('dozbalagh.index', array_merge(request()->except('page'), ['status' => ''])) }}"
               class="px-3 py-2 rounded-xl transition-all {{ !request('status') ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                همه درخواست‌ها
            </a>
        </div>

        {{-- کادر جستجوی متنی متصل به فیلتر وضعیت --}}
        <form action="{{ route('dozbalagh.index') }}" method="GET" class="relative w-full lg:w-72">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <input type="text" name="search" id="tableSearchInput" value="{{ request('search') }}" placeholder="جستجو در این وضعیت..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
            @if(request('search'))
                <a href="{{ route('dozbalagh.index', request()->except('search')) }}" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-rose-500 transition-colors p-1 rounded-full hover:bg-slate-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </a>
            @endif
        </form>
    </div>

    {{-- باکس اصلی جدول داده‌ها --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-bold whitespace-nowrap">
                        <th class="p-4">کد رهگیری</th>
                        <th class="p-4">راننده متقاضی</th>
                        <th class="p-4 text-center">ناوگان / پلاک</th>
                        <th class="p-4">مجموع مبلغ (ریال)</th>
                        <th class="p-4">نوع / وضعیت</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                    @forelse($requests as $item)
                        @php
                            $requestType = $item->request_type ?: 'new';
                            $requestTypeLabel = $requestType === 'renewal' ? 'درخواست تمدید' : 'درخواست جدید';
                            $requestTypeClass = $requestType === 'renewal'
                                ? 'bg-indigo-50 text-indigo-700 border-indigo-100'
                                : 'bg-slate-50 text-slate-700 border-slate-200';
                            $statusLabels = [
                                'pending' => ['در حال بررسی', 'bg-amber-50 text-amber-700 border-amber-100'],
                                'approved' => ['تایید شده', 'bg-emerald-50 text-emerald-700 border-emerald-100'],
                                'issued' => ['صادر شده / معتبر', 'bg-sky-50 text-sky-700 border-sky-100'],
                                'rejected' => ['رد شده', 'bg-rose-50 text-rose-700 border-rose-100'],
                                'returned' => ['رد شده', 'bg-rose-50 text-rose-700 border-rose-100'],
                                'collected' => ['لاشه تحویل شده', 'bg-slate-100 text-slate-700 border-slate-200'],
                                'archived' => ['بایگانی شده', 'bg-slate-100 text-slate-700 border-slate-200'],
                                'lost' => ['مفقودی', 'bg-zinc-100 text-zinc-700 border-zinc-200'],
                            ];
                            [$statusText, $statusClass] = $statusLabels[$item->status] ?? ['نامشخص: ' . $item->status, 'bg-slate-100 text-slate-700 border-slate-200'];
                            $associationNote = $item->reject_reason ?: null;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="p-4 font-mono font-bold text-slate-900">
                                <span onclick="copyToClipboard('{{ $item->d_code }}', this)" class="cursor-pointer bg-slate-50 hover:bg-indigo-50 text-slate-700 hover:text-indigo-600 px-2.5 py-1.5 rounded-lg border border-slate-200 hover:border-indigo-200 transition-all inline-flex items-center gap-1.5 group" title="کلیک جهت کپی کدرهگیری">
                                    {{ $item->d_code }}
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-500 transition-colors"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5" /></svg>
                                </span>
                            </td>
                            
                            <td class="p-4 font-semibold whitespace-nowrap">
                                {{ $item->driver ? trim(($item->driver->first_name_fa ?? '') . ' ' . ($item->driver->last_name_fa ?? '')) : 'نامشخص' }}
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $item->driver->national_code ?? '' }}</div>
                            </td>
                            
                            <td class="p-4 flex justify-center">
                                <x-iran-plate :plate="optional($item->fleet)->transit_plate" size="sm" />
                                @if(false)
                                @php
                                    $rawPlate = optional($item->fleet)->transit_plate ?? '';
                                    $plateParts = !empty($rawPlate) ? explode('-', $rawPlate) : [];
                                @endphp
                                @if(count($plateParts) == 4)
                                    <div dir="ltr" class="inline-flex items-stretch border border-slate-900 rounded-md bg-[#fab800] text-slate-950 font-black h-8 overflow-hidden shadow-sm" style="min-width: 140px;">
                                        <div class="bg-[#0033a0] flex flex-col items-center justify-between py-0.5 px-0.5 text-white border-r border-slate-900" style="width: 16px; min-width: 16px;">
                                            <div class="w-full rounded-sm overflow-hidden flex flex-col" style="height: 4px;">
                                                <div class="bg-[#228B22]" style="height: 33.33%;"></div><div class="bg-white" style="height: 33.33%;"></div><div class="bg-[#DA291C]" style="height: 33.33%;"></div>
                                            </div>
                                            <div class="flex flex-col items-center text-[4px] font-sans font-bold tracking-tighter" style="line-height: 1;">
                                                <span>I.R.</span><span>IRAN</span>
                                            </div>
                                        </div>
                                        <div class="flex-1 flex items-center justify-center gap-1.5 px-1.5 text-sm font-mono font-black tracking-wide">
                                            <span>{{ $plateParts[0] }}</span>
                                            <span class="font-sans font-black text-xs">{{ $plateParts[1] }}</span>
                                            <span>{{ $plateParts[2] }}</span>
                                        </div>
                                        <div class="border-l border-slate-900 flex flex-col items-center justify-center bg-[#fab800] text-slate-950 font-bold text-center" style="width: 32px; min-width: 32px; line-height: 1;">
                                            <span class="text-[7px]">ایران</span>
                                            <div class="w-full border-t border-slate-900 my-px"></div>
                                            <span class="text-[10px] font-mono">{{ $plateParts[3] }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="bg-slate-100 text-slate-500 px-3 py-1.5 rounded-lg text-xs font-bold">{{ $rawPlate ?: 'بدون پلاک' }}</span>
                                @endif
                                @endif
                            </td>
                            
                            <td class="p-4 font-bold text-slate-900">{{ number_format($item->total_amount) }}</td>
                            
                            <td class="p-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-black border {{ $requestTypeClass }}">{{ $requestTypeLabel }}</span>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border {{ $statusClass }}">{{ $statusText }}</span>
                                    @if($requestType === 'renewal' && !empty($item->previous_serial_number))
                                        <span class="text-[10px] font-mono text-indigo-600">شماره قبلی: {{ $item->previous_serial_number }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="p-4 text-center whitespace-nowrap">
                                <div class="flex flex-wrap justify-center gap-1.5">
                                    <button onclick="openModal('modal-{{ $item->id }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-colors shadow-md shadow-indigo-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        جزئیات
                                    </button>

                                    @if($item->status === 'issued')
                                        @if(empty($item->company_return_image))
                                            <button onclick='openReturnLashModal(@json($item->id), @json($item->d_code))' class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-colors shadow-md shadow-emerald-100">
                                                ثبت لاشه
                                            </button>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-lg text-xs font-black">
                                                کد پیک: {{ $item->courier_delivery_code ?? '---' }}
                                            </span>
                                        @endif

                                        <button onclick='reportLost(@json($item->id), @json($item->d_code))' class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition-colors shadow-md shadow-rose-100">
                                            مفقودی
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- مودال شیشه‌ای جزئیات کامل سفر --}}
                        <div id="modal-{{ $item->id }}" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4 sm:p-6 opacity-0 transition-opacity duration-300">
                            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-md" onclick="closeModal('modal-{{ $item->id }}')"></div>
                            
                            <div class="relative w-full max-w-3xl bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 overflow-hidden transform scale-95 transition-transform duration-300" id="modal-content-{{ $item->id }}">
                                <div class="bg-slate-50/80 border-b border-slate-200/60 p-5 flex justify-between items-center">
                                    <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                                        <span class="bg-indigo-100 p-1.5 rounded-lg text-sm"></span> جزئیات کامل سفر پرونده {{ $item->d_code }}
                                    </h3>
                                    <button onclick="closeModal('modal-{{ $item->id }}')" class="p-2 bg-white rounded-full hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-colors shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                
                                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-right">
                                        <div class="border border-slate-200/60 bg-slate-50/50 rounded-2xl p-4 relative flex flex-col justify-between min-h-[140px]">
                                            <div>
                                                <h4 class="font-black text-slate-800 text-base">
                                                    {{ $item->driver ? trim(($item->driver->first_name_fa ?? '') . ' ' . ($item->driver->last_name_fa ?? '')) : '---' }}
                                                </h4>
                                                <p class="text-xs font-bold text-slate-500 mt-1">(کد ملی: {{ $item->driver->national_code ?? '---' }})</p>
                                            </div>
                                            <div class="border-t border-slate-200/60 pt-3 mt-4 flex flex-col gap-1 text-xs text-slate-600 font-bold">
                                                <p>نام لاتین: <span class="font-mono uppercase tracking-wider text-slate-700">{{ $item->driver ? trim(($item->driver->first_name_en ?? '') . ' ' . ($item->driver->last_name_en ?? '')) : '---' }}</span></p>
                                                <p>شماره گذرنامه: <span class="text-slate-700 font-mono">{{ $item->driver->passport_number ?? '---' }}</span></p>
                                            </div>
                                        </div>

                                        <div class="border border-slate-200/60 bg-slate-50/50 rounded-2xl p-4 flex flex-col justify-between min-h-[140px]">
                                            <div class="flex justify-between items-start text-xs text-slate-600 font-bold">
                                                <p>نوع: <span class="text-slate-800">{{ optional($item->fleet)->truck_type ?? '---' }}</span></p>
                                                <p>کارت هوشمند: <span class="text-slate-700 font-mono">{{ optional($item->fleet)->smart_card_number ?? '---' }}</span></p>
                                            </div>

                                            <div class="flex justify-center my-2">
                                                <x-iran-plate :plate="optional($item->fleet)->transit_plate" size="md" />
                                                @if(false)
                                                @if(count($plateParts) == 4)
                                                    <div dir="ltr" class="inline-flex items-stretch border border-slate-900 rounded-xl bg-[#fab800] text-slate-950 font-black h-12 overflow-hidden shadow-md" style="min-width: 240px;">
                                                        <div class="bg-[#0033a0] flex flex-col items-center justify-between py-1 px-1 text-white border-r border-slate-900" style="width: 24px; min-width: 24px;">
                                                            <div class="w-full h-2 rounded-sm overflow-hidden flex flex-col" style="height: 6px;">
                                                                <div class="bg-[#228B22]" style="height: 33.33%;"></div><div class="bg-white" style="height: 33.33%;"></div><div class="bg-[#DA291C]" style="height: 33.33%;"></div>
                                                            </div>
                                                            <div class="flex flex-col items-center text-[5px] font-sans font-bold tracking-tighter" style="line-height: 1;">
                                                                <span>I.R.</span><span>IRAN</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex-1 flex items-center justify-center gap-4 px-3 text-xl font-mono font-black tracking-wide">
                                                            <span>{{ $plateParts[0] }}</span>
                                                            <span class="font-sans font-black text-lg text-slate-900">{{ $plateParts[1] }}</span>
                                                            <span>{{ $plateParts[2] }}</span>
                                                        </div>
                                                        <div class="border-l border-slate-900 flex flex-col items-center justify-center bg-[#fab800] text-slate-950 font-bold text-center" style="width: 44px; min-width: 44px; line-height: 1.1;">
                                                            <span class="text-[9px] text-slate-800">ایران</span>
                                                            <div class="w-full border-t border-slate-900 my-0.5"></div>
                                                            <span class="text-sm font-mono tracking-tight">{{ $plateParts[3] }}</span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="bg-slate-200 text-slate-800 px-3 py-1 rounded-xl font-mono font-bold">{{ $rawPlate ?: '---' }}</span>
                                                @endif
                                            </div>

                                            <div class="flex justify-between border-t border-slate-200/60 pt-2 text-[10px] font-bold text-slate-500">
                                                <span>اسب: <strong class="font-mono text-indigo-600">{{ optional($item->fleet)->transit_horse ?? '---' }}</strong></span>
                                                <span>یدک: <strong class="font-mono text-indigo-600">{{ optional($item->fleet)->transit_trailer ?? '---' }}</strong></span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- مقاصد سفر و فایل‌های پیوست بارگذاری‌شده --}}
                                    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                                        <p class="text-xs text-slate-700 font-black mb-3 flex items-center gap-1.5">
                                            <span class="w-1.5 h-3 bg-emerald-500 rounded-full"></span> 
                                            کشورهای مقصد سفر و مدارک پیوستی
                                        </p>
                                        
                                        <div class="space-y-3">
                                            @php
                                                $items = DB::table('permit_request_items')
                                                    ->where('permit_request_id', $item->id)
                                                    ->get();
                                            @endphp

                                            @forelse($items as $subItem)
                                                @php
                                                    $countryInfo = \App\Models\Country::find($subItem->country_id);
                                                @endphp
                                                <div class="flex justify-between items-center text-xs bg-slate-50 border border-slate-100 p-3 rounded-xl shadow-inner font-bold">
                                                    <span class="text-slate-700 flex items-center gap-1">
                                                        {{ $countryInfo->name ?? 'کشور مقصد' }} 
                                                        <span class="text-slate-400 text-[10px]">[{{ str_replace('_', '-', $subItem->permit_type) }}]</span>
                                                    </span>
                                                    
                                                    @if(!empty($subItem->document_path) || !empty($subItem->document))
                                                        <a href="{{ asset('storage/' . ($subItem->document_path ?? $subItem->document)) }}" target="_blank" class="flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg border border-emerald-200 transition-colors">
                                                            مشاهده مدرک پیوست
                                                        </a>
                                                    @else
                                                        <span class="text-slate-400 font-medium text-[11px]">بدون فایل پیوست</span>
                                                    @endif
                                                </div>
                                            @empty
                                                <p class="text-center text-xs text-slate-400 py-2">هیچ مسیر سفری برای این پرونده ثبت نشده است.</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- باکس وضعیت مالی و زمان درخواست --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        @php
                                            $isRefundedPayment = in_array($item->status, ['rejected', 'returned'], true) || $item->payment_status === 'refunded';
                                            $isReservedPayment = !$isRefundedPayment && $item->payment_status === 'reserved';
                                        @endphp
                                        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex flex-col justify-between">
                                            <span class="text-xs text-slate-400 font-bold block mb-1">وضعیت پرداخت تراکنش</span>
                                            <div class="flex justify-between items-center mt-2">
                                                <span class="text-base font-black text-slate-800 font-mono">{{ number_format($item->total_amount) }} ریال</span>
                                                @if($isRefundedPayment)
                                                    <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 text-xs font-bold">وجه عودت داده شده</span>
                                                @elseif($isReservedPayment)
                                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 text-xs font-bold">مبلغ بلوکه شده</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-xs font-bold">تسویه نهایی</span>
                                                @endif
                                                @endif
                                            </div>
                                        </div>
                                        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                                            <span class="text-xs text-slate-400 font-bold block mb-1">زمان دقیق ثبت درخواست</span>
                                            <div class="text-sm font-bold text-slate-700 mt-2">
                                                تاریخ: {{ \Hekmatinasser\Verta\Verta::instance($item->created_at)->format('Y/m/d') }}
                                                <span class="text-slate-400 px-1">|</span>
                                                ساعت: {{ \Hekmatinasser\Verta\Verta::instance($item->created_at)->format('H:i') }}
                                            </div>
                                        </div>
                                    </div>

                                    @if($item->company_note)
                                        <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-800 font-medium leading-relaxed">
                                             <strong>توضیحات پرونده:</strong> {{ $item->company_note }}
                                        </div>
                                    @endif
                                    @if($item->status === 'rejected' && $associationNote)
                                        <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800 font-medium leading-relaxed">
                                            <strong>توضیحات انجمن:</strong> {{ $associationNote }}
                                        </div>
                                    @endif
                                    @if(!empty($item->company_return_image))
                                        <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-800 font-medium leading-relaxed space-y-1">
                                            <div><strong>وضعیت لاشه:</strong> لاشه توسط شرکت ثبت و به پیک سپرده شده است.</div>
                                            <div><strong>پیک:</strong> {{ $item->courier_name ?? '---' }} | <span dir="ltr">{{ $item->courier_mobile ?? '---' }}</span></div>
                                            <div><strong>کد تحویل پیک:</strong> <span class="font-mono font-black">{{ $item->courier_delivery_code ?? '---' }}</span></div>
                                            <a href="{{ asset('storage/' . $item->company_return_image) }}" target="_blank" class="inline-flex mt-1 px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-black">
                                                مشاهده تصویر لاشه
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-12 text-slate-400 font-medium">
                                هیچ درخواست دوزوله‌ای با این مشخصات یافت نشد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- پجینیشن هر ۱۰ تا درخواست فیکس با فیلترها --}}
        @if($requests->hasPages())
            <div class="p-4 bg-slate-50 border-t border-slate-100">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>

{{-- توست اطلاع‌رسانی کپی کد رهگیری --}}
<div id="copy-toast" class="fixed top-5 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-2xl transition-all duration-300 opacity-0 pointer-events-none z-[200] flex items-center gap-2">
    <span></span> کد رهگیری با موفقیت کپی شد.
</div>

<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
    function copyToClipboard(text, element) {
        navigator.clipboard.writeText(text).then(function() {
            const toast = document.getElementById('copy-toast');
            const originalBg = element.className;
            
            element.className = element.className.replace('bg-slate-5', 'bg-emerald-50').replace('border-slate-20', 'border-emerald-300');
            toast.classList.remove('opacity-0');
            toast.classList.add('opacity-100', 'top-8');
            
            setTimeout(function() {
                toast.classList.remove('opacity-100', 'top-8');
                toast.classList.add('opacity-0', 'top-5');
                element.className = originalBg;
            }, 2000);
        }).catch(function(err) {
            console.error('خطا در کپی: ', err);
        });
    }

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        const content = document.getElementById(modalId.replace('modal-', 'modal-content-'));
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 15);
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        const content = document.getElementById(modalId.replace('modal-', 'modal-content-'));
        modal.classList.add('opacity-0');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 250);
    }

    document.getElementById('tableSearchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.closest('form').submit();
        }
    });

    function openReturnLashModal(id, dCode) {
        Swal.fire({
            title: 'ثبت لاشه دوزوله',
            html: `
                <div class="space-y-3 text-right" dir="rtl">
                    <p class="text-xs text-slate-500 font-bold">پرونده ${dCode}: عکس لاشه و مشخصات پیکی که لاشه را به انجمن تحویل می‌دهد ثبت می‌شود.</p>
                    <input id="return_lash_image" type="file" accept="image/*" class="w-full text-xs border border-slate-200 rounded-xl p-2">
                    <input id="courier_name" class="w-full text-sm border border-slate-200 rounded-xl p-2.5" placeholder="نام و نام خانوادگی پیک">
                    <input id="courier_mobile" class="w-full text-sm border border-slate-200 rounded-xl p-2.5 font-mono text-left" dir="ltr" placeholder="شماره موبایل پیک">
                    <input id="courier_national_code" class="w-full text-sm border border-slate-200 rounded-xl p-2.5 font-mono text-left" dir="ltr" placeholder="کد ملی پیک (اختیاری)">
                    <input id="courier_vehicle_plate" class="w-full text-sm border border-slate-200 rounded-xl p-2.5" placeholder="مشخصات وسیله/پلاک پیک (اختیاری)">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'ثبت و ساخت کد پیک',
            cancelButtonText: 'انصراف',
            confirmButtonColor: '#059669',
            preConfirm: () => {
                const image = document.getElementById('return_lash_image').files[0];
                const courierName = document.getElementById('courier_name').value.trim();
                const courierMobile = document.getElementById('courier_mobile').value.trim();

                if (!image || !courierName || !courierMobile) {
                    Swal.showValidationMessage('عکس لاشه، نام پیک و شماره موبایل پیک الزامی است.');
                    return false;
                }

                const formData = new FormData();
                formData.append('return_image', image);
                formData.append('courier_name', courierName);
                formData.append('courier_mobile', courierMobile);
                formData.append('courier_national_code', document.getElementById('courier_national_code').value.trim());
                formData.append('courier_vehicle_plate', document.getElementById('courier_vehicle_plate').value.trim());

                return fetch(`/web/company/dozbalagh/${id}/return-lash`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'ثبت لاشه انجام نشد.');
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(error.message);
                });
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                const smsStatus = result.value.sms_sent
                    ? '<div class="text-xs text-emerald-600 mt-2">کد برای شماره پیک پیامک شد.</div>'
                    : '<div class="text-xs text-amber-600 mt-2">کد ساخته شد اما ارسال پیامک تایید نشد؛ کد را به پیک اعلام کنید.</div>';
                Swal.fire({
                    icon: 'success',
                    title: 'لاشه ثبت شد',
                    html: `<div class="text-sm font-bold">کد تحویل پیک: <span class="font-mono text-emerald-700">${result.value.delivery_code}</span></div>${smsStatus}`,
                    confirmButtonText: 'متوجه شدم',
                    confirmButtonColor: '#059669'
                }).then(() => window.location.reload());
            }
        });
    }

    function reportLost(id, dCode) {
        Swal.fire({
            title: 'اعلام مفقودی لاشه',
            input: 'textarea',
            inputLabel: `توضیحات مفقودی پرونده ${dCode}`,
            inputPlaceholder: 'در صورت نیاز علت یا توضیح مفقودی را وارد کنید...',
            showCancelButton: true,
            confirmButtonText: 'ثبت مفقودی',
            cancelButtonText: 'انصراف',
            confirmButtonColor: '#dc2626',
            preConfirm: (reason) => {
                const formData = new FormData();
                formData.append('lost_reason', reason || '');

                return fetch(`/web/company/dozbalagh/${id}/report-lost`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'ثبت مفقودی انجام نشد.');
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(error.message);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ icon: 'success', title: 'مفقودی ثبت شد', text: result.value.message, confirmButtonColor: '#dc2626' })
                    .then(() => window.location.reload());
            }
        });
    }
</script>
@endsection
