@extends('layouts.admin')

@section('header_title')
    تنظیمات پایه / <span class="text-blue-600 font-black">مدیریت کشورهای مقصد</span>
@endsection

@section('header_actions')
    <a href="{{ route('admin.countries.permit-statuses.index') }}" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-black text-blue-700 shadow-sm transition hover:bg-blue-100">فعال/غیرفعال‌سازی مجوزها</a>
    <button onclick="openCountryModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 shadow-sm transition">
        ➕ افزودن کشور جدید
    </button>
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-4">
        <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
            <div class="pointer-events-none absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
            <div class="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>
            <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21a9 9 0 1 0 0-18m0 18c2.2 0 4-4.03 4-9s-1.8-9-4-9m0 18c-2.2 0-4-4.03-4-9s1.8-9 4-9m-8.5 6h17M3.5 15h17"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-black sm:text-2xl">مدیریت کشورهای مقصد</h1>
                        <p class="mt-1 text-xs font-semibold text-blue-100">تنظیم مقصدها، تعرفه، اعتبار و انواع مجوز قابل درخواست برای هر کشور</p>
                    </div>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="grid grid-cols-3 gap-2">
                        <div class="rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 text-center"><span class="block text-[9px] font-bold text-blue-100">کل کشورها</span><strong class="mt-0.5 block text-lg font-black">{{ number_format($countries->count()) }}</strong></div>
                        <div class="rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 text-center"><span class="block text-[9px] font-bold text-blue-100">فعال</span><strong class="mt-0.5 block text-lg font-black text-emerald-300">{{ number_format($countries->where('is_active', true)->count()) }}</strong></div>
                        <div class="rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 text-center"><span class="block text-[9px] font-bold text-blue-100">غیرفعال</span><strong class="mt-0.5 block text-lg font-black text-amber-300">{{ number_format($countries->where('is_active', false)->count()) }}</strong></div>
                    </div>
                    <button type="button" onclick="openCountryModal()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-black text-blue-800 shadow-md transition hover:bg-blue-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        افزودن کشور
                    </button>
                </div>
            </div>
        </section>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div><h2 class="font-black text-slate-800">فهرست کشورهای مجاز دوزوله</h2><p class="mt-1 text-xs text-slate-500">کشورهای فعال در فرم ثبت درخواست شرکت‌ها نمایش داده می‌شوند.</p></div>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full text-right text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <th class="p-4 w-16 text-center">شناسه</th>
                        <th class="p-4">نام کشور</th>
                        <th class="p-4 text-center">کد اختصاری</th>
                        <th class="p-4 text-center text-blue-700">تعرفه عضو (ریال)</th>
                        <th class="p-4 text-center text-violet-700">تعرفه غیرعضو (ریال)</th>
                        <th class="p-4 text-center">اعتبار (روز)</th>
                        <th class="p-4 text-center">نوع مجوزها</th> 
                        <th class="p-4 text-center">وضعیت</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                    @forelse($countries as $country)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4 text-center text-slate-400 font-mono">{{ $country->id }}</td>
                            <td class="p-4 font-bold text-slate-700">{{ $country->name }}</td>
                            <td class="p-4 text-center font-mono text-slate-500">{{ $country->code ?? '---' }}</td>
                            <td class="p-4 text-center font-mono font-bold text-blue-700">{{ number_format($country->member_price ?? $country->price ?? 0) }}</td>
                            <td class="p-4 text-center font-mono font-bold text-violet-700">{{ number_format($country->non_member_price ?? $country->price ?? 0) }}</td>
                            <td class="p-4 text-center font-mono font-bold text-emerald-700">
                                {{ (int) $country->validity_days === 0 ? 'بدون محدودیت' : number_format($country->validity_days) }}
                            </td>
                            
                            <td class="p-4 text-center">
                                @if(!empty($country->allowed_permit_types) && is_array($country->allowed_permit_types))
                                    <div class="flex flex-wrap justify-center gap-1">
                                        @foreach($country->allowed_permit_types as $permit)
                                            @php
                                                $labels = [
                                                    'bilateral' => 'دوجانبه',
                                                    'transit' => 'ترانزیت',
                                                    'bilateral_transit' => 'دوجانبه ترانزیت',
                                                    'third_country_transit' => 'ترانزیت ثالث',
                                                    'third_country' => 'ثالث'
                                                ];
                                                $showLabel = $labels[$permit] ?? str_replace('_', ' ', $permit);
                                                $isRequestable = in_array($permit, $country->requestablePermitTypes(), true);
                                            @endphp
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $isRequestable ? 'bg-indigo-50 text-indigo-600 border-indigo-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                                {{ $showLabel }}{{ $isRequestable ? '' : ' (غیرفعال)' }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs">---</span>
                                @endif
                            </td>

                            <td class="p-3 text-center">
                                <div class="flex flex-col items-center gap-1.5">
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $country->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $country->is_active ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                    <form action="{{ route('admin.countries.toggle-status', $country) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="rounded-lg px-2 py-1 text-[10px] font-black transition {{ $country->is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                            {{ $country->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <td class="p-4 text-center flex items-center justify-center gap-2">
                                {{-- 🟢 اصلاحیه طلایی: اضافه کردن فیلد به دیتای دکمه جهت خواندن قطعی توسط جاوااسکریپت بدون باگ کش مرورگر --}}
                                <button type="button"
                                        data-permits='@json($country->allowed_permit_types ?? [])'
                                        data-requestable-permits='@json($country->requestablePermitTypes())'
                                        data-validity="{{ $country->validity_days ?? 30 }}"
                                        data-name="{{ $country->name }}"
                                        data-code="{{ $country->code ?? '' }}"
                                        data-member-price="{{ $country->member_price ?? $country->price ?? 0 }}"
                                        data-non-member-price="{{ $country->non_member_price ?? $country->price ?? 0 }}"
                                        onclick="openEditCountryModal(this, '{{ $country->id }}')"
                                        class="text-indigo-600 hover:text-indigo-800 font-bold text-xs bg-indigo-50 px-3 py-1.5 rounded-md transition flex items-center gap-1">
                                    ✏️ ویرایش
                                </button>
                                
                                <form action="{{ route('admin.countries.destroy', $country->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold text-xs bg-rose-50 px-3 py-1.5 rounded-md transition flex items-center gap-1">
                                           🗑️ حذف
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400 font-bold">هیچ کشوری در سیستم ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- مودال افزودن کشور --}}
    <div id="country_modal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between bg-gradient-to-l from-[#064e3b] via-[#073b3a] to-[#071b2a] p-4 text-white">
                <h3 class="font-black text-lg">🌍 ثبت کشور جدید</h3>
                <button onclick="closeCountryModal()" class="text-slate-400 hover:text-white text-2xl">✕</button>
            </div>
            
            <form action="{{ route('admin.countries.store') }}" method="POST" class="flex min-h-0 flex-col">
                @csrf
                <div class="min-h-0 space-y-4 overflow-y-auto p-4 sm:p-5">
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">نام کشور <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required class="w-full p-3 border border-slate-300 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="مثال: ترکیه">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 font-bold mb-2 text-sm">کد اختصاری (اختیاری)</label>
                            <input type="text" name="code" class="w-full p-3 border border-slate-300 rounded-xl text-left font-mono focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="مثال: TR" dir="ltr">
                        </div>
                        <div>
                            <label class="block text-slate-700 font-bold mb-2 text-sm">اعتبار پیش‌فرض (روز) <span class="text-rose-500">*</span></label>
                            <input type="number" name="validity_days" value="30" required min="0" class="w-full p-3 border border-slate-300 rounded-xl text-left font-mono focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" dir="ltr">
                            <p class="mt-1 text-[10px] text-slate-500">صفر یعنی تمدید بدون محدودیت زمانی، حتی در همان روز.</p>
                        </div>
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label class="block text-slate-700 font-bold text-sm">تعرفه دوزوله بر اساس عضویت (ریال) <span class="text-rose-500">*</span></label>
                            <span class="text-[10px] font-bold text-slate-400">فعلاً فقط ذخیره‌سازی تعرفه</span>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="rounded-xl border border-blue-100 bg-blue-50/60 p-3 text-xs font-bold text-blue-800">عضو انجمن
                                <input type="number" name="member_price" value="{{ old('member_price', 0) }}" required min="0" class="mt-2 w-full rounded-lg border border-blue-200 bg-white p-2.5 text-left font-mono text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" dir="ltr">
                            </label>
                            <label class="rounded-xl border border-violet-100 bg-violet-50/60 p-3 text-xs font-bold text-violet-800">غیرعضو انجمن
                                <input type="number" name="non_member_price" value="{{ old('non_member_price', 0) }}" required min="0" class="mt-2 w-full rounded-lg border border-violet-200 bg-white p-2.5 text-left font-mono text-sm text-slate-800 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500" dir="ltr">
                            </label>
                        </div>
                        <p class="mt-2 text-[10px] text-slate-500">اعمال این دو تعرفه در فرایند ثبت درخواست در مرحله بعد متصل خواهد شد.</p>
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">مجوزهای تعریف‌شده برای کشور و انبار <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-1 gap-2.5 p-4 border border-slate-200 rounded-xl bg-slate-50">
                            @php
                                $permitOptions = [
                                    'bilateral' => 'دوجانبه', 
                                    'transit' => 'ترانزیت', 
                                    'bilateral_transit' => 'دوجانبه ترانزیت',
                                    'third_country_transit' => 'ترانزیت ثالث',
                                    'third_country' => 'ثالث'
                                ];
                            @endphp
                            @foreach($permitOptions as $key => $label)
                                <label class="flex items-center gap-2 cursor-pointer text-sm font-bold text-slate-600 hover:text-slate-900 transition">
                                    <input type="checkbox" name="permit_types[]" value="{{ $key }}" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t bg-slate-50 p-4">
                    <button type="button" onclick="closeCountryModal()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 transition hover:bg-slate-200">انصراف</button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold transition hover:bg-blue-700">💾 ذخیره کشور</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال ویرایش کشور --}}
    <div id="edit_country_modal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between bg-gradient-to-l from-[#064e3b] via-[#073b3a] to-[#071b2a] p-4 text-white">
                <h3 class="font-black text-lg">✏️ ویرایش کشور</h3>
                <button onclick="closeEditCountryModal()" class="text-slate-400 hover:text-white text-2xl">✕</button>
            </div>
            
            <form id="edit_country_form" action="" method="POST" class="flex min-h-0 flex-col">
                @csrf
                @method('PUT')
                <div class="min-h-0 space-y-4 overflow-y-auto p-4 sm:p-5">
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">نام کشور <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="edit_name" required class="w-full p-3 border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 font-bold mb-2 text-sm">کد اختصاری (اختیاری)</label>
                            <input type="text" name="code" id="edit_code" class="w-full p-3 border border-slate-300 rounded-xl text-left font-mono focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" dir="ltr">
                        </div>
                        <div>
                            <label class="block text-slate-700 font-bold mb-2 text-sm">اعتبار (روز) <span class="text-rose-500">*</span></label>
                            <input type="number" name="validity_days" id="edit_validity_days" required min="0" class="w-full p-3 border border-slate-300 rounded-xl text-left font-mono focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" dir="ltr">
                            <p class="mt-1 text-[10px] text-slate-500">صفر یعنی تمدید بدون محدودیت زمانی، حتی در همان روز.</p>
                        </div>
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label class="block text-slate-700 font-bold text-sm">تعرفه دوزوله بر اساس عضویت (ریال) <span class="text-rose-500">*</span></label>
                            <span class="text-[10px] font-bold text-slate-400">فعلاً فقط ذخیره‌سازی تعرفه</span>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="rounded-xl border border-blue-100 bg-blue-50/60 p-3 text-xs font-bold text-blue-800">عضو انجمن
                                <input type="number" name="member_price" id="edit_member_price" required min="0" class="mt-2 w-full rounded-lg border border-blue-200 bg-white p-2.5 text-left font-mono text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" dir="ltr">
                            </label>
                            <label class="rounded-xl border border-violet-100 bg-violet-50/60 p-3 text-xs font-bold text-violet-800">غیرعضو انجمن
                                <input type="number" name="non_member_price" id="edit_non_member_price" required min="0" class="mt-2 w-full rounded-lg border border-violet-200 bg-white p-2.5 text-left font-mono text-sm text-slate-800 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500" dir="ltr">
                            </label>
                        </div>
                        <p class="mt-2 text-[10px] text-slate-500">اعمال این دو تعرفه در فرایند ثبت درخواست در مرحله بعد متصل خواهد شد.</p>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">مجوزهای تعریف‌شده برای کشور و انبار <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-1 gap-2.5 p-4 border border-slate-200 rounded-xl bg-slate-50" id="edit_permit_types_container">
                            @php
                                $editPermitOptions = [
                                    'bilateral' => 'دوجانبه', 
                                    'transit' => 'ترانزیت', 
                                    'bilateral_transit' => 'دوجانبه ترانزیت',
                                    'third_country_transit' => 'ترانزیت ثالث',
                                    'third_country' => 'ثالث'
                                ];
                            @endphp
                            @foreach($editPermitOptions as $key => $label)
                                <label class="flex items-center gap-2 cursor-pointer text-sm font-bold text-slate-600 hover:text-slate-900 transition">
                                    <input type="checkbox" name="permit_types[]" value="{{ $key }}" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 edit-permit-checkbox">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-[10px] font-bold text-amber-700">این فهرست به تعریف کشور و انبار مربوط است. برای توقف انتخاب یک مجوز، از بخش زیر استفاده کنید.</p>
                    </div>
                    <div>
                        <input type="hidden" name="requestable_permit_types_present" value="1">
                        <label class="block text-slate-700 font-bold mb-2 text-sm">قابل انتخاب در درخواست‌های جدید</label>
                        <div class="grid grid-cols-1 gap-2.5 p-4 border border-emerald-200 rounded-xl bg-emerald-50/50">
                            @foreach($editPermitOptions as $key => $label)
                                <label class="flex items-center justify-between gap-3 text-sm font-bold text-slate-600 transition requestable-permit-label" data-permit-key="{{ $key }}">
                                    <span>{{ $label }}</span>
                                    <input type="checkbox" name="requestable_permit_types[]" value="{{ $key }}" class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 edit-requestable-permit-checkbox">
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-[10px] font-bold text-emerald-700">برداشتن تیک در این بخش فقط انتخاب کاربر را می‌بندد و هیچ تأثیری بر موجودی و سوابق انبار ندارد.</p>
                    </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t bg-slate-50 p-4">
                    <button type="button" onclick="closeEditCountryModal()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 transition hover:bg-slate-200">انصراف</button>
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold transition hover:bg-indigo-700">🔄 بروزرسانی کشور</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
    function openCountryModal() { 
        $('#country_modal').removeClass('hidden'); 
    }
    function closeCountryModal() { 
        $('#country_modal').addClass('hidden'); 
    }

    function openEditCountryModal(btnElement, id) {
        // پر کردن مقادیر داینامیک از دیتابیس در فرم مودال
        $('#edit_name').val($(btnElement).attr('data-name') || '');
        $('#edit_code').val($(btnElement).attr('data-code') || '');
        $('#edit_member_price').val($(btnElement).attr('data-member-price') || 0);
        $('#edit_non_member_price').val($(btnElement).attr('data-non-member-price') || 0);
        
        // 🟢 اصلاحیه قطعی: واکشی امن دیتا مستقیماً از دیتا-ویژگی المنت HTML دکمه جدول
        let validityDays = $(btnElement).data('validity');
        if (validityDays === undefined || validityDays === null || validityDays === '') validityDays = 30;
        $('#edit_validity_days').val(validityDays); 
        
        let permits = $(btnElement).data('permits');
        $('.edit-permit-checkbox').prop('checked', false);
        
        if(permits && Array.isArray(permits)) {
            permits.forEach(function(permitValue) {
                $('.edit-permit-checkbox[value="' + permitValue + '"]').prop('checked', true);
            });
        }

        let requestablePermits = [];
        try {
            requestablePermits = JSON.parse($(btnElement).attr('data-requestable-permits') || '[]');
        } catch (error) {
            requestablePermits = [];
        }
        $('.edit-requestable-permit-checkbox').prop('checked', false);
        requestablePermits.forEach(function(permitValue) {
            $('.edit-requestable-permit-checkbox[value="' + permitValue + '"]').prop('checked', true);
        });
        syncRequestablePermitCheckboxes();
        
        let updateUrl = "{{ route('admin.countries.update', ':id') }}";
        updateUrl = updateUrl.replace(':id', id);
        $('#edit_country_form').attr('action', updateUrl);
        
        $('#edit_country_modal').removeClass('hidden');
    }

    function syncRequestablePermitCheckboxes() {
        $('.edit-requestable-permit-checkbox').each(function() {
            const permitValue = $(this).val();
            const isDefined = $('.edit-permit-checkbox[value="' + permitValue + '"]').is(':checked');
            $(this).prop('disabled', !isDefined);
            $(this).closest('.requestable-permit-label')
                .toggleClass('opacity-40 cursor-not-allowed', !isDefined)
                .toggleClass('cursor-pointer', isDefined);
            if (!isDefined) $(this).prop('checked', false);
        });
    }

    $(document).on('change', '.edit-permit-checkbox', syncRequestablePermitCheckboxes);
    
    function closeEditCountryModal() { 
        $('#edit_country_modal').addClass('hidden'); 
    }

    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'عملیات موفق', text: @json(session('success')), confirmButtonText: 'باشه' });
    @endif

    @if($errors->any())
        Swal.fire({ icon: 'error', title: 'خطا در اطلاعات', text: @json($errors->first()), confirmButtonText: 'متوجه شدم' });
    @endif
</script>
@endsection
