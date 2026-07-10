@extends('layouts.admin')

@section('header_title')
    تنظیمات پایه / <span class="text-blue-600 font-black">مدیریت کشورهای مقصد</span>
@endsection

@section('header_actions')
    <button onclick="openCountryModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 shadow-sm transition">
        ➕ افزودن کشور جدید
    </button>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-950 p-6 text-white flex justify-between items-center">
            <div>
                <h1 class="text-base font-black">لیست کشورهای مجاز دوزوله</h1>
                <p class="text-slate-400 text-[11px] mt-0.5">کشورهایی که در این لیست فعال باشند، در فرم درخواست نمایش داده می‌شوند.</p>
            </div>
            <button onclick="openCountryModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black px-5 py-2.5 rounded-xl text-xs shadow-lg transition">
                ➕ افزودن کشور
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <th class="p-4 w-16 text-center">شناسه</th>
                        <th class="p-4">نام کشور</th>
                        <th class="p-4 text-center">کد اختصاری</th>
                        <th class="p-4 text-center">مبلغ (ریال)</th>
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
                            <td class="p-4 text-center font-mono font-bold text-slate-700">{{ number_format($country->price) }}</td>
                            
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
                                            @endphp
                                            <span class="bg-indigo-50 text-indigo-600 border border-indigo-200 px-2 py-0.5 rounded text-[10px] font-bold">
                                                {{ $showLabel }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs">---</span>
                                @endif
                            </td>

                            <td class="p-4 text-center">
                                @if($country->is_active)
                                    <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-md text-xs font-bold">فعال</span>
                                @else
                                    <span class="bg-rose-100 text-rose-700 px-3 py-1 rounded-md text-xs font-bold">غیرفعال</span>
                                @endif
                            </td>
                            <td class="p-4 text-center flex items-center justify-center gap-2">
                                {{-- 🟢 اصلاحیه طلایی: اضافه کردن فیلد به دیتای دکمه جهت خواندن قطعی توسط جاوااسکریپت بدون باگ کش مرورگر --}}
                                <button type="button" 
                                        data-permits="{{ json_encode($country->allowed_permit_types ?? []) }}"
                                        data-validity="{{ $country->validity_days ?? 30 }}"
                                        onclick="openEditCountryModal(this, '{{ $country->id }}', '{{ $country->name }}', '{{ $country->code }}', '{{ $country->price }}')" 
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
                            <td colspan="7" class="p-8 text-center text-slate-400 font-bold">هیچ کشوری در سیستم ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- مودال افزودن کشور --}}
    <div id="country_modal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl max-w-md w-full shadow-2xl overflow-hidden border border-slate-200">
            <div class="bg-slate-950 p-5 flex justify-between items-center text-white">
                <h3 class="font-black text-lg">🌍 ثبت کشور جدید</h3>
                <button onclick="closeCountryModal()" class="text-slate-400 hover:text-white text-2xl">✕</button>
            </div>
            
            <form action="{{ route('admin.countries.store') }}" method="POST">
                @csrf
                <div class="p-6 space-y-5">
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
                            <input type="number" name="validity_days" value="30" required min="1" class="w-full p-3 border border-slate-300 rounded-xl text-left font-mono focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" dir="ltr">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">مبلغ هر دوزوله (ریال) <span class="text-rose-500">*</span></label>
                        <input type="number" name="price" value="{{ old('price', 0) }}" required min="0" class="w-full p-3 border border-slate-300 rounded-xl text-left font-mono focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" dir="ltr">
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">نوع مجوزهای مجاز <span class="text-rose-500">*</span></label>
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

                <div class="bg-slate-50 p-5 border-t flex justify-end gap-3">
                    <button type="button" onclick="closeCountryModal()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 transition hover:bg-slate-200">انصراف</button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold transition hover:bg-blue-700">💾 ذخیره کشور</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال ویرایش کشور --}}
    <div id="edit_country_modal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl max-w-md w-full shadow-2xl overflow-hidden border border-slate-200">
            <div class="bg-slate-950 p-5 flex justify-between items-center text-white">
                <h3 class="font-black text-lg">✏️ ویرایش کشور</h3>
                <button onclick="closeEditCountryModal()" class="text-slate-400 hover:text-white text-2xl">✕</button>
            </div>
            
            <form id="edit_country_form" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="p-6 space-y-5">
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
                            <input type="number" name="validity_days" id="edit_validity_days" required min="1" class="w-full p-3 border border-slate-300 rounded-xl text-left font-mono focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" dir="ltr">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">مبلغ هر دوزوله (ریال) <span class="text-rose-500">*</span></label>
                        <input type="number" name="price" id="edit_price" required min="0" class="w-full p-3 border border-slate-300 rounded-xl text-left font-mono focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" dir="ltr">
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-2 text-sm">نوع مجوزهای مجاز <span class="text-rose-500">*</span></label>
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
                    </div>
                </div>

                <div class="bg-slate-50 p-5 border-t flex justify-end gap-3">
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

    function openEditCountryModal(btnElement, id, name, code, price) {
        // پر کردن مقادیر داینامیک از دیتابیس در فرم مودال
        $('#edit_name').val(name);
        $('#edit_code').val(code !== '---' ? code : '');
        $('#edit_price').val(price); 
        
        // 🟢 اصلاحیه قطعی: واکشی امن دیتا مستقیماً از دیتا-ویژگی المنت HTML دکمه جدول
        let validityDays = $(btnElement).data('validity') || 30;
        $('#edit_validity_days').val(validityDays); 
        
        let permits = $(btnElement).data('permits');
        $('.edit-permit-checkbox').prop('checked', false);
        
        if(permits && Array.isArray(permits)) {
            permits.forEach(function(permitValue) {
                $('.edit-permit-checkbox[value="' + permitValue + '"]').prop('checked', true);
            });
        }
        
        let updateUrl = "{{ route('admin.countries.update', ':id') }}";
        updateUrl = updateUrl.replace(':id', id);
        $('#edit_country_form').attr('action', updateUrl);
        
        $('#edit_country_modal').removeClass('hidden');
    }
    
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
