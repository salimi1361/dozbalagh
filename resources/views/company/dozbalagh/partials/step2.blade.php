<h2 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-2">
    <span class="bg-blue-100 text-blue-600 p-2 rounded-xl">🌍</span> اطلاعات سفر و مقاصد
</h2>

{{-- ========================================================= --}}
{{-- 📦 کادر ثابت بالا (مبدا، مقصد، نوع بار و مدارک وابسته) --}}
{{-- ========================================================= --}}
<div class="bg-slate-50 border border-slate-200 rounded-3xl p-5 mb-8 shadow-sm">
    <div class="border-b border-slate-200 pb-3 mb-5 flex justify-between items-center">
        <h3 class="text-base font-black text-rose-600 flex items-center gap-2">
            <span>📌</span> اطلاعات ثابت سفر (فقط یکبار تکمیل شود)
        </h3>
    </div>

    {{-- ردیف اول: نوع بار، مبدا، مقصد --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">نوع بار / عملیات حمل <span class="text-rose-500">*</span></label>
            <select name="cargo_type" id="main_cargo_type" required class="w-full border border-slate-300 rounded-xl p-3 bg-white outline-none focus:border-blue-500 transition-colors">
                <option value="">انتخاب کنید...</option>
                <option value="صادرات">صادرات</option>
                <option value="واردات">واردات</option>
                <option value="ترانزیت">ترانزیت</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">مبدا بارگیری <span class="text-rose-500">*</span></label>
            <select name="loading_origin" id="loading_origin" required class="w-full border border-slate-300 rounded-xl p-3 text-sm bg-white outline-none select2-search">
                <option value="">جستجو و انتخاب مبدا...</option>
                @foreach($allWorldCountries as $country)
                    {{-- 🟢 اصلاح کاملاً هوشمند تگ‌ها برای پشتیبانی همزمان از هر نوع ساختار داده (آرایه تودرتو یا مدل دیتابیس) --}}
                    @php 
                        $fa = '';
                        $en = '';
                        if (is_array($country)) {
                            $fa = $country['name_fa'] ?? $country['fa'] ?? '';
                            $en = $country['name_en'] ?? $country['en'] ?? '';
                        } elseif (is_object($country)) {
                            $fa = $country->name_fa ?? $country->fa ?? '';
                            $en = $country->name_en ?? $country->en ?? '';
                        }
                    @endphp
                    <option value="{{ $fa }}">{{ $fa }} - {{ $en }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">مقصد بارگیری <span class="text-rose-500">*</span></label>
            <select name="loading_destination" id="loading_destination" required class="w-full border border-slate-300 rounded-xl p-3 text-sm bg-white outline-none select2-search">
                <option value="">جستجو و انتخاب مقصد...</option>
                @foreach($allWorldCountries as $country)
                    {{-- 🟢 اصلاح کاملاً هوشمند تگ‌ها برای پشتیبانی همزمان از هر نوع ساختار داده (آرایه تودرتو یا مدل دیتابیس) --}}
                    @php 
                        $fa = '';
                        $en = '';
                        if (is_array($country)) {
                            $fa = $country['name_fa'] ?? $country['fa'] ?? '';
                            $en = $country['name_en'] ?? $country['en'] ?? '';
                        } elseif (is_object($country)) {
                            $fa = $country->name_fa ?? $country->fa ?? '';
                            $en = $country->name_en ?? $country->en ?? '';
                        }
                    @endphp
                    <option value="{{ $fa }}">{{ $fa }} - {{ $en }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ردیف دوم: مدارک و پیوست‌ها (داینامیک بر اساس نوع بار) --}}
    <div id="fixed_documents_container" class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6 border-t border-slate-200 pt-6">
        
        {{-- باکس CITS (همیشه ثابت) --}}
        <div class="p-4 rounded-xl border border-slate-200 bg-white shadow-sm md:col-span-4 lg:col-span-1">
            <label class="block text-xs font-bold text-slate-700 mb-2">کد درخواست سامانه جامع (CITS) <span class="text-rose-500">*</span></label>
            <input type="text" name="cits_code" required class="w-full border border-emerald-300 rounded-xl p-3 text-sm bg-slate-50 font-mono focus:border-emerald-500 outline-none transition-colors" placeholder="الزامی">
        </div>

        {{-- باکس فیش (داینامیک) --}}
        <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-white flex flex-col justify-between gap-3 shadow-sm dynamic-doc">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">شماره فیش سازمان <span class="text-rose-500">*</span></label>
                <input type="text" name="receipt_code" class="w-full border border-slate-300 rounded-lg p-2 text-xs font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">مبلغ واریزی (ریال) <span class="text-rose-500">*</span></label>
                <input type="text" name="receipt_amount" class="w-full border border-slate-300 rounded-lg p-2 text-xs font-mono number-format" placeholder="50,000,000">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">تصویر فیش <span class="text-rose-500">*</span></label>
                <input type="file" name="receipt_file" accept=".jpg,.png,.pdf" class="w-full text-xs bg-slate-50 border border-slate-200 p-1.5 rounded-lg cursor-pointer">
            </div>
        </div>

        {{-- باکس CMR (داینامیک) --}}
        <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-white flex flex-col justify-between gap-3 shadow-sm dynamic-doc">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">کد سفر <span class="text-[10px] font-normal text-slate-400">(شروع با J)</span> <span class="text-rose-500">*</span></label>
                <input type="text" name="trip_code" class="w-full border border-slate-300 rounded-lg p-2 text-xs font-mono uppercase" placeholder="J-123456">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">تاریخ بارگیری CMR <span class="text-rose-500">*</span></label>
                <input type="date" name="cmr_date" class="w-full border border-slate-300 rounded-lg p-1.5 text-xs text-center font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">تصویر CMR <span class="text-rose-500">*</span></label>
                <input type="file" name="cmr_file" accept=".jpg,.png" class="w-full text-xs bg-slate-50 border border-slate-200 p-1.5 rounded-lg cursor-pointer">
            </div>
        </div>

        {{-- باکس کارنه تیر (داینامیک) --}}
        <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-white flex flex-col justify-between gap-3 shadow-sm dynamic-doc">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">شماره کارنه تیر</label>
                <input type="text" name="tir_carnet_number" class="w-full border border-slate-300 rounded-lg p-2 text-xs font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">تاریخ کارنه تیر</label>
                <input type="date" name="tir_carnet_date" class="w-full border border-slate-300 rounded-lg p-1.5 text-xs text-center font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">تصویر کارنه تیر</label>
                <input type="file" name="tir_file" accept=".jpg,.png" class="w-full text-xs bg-slate-50 border border-slate-200 p-1.5 rounded-lg cursor-pointer">
            </div>
        </div>

        {{-- باکس اظهارنامه (داینامیک) --}}
        <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-white flex flex-col justify-center items-center text-center gap-3 shadow-sm dynamic-doc">
            <span class="text-xs text-slate-400 font-bold mb-2">اطلاعات تکمیلی پرونده</span>
            <div class="w-full text-right">
                <label class="block text-xs font-bold text-slate-600 mb-1">تصویر اظهارنامه</label>
                <input type="file" name="declaration_file" accept=".jpg,.png" class="w-full text-xs bg-slate-50 border border-slate-200 p-1.5 rounded-lg cursor-pointer">
                <small class="text-[10px] text-slate-400 block mt-1">اختیاری (در صورت وجود)</small>
            </div>
        </div>

    </div>
</div>

{{-- ========================================================= --}}
{{-- 🌍 مقاصد متغیر: کشورها و نوع دوزوله --}}
{{-- ========================================================= --}}
<div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
    <h3 class="text-base font-black text-slate-800 bg-white px-4 py-2 rounded-xl border border-slate-200 shadow-sm inline-block">کشورهای مسیر و نوع مجوزها</h3>
    <button type="button" id="add_destination_btn" class="bg-blue-50 text-blue-600 border border-blue-200 px-5 py-2.5 rounded-xl hover:bg-blue-100 transition flex items-center text-sm font-bold shadow-sm">
        <span class="ml-2">➕</span> افزودن کشور عبوری/مقصد
    </button>
</div>

<div id="destinations_wrapper" class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-200">
    {{-- ردیف اول کشور (پیش‌فرض) --}}
    <div class="country-row flex flex-col md:flex-row items-center gap-4 bg-white border border-slate-200 p-4 rounded-xl shadow-sm relative" data-index="0">
        <div class="w-full md:w-1/2">
            <label class="block text-xs font-bold text-slate-600 mb-1">کشور مقصد/عبوری <span class="text-rose-500">*</span></label>
            <select name="destinations[0][country_id]" class="w-full border border-slate-300 rounded-lg p-2.5 country-select outline-none focus:border-blue-500">
                <option value="">انتخاب کشور...</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" data-price="{{ $country->price ?? 0 }}">{{ $country->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full md:w-1/2">
            <label class="block text-xs font-bold text-slate-600 mb-1">نوع مجوز دوزوله <span class="text-rose-500">*</span></label>
            <select name="destinations[0][permit_type]" class="w-full border border-slate-300 rounded-lg p-2.5 permit-select outline-none focus:border-blue-500">
                <option value="">ابتدا کشور را انتخاب کنید...</option>
            </select>
        </div>
    </div>
</div>