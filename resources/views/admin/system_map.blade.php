@extends('layouts.admin')

@section('content')
<div class="p-6 bg-slate-950 min-h-screen text-slate-100 rounded-3xl border border-slate-800 shadow-2xl">
    
    {{-- هدر اصلی --}}
    <div class="mb-8 border-b border-slate-800 pb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-sky-400 to-indigo-400 flex items-center gap-3">
                <span>🗺️</span> شناسنامه فنی و نقشه راه زنده سامانه دوزبِلاغ
            </h1>
            <p class="text-xs text-slate-400 mt-1.5 font-bold leading-relaxed">
                این صفحه برای مانیتورینگ زنده اتصالات طراحی شده است. هر روت نشان می‌دهد کدهای بک‌اند و فایل‌های ویوی فرانت‌اند دقیقاً در کدام مسیر سرور لینوکس قرار دارند.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <span class="text-[10px] font-black bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1.5 rounded-xl">بومی پروژه (بدون CDN)</span>
            <span class="text-[10px] font-black bg-slate-800 text-slate-400 px-3 py-1.5 rounded-xl border border-slate-700">Laravel 11 & PHP 8.3</span>
        </div>
    </div>

    {{-- نوار جستجوی هوشمند --}}
    <div class="mb-6 relative">
        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500 text-base">
            <span>🔍</span>
        </div>
        <input type="text" id="tableSearchInput" onkeyup="liveSearchTable()" placeholder="جستجوی آنی در آدرس‌ها، عملکرد فارسی، نام فایل بلید یا کنترلر..." class="w-full bg-slate-900 border border-slate-800 rounded-2xl py-3.5 pr-11 pl-4 text-sm font-bold text-white placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all shadow-inner">
        <div id="searchBadge" class="absolute inset-y-0 left-0 pl-4 flex items-center hidden">
            <span class="text-[10px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20 px-2.5 py-1 rounded-lg">فیلتر شده</span>
        </div>
    </div>

    {{-- دکمه‌های ناوبری تب‌ها --}}
    <div class="flex flex-wrap gap-2 border-b border-slate-800 mb-6" id="tabsHeader">
        <button onclick="switchTab('company')" id="btn-company" class="tab-btn px-5 py-3 font-bold text-sm border-b-2 border-sky-500 text-sky-400 transition-all">
            🏢 پنل شرکت‌های حمل‌ونقل ({{ count($map['company']) }})
        </button>
        <button onclick="switchTab('association')" id="btn-association" class="tab-btn px-5 py-3 text-slate-400 text-sm font-bold hover:text-slate-200 transition-all">
            🏛️ کارتابل انجمن صنفی ({{ count($map['association']) }})
        </button>
        <button onclick="switchTab('admin')" id="btn-admin" class="tab-btn px-5 py-3 text-slate-400 text-sm font-bold hover:text-slate-200 transition-all">
            👑 ادمین کل سیستم ({{ count($map['admin']) }})
        </button>
        <button onclick="switchTab('shared')" id="btn-shared" class="tab-btn px-5 py-3 text-slate-400 text-sm font-bold hover:text-indigo-300 transition-all bg-indigo-950/10 rounded-t-2xl border border-b-0 border-indigo-900/20">
            🔗 کنترلرهای مشترک و هم‌پوشان ({{ count($map['shared']) }})
        </button>
    </div>

    {{-- لیست بخش‌ها --}}
    <div class="space-y-6">
        @foreach(['company', 'association', 'admin', 'shared'] as $portalType)
            <div id="portal-container-{{ $portalType }}" class="portal-content {{ $portalType === 'company' ? '' : 'hidden' }} bg-slate-900 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-slate-950 text-slate-400 font-black border-b border-slate-800">
                            <tr>
                                <th class="p-4 w-24 text-center">نوع عملیات</th>
                                <th class="p-4 text-amber-400 w-64">🎯 عملکرد و بیزینس صفحه</th>
                                <th class="p-4">آدرس دسترسی (URL)</th>
                                <th class="p-4 text-sky-400">📍 مسیر فایل کنترلر (بک‌اند)</th>
                                <th class="p-4 text-fuchsia-400">🎨 مسیر فایل قالب بلید (فرانت)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40 font-semibold text-slate-300">
                            @if(count($map[$portalType]) === 0)
                                <tr>
                                    <td colspan="5" class="p-12 text-center text-slate-500 font-bold text-sm">هیچ روتی در این کارتابل ثبت نشده است.</td>
                                </tr>
                            @else
                                @foreach($map[$portalType] as $item)
                                    <tr class="search-row hover:bg-slate-800/20 transition-colors {{ $item['is_shared'] ? 'bg-indigo-950/5' : '' }}">
                                        
                                        <td class="p-4 text-center">
                                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-black block w-max mx-auto border 
                                                {{ str_contains($item['method'], 'POST') ? 'bg-emerald-950/60 text-emerald-400 border-emerald-800/60' : '' }}
                                                {{ str_contains($item['method'], 'PUT') ? 'bg-blue-950/60 text-blue-400 border-blue-800/60' : '' }}
                                                {{ str_contains($item['method'], 'DELETE') ? 'bg-rose-950/60 text-rose-400 border-rose-800/60' : '' }}
                                                {{ str_contains($item['method'], 'GET') ? 'bg-slate-800/80 text-slate-300 border-slate-700' : '' }}
                                            ">
                                                {{ $item['method_type'] }}
                                            </span>
                                        </td>
                                        
                                        <td class="p-4 text-slate-100 font-bold text-xs search-field-desc">
                                            <div class="flex flex-col gap-1.5">
                                                <span>{{ $item['description'] }}</span>
                                                @if($item['is_shared'])
                                                    <span class="w-max px-2 py-0.5 rounded-md text-[8px] bg-indigo-500 text-white font-black">🔄 کنترلر مشترک</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="p-4 font-mono text-sky-400 tracking-wide text-left text-xs search-field-uri" dir="text-left">{{ $item['uri'] }}</td>
                                        
                                        {{-- مسیر کنترلر --}}
                                        <td class="p-4 font-mono text-emerald-400 text-[11px] text-left search-field-file" dir="text-left">
                                            <span class="bg-slate-950/60 px-2 py-1 rounded border border-slate-800/80 block w-max" title="{{ $item['action'] }}">{{ $item['file_path'] }}</span>
                                        </td>

                                        {{-- 🎨 ستون جدید: آدرس دقیق فایل بلید فرانت‌اند روی هارد لینوکس --}}
                                        <td class="p-4 font-mono text-fuchsia-400 text-[11px] text-left search-field-blade" dir="text-left">
                                            @if($item['blade_path'] !== 'بدون قالب (عملیات پردازشی/AJAX)')
                                                <span class="bg-fuchsia-950/30 px-2 py-1 rounded border border-fuchsia-900/40 block w-max font-bold shadow-sm">{{ $item['blade_path'] }}</span>
                                            @else
                                                <span class="text-slate-600 px-2 py-1 block w-max text-[10px]">⚙️ عملیات بک‌اند / بدون ویو</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    let currentActiveTab = 'company';

    function switchTab(portal) {
        currentActiveTab = portal;
        document.querySelectorAll('.portal-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.className = "tab-btn px-5 py-3 text-slate-400 text-sm font-bold hover:text-slate-200 transition-all";
        });

        document.getElementById('portal-container-' + portal).classList.remove('hidden');
        
        if(portal === 'shared') {
            document.getElementById('btn-shared').className = "tab-btn px-5 py-3 font-bold text-sm border-b-2 border-indigo-500 text-indigo-400 transition-all bg-indigo-950/30 rounded-t-2xl";
        } else {
            document.getElementById('btn-' + portal).className = "tab-btn px-5 py-3 font-bold text-sm border-b-2 border-sky-500 text-sky-400 transition-all";
            document.getElementById('btn-shared').className = "tab-btn px-5 py-3 text-slate-400 text-sm font-bold hover:text-indigo-300 transition-all bg-indigo-950/10 rounded-t-2xl border border-b-0 border-indigo-900/20";
        }

        liveSearchTable();
    }

    function liveSearchTable() {
        let input = document.getElementById('tableSearchInput');
        let filter = input.value.toLowerCase().trim();
        let badge = document.getElementById('searchBadge');
        
        let activeContainer = document.getElementById('portal-container-' + currentActiveTab);
        let rows = activeContainer.getElementsByClassName('search-row');

        if (filter === "") {
            badge.classList.add('hidden');
            for (let i = 0; i < rows.length; i++) {
                rows[i].style.display = "";
            }
            return;
        }

        badge.classList.remove('hidden');

        for (let i = 0; i < rows.length; i++) {
            let row = rows[i];
            
            let descText = row.getElementsByClassName('search-field-desc')[0]?.textContent || '';
            let uriText = row.getElementsByClassName('search-field-uri')[0]?.textContent || '';
            let fileText = row.getElementsByClassName('search-field-file')[0]?.textContent || '';
            let bladeText = row.getElementsByClassName('search-field-blade')[0]?.textContent || '';
            
            let combinedText = (descText + ' ' + uriText + ' ' + fileText + ' ' + bladeText).toLowerCase();

            if (combinedText.indexOf(filter) > -1) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }
    }
</script>
@endsection