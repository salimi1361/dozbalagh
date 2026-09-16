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
                @foreach($cargoRules as $cargoType => $cargoRule)
                    <option value="{{ $cargoType }}" @selected(old('cargo_type', $cargoTypeVal ?? '') === $cargoType)>{{ $cargoType }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-[11px] leading-5 text-slate-500">گزینه‌ها و مدارک هر عملیات از «قوانین داینامیک دوزوله» خوانده می‌شوند.</p>
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
            <input id="cits_code" type="text" name="cits_code" value="{{ old('cits_code', 'D') }}" required dir="ltr" inputmode="numeric" pattern="D[0-9]+" class="w-full border border-emerald-300 rounded-xl p-3 text-sm bg-slate-50 font-mono focus:border-emerald-500 outline-none transition-colors" placeholder="D123456" aria-describedby="cits_code_hint">
            <p id="cits_code_hint" class="mt-2 text-[11px] text-slate-500">حرف بزرگ D ثابت است؛ فقط ارقام پس از آن را وارد کنید.</p>
        </div>

        {{-- باکس فیش (داینامیک) --}}
        <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-white flex flex-col justify-between gap-3 shadow-sm dynamic-doc">
            <div class="dynamic-field">
                <label class="block text-xs font-bold text-slate-700 mb-1">شماره فیش سازمان <span class="text-rose-500">*</span></label>
                <input type="text" name="receipt_code" class="w-full border border-slate-300 rounded-lg p-2 text-xs font-mono">
            </div>
            <div class="dynamic-field">
                <label class="block text-xs font-bold text-slate-700 mb-1">مبلغ واریزی (ریال) <span class="text-rose-500">*</span></label>
                <input type="text" name="receipt_amount" class="w-full border border-slate-300 rounded-lg p-2 text-xs font-mono number-format" placeholder="50,000,000">
            </div>
            <div class="dynamic-field">
                <label class="block text-xs font-bold text-slate-600 mb-1">تصویر فیش <span class="text-rose-500">*</span></label>
                <input type="file" name="receipt_file" accept=".jpg,.png,image/jpeg,image/png" class="js-image-upload w-full text-xs bg-slate-50 border border-slate-200 p-1.5 rounded-lg cursor-pointer">
                <small class="text-[10px] text-slate-400 block mt-1">فقط JPG یا PNG؛ تصاویر بزرگ‌تر به‌صورت خودکار کم‌حجم می‌شوند</small>
                <div class="js-image-preview hidden mt-2 text-center">
                    <button type="button" class="js-image-preview-open rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-600 transition hover:bg-blue-100">مشاهده پیش‌نمایش</button>
                    <p class="js-image-preview-name mt-1 truncate text-[10px] text-slate-500"></p>
                </div>
            </div>
        </div>

        {{-- باکس CMR (داینامیک) --}}
        <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-white flex flex-col justify-between gap-3 shadow-sm dynamic-doc">
            <div class="dynamic-field">
                <label class="block text-xs font-bold text-slate-700 mb-1">کد سفر <span class="text-[10px] font-normal text-slate-400">(شروع با J)</span> <span class="text-rose-500">*</span></label>
                <input type="text" name="trip_code" class="w-full border border-slate-300 rounded-lg p-2 text-xs font-mono uppercase" placeholder="J-123456">
            </div>
            <div class="dynamic-field">
                <label class="block text-xs font-bold text-slate-700 mb-1">تاریخ بارگیری CMR <span class="text-rose-500">*</span></label>
                <input type="date" name="cmr_date" class="w-full border border-slate-300 rounded-lg p-1.5 text-xs text-center font-mono">
            </div>
            <div class="dynamic-field">
                <label class="block text-xs font-bold text-slate-600 mb-1">تصویر CMR <span class="text-rose-500">*</span></label>
                <input type="file" name="cmr_file" accept=".jpg,.png,image/jpeg,image/png" class="js-image-upload w-full text-xs bg-slate-50 border border-slate-200 p-1.5 rounded-lg cursor-pointer">
                <small class="text-[10px] text-slate-400 block mt-1">فقط JPG یا PNG؛ تصاویر بزرگ‌تر به‌صورت خودکار کم‌حجم می‌شوند</small>
                <div class="js-image-preview hidden mt-2 text-center">
                    <button type="button" class="js-image-preview-open rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-600 transition hover:bg-blue-100">مشاهده پیش‌نمایش</button>
                    <p class="js-image-preview-name mt-1 truncate text-[10px] text-slate-500"></p>
                </div>
            </div>
        </div>

        {{-- باکس کارنه تیر (داینامیک) --}}
        <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-white flex flex-col justify-between gap-3 shadow-sm dynamic-doc">
            <div class="dynamic-field">
                <label class="block text-xs font-bold text-slate-700 mb-1">شماره کارنه تیر <span class="text-rose-500 hidden">*</span></label>
                <input type="text" name="tir_carnet_number" class="w-full border border-slate-300 rounded-lg p-2 text-xs font-mono">
            </div>
            <div class="dynamic-field">
                <label class="block text-xs font-bold text-slate-700 mb-1">تاریخ کارنه تیر <span class="text-rose-500 hidden">*</span></label>
                <input type="date" name="tir_carnet_date" class="w-full border border-slate-300 rounded-lg p-1.5 text-xs text-center font-mono">
            </div>
            <div class="dynamic-field">
                <label class="block text-xs font-bold text-slate-600 mb-1">تصویر کارنه تیر <span class="text-rose-500 hidden">*</span></label>
                <input type="file" name="tir_file" accept=".jpg,.png,image/jpeg,image/png" class="js-image-upload w-full text-xs bg-slate-50 border border-slate-200 p-1.5 rounded-lg cursor-pointer">
                <small class="text-[10px] text-slate-400 block mt-1">فقط JPG یا PNG؛ تصاویر بزرگ‌تر به‌صورت خودکار کم‌حجم می‌شوند</small>
                <div class="js-image-preview hidden mt-2 text-center">
                    <button type="button" class="js-image-preview-open rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-600 transition hover:bg-blue-100">مشاهده پیش‌نمایش</button>
                    <p class="js-image-preview-name mt-1 truncate text-[10px] text-slate-500"></p>
                </div>
            </div>
        </div>

        {{-- باکس اظهارنامه (داینامیک) --}}
        <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-white flex flex-col justify-center items-center text-center gap-3 shadow-sm dynamic-doc">
            <span class="text-xs text-slate-400 font-bold mb-2">اطلاعات تکمیلی پرونده</span>
            <div class="dynamic-field w-full text-right">
                <label class="block text-xs font-bold text-slate-600 mb-1">تصویر اظهارنامه <span class="text-rose-500 hidden">*</span></label>
                <input type="file" name="declaration_file" accept=".jpg,.png,image/jpeg,image/png" class="js-image-upload w-full text-xs bg-slate-50 border border-slate-200 p-1.5 rounded-lg cursor-pointer">
                <small class="text-[10px] text-slate-400 block mt-1">فقط JPG یا PNG؛ تصاویر بزرگ‌تر به‌صورت خودکار کم‌حجم می‌شوند</small>
                <div class="js-image-preview hidden mt-2 text-center">
                    <button type="button" class="js-image-preview-open rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-600 transition hover:bg-blue-100">مشاهده پیش‌نمایش</button>
                    <p class="js-image-preview-name mt-1 truncate text-[10px] text-slate-500"></p>
                </div>
                <small class="text-[10px] text-slate-400 block mt-1">وضعیت این مدرک بر اساس قانون نوع بار تعیین می‌شود.</small>
            </div>
        </div>

    </div>
</div>

@once
    <script>
        (() => {
            const persianDigits = '۰۱۲۳۴۵۶۷۸۹';
            const arabicDigits = '٠١٢٣٤٥٦٧٨٩';

            const normalizeCitsCode = (value) => {
                const englishDigits = String(value ?? '')
                    .replace(/[۰-۹]/g, (digit) => persianDigits.indexOf(digit))
                    .replace(/[٠-٩]/g, (digit) => arabicDigits.indexOf(digit));

                return `D${englishDigits.replace(/[^0-9]/g, '')}`;
            };

            const initializeCitsCode = () => {
                const input = document.getElementById('cits_code');

                if (!input || input.dataset.citsPrefixBound === 'true') {
                    return;
                }

                input.dataset.citsPrefixBound = 'true';

                const normalize = () => {
                    const normalizedValue = normalizeCitsCode(input.value);

                    if (input.value !== normalizedValue) {
                        input.value = normalizedValue;
                    }
                };

                input.addEventListener('input', normalize);
                input.addEventListener('blur', normalize);
                input.addEventListener('keydown', (event) => {
                    if (
                        (event.key === 'Backspace' && input.selectionStart <= 1 && input.selectionEnd <= 1)
                        || (event.key === 'Delete' && input.selectionStart === 0)
                    ) {
                        event.preventDefault();
                    }
                });

                normalize();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeCitsCode, { once: true });
            } else {
                initializeCitsCode();
            }
        })();
    </script>
@endonce

<div id="image_preview_modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/75 p-4" role="dialog" aria-modal="true" aria-labelledby="image_preview_title">
    <div class="relative flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h3 id="image_preview_title" class="text-sm font-black text-slate-800">پیش‌نمایش تصویر انتخاب‌شده</h3>
            <button type="button" class="js-image-preview-close flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-600 transition hover:bg-rose-100 hover:text-rose-600" aria-label="بستن پیش‌نمایش">×</button>
        </div>
        <div class="flex min-h-0 flex-1 items-center justify-center bg-slate-100 p-4">
            <img id="image_preview_modal_img" class="max-h-[75vh] max-w-full rounded-lg object-contain" alt="تصویر انتخاب‌شده">
        </div>
        <p id="image_preview_modal_name" class="truncate border-t border-slate-200 px-4 py-3 text-center text-xs text-slate-500"></p>
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
                    <option value="{{ $country->id }}" data-price="{{ ($isAssociationMember ?? false) ? ($country->member_price ?? $country->price ?? 0) : ($country->non_member_price ?? $country->price ?? 0) }}">{{ $country->name }}</option>
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
