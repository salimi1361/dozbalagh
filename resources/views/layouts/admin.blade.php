<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت کل - سامانه جامع دوزوله</title>
    
    <script src="{{ asset('assets/js/tailwindcss.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/iran-plate.css') }}">
    
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
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden transition-opacity md:hidden" onclick="toggleSidebar()"></div>

    <aside id="sidebar" class="fixed inset-y-0 right-0 z-50 w-64 bg-slate-900 text-slate-300 transform transition-transform duration-300 translate-x-full md:relative md:translate-x-0 flex flex-col shadow-2xl">
        
        <div class="h-24 border-b border-slate-800 flex items-center justify-center px-2 bg-slate-900/50">
            <img src="{{ asset('images/logo1.png') }}" alt="لوگو" class="h-16 w-auto drop-shadow-lg">
            <div class="mr-2 flex flex-col">
                <span class="text-white font-black text-sm tracking-wide">مدیریت دوزوله</span>
                <span class="text-slate-500 text-[10px] font-bold">انجمن خراسان رضوی</span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1.5">
    
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                داشبورد کل
            </a>

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
                    <a href="/web/association/driver/list" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/driver/list') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>⏳</span> درخواست‌های معلق
                    </a>
                    <a href="/web/association/approved/permits" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/approved/permits') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>✍️</span> صدور و تخصیص سریال
                    </a>
                    <a href="/web/association/transit-permits" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/transit-permits') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>🚚</span> مدیریت تردد
                    </a>
                    <a href="/web/association/permits/archive" class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all {{ request()->is('web/association/permits/archive') ? 'text-sky-400 bg-slate-800/50' : 'text-slate-400 hover:text-white' }}">
                        <span>🗂️</span> بایگانی کل پروانه‌ها
                    </a>
                </div>
            </div>

            <a href="{{ route('association.reports.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('association.reports.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('association.reports.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                گزارش دوزوله‌ها
            </a>

            <a href="{{ route('admin.financial.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.financial.*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.financial.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M5 7h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2zm2-4h10a2 2 0 012 2v2H5V5a2 2 0 012-2z"></path>
                </svg>
                گزارش مالی دوزوله‌ها
            </a>

            <a href="{{ route('admin.companies.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.companies.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.companies.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                مدیریت شرکت‌ها
            </a>

            <a href="{{ route('admin.association_crm.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.association_crm.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.association_crm.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m-9 7l3.5-3.5H19a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10.5a2 2 0 002 2H4v3.5z"></path></svg>
                CRM انجمن
            </a>
            
            <a href="{{ route('admin.countries.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.countries.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.countries.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                کشورها و مرزها
            </a>
            
            <a href="{{ route('admin.inventory.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.inventory.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.inventory.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                انبار سریال دوزوله
            </a>
            
            <a href="{{ route('admin.allocations.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.allocations.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.allocations.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                تخصیص سهمیه‌ها
            </a>

            <div class="pt-4 mt-2 border-t border-slate-800">
                <div class="px-4 mb-2 text-xs font-black text-slate-500 tracking-wider">جامعه هدف</div>
                
                <a href="{{ route('admin.drivers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.drivers.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('admin.drivers.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    مدیریت رانندگان
                </a>
                
                <a href="{{ route('admin.fleets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.fleets.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('admin.fleets.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    مدیریت ناوگان
                </a>

                <a href="{{ route('admin.cargo_rules.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-200 {{ request()->routeIs('admin.cargo_rules.*') ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('admin.cargo_rules.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    قوانین مدارک دوزوله
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-30 sticky top-0">
            
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h1 class="text-lg sm:text-xl font-black text-slate-800 hidden sm:block">@yield('header_title', 'سامانه مدیریت دوزوله')</h1>
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
                                <span class="font-bold text-slate-800 text-sm">{{ auth()->user()->name ?? 'مدیر سیستم' }}</span>
                                <span class="text-xs text-slate-500 mt-0.5">ادمین کل</span>
                            </div>
                        </div>
                        
                        <div class="p-2">
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
    
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('translate-x-full');
            
            if (overlay.classList.contains('hidden')) {
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.add('opacity-100'), 10);
            } else {
                overlay.classList.remove('opacity-100');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
        }
        
        function toggleDropdown() {
            document.getElementById('userDropdown').classList.toggle('hidden');
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

        document.addEventListener('click', function(event) {
            const button = document.getElementById('userMenuButton');
            const dropdown = document.getElementById('userDropdown');
            if (button && dropdown && !button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
    
    @yield('scripts')
</body>
</html>
