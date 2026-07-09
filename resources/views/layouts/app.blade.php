<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .active-menu { background-color: #ecfdf5; color: #059669; border-right: 4px solid #059669; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden transition-opacity md:hidden" onclick="toggleSidebar()"></div>

    <aside id="sidebar" class="fixed inset-y-0 right-0 z-50 w-64 bg-slate-900 text-slate-300 transform transition-transform duration-300 translate-x-full md:relative md:translate-x-0 flex flex-col shadow-2xl">
        <div class="h-40 border-b border-slate-800 flex flex-col items-center justify-center px-4 bg-slate-900/50">
            <img src="{{ asset('images/logo1.png') }}" alt="لوگو" class="h-28 w-auto drop-shadow-lg">
            <span class="text-white font-black text-xs mt-4 tracking-widest">پنل شرکت حمل و نقل</span>
        </div>

        <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm {{ request()->routeIs('dashboard') ? 'active-menu bg-emerald-900 text-emerald-400' : 'hover:bg-slate-800' }}">🏠 داشبورد</a>
            
            {{-- 🚀 اصلاح مسیر ثبت درخواست به صفحه لیست بر اساس سناریوی جدید UX --}}
            <a href="{{ route('dozbalagh.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm {{ request()->routeIs('dozbalagh.index') ? 'active-menu bg-emerald-900 text-emerald-400' : 'hover:bg-slate-800' }}">🚀 ثبت درخواست</a>
            
            <a href="{{ route('web.company.driver.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm {{ request()->routeIs('web.company.driver.index') ? 'active-menu bg-emerald-900 text-emerald-400' : 'hover:bg-slate-800' }}">👤 رانندگان</a>
            <a href="{{ route('company.driver_messages.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm {{ request()->routeIs('company.driver_messages.*') ? 'active-menu bg-emerald-900 text-emerald-400' : 'hover:bg-slate-800' }}">💬 پیام رانندگان</a>
            <a href="{{ route('company.association_crm.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm {{ request()->routeIs('company.association_crm.index') ? 'active-menu bg-emerald-900 text-emerald-400' : 'hover:bg-slate-800' }}">پیام‌ها و پشتیبانی انجمن</a>
            <a href="{{ route('company.association_crm.tickets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm {{ request()->routeIs('company.association_crm.tickets.*') ? 'active-menu bg-emerald-900 text-emerald-400' : 'hover:bg-slate-800' }}">تیکت انجمن</a>
            <a href="{{ route('web.company.fleet.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm {{ request()->is('*fleet*') ? 'active-menu bg-emerald-900 text-emerald-400' : 'hover:bg-slate-800' }}">🚛 ناوگان</a>
            <a href="{{ route('report.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm {{ request()->is('*report*') ? 'active-menu bg-emerald-900 text-emerald-400' : 'hover:bg-slate-800' }}">📊 گزارشات</a>
            
            <div class="pt-4 mt-4 border-t border-slate-800">
                 <button onclick="$('#submenu-profile').slideToggle();" class="w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-sm hover:bg-slate-800">
                    <span>⚙️ تنظیمات</span>
                </button>
                <div id="submenu-profile" class="hidden pr-4 mt-1 space-y-1">
                    <a href="{{ route('company.profile.edit') }}" class="block p-2 text-xs font-semibold text-slate-400 hover:text-emerald-400">ویرایش پروفایل</a>
                </div>
            </div>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden">
        
        @php
            $user = auth()->user();
            $companyId = $user->company_id ?? $user->company->id ?? null;
            $ceoName = $user->company->ceo_name ?? $user->name ?? 'مدیر شرکت';
            
            $walletBalance = 0;
            if($companyId) {
                $headerWallet = \App\Models\Wallet::firstOrCreate(['company_id' => $companyId]);
                $walletBalance = $headerWallet->balance; // تغییر نام ستون به balance جهت هماهنگی نهایی دیتابیس
            }
        @endphp

        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-4 sm:px-8 z-30 sticky top-0">
            <button onclick="toggleSidebar()" class="md:hidden p-2 text-slate-500 hover:bg-slate-100 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            
            <h1 class="text-sm font-bold text-slate-800">@yield('header_title', 'سامانه مدیریت دوزبلاغ')</h1>

            <div class="flex items-center gap-3 sm:gap-4">
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

                <div class="relative">
                    <button onclick="toggleDropdown()" id="userMenuButton" class="h-10 w-10 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 hover:bg-emerald-100 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                    </button>
                    
                    <div id="userDropdown" class="hidden absolute left-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border border-slate-100 z-50 overflow-hidden transition-all">
                        <div class="bg-slate-50 p-4 border-b border-slate-100 flex items-center gap-3">
                            <div class="h-12 w-12 rounded-full bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-800 text-sm">{{ $ceoName }}</span>
                                <span class="text-xs text-slate-500 mt-0.5">مدیرعامل شرکت</span>
                            </div>
                        </div>
                        
                        <div class="p-2">
                            <a href="{{ route('company.profile.edit') }}" class="flex items-center gap-2 w-full px-3 py-2.5 text-sm text-slate-600 font-bold hover:bg-slate-50 rounded-xl transition">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                تنظیمات رمز عبور
                            </a>
                            
                            <div class="border-t border-slate-100 my-1"></div>
                            
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

        <div class="flex-1 overflow-y-auto p-6 bg-slate-50">
            @yield('content')
        </div>
    </main>

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/iran-plate.js') }}"></script>
    <script>
        function toggleSidebar() {
            $('#sidebar').toggleClass('translate-x-full');
            $('#sidebarOverlay').toggleClass('hidden');
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
    </script>
    @yield('scripts')
</body>
</html>
