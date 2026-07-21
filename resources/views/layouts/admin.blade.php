<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>{{ auth()->user()?->hasRole('admin') ? 'پنل مدیریت کل' : 'پنل انجمن' }} - سامانه جامع دوزوله</title>

    <script src="{{ asset('assets/js/tailwindcss.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/iran-plate.css') }}">
    @vite(['resources/js/app.js'])
    
    <style>
        /* فونت‌های محلی */
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ asset('assets/fonts/Vazirmatn-Regular.woff2') }}') format('woff2');
            font-weight: 400;
            font-display: swap;
        }
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ asset('assets/fonts/Vazirmatn-Bold.woff2') }}') format('woff2');
            font-weight: 700;
            font-display: swap;
        }
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ asset('assets/fonts/Vazirmatn-Black.woff2') }}') format('woff2');
            font-weight: 900;
            font-display: swap;
        }
        html, body, button, input, select, textarea {
            font-family: 'Vazirmatn', Tahoma, sans-serif;
        }
        body {
            background-color: #f8fafc; /* رنگ پس زمینه بسیار ملایم برای محتوا */
        }
        
        /* زیباسازی اسکرول‌بار برای حالت حرفه‌ای */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .sidebar-tree summary::-webkit-details-marker { display: none; }
        .sidebar-tree .tree-arrow { transition: transform .2s ease; }
        .sidebar-tree[open] .tree-arrow { transform: rotate(180deg); }

        #sidebar { width: 16rem; }
        @media (min-width: 768px) {
            #sidebar { position: relative; transform: none !important; width: 5rem; overflow: hidden; }
            #sidebar.sidebar-open { width: 16rem; }
            #sidebar > * { min-width: 16rem; }
            #sidebar:not(.sidebar-open) .h-24 { width: 5rem; min-width: 5rem; padding: 0; }
            #sidebar:not(.sidebar-open) .h-24 img { height: 3rem; }
            #sidebar:not(.sidebar-open) .h-24 div { display: none; }
            #sidebar:not(.sidebar-open) nav { width: 5rem; min-width: 5rem; padding-left: .75rem; padding-right: .75rem; overflow-x: hidden; }
            #sidebar:not(.sidebar-open) nav > a,
            #sidebar:not(.sidebar-open) nav > .pt-4 a { justify-content: center; gap: 0; padding-left: 0; padding-right: 0; font-size: 0; }
            #sidebar:not(.sidebar-open) nav > a svg,
            #sidebar:not(.sidebar-open) nav > .pt-4 a svg { width: 1.25rem; height: 1.25rem; }
            #sidebar:not(.sidebar-open) nav > div:not(.pt-4) button { justify-content: center; padding-left: 0; padding-right: 0; }
            #sidebar:not(.sidebar-open) nav > div:not(.pt-4) button > div { gap: 0; }
            #sidebar:not(.sidebar-open) nav > div:not(.pt-4) button > div span:last-child,
            #sidebar:not(.sidebar-open) nav > div:not(.pt-4) button > svg,
            #sidebar:not(.sidebar-open) #subDozbalaghMenu,
            #sidebar:not(.sidebar-open) #subSettingsMenu,
            #sidebar:not(.sidebar-open) nav > .pt-4 > div:first-child { display: none; }
            #sidebar:not(.sidebar-open) .sidebar-tree summary { justify-content: center; font-size: 0; padding-left: 0; padding-right: 0; }
            #sidebar:not(.sidebar-open) .sidebar-tree summary span:first-child { font-size: 1.1rem; }
            #sidebar:not(.sidebar-open) .sidebar-tree summary span:first-child { max-width: 1.5rem; overflow: hidden; white-space: nowrap; }
            #sidebar:not(.sidebar-open) .sidebar-tree .tree-arrow,
            #sidebar:not(.sidebar-open) .sidebar-tree > div { display: none; }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden transition-opacity md:hidden" onclick="toggleSidebar()"></div>

    <aside id="sidebar" class="sidebar-open fixed inset-y-0 right-0 z-50 bg-slate-900 text-slate-300 transform transition-all duration-300 translate-x-full flex flex-col shadow-2xl">
        
        <div class="h-24 border-b border-slate-800 flex items-center justify-center px-2 bg-slate-900/50">
            <img src="{{ asset('images/logo1.png') }}" alt="لوگو" class="h-16 w-auto drop-shadow-lg">
            <div class="mr-2 flex flex-col">
                <span class="text-white font-black text-sm tracking-wide">مدیریت دوزوله</span>
                <span class="text-slate-500 text-[10px] font-bold">{{ auth()->user()?->hasRole('admin') ? 'مدیریت کل سامانه' : 'انجمن خراسان رضوی' }}</span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1.5">
            @php
                $panelFeatures = app(\App\Services\PanelFeatureService::class);
                $showAssociationFeature = fn (string $feature) => auth()->user()?->hasRole('admin') || $panelFeatures->enabledForRole('association', $feature);
            @endphp
    
            @if(auth()->user()?->hasRole('association') && $showAssociationFeature('dashboard'))
            <a href="{{ route('association.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('association.dashboard') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">🏠 داشبورد انجمن</a>
            @endif

            @if(auth()->user()?->hasRole('admin') || $showAssociationFeature('admin_dashboard'))
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                داشبورد کل
            </a>
            @endif

            @if($showAssociationFeature('shahbaz_company_review'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('association.shahbaz.*', 'admin.shahbaz.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>🛡️ پرونده شرکت و شهباز</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-emerald-800 pr-2">
                    <a href="{{ route('association.shahbaz.companies.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('association.shahbaz.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">کنترل و بررسی شرکت‌ها</a>
                    @if(auth()->user()?->hasRole('admin'))
                    <a href="{{ route('admin.shahbaz.settings.edit') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.shahbaz.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">تنظیمات مراحل شهباز</a>
                    @endif
                </div>
            </details>
            @endif

            @php
                $associationCmrFeatures = [
                    'cmr_documents', 'cmr_issuance', 'cmr_master_data',
                    'cmr_company_settings', 'cmr_financial', 'cmr_reports',
                ];
                $showAssociationCmr = collect($associationCmrFeatures)->contains(
                    fn (string $feature) => $showAssociationFeature($feature)
                );
            @endphp
            @if($showAssociationCmr)
            @php
                $isCmrActive = request()->routeIs('admin.cmr.*');
            @endphp
            <div class="relative">
                <button type="button" onclick="toggleCmrMenu()" class="w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ $isCmrActive ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <span class="flex items-center gap-3"><span class="text-lg">▣</span><span>مدیریت e-CMR</span></span>
                    <svg id="arrowCmr" class="h-4 w-4 transform transition-transform {{ $isCmrActive ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="subCmrMenu" class="{{ $isCmrActive ? '' : 'hidden' }} mt-1 mr-3 space-y-1 border-r-2 border-emerald-700 pr-2">
                    @if($showAssociationFeature('cmr_documents'))
                    <a href="{{ route('admin.cmr.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.index') && !request('scope') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">کارتابل اسناد</a>
                    <a href="{{ route('admin.cmr.index',['scope'=>'drafts']) }}" class="block rounded-lg px-4 py-2 text-xs font-bold text-slate-400 hover:text-white">پیش‌نویس‌ها</a>
                    <a href="{{ route('admin.cmr.index',['scope'=>'active']) }}" class="block rounded-lg px-4 py-2 text-xs font-bold text-slate-400 hover:text-white">حمل‌های در جریان</a>
                    <a href="{{ route('admin.cmr.index',['scope'=>'archive']) }}" class="block rounded-lg px-4 py-2 text-xs font-bold text-slate-400 hover:text-white">تحویل‌شده و بایگانی</a>
                    @endif
                    @if($showAssociationFeature('cmr_issuance'))
                    <a href="{{ route('admin.cmr.create') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.create') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">صدور e-CMR</a>
                    <a href="{{ route('admin.cmr.help') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.help') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">راهنمای فارسی صدور</a>
                    @endif
                    @if($showAssociationFeature('cmr_master_data'))
                    <a href="{{ route('admin.cmr.master-data.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.master-data.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">اطلاعات پایه</a>
                    @endif
                    @if($showAssociationFeature('cmr_company_settings'))
                    <a href="{{ route('admin.cmr.company-settings.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.company-settings.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">شماره‌ها و قالب چاپ</a>
                    @endif
                    @if($showAssociationFeature('cmr_financial'))
                    <a href="{{ route('admin.cmr.settings') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.settings*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">تعرفه و مالی</a>
                    @endif
                    @if($showAssociationFeature('cmr_reports'))
                    <a href="{{ route('admin.cmr.reports.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.reports.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">گزارش‌های e-CMR</a>
                    @endif
                </div>
            </div>
            @endif

            @if($showAssociationFeature('requests') || $showAssociationFeature('issuance') || $showAssociationFeature('transit') || $showAssociationFeature('archive'))
            <div class="relative">
                @php 
                    $isDozbalaghActive = request()->routeIs('association.pending.*', 'association.approved.*', 'association.transit.*', 'association.archive.*');
                @endphp
                <button onclick="toggleDozbalaghMenu()" class="w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ $isDozbalaghActive ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">💼</span>
                        <span>کارتابل درخواست‌ها</span>
                    </div>
                    <svg id="arrowDozbalagh" class="w-4 h-4 transform transition-transform duration-200 {{ $isDozbalaghActive ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div id="subDozbalaghMenu" class="{{ $isDozbalaghActive ? '' : 'hidden' }} mt-1 mr-3 pr-2 border-r-2 border-slate-800 space-y-1">
                    @if($showAssociationFeature('requests'))
                    <a href="/web/association/driver/list" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/driver/list') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>⏳</span> درخواست‌های معلق
                    </a>
                    @endif
                    @if($showAssociationFeature('issuance'))
                    <a href="/web/association/approved/permits" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/approved/permits') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>✍️</span> صدور و تخصیص سریال
                    </a>
                    @endif
                    @if($showAssociationFeature('transit'))
                    <a href="/web/association/transit-permits" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/transit-permits') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>🚚</span> مدیریت تردد
                    </a>
                    @endif
                    @if($showAssociationFeature('archive'))
                    <a href="/web/association/permits/archive" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/permits/archive') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>🗂️</span> بایگانی کل پروانه‌ها
                    </a>
                    @endif
                </div>
            </div>
            @endif

            @if($showAssociationFeature('reports') || $showAssociationFeature('financial'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('association.reports.*', 'association.issued-financial.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>📊 گزارش‌های انجمن</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
            @if($showAssociationFeature('reports'))
            <a href="{{ route('association.reports.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('association.reports.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('association.reports.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                گزارش دوزوله‌ها
            </a>
            @endif

            @if($showAssociationFeature('financial'))
            <a href="{{ route('association.issued-financial.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('association.issued-financial.*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('association.issued-financial.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M5 7h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2zm2-4h10a2 2 0 012 2v2H5V5a2 2 0 012-2z"></path>
                </svg>
                مبالغ دوزوله‌های صادرشده
            </a>
            @endif
                </div>
            </details>
            @endif

            @if($showAssociationFeature('companies') || auth()->user()?->hasRole('admin') || $showAssociationFeature('crm'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('admin.companies.*', 'admin.association-users.*', 'admin.reports.pwa-installations.*', 'admin.association_crm.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>🏢 اعضا و ارتباطات</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
            @if($showAssociationFeature('companies'))
            <a href="{{ route('admin.companies.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.companies.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.companies.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                مدیریت شرکت‌ها
            </a>
            @endif

            @if(auth()->user()?->hasRole('admin'))
            <a href="{{ route('admin.association-users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.association-users.*') ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/30' : 'hover:bg-slate-800 hover:text-white' }}">👥 مدیریت کاربران انجمن</a>
            <a href="{{ route('admin.reports.pwa-installations.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.reports.pwa-installations.*') ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30' : 'hover:bg-slate-800 hover:text-white' }}">📱 وضعیت اپلیکیشن‌ها</a>
            @endif

            @if($showAssociationFeature('crm'))
            <a href="{{ route('admin.association_crm.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.association_crm.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.association_crm.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m-9 7l3.5-3.5H19a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10.5a2 2 0 002 2H4v3.5z"></path></svg>
                CRM انجمن
            </a>
            @endif
                </div>
            </details>
            @endif

            @if($showAssociationFeature('countries') || $showAssociationFeature('inventory') || $showAssociationFeature('allocations'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('admin.countries.*', 'admin.inventory.*', 'admin.allocations.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>📦 منابع و سهمیه</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
            @if($showAssociationFeature('countries'))
            <a href="{{ route('admin.countries.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.countries.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.countries.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                کشورها و مرزها
            </a>
            @endif

            @if($showAssociationFeature('inventory'))
            <a href="{{ route('admin.inventory.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.inventory.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.inventory.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                انبار سریال دوزوله
            </a>
            @endif

            @if($showAssociationFeature('allocations'))
            <a href="{{ route('admin.allocations.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.allocations.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.allocations.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                تخصیص سهمیه‌ها
            </a>
            @endif
                </div>
            </details>
            @endif

            @if($showAssociationFeature('drivers') || $showAssociationFeature('tracking') || $showAssociationFeature('fleets') || $showAssociationFeature('cargo_rules') || $showAssociationFeature('driver_announcements'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('admin.drivers.*', 'admin.tracking.*', 'admin.fleets.*', 'admin.cargo_rules.*', 'driver-announcements.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>🚚 رانندگان و ناوگان</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">

                @if($showAssociationFeature('drivers'))
                <a href="{{ route('admin.drivers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.drivers.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('admin.drivers.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    مدیریت رانندگان
                </a>
                @endif

                @if($showAssociationFeature('tracking'))
                <a href="{{ route('admin.tracking.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.tracking.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">📍 ردیابی رانندگان</a>
                @endif

                @if($showAssociationFeature('driver_announcements'))
                <a href="{{ route('driver-announcements.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('driver-announcements.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">📢 اطلاع‌رسانی به رانندگان</a>
                @endif

                @if($showAssociationFeature('fleets'))
                <a href="{{ route('admin.fleets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.fleets.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('admin.fleets.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    مدیریت ناوگان
                </a>
                @endif

                @if($showAssociationFeature('cargo_rules'))
                <a href="{{ route('admin.cargo_rules.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.cargo_rules.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('admin.cargo_rules.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    قوانین مدارک دوزوله
                </a>
                @endif
                </div>
            </details>
            @endif

            @if($showAssociationFeature('admin_financial') || $showAssociationFeature('system_map'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('admin.financial.*', 'system.map')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800 hover:text-white"><span>🛡️ مدیریت کل</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
            @if($showAssociationFeature('admin_financial'))
            <a href="{{ route('admin.financial.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.financial.*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white' }}">💳 مدیریت مالی کل</a>
            @endif

            @if($showAssociationFeature('system_map'))
            <a href="{{ route('system.map') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('system.map') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">🗺️ نقشه جامع سیستم</a>
            @endif
                </div>
            </details>
            @endif

            @if(auth()->user()?->hasRole('admin') || $showAssociationFeature('print_layouts') || $showAssociationFeature('driver_device_reset') || $showAssociationFeature('account_security'))
            <div class="relative pt-2 mt-2 border-t border-slate-800">
                @php $isSettingsActive = request()->routeIs('association.print-layouts.*', 'admin.settings.panel-features.*', 'admin.mobile-app.versions.*', 'driver-device-reset.*', 'association.account.security.*'); @endphp
                <button onclick="toggleSettingsMenu()" class="w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ $isSettingsActive ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <div class="flex items-center gap-3"><span class="text-lg">⚙️</span><span>تنظیمات</span></div>
                    <svg id="arrowSettings" class="w-4 h-4 transform transition-transform duration-200 {{ $isSettingsActive ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div id="subSettingsMenu" class="{{ $isSettingsActive ? '' : 'hidden' }} mt-1 mr-3 pr-2 border-r-2 border-slate-800 space-y-1">
                    @if($showAssociationFeature('print_layouts'))
                    <a href="{{ route('association.print-layouts.index') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('association.print-layouts.*') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}"><span>🖨️</span> تنظیمات چاپ دوزوله</a>
                    @endif
                    @if($showAssociationFeature('driver_device_reset'))
                    <a href="{{ route('driver-device-reset.index') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('driver-device-reset.*') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}"><span>📱</span> دستگاه اپلیکیشن رانندگان</a>
                    @endif
                    @if(auth()->user()?->hasRole('association') && $showAssociationFeature('account_security'))
                    <a href="{{ route('association.account.security.edit') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('association.account.security.*') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}"><span>🔐</span> تغییر رمز ورود</a>
                    @endif
                    @if(auth()->user()?->hasRole('admin'))
                    <a href="{{ route('admin.mobile-app.versions.index') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('admin.mobile-app.versions.*') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}"><span>🔄</span> نسخه و بروزرسانی اپ</a>
                    <a href="{{ route('admin.settings.panel-features.index') }}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->routeIs('admin.settings.panel-features.*') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}"><span>🔐</span> دسترسی پنل‌ها</a>
                    @endif
                </div>
            </div>
            @endif
        </nav>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-30 sticky top-0">
            
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="باز و بسته کردن منو" aria-controls="sidebar" aria-expanded="true" class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h1 class="text-lg sm:text-xl font-black text-slate-800 hidden sm:block">@yield('header_title', auth()->user()?->hasRole('admin') ? 'مدیریت کل سامانه' : 'پنل انجمن')</h1>
            </div>

            <div class="flex items-center gap-3 sm:gap-4">
                <a href="https://cits.rmto.ir" target="_blank" class="hidden lg:flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-sky-600 bg-slate-100 hover:bg-sky-50 px-3 py-2 rounded-lg transition-colors border border-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                    سایت دوزوله راهداری
                </a>
                
                <div class="hidden sm:flex items-center rounded-lg border border-slate-200 overflow-hidden shadow-sm">
                    <div class="bg-indigo-50/50 text-indigo-700 px-3 py-2 flex items-center gap-2 text-xs sm:text-sm font-bold border-l border-indigo-100/50">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        {{ verta()->format('%A %d %B %Y') }}
                    </div>
                    <div class="bg-slate-50 text-slate-500 px-3 py-2 flex items-center gap-1.5 text-xs font-bold font-mono" dir="ltr">
                        {{ now()->format('d M Y') }}
                    </div>
                </div>
                <div class="relative">
                    <button onclick="toggleDropdown()" id="userMenuButton" class="h-10 w-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 shadow-sm hover:bg-slate-200 cursor-pointer transition-colors focus:outline-none" title="پروفایل مدیر">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </button>
                    
                    <div id="userDropdown" class="hidden absolute left-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border border-slate-100 z-50 overflow-hidden transition-all">
                        <div class="bg-slate-50 p-4 border-b border-slate-100 flex items-center gap-3">
                            <div class="h-12 w-12 rounded-full bg-sky-100 border border-sky-200 flex items-center justify-center text-sky-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-800 text-sm">{{ auth()->user()?->hasRole('admin') ? 'مدیر سیستم' : 'کاربر انجمن' }}</span>
                                <span class="text-xs text-slate-500 mt-0.5">{{ auth()->user()?->hasRole('admin') ? 'ادمین کل' : 'انجمن' }}</span>
                            </div>
                        </div>
                        
                        <div class="p-2">
                            <button type="button" data-pwa-install class="hidden flex items-center gap-2 w-full px-3 py-2.5 text-sm text-sky-700 font-bold hover:bg-sky-50 rounded-xl transition">📲 نصب وب‌اپلیکیشن</button>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="flex items-center gap-2 w-full px-3 py-2.5 text-sm text-rose-600 font-bold hover:bg-rose-50 rounded-xl transition">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    خروج از سامانه
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 scroll-smooth relative">
            @yield('content')
        </div>
        
    </main>
    
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/iran-plate.js') }}"></script>
    <script src="{{ asset('assets/js/pwa-tracker.js') }}"></script>
    
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            const desktop = window.matchMedia('(min-width: 768px)').matches;
            if (desktop) {
                sidebar.classList.toggle('sidebar-open');
            } else {
                sidebar.classList.toggle('translate-x-full');
                overlay.classList.toggle('hidden');
            }
            const open = desktop ? sidebar.classList.contains('sidebar-open') : !sidebar.classList.contains('translate-x-full');
            document.querySelectorAll('[aria-controls="sidebar"]').forEach(button => button.setAttribute('aria-expanded', String(open)));
        }
        
        function toggleDropdown() {
            document.getElementById('userDropdown').classList.toggle('hidden');
        }

        function toggleCmrMenu() {
            const menu = document.getElementById('subCmrMenu');
            const arrow = document.getElementById('arrowCmr');
            menu.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
        }
        
        // 🔒 تابع باز و بسته شدن آکاردئونی منوی دوزبلاغ
        function toggleDozbalaghMenu() {
            const menu = document.getElementById('subDozbalaghMenu');
            const arrow = document.getElementById('arrowDozbalagh');
            
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                arrow.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        }

        function toggleSettingsMenu() {
            const menu = document.getElementById('subSettingsMenu');
            const arrow = document.getElementById('arrowSettings');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                arrow.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        }

        document.addEventListener('click', function(event) {
            const button = document.getElementById('userMenuButton');
            const dropdown = document.getElementById('userDropdown');
            if (button && dropdown && !button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
    
    @if(request()->routeIs('admin.cmr.*'))
        @include('CMR.admin.partials.feedback')
    @endif
    @yield('scripts')
</body>
</html>
