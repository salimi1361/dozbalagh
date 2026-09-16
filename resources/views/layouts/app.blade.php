<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>پنل شرکت - سامانه جامع دوزوله </title>
    
    <link class="hidden" rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <script src="{{ asset('assets/js/tailwindcss.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/iran-plate.css') }}">
    <style>
        @font-face { font-family: 'Vazirmatn'; src: url('{{ asset('assets/fonts/Vazirmatn-Regular.woff2') }}') format('woff2'); font-weight: 400; font-display: swap; }
        @font-face { font-family: 'Vazirmatn'; src: url('{{ asset('assets/fonts/Vazirmatn-Bold.woff2') }}') format('woff2'); font-weight: 700; font-display: swap; }
        @font-face { font-family: 'Vazirmatn'; src: url('{{ asset('assets/fonts/Vazirmatn-Black.woff2') }}') format('woff2'); font-weight: 900; font-display: swap; }
        html, body, button, input, select, textarea { font-family: 'Vazirmatn', Tahoma, sans-serif; }
        body { background-color: #f8fafc; }
        .company-page-header { position: relative; overflow: hidden; background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%) !important; color: #fff; box-shadow: 0 12px 25px rgba(15, 23, 42, .18); }
        .company-page-header::before, .company-page-header::after { content: ''; position: absolute; border-radius: 9999px; background: rgba(255,255,255,.09); pointer-events: none; }
        .company-page-header::before { width: 10rem; height: 10rem; left: -3rem; top: -4rem; }
        .company-page-header::after { width: 9rem; height: 9rem; right: 44%; bottom: -5rem; background: rgba(56,189,248,.12); }
        .company-page-header > * { position: relative; z-index: 1; }
        .company-page-header h1 { color: #fff !important; }
        .company-page-header p { color: #dbeafe !important; }
        .company-header-back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.75rem;
            padding: .65rem 1rem;
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: .8rem;
            background: rgba(255, 255, 255, .13);
            color: #e0f2fe !important;
            font-size: .9375rem !important;
            font-weight: 900 !important;
            line-height: 1.5;
            text-decoration: none !important;
            white-space: nowrap;
            box-shadow: 0 3px 8px rgba(2, 6, 23, .12);
            transition: background-color .2s ease, border-color .2s ease, color .2s ease, transform .2s ease;
        }
        .company-header-back-link:hover {
            border-color: rgba(255, 255, 255, .55);
            background: rgba(255, 255, 255, .23);
            color: #fff !important;
            transform: translateY(-1px);
        }
        .company-header-back-link:focus-visible {
            outline: 3px solid rgba(125, 211, 252, .95);
            outline-offset: 2px;
        }
        @media (max-width: 640px) {
            .company-header-back-link { min-height: 2.6rem; padding: .55rem .85rem; font-size: .875rem !important; }
        }        .active-menu { background-color: #ecfdf5; color: #059669; border-right: 4px solid #059669; }
        /* یک معیار واحد برای وضعیت انتخاب‌شده‌ی همه‌ی آیتم‌ها و گروه‌ها */
        #sidebar nav a[class*="bg-sky-500"],
        #sidebar nav a[class*="bg-emerald-600"],
        #sidebar nav a[class*="bg-violet-600"],
        #sidebar nav a[class*="bg-cyan-600"],
        #sidebar nav a[class*="active-menu"],
        #sidebar nav a[class*="bg-slate-800/50"] {
            background: #0ea5e9 !important;
            color: #fff !important;
            border-right: 4px solid #7dd3fc;
            box-shadow: none !important;
        }
        #sidebar nav a[class*="bg-sky-500"] svg,
        #sidebar nav a[class*="bg-emerald-600"] svg,
        #sidebar nav a[class*="bg-violet-600"] svg,
        #sidebar nav a[class*="bg-cyan-600"] svg,
        #sidebar nav a[class*="active-menu"] svg { color: #fff !important; }
        #sidebar .sidebar-tree[open] > summary {
            background: transparent;
            color: inherit;
            border-right: 0;
        }
        #sidebar nav a[class*="bg-slate-800/50"] {
            background: transparent !important;
            color: #34d399 !important;
            border-right: 0;
            box-shadow: none !important;
        }
        /* لینک فعالِ داخل هر گروه همیشه به‌صورت انتخاب متنی نمایش داده شود */
        #sidebar nav .sidebar-tree > div a[class*="bg-sky-500"],
        #sidebar nav .sidebar-tree > div a[class*="bg-emerald-600"],
        #sidebar nav .sidebar-tree > div a[class*="bg-violet-600"],
        #sidebar nav .sidebar-tree > div a[class*="bg-cyan-600"],
        #sidebar nav .sidebar-tree > div a[class*="active-menu"] {
            background: transparent !important;
            color: #34d399 !important;
            border-right: 0;
            box-shadow: none !important;
        }
        /* پیمایش منو حفظ می‌شود، اما نوار اسکرول مزاحم نمایش داده نمی‌شود */
        #sidebar nav { scrollbar-width: none; -ms-overflow-style: none; }
        #sidebar nav::-webkit-scrollbar { display: none; }
        .sidebar-footer { background: transparent; }
        .analog-clock { position: relative; width: 6.25rem; height: 6.25rem; flex: 0 0 auto; border: 3px solid #dbeafe; border-radius: 9999px; background: #fff; box-shadow: 0 6px 16px rgba(2, 6, 23, .3), inset 0 0 0 1px #cbd5e1; }
        .clock-face { position: absolute; inset: 0; width: 100%; height: 100%; }
        .clock-hand { position: absolute; left: 50%; bottom: 50%; width: 3px; border-radius: 9999px; transform-origin: 50% 100%; }
        .clock-hour { height: 23%; width: 4px; background: #050505; }
        .clock-minute { height: 32%; width: 3px; background: #050505; }
        .clock-second { height: 38%; width: 1px; background: #ef4444; }
        .clock-pin { position: absolute; left: 50%; top: 50%; width: 9px; height: 9px; border: 2px solid #fff; border-radius: 9999px; background: #ef4444; transform: translate(-50%, -50%); box-shadow: 0 1px 3px rgba(15, 23, 42, .35); }
        .sidebar-version { display: flex; width: 8rem; align-items: center; justify-content: center; gap: .5rem; white-space: nowrap; font-size: .68rem; font-weight: 900; letter-spacing: .015em; }
        .sidebar-version::before, .sidebar-version::after { content: ''; height: 1px; flex: 1; background: linear-gradient(90deg, transparent, #34d399); opacity: .65; }
        .sidebar-version::after { transform: scaleX(-1); }
        #companyHeaderTicker { min-width: 0; overflow: hidden !important; isolation: isolate; }
        .company-header-ticker-track { position: relative; display: block; width: 100%; height: 1.5rem; direction: ltr; }
        .company-header-ticker-segment { position: absolute; top: 50%; left: 0; display: inline-flex; align-items: center; width: max-content; min-width: max-content; padding: 0 1.5rem; direction: ltr; transform: translate3d(0, -50%, 0); box-sizing: border-box; white-space: nowrap; }
        .company-header-ticker-content { display: inline-flex; flex: 0 0 auto; align-items: center; gap: 1.5rem; direction: rtl; }
        .company-header-ticker-item { display: inline-flex; align-items: center; gap: .75rem; direction: rtl; }
        .company-header-ticker-icon { line-height: 1; }
        #sidebar { width: 16rem; }
        @media (min-width: 768px) {
            #sidebar { position: relative; transform: none !important; width: 5rem; overflow: hidden; }
            #sidebar.sidebar-open { width: 16rem; }
            #sidebar > * { min-width: 16rem; }
            #sidebar:not(.sidebar-open) .h-40 { width: 5rem; min-width: 5rem; height: 6rem; padding: 0; }
            #sidebar:not(.sidebar-open) .h-40 img { height: 3.5rem; }
            #sidebar:not(.sidebar-open) .h-40 span { display: none; }
            #sidebar:not(.sidebar-open) nav { width: 5rem; min-width: 5rem; padding-left: .75rem; padding-right: .75rem; overflow-x: hidden; }
            #sidebar:not(.sidebar-open) nav > a { justify-content: center; padding-left: 0; padding-right: 0; font-size: 0; }
            #sidebar:not(.sidebar-open) nav > a::before,
            #sidebar:not(.sidebar-open) nav > div button::before { content: ''; width: 1.35rem; height: 1.35rem; background: center / contain no-repeat; opacity: .9; }
            #sidebar:not(.sidebar-open) nav > a:nth-child(1)::before { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%2394a3b8' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M3 11l9-8 9 8v9a1 1 0 01-1 1h-5v-7H9v7H4a1 1 0 01-1-1z'/%3E%3C/svg%3E"); }
            #sidebar:not(.sidebar-open) nav > a:nth-child(2)::before { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%2394a3b8' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M12 2l4 7 6 3-6 3-4 7-4-7-6-3 6-3z'/%3E%3C/svg%3E"); }
            #sidebar:not(.sidebar-open) nav > a:nth-child(3)::before { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%2394a3b8' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='12' cy='7' r='4'/%3E%3Cpath d='M4 21v-2a8 8 0 0116 0v2'/%3E%3C/svg%3E"); }
            #sidebar:not(.sidebar-open) nav > a:nth-child(4)::before,
            #sidebar:not(.sidebar-open) nav > a:nth-child(5)::before { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%2394a3b8' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M21 15a4 4 0 01-4 4H8l-5 3V7a4 4 0 014-4h10a4 4 0 014 4z'/%3E%3C/svg%3E"); }
            #sidebar:not(.sidebar-open) nav > a:nth-child(6)::before { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%2394a3b8' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M3 6h12v11H3zM15 10h4l2 3v4h-6z'/%3E%3Ccircle cx='7' cy='19' r='2'/%3E%3Ccircle cx='18' cy='19' r='2'/%3E%3C/svg%3E"); }
            #sidebar:not(.sidebar-open) nav > a:nth-child(7)::before { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%2394a3b8' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M4 20V10M10 20V4M16 20v-7M22 20H2'/%3E%3C/svg%3E"); }
            #sidebar:not(.sidebar-open) nav > div { padding-top: .5rem; margin-top: .5rem; }
            #sidebar:not(.sidebar-open) nav > div button { justify-content: center; padding-left: 0; padding-right: 0; font-size: 0; }
            #sidebar:not(.sidebar-open) nav > div button::before { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%2394a3b8' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='12' cy='12' r='3'/%3E%3Cpath d='M19.4 15a2 2 0 00.4 2.2l.1.1-2.6 2.6-.1-.1a2 2 0 00-2.2-.4 2 2 0 00-1.2 1.8V21h-3.6v-.2A2 2 0 009 19a2 2 0 00-2.2.4l-.1.1-2.6-2.6.1-.1a2 2 0 00.4-2.2A2 2 0 002.8 13H3V9.4h-.2A2 2 0 004.6 8a2 2 0 00-.4-2.2l-.1-.1 2.6-2.6.1.1A2 2 0 009 3.6 2 2 0 0010.2 2h3.6A2 2 0 0015 3.6a2 2 0 002.2-.4l.1-.1 2.6 2.6-.1.1a2 2 0 00-.4 2.2 2 2 0 001.8 1.2h.2v3.6h-.2A2 2 0 0019.4 15z'/%3E%3C/svg%3E"); }
            #sidebar:not(.sidebar-open) #submenu-profile { display: none !important; }
            #sidebar:not(.sidebar-open) .sidebar-tree summary { justify-content: center; padding-left: 0; padding-right: 0; font-size: 0; }
            #sidebar:not(.sidebar-open) .sidebar-tree summary .group-icon { font-size: 1.2rem; }
            #sidebar:not(.sidebar-open) .sidebar-tree summary .tree-arrow,
            #sidebar:not(.sidebar-open) .sidebar-tree > div { display: none; }
            #sidebar:not(.sidebar-open) .sidebar-footer { min-width: 5rem; padding: .75rem 0; }
            #sidebar:not(.sidebar-open) .analog-clock { width: 3.25rem; height: 3.25rem; border-width: 3px; }
            #sidebar:not(.sidebar-open) .sidebar-version { display: none; }

            /* Desktop sidebar is temporarily fixed open. */
            #sidebar,
            #sidebar.sidebar-open { width: 16rem; overflow: visible; }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden transition-opacity md:hidden" onclick="toggleSidebar()"></div>

    <aside id="sidebar" class="sidebar-open fixed inset-y-0 right-0 z-50 bg-slate-900 text-slate-300 transform transition-all duration-300 translate-x-full flex flex-col shadow-2xl">
        <div class="h-40 border-b border-slate-800 flex flex-col items-center justify-center px-4 bg-slate-900/50">
            <img src="{{ asset('images/logo1.png') }}" alt="لوگو" class="h-28 w-auto drop-shadow-lg">
            <span class="text-white font-black text-xs mt-4 tracking-widest">پنل شرکت حمل و نقل</span>
        </div>

        <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1">
            @php
                $panelFeatures = app(\App\Services\PanelFeatureService::class);
                $companyEligibility = app(\App\Shahbaz\Services\CompanyEligibilityService::class);
                $companyCanOperate = auth()->user()?->company
                    ? $companyEligibility->canOperate(auth()->user()->company)
                    : false;
            @endphp
            @unless($companyCanOperate)
                <div class="mb-3 rounded-xl border border-amber-700/40 bg-amber-950/40 px-4 py-3 text-xs font-bold leading-6 text-amber-200">
                    تا تأیید نهایی پرونده شحباز، منوهای عملیاتی شرکت بسته هستند.
                </div>
            @endunless
            @if($companyCanOperate)
            @if($panelFeatures->enabledForRole('company', 'dashboard'))
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm {{ request()->routeIs('dashboard') ? 'active-menu bg-emerald-900 text-emerald-400' : 'hover:bg-slate-800' }}">🏠 داشبورد</a>
            @endif
            
            @if($panelFeatures->enabledForRole('company', 'requests') || $panelFeatures->enabledForRole('company', 'issued'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('dozbalagh.*', 'company.dozbalagh.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800"><span><span class="group-icon">📄</span> دوزوله</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
                    @if($panelFeatures->enabledForRole('company', 'requests'))
                    <a href="{{ route('dozbalagh.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('dozbalagh.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">درخواست‌ها و ثبت دوزوله</a>
                    <a href="{{ route('company.dozbalagh.transfers') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.dozbalagh.transfers') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">دوزوله‌های انتقالی</a>
                    @endif
                    @if($panelFeatures->enabledForRole('company', 'issued'))
                    <a href="{{ route('company.dozbalagh.issued') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.dozbalagh.issued') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">دوزوله‌های صادرشده</a>
                    @endif
                </div>
            </details>
            @endif

            @if($panelFeatures->enabledForRole('company', 'drivers') || $panelFeatures->enabledForRole('company', 'fleets') || $panelFeatures->enabledForRole('company', 'tracking') || $panelFeatures->enabledForRole('company', 'driver_device_reset'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('web.company.driver.*', 'web.company.fleet.*', 'company.tracking.*', 'driver-device-reset.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800"><span><span class="group-icon">🚛</span> رانندگان و ناوگان</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
                    @if($panelFeatures->enabledForRole('company', 'drivers'))<a href="{{ route('web.company.driver.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('web.company.driver.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">رانندگان</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'fleets'))<a href="{{ route('web.company.fleet.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('web.company.fleet.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">ناوگان</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'tracking'))<a href="{{ route('company.tracking.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.tracking.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">ردیابی ناوگان</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'driver_device_reset'))<a href="{{ route('driver-device-reset.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('driver-device-reset.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">دستگاه رانندگان</a>@endif
                </div>
            </details>
            @endif

            @if($panelFeatures->enabledForRole('company', 'driver_messages') || $panelFeatures->enabledForRole('company', 'driver_announcements') || $panelFeatures->enabledForRole('company', 'association_crm'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('company.driver_messages.*', 'driver-announcements.*', 'company.association_crm.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800"><span><span class="group-icon">💬</span> پیام‌ها و ارتباطات</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
                    @if($panelFeatures->enabledForRole('company', 'driver_messages'))<a href="{{ route('company.driver_messages.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.driver_messages.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">پیام رانندگان</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'driver_announcements'))<a href="{{ route('driver-announcements.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('driver-announcements.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">اطلاع‌رسانی به رانندگان</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'association_crm'))<a href="{{ route('company.association_crm.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.association_crm.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">پشتیبانی انجمن</a>@endif
                </div>
            </details>
            @endif

            @if($panelFeatures->enabledForRole('company', 'reports'))
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('report.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800"><span><span class="group-icon">📊</span> گزارش‌ها</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
                    <a href="{{ route('report.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('report.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">گزارش‌های دوزوله</a>
                </div>
            </details>
            @endif

            @php
                $companyCmrFeatures = [
                    'cmr_documents', 'cmr_issuance', 'cmr_master_data',
                    'cmr_company_settings', 'cmr_financial', 'cmr_reports',
                ];
                $showCompanyCmr = collect($companyCmrFeatures)->contains(
                    fn (string $feature) => $panelFeatures->enabledForRole('company', $feature)
                );
            @endphp
            @if($showCompanyCmr)
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('admin.cmr.*', 'company.cmr-reports.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800"><span><span class="group-icon">▣</span> CMR</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
                    @if($panelFeatures->enabledForRole('company', 'cmr_documents'))<a href="{{ route('admin.cmr.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.index', 'admin.cmr.show', 'admin.cmr.edit') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">کارتابل اسناد</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'cmr_issuance'))<a href="{{ route('admin.cmr.create') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.create') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">صدور e-CMR</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'cmr_master_data'))<a href="{{ route('admin.cmr.master-data.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.master-data.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">اطلاعات پایه</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'cmr_company_settings'))<a href="{{ route('admin.cmr.company-settings.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.company-settings.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">شماره‌ها و قالب چاپ</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'cmr_financial'))<a href="{{ route('admin.cmr.settings') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.cmr.settings*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">تعرفه و امور مالی</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'cmr_reports'))<a href="{{ route('company.cmr-reports.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.cmr-reports.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">گزارش‌های e-CMR</a>@endif
                </div>
            </details>
            @endif
            @endif

            @php
                $companyShahbazFeatures = [
                    'shahbaz_overview', 'shahbaz_profile', 'shahbaz_requests', 'shahbaz_people',
                    'shahbaz_fleet', 'shahbaz_facilities', 'shahbaz_gazettes', 'shahbaz_registration',
                    'shahbaz_licenses', 'shahbaz_branches', 'shahbaz_manual_status',
                    'shahbaz_misc_documents', 'shahbaz_workflow',
                ];
                $showCompanyShahbaz = collect($companyShahbazFeatures)->contains(
                    fn (string $feature) => $panelFeatures->enabledForRole('company', $feature)
                );
            @endphp
            @if($showCompanyShahbaz)
            <details class="sidebar-tree rounded-xl" @if(request()->routeIs('company.shahbaz.*')) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800"><span><span class="group-icon">🛡️</span> شحباز</span><span class="tree-arrow text-xs">⌄</span></summary>
                <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_overview'))<a href="{{ route('company.shahbaz.dossier.show') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.dossier.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">خانه پرونده</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_profile'))<a href="{{ route('company.shahbaz.profile.edit') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.profile.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">مشخصات پایه شرکت</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_requests'))<a href="{{ route('company.shahbaz.requests.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.requests.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">درخواست‌های پروانه فعالیت</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_people'))<a href="{{ route('company.shahbaz.people.index', ['type' => 'personnel']) }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.people.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">مدیران، اعضا و سهامداران</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_fleet'))<a href="{{ route('company.shahbaz.fleet.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.fleet.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">پرونده ناوگان</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_facilities'))<a href="{{ route('company.shahbaz.facilities.edit') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.facilities.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">امکانات و محل فعالیت</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_gazettes'))<a href="{{ route('company.shahbaz.gazettes.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.gazettes.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">روزنامه رسمی</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_registration'))<a href="{{ route('company.shahbaz.registration.edit') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.registration.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">اطلاعات ثبتی</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_licenses'))<a href="{{ route('company.shahbaz.licenses.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.licenses.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">پروانه‌های فعالیت</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_branches'))<a href="{{ route('company.shahbaz.branches.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.branches.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">مجوز شعب</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_manual_status'))<a href="{{ route('company.shahbaz.manual-status.show') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.manual-status.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">وضعیت استعلام دستی</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_misc_documents'))<a href="{{ route('company.shahbaz.misc-documents.index') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.misc-documents.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">سایر مدارک</a>@endif
                    @if($panelFeatures->enabledForRole('company', 'shahbaz_workflow'))<a href="{{ route('company.shahbaz.workflow.show') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.shahbaz.workflow.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">گردش کار پرونده</a>@endif
                </div>
            </details>
            @endif
            
            @if($panelFeatures->enabledForRole('company', 'profile'))
            <div class="pt-4 mt-4 border-t border-slate-800">
                <details class="sidebar-tree rounded-xl" @if(request()->routeIs('company.profile.*', 'company.shahbaz.profile.*', 'company.password.*')) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-4 py-3 text-sm font-bold hover:bg-slate-800"><span><span class="group-icon">⚙️</span> تنظیمات شرکت</span><span class="tree-arrow text-xs">⌄</span></summary>
                    <div class="mt-1 mr-3 space-y-1 border-r-2 border-slate-800 pr-2">
                        <a href="{{ route('company.shahbaz.profile.edit') }}" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.profile.*', 'company.shahbaz.profile.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">اطلاعات شرکت و تکمیل پرونده</a>
                        <a href="{{ route('company.password.edit') }}#change-password" class="block rounded-lg px-4 py-2 text-xs font-bold {{ request()->routeIs('company.password.*') ? 'bg-slate-800/50 text-emerald-400' : 'text-slate-400 hover:text-white' }}">تنظیمات امنیتی و تغییر رمز</a>
                    </div>
                </details>
                </div>
            @endif
        </nav>
        <footer class="sidebar-footer shrink-0 px-4 py-3">
            <div class="flex flex-col items-center justify-center gap-2">
                <div class="analog-clock" role="img" aria-label="ساعت عقربه‌ای زمان محلی">
                    <svg class="clock-face" viewBox="0 0 100 100" aria-hidden="true"><g fill="#111827" font-family="Arial, sans-serif" font-size="11" text-anchor="middle"><text x="50" y="14">12</text><text x="70" y="20">1</text><text x="84" y="35">2</text><text x="90" y="54">3</text><text x="83" y="73">4</text><text x="68" y="87">5</text><text x="50" y="94">6</text><text x="31" y="87">7</text><text x="17" y="73">8</text><text x="10" y="54">9</text><text x="16" y="35">10</text><text x="30" y="20">11</text></g></svg>
                    <span class="clock-hand clock-hour" data-clock-hour></span><span class="clock-hand clock-minute" data-clock-minute></span><span class="clock-hand clock-second" data-clock-second></span><span class="clock-pin"></span>
                </div>
                <div class="sidebar-version text-center text-[10px] font-bold text-emerald-400">نسخه آزمایشی</div>
            </div>
        </footer>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        @php
            $user = auth()->user();
            $companyId = $user->company_id ?? $user->company->id ?? null;
            $ceoName = $user->company->ceo_name ?? $user->name ?? 'مدیر شرکت';
            $companyName = $user->company->name_fa ?? $user->company->name ?? 'شرکت حمل و نقل';
            $companyNationalId = $user->company->national_id ?? null;
            
            $walletBalance = 0;
            if($companyId) {
                $headerWallet = \App\Models\Wallet::firstOrCreate(['company_id' => $companyId]);
                $walletBalance = $headerWallet->balance; // تغییر نام ستون به balance جهت هماهنگی نهایی دیتابیس
            }
            $headerTickerMessages = [];
            $headerTickerTableExists = \Illuminate\Support\Facades\Schema::hasTable('company_header_ticker_items');
            if ($headerTickerTableExists) {
                $headerTickerMessages = \App\Models\CompanyHeaderTickerItem::query()
                    ->visibleNow()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->pluck('message')
                    ->map(fn ($message) => trim((string) $message))
                    ->filter()
                    ->values()
                    ->all();
            }
            if (! $headerTickerTableExists) {
                $rawHeaderTicker = \App\Models\SystemSetting::getValue('company_header_ticker', '{}');
                $headerTicker = is_array($rawHeaderTicker) ? $rawHeaderTicker : json_decode((string) $rawHeaderTicker, true);
                if (is_array($headerTicker) && ($headerTicker['is_active'] ?? false) && trim((string) ($headerTicker['message'] ?? '')) !== '') {
                    $headerTickerMessages = [trim((string) $headerTicker['message'])];
                }
            }
            $headerTickerMessage = implode('   •   ', $headerTickerMessages);
        @endphp

        <header class="h-24 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between gap-4 px-4 sm:px-6 lg:px-8 z-30 sticky top-0">
            <div class="flex shrink-0 items-center gap-4">
                <button onclick="toggleSidebar()" aria-label="باز و بسته کردن منو" aria-controls="sidebar" aria-expanded="true" class="md:hidden shrink-0 p-2 text-slate-500 hover:bg-slate-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>

                <h1 class="min-w-0 truncate text-sm sm:text-base font-black text-slate-800">@yield('header_title', 'سامانه مدیریت دوزبلاغ')</h1>
            </div>

            <div id="companyHeaderTicker" data-feed-url="{{ route('company.header-ticker.feed') }}" class="company-header-ticker {{ $headerTickerMessage === '' ? 'hidden' : 'md:flex' }} min-w-0 flex-1 items-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 shadow-sm" role="status" aria-live="polite" aria-label="پیام مهم انجمن">
                    <div data-ticker-track class="company-header-ticker-track whitespace-nowrap text-xs font-black text-slate-700">
                        @for($tickerCopy = 0; $tickerCopy < 2; $tickerCopy++)
                            <span class="company-header-ticker-segment" @if($tickerCopy === 1) aria-hidden="true" @endif>
                                <span class="company-header-ticker-content">
                                    @foreach($headerTickerMessages as $tickerIndex => $tickerItemMessage)
                                        <span class="company-header-ticker-item">
                                            @if($tickerIndex === 0)<span aria-hidden="true" class="company-header-ticker-icon">📢</span>@endif
                                            @if($tickerIndex > 0)<span class="text-slate-400" aria-hidden="true">•</span>@endif
                                            <span>{{ $tickerItemMessage }}</span>
                                        </span>
                                    @endforeach
                                </span>
                            </span>
                        @endfor
                    </div>
                </div>

            <div class="flex shrink-0 items-center gap-3 sm:gap-4">
                <a href="https://cits.rmto.ir" target="_blank" class="hidden lg:flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-sky-600 bg-slate-100 hover:bg-sky-50 px-3 py-2 rounded-lg transition-colors border border-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 0114-9"></path></svg>
                    سایت دوزوله راهداری
                </a>
                
                <div class="hidden sm:flex items-center rounded-lg border border-slate-200 overflow-hidden shadow-sm">
                    <div class="bg-emerald-50 text-emerald-700 px-3 py-2 flex items-center gap-1.5 text-xs font-bold border-l border-emerald-100">
                        <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        {{ verta()->format('%A %d %B %Y') }}
                    </div>
                    <div class="bg-slate-50 text-slate-500 px-3 py-2 flex items-center gap-1.5 text-[11px] font-bold font-mono" dir="ltr">
                        {{ now()->format('d M Y') }}
                    </div>
                </div>
                @if($panelFeatures->enabledForRole('company', 'wallet'))
                <a href="{{ route('company.wallet.index') }}" class="group flex items-center bg-white border border-slate-200 hover:border-blue-300 hover:shadow-md rounded-full p-1 transition duration-300">
                    
                    <button type="button" class="bg-emerald-100 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-lg transition duration-300 ml-1">
                        +
                    </button>

                    <div class="flex flex-col justify-center text-center px-2">
                        <span class="text-[9px] sm:text-[10px] text-slate-400 font-bold mb-0.5">کیف پول شرکت (ریال)</span>
                        <span class="text-xs sm:text-sm font-black font-mono text-slate-700 leading-none" dir="ltr">
                            {{ number_format($walletBalance) }}
                        </span>
                    </div>

                    <div class="bg-blue-50 text-blue-600 p-1.5 rounded-full group-hover:bg-blue-600 group-hover:text-white transition duration-300 mr-1 hidden sm:block">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                        </svg>
                    </div>
                </a>
                @endif

                <div class="relative">
                    <button onclick="toggleDropdown()" id="userMenuButton" class="h-10 w-10 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 hover:bg-emerald-100 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                    </button>
                    
                    <div id="userDropdown" class="hidden absolute -left-4 top-[calc(100%+1.25rem)] w-[18rem] max-w-[calc(100vw-1rem)] overflow-hidden bg-white z-50 transition-all">
                        <div class="px-6 pb-4 pt-5 text-center">
                            <div class="relative mx-auto w-fit">
                                <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-indigo-400 ring-2 ring-slate-200 ring-offset-2 ring-offset-white">
                                    <svg class="mt-5 h-20 w-20" viewBox="0 0 100 100" fill="currentColor" aria-hidden="true"><circle cx="50" cy="31" r="22"/><path d="M10 96c1-25 17-39 40-39s39 14 40 39z"/></svg>
                                </div>
                                <button type="button" onclick="toggleDropdown()" aria-label="بستن پنل کاربر" class="absolute -bottom-1 -left-2 flex h-8 w-8 items-center justify-center rounded-full bg-white text-rose-600 ring-1 ring-slate-200 transition hover:bg-rose-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <p class="mx-auto mt-4 max-w-[220px] whitespace-normal break-words text-center text-sm font-black leading-6 text-slate-800">{{ $companyName }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ $ceoName }}</p>
                            @if($companyNationalId)
                                <p class="mt-1 text-[11px] text-slate-400">شناسه ملی <span class="font-mono" dir="ltr">{{ $companyNationalId }}</span></p>
                            @endif
                        </div>

                        <div class="border-y border-slate-100 py-1 text-center">
                            @if($panelFeatures->enabledForRole('company', 'profile'))
                                <a href="{{ route('company.shahbaz.profile.edit') }}" class="block px-5 py-3 text-xs font-bold text-slate-500 transition hover:bg-slate-50 hover:text-blue-700">اطلاعات شرکت و تکمیل پرونده</a>
                                <a href="{{ route('company.password.edit') }}#change-password" class="block px-5 py-3 text-xs font-bold text-slate-500 transition hover:bg-slate-50 hover:text-blue-700">تنظیمات امنیتی و حساب</a>
                            @endif
                            <a href="{{ route('dashboard') }}" class="block px-5 py-3 text-xs font-bold text-slate-500 transition hover:bg-slate-50 hover:text-blue-700">داشبورد شرکت</a>
                        </div>

                        <form action="{{ route('logout') }}" method="POST" class="flex flex-col items-center px-5 py-4">
                            @csrf
                            <button type="submit" class="group flex h-16 w-16 items-center justify-center rounded-full border border-rose-500 bg-white text-rose-600 transition hover:bg-rose-600 hover:text-white hover:shadow-lg" aria-label="خروج از سامانه">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            </button>
                            <span class="mt-2 text-xs font-black text-rose-600">خروج از سامانه</span>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 bg-slate-50">
            @yield('content')
        </div>
    </main>

    @if(app(\App\Services\PanelFeatureService::class)->enabledForUser(auth()->user(), 'assistant'))
        @include('components.assistant.chat')
    @endif

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/iran-plate.js') }}"></script>
    <script src="{{ asset('assets/js/pwa-tracker.js') }}"></script>
    <script>
        function updateAnalogClock() {
            const now = new Date();
            const seconds = now.getSeconds() + now.getMilliseconds() / 1000;
            const minutes = now.getMinutes() + seconds / 60;
            const hours = (now.getHours() % 12) + minutes / 60;
            document.querySelector('[data-clock-hour]')?.style.setProperty('transform', `translateX(-50%) rotate(${hours * 30}deg)`);
            document.querySelector('[data-clock-minute]')?.style.setProperty('transform', `translateX(-50%) rotate(${minutes * 6}deg)`);
            document.querySelector('[data-clock-second]')?.style.setProperty('transform', `translateX(-50%) rotate(${seconds * 6}deg)`);
        }
        updateAnalogClock();
        window.setInterval(updateAnalogClock, 1000);

        const companyHeaderTickerDuration = 22000;
        const companyHeaderTickerClockKey = 'company-header-ticker-clock-v3';
        let companyHeaderTickerSignature = @json(array_values($headerTickerMessages));
        let companyHeaderTickerFrame = 0;
        let companyHeaderTickerStartedAt = 0;

        function stopCompanyHeaderTickerAnimation() {
            if (companyHeaderTickerFrame) {
                window.cancelAnimationFrame(companyHeaderTickerFrame);
                companyHeaderTickerFrame = 0;
            }
        }

        function getCompanyHeaderTickerStartedAt(signature, reset = false) {
            const serializedSignature = JSON.stringify(signature);
            const now = Date.now();
            if (!reset) {
                try {
                    const stored = JSON.parse(window.localStorage.getItem(companyHeaderTickerClockKey) || 'null');
                    if (stored && stored.signature === serializedSignature && Number.isFinite(stored.startedAt)) {
                        return stored.startedAt;
                    }
                } catch (error) {
                    // Storage may be unavailable in a private browsing context.
                }
            }

            try {
                window.localStorage.setItem(companyHeaderTickerClockKey, JSON.stringify({ signature: serializedSignature, startedAt: now }));
            } catch (error) {
                // The ticker can still animate for the current page without storage.
            }
            return now;
        }

        function startCompanyHeaderTickerAnimation(resetClock = false) {
            const ticker = document.getElementById('companyHeaderTicker');
            const track = ticker?.querySelector('[data-ticker-track]');
            if (!ticker || !track || track.children.length < 2 || ticker.classList.contains('hidden')) {
                stopCompanyHeaderTickerAnimation();
                return;
            }

            stopCompanyHeaderTickerAnimation();
            companyHeaderTickerStartedAt = getCompanyHeaderTickerStartedAt(companyHeaderTickerSignature, resetClock);
            const getLayout = () => {
                const firstSegment = track.firstElementChild;
                const segmentWidth = firstSegment?.getBoundingClientRect().width || 0;
                if (!segmentWidth) return null;

                const rootFontSize = parseFloat(window.getComputedStyle(document.documentElement).fontSize) || 16;
                const gap = rootFontSize * 2.5;
                const viewportWidth = track.clientWidth || ticker.clientWidth;
                const requiredCopies = Math.max(2, Math.ceil((viewportWidth + gap) / (segmentWidth + gap)) + 2);
                while (track.children.length < requiredCopies) {
                    const clone = firstSegment.cloneNode(true);
                    clone.setAttribute('aria-hidden', 'true');
                    track.append(clone);
                }
                return { segmentWidth, gap };
            };

            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const animate = () => {
                const layout = getLayout();
                if (layout) {
                    const { segmentWidth, gap } = layout;
                    const copyDistance = segmentWidth + gap;
                    const elapsed = ((Date.now() - companyHeaderTickerStartedAt) % companyHeaderTickerDuration + companyHeaderTickerDuration) % companyHeaderTickerDuration;
                    const progress = elapsed / companyHeaderTickerDuration;
                    const baseOffset = reducedMotion ? 0 : progress * copyDistance;
                    Array.from(track.children).forEach((segment, index) => {
                        const offset = reducedMotion ? (index === 0 ? 0 : -100000) : baseOffset + ((index - 1) * copyDistance);
                        segment.style.transform = `translate3d(${offset}px, -50%, 0)`;
                    });
                }
                if (reducedMotion) return;
                companyHeaderTickerFrame = window.requestAnimationFrame(animate);
            };
            companyHeaderTickerFrame = window.requestAnimationFrame(animate);
        }

        function renderCompanyHeaderTicker(messages) {
            const ticker = document.getElementById('companyHeaderTicker');
            const track = ticker?.querySelector('[data-ticker-track]');
            if (!ticker || !track) return;

            const cleanMessages = (Array.isArray(messages) ? messages : [])
                .map((message) => String(message || '').trim())
                .filter(Boolean);
            const nextSignature = JSON.stringify(cleanMessages);
            if (nextSignature === JSON.stringify(companyHeaderTickerSignature) && track.children.length > 0) {
                return;
            }
            companyHeaderTickerSignature = cleanMessages;

            track.replaceChildren();
            if (cleanMessages.length === 0) {
                stopCompanyHeaderTickerAnimation();
                ticker.classList.add('hidden');
                ticker.classList.remove('md:flex');
                return;
            }

            const createSegment = (hidden = false) => {
                const segment = document.createElement('span');
                segment.className = 'company-header-ticker-segment';
                if (hidden) segment.setAttribute('aria-hidden', 'true');

                const content = document.createElement('span');
                content.className = 'company-header-ticker-content';

                cleanMessages.forEach((message, index) => {
                    const item = document.createElement('span');
                    item.className = 'company-header-ticker-item';
                    if (index === 0) {
                        const icon = document.createElement('span');
                        icon.className = 'company-header-ticker-icon';
                        icon.setAttribute('aria-hidden', 'true');
                        icon.textContent = '📢';
                        item.append(icon);
                    }
                    if (index > 0) {
                        const separator = document.createElement('span');
                        separator.className = 'text-slate-400';
                        separator.setAttribute('aria-hidden', 'true');
                        separator.textContent = '•';
                        item.append(separator);
                    }
                    const messageNode = document.createElement('span');
                    messageNode.textContent = message;
                    item.append(messageNode);
                    content.append(item);
                });
                segment.append(content);
                return segment;
            };

            track.append(createSegment(), createSegment(true));
            ticker.classList.remove('hidden');
            ticker.classList.add('md:flex');
            startCompanyHeaderTickerAnimation(true);
        }

        async function refreshCompanyHeaderTicker() {
            const ticker = document.getElementById('companyHeaderTicker');
            const url = ticker?.dataset.feedUrl;
            if (!url) return;

            try {
                const response = await fetch(`${url}?_=${Date.now()}`, {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) return;
                const payload = await response.json();
                renderCompanyHeaderTicker(payload.messages);
            } catch (error) {
                // A temporary network failure must not interrupt the rest of the panel.
            }
        }

        startCompanyHeaderTickerAnimation();
        window.setInterval(refreshCompanyHeaderTicker, 30000);

        function toggleSidebar() {
            const desktop = window.matchMedia('(min-width: 768px)').matches;
            if (desktop) {
                return;
            } else {
                $('#sidebar').toggleClass('translate-x-full');
                $('#sidebarOverlay').toggleClass('hidden');
            }
            const open = desktop ? $('#sidebar').hasClass('sidebar-open') : !$('#sidebar').hasClass('translate-x-full');
            $('[aria-controls="sidebar"]').attr('aria-expanded', String(open));
        }
        
        function toggleDropdown() {
            $('#userDropdown').toggleClass('hidden');
        }
        
        $(document).click(function(event) {
            if (!$(event.target).closest('#userMenuButton, #userDropdown').length) {
                if (!$('#userDropdown').hasClass('hidden')) {
                    $('#userDropdown').addClass('hidden');
                }
            }
        });

        async function copyInlineText(text) {
            if (!text || ['ثبت نشده', '—', '---'].includes(text)) return;
            try {
                await navigator.clipboard.writeText(text);
            } catch (error) {
                const input = document.createElement('textarea');
                input.value = text;
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                input.remove();
            }

            const toast = document.createElement('div');
            toast.textContent = 'کپی شد';
            toast.className = 'fixed bottom-5 left-5 z-[9999] rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white shadow-lg';
            document.body.appendChild(toast);
            window.setTimeout(() => toast.remove(), 1400);
        }

        document.addEventListener('click', function(event) {
            const target = event.target.closest('[data-copy-text], [data-copy-dynamic]');
            if (!target) return;
            const text = (target.dataset.copyText || target.textContent || '').trim();
            copyInlineText(text);
        });
    </script>
    @yield('scripts')
</body>
</html>
