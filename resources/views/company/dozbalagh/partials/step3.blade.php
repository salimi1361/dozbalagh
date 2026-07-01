{{-- استپ ۳: کنترل نهایی و تایید مدارک (نسخه بدون همپوشانی و کارت اضافه) --}}


{{-- هدر صفحه + کد رهگیری یکپارچه بدون خط تیره --}}
<div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 pb-4 border-b border-slate-200 gap-4">
    <div>
        <h2 class="text-2xl font-black text-slate-800 flex items-center gap-3">
            <span class="bg-indigo-100 text-indigo-600 w-10 h-10 flex items-center justify-center rounded-xl shadow-inner">📋</span> 
            کنترل نهایی و تایید مدارک
        </h2>
    </div>
    
    <div class="bg-slate-50 px-5 py-2.5 rounded-xl border border-slate-200 shadow-sm flex flex-col items-end">
        <span class="text-[10px] text-slate-400 font-bold tracking-widest">شماره پرونده سیستمی (جهت پیگیری)</span>
        <span class="font-mono font-black text-lg text-slate-800 tracking-wider" dir="ltr">{{ $newDCode ?? 'D20260613001' }}</span>
        <input type="hidden" name="tracking_code" value="{{ $newDCode ?? '' }}">
    </div>
</div>

{{-- باکس تعهدنامه حقوقی و اصالت مدارک شرکت --}}
<div class="bg-amber-50 border-r-4 border-amber-500 rounded-l-2xl p-5 mb-6 shadow-sm flex gap-4 items-start relative overflow-hidden">
    <div class="absolute left-4 bottom-2 text-6xl opacity-5 pointer-events-none select-none">⚖️</div>
    
    <span class="text-amber-600 text-2xl mt-0.5 shrink-0">⚠️</span>
    <div class="w-full">
        <h4 class="text-sm font-black text-amber-900 mb-2 flex items-center gap-2">
            تعهدنامه اصالت اطلاعات و مسئولیت حقوقی شرکت
        </h4>
        <p class="text-xs text-amber-800 leading-relaxed text-justify font-medium space-y-1">
            بدین‌وسیله تایید می‌گردد که این درخواست از سوی شرکت 
            <strong class="text-slate-900 bg-amber-100/80 px-1.5 py-0.5 rounded border border-amber-200/60 shadow-sm">
                {{ auth()->user()->company->name ?? 'شرکت متبوع' }}
            </strong> 
            به مدیریت عاملی 
            <strong class="text-slate-900 bg-amber-100/80 px-1.5 py-0.5 rounded border border-amber-200/60 shadow-sm">
                {{ auth()->user()->company->ceo_name ?? auth()->user()->company->manager_name ?? 'مدیرعامل محترم' }}
            </strong> 
            در سامانه ثبت شده است. 
        </p>
        <p class="text-[11px] text-amber-700 mt-2 leading-relaxed text-justify">
            کلیه اطلاعات راننده، مشخصات ناوگان، کدهای جامع و مدارک بارگذاری‌شده مبنای **چاپ و صدور پروانه دوزوله** شما خواهند بود؛ لذا صراحتاً اعلام می‌دارد مسئولیت حقوقی، اداری و عواقب ناشی از درج هرگونه اطلاعات غلط، مغایر، صوری یا مخدوش تماماً بر عهده این شرکت بوده و انجمن حق ابطال درخواست و پیگیری قانونی را برای خود محفوظ می‌دارد.
        </p>
    </div>
</div>

<div class="space-y-6">
    
    {{-- ۱. باکس داینامیک مالی و وضعیت کیف پول (دکمه شارژ و هشدارها اینجاست) --}}
    <div id="wallet-checkout-container">
        {{-- توسط جاوااسکریپت مدیریت و تزریق می‌شود --}}
    </div>

    {{-- ۲. کارت اطلاعات ناوگان و راننده --}}
 {{-- 🟢 کادر نمایش خلاصه اطلاعات متقاضی و ناوگان در استپ ۳ (دقیقاً مطابق عکس هدف) --}}
<div class="bg-white border border-slate-200 rounded-3xl p-5 mb-6 shadow-sm">
    <div class="border-b border-slate-100 pb-3 mb-4">
        <h3 class="text-sm font-black text-slate-800 flex items-center gap-2">
            <span class="w-1.5 h-4 bg-indigo-600 rounded-full"></span> اطلاعات متقاضی و ناوگان
        </h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        {{-- 👤 کارت راننده --}}
        <div class="border border-slate-100 rounded-2xl bg-white p-4 shadow-sm relative flex flex-col justify-between min-h-[140px]">
            <div class="absolute top-3 left-4">
                <img src="{{ asset('assets/images/driver-avatar.png') }}" class="w-10 h-10 rounded-full opacity-80" alt="Driver Avatar">
            </div>
            <div class="text-right">
                <h4 id="s3_driver_name" class="font-black text-slate-800 text-base">---</h4>
                <p id="s3_driver_national_parent" class="text-xs font-bold text-slate-500 mt-1">(<span id="s3_driver_national">---</span>)</p>
            </div>
            <div class="border-t border-slate-100 pt-3 mt-4 flex flex-col gap-1 text-xs text-slate-600 font-bold">
                <div class="flex items-center gap-1">
                    <span class="text-slate-400">👤</span>
                    <span id="s3_driver_en" class="font-mono uppercase tracking-wider text-slate-700">---</span>
                </div>
                <div class="flex gap-4 text-[11px] text-slate-400 mt-0.5">
                    <p>کد ملی: <span id="s3_lbl_national" class="text-slate-600 font-mono">---</span></p>
                    <p>گذرنامه: <span id="s3_lbl_passport" class="text-slate-600 font-mono">---</span></p>
                </div>
            </div>
        </div>

        {{-- 🚛 کارت ناوگان --}}
        <div class="border border-slate-100 rounded-2xl bg-white p-4 shadow-sm flex flex-col justify-between min-h-[140px]">
            <div class="flex justify-between items-start text-xs text-slate-600 font-bold">
                <p class="flex items-center gap-1">
                    <span>🚛</span> نوع کامیون: <span id="s3_truck_type" class="text-slate-800">---</span>
                </p>
                <p class="flex items-center gap-1">
                    <span>💳</span> کارت هوشمند: <span id="s3_smart_card" class="text-slate-700 font-mono">---</span>
                </p>
            </div>

            {{-- 🔲 پلاک استاندارد و زیبای ایران در وسط کارت --}}
            <div class="flex justify-center my-3">
                <div dir="ltr" class="inline-flex items-stretch border border-slate-900 rounded-xl bg-[#fab800] text-slate-950 font-black h-12 overflow-hidden shadow-md" style="min-width: 250px;">
                    <div class="bg-[#0033a0] flex flex-col items-center justify-between py-1 px-1 text-white border-r border-slate-900" style="width: 26px; min-width: 26px;">
                        <div class="w-full h-2 rounded-sm overflow-hidden flex flex-col" style="height: 6px;">
                            <div class="bg-[#228B22]" style="height: 33.33%;"></div>
                            <div class="bg-white" style="height: 33.33%;"></div>
                            <div class="bg-[#DA291C]" style="height: 33.33%;"></div>
                        </div>
                        <div class="flex flex-col items-center text-[5px] font-sans font-bold tracking-tighter" style="line-height: 1;">
                            <span>I.R.</span><span>IRAN</span>
                        </div>
                    </div>
                    <div class="flex-1 flex items-center justify-center gap-4 px-3 text-xl font-mono font-black tracking-wide">
                        <span id="s3_plate_1">--</span>
                        <span id="s3_plate_2" class="font-sans font-black text-lg text-slate-900">--</span>
                        <span id="s3_plate_3">---</span>
                    </div>
                    <div class="border-l border-slate-900 flex flex-col items-center justify-center bg-[#fab800] text-slate-950 font-bold text-center" style="width: 48px; min-width: 48px; line-height: 1.1;">
                        <span class="text-[9px] text-slate-800">ایران</span>
                        <div class="w-full border-t border-slate-900 my-0.5"></div>
                        <span id="s3_plate_4" class="text-sm font-mono tracking-tight">--</span>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-2 border-t border-slate-100 text-[11px] font-bold">
                <span class="bg-slate-50 text-slate-600 px-2 py-1 rounded-md">ID ترانزیت اسب: <strong id="s3_horse" class="font-mono text-indigo-600">---</strong></span>
                <span class="bg-slate-50 text-slate-600 px-2 py-1 rounded-md">ID ترانزیت یدک: <strong id="s3_trailer" class="font-mono text-indigo-600">---</strong></span>
            </div>
        </div>
    </div>
</div>

    {{-- ۳. لیست مقاصد و مدارک بارگذاری‌شده --}}
    <div class="bg-white border border-slate-200 rounded-3xl p-5 md:p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-6">
            <span class="w-2 h-6 bg-emerald-500 rounded-full block"></span>
            <span class="bg-slate-200 text-slate-600 px-3 py-1.5 rounded-lg text-sm font-bold tracking-wide">جزئیات سفر و مدارک بارگذاری‌شده (جهت بازبینی)</span>
        </div>
        <div id="summary-countries-list" class="space-y-4">
            {{-- جاوااسکریپت رندر می‌کند --}}
        </div>
    </div>

</div>