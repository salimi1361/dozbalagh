@extends('layouts.admin')

@section('header_title')
    مدیریت مدارک / <span class="text-indigo-400 font-black">انواع بار</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto pb-10">
    
    {{-- هدر تیره --}}
    <div class="bg-[#0f172a] rounded-t-3xl p-6 md:p-8 flex flex-col md:flex-row justify-between items-center gap-4 relative overflow-hidden">
        <div class="absolute -left-10 -top-10 w-40 h-40 bg-indigo-500 opacity-20 blur-3xl rounded-full pointer-events-none"></div>
        <div class="z-10 text-right w-full">
            <h2 class="text-xl md:text-2xl font-black text-white mb-2">لیست قوانین داینامیک دوزوله</h2>
            <p class="text-xs md:text-sm text-slate-400 font-medium">در این بخش برای هر نوع عملیات، وضعیت نمایش و اجباری بودن تک‌تک فیلدها را تعیین کنید.</p>
        </div>
    </div>

    {{-- جدول اطلاعات --}}
    <div class="bg-white rounded-b-3xl shadow-sm border-x border-b border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">شناسه</th>
                        <th class="p-4">نوع بار / عملیات</th>
                        <th class="p-4 text-slate-400 text-xs font-normal">وضعیت فیلدها به صورت داینامیک تنظیم می‌شود</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rules as $rule)
                    <tr class="border-b border-slate-100 hover:bg-slate-50/80 transition duration-200">
                        <td class="p-4 font-mono text-slate-400">{{ $rule->id }}</td>
                        <td class="p-4 font-black text-slate-800 text-base">{{ $rule->cargo_type }}</td>
                        <td class="p-4">
                            <span class="bg-slate-100 text-slate-500 px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200">
                                ⚙️ دارای تنظیمات اختصاصی ({{ count($rule->fields_config ?? []) }} فیلد)
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <button type="button" 
                                    data-id="{{ $rule->id }}" 
                                    data-title="{{ $rule->cargo_type }}" 
                                    data-config="{{ json_encode($rule->fields_config ?? []) }}"
                                    onclick="openEditModal(this)"
                                    class="bg-indigo-50 text-indigo-600 border border-indigo-200 px-4 py-2 rounded-xl text-xs font-bold hover:bg-indigo-600 hover:text-white transition shadow-sm">
                                ✏️ تنظیمات فیلدها
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- مودال پاپ‌آپ ویرایش تنظیمات پیشرفته --}}
<div id="editModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl my-auto overflow-hidden transform transition-all">
        <div class="bg-slate-50 p-5 border-b border-slate-200 flex justify-between items-center sticky top-0 z-10">
            <h3 class="font-black text-slate-800 text-lg">پیکربندی فیلدها: <span id="modalCargoTitle" class="text-indigo-600"></span></h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-rose-500 text-2xl font-black leading-none">&times;</button>
        </div>
        
        <form id="editForm" method="POST" action="">
            @csrf
            @method('PUT')
            
            <div class="p-6">
                <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold p-3 rounded-xl mb-6 flex gap-2 items-center">
                    <span class="text-base">💡</span>
                    <p><strong>اجباری:</strong> نمایش با ستاره قرمز (شرکت باید پر کند) | <strong>اختیاری:</strong> نمایش بدون ستاره | <strong>مخفی:</strong> کلاً از فرم شرکت حذف می‌شود.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    {{-- 1. گروه مالی و فیش --}}
                    <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50">
                        <h4 class="text-sm font-black text-slate-700 mb-4 border-b pb-2">💳 بخش مالی و فیش سازمان</h4>
                        <div class="space-y-3">
                            @foreach(['receipt_code' => 'شماره فیش', 'receipt_amount' => 'مبلغ واریزی', 'receipt_file' => 'تصویر فیش'] as $key => $label)
                            <div class="flex justify-between items-center">
                                <label class="text-xs font-bold text-slate-600">{{ $label }}</label>
                                <select name="fields_config[{{ $key }}]" id="conf_{{ $key }}" class="border border-slate-300 rounded-lg text-xs p-1.5 bg-white w-40">
                                    <option value="required">🔴 اجباری</option>
                                    <option value="optional">🟡 اختیاری</option>
                                    <option value="hidden">⚪ مخفی</option>
                                </select>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 2. گروه CMR --}}
                    <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50">
                        <h4 class="text-sm font-black text-slate-700 mb-4 border-b pb-2">📄 بخش بارنامه (CMR)</h4>
                        <div class="space-y-3">
                            @foreach(['trip_code' => 'کد سفر (با J)', 'cmr_date' => 'تاریخ CMR', 'cmr_file' => 'تصویر CMR'] as $key => $label)
                            <div class="flex justify-between items-center">
                                <label class="text-xs font-bold text-slate-600">{{ $label }}</label>
                                <select name="fields_config[{{ $key }}]" id="conf_{{ $key }}" class="border border-slate-300 rounded-lg text-xs p-1.5 bg-white w-40">
                                    <option value="required">🔴 اجباری</option>
                                    <option value="optional">🟡 اختیاری</option>
                                    <option value="hidden">⚪ مخفی</option>
                                </select>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 3. گروه کارنه تیر --}}
                    <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50">
                        <h4 class="text-sm font-black text-slate-700 mb-4 border-b pb-2">📘 بخش کارنه تیر</h4>
                        <div class="space-y-3">
                            @foreach(['tir_carnet_number' => 'شماره کارنه تیر', 'tir_carnet_date' => 'تاریخ کارنه', 'tir_file' => 'تصویر کارنه تیر'] as $key => $label)
                            <div class="flex justify-between items-center">
                                <label class="text-xs font-bold text-slate-600">{{ $label }}</label>
                                <select name="fields_config[{{ $key }}]" id="conf_{{ $key }}" class="border border-slate-300 rounded-lg text-xs p-1.5 bg-white w-40">
                                    <option value="required">🔴 اجباری</option>
                                    <option value="optional">🟡 اختیاری</option>
                                    <option value="hidden">⚪ مخفی</option>
                                </select>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 4. گروه اظهارنامه --}}
                    <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50">
                        <h4 class="text-sm font-black text-slate-700 mb-4 border-b pb-2">📝 بخش اظهارنامه</h4>
                        <div class="space-y-3">
                            @foreach(['declaration_file' => 'تصویر اظهارنامه'] as $key => $label)
                            <div class="flex justify-between items-center">
                                <label class="text-xs font-bold text-slate-600">{{ $label }}</label>
                                <select name="fields_config[{{ $key }}]" id="conf_{{ $key }}" class="border border-slate-300 rounded-lg text-xs p-1.5 bg-white w-40">
                                    <option value="required">🔴 اجباری</option>
                                    <option value="optional">🟡 اختیاری</option>
                                    <option value="hidden">⚪ مخفی</option>
                                </select>
                            </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
            
            <div class="p-5 bg-slate-50 border-t border-slate-200 flex justify-end gap-3 sticky bottom-0">
                <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-100 transition">انصراف</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 rounded-xl text-sm font-bold text-white hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition">💾 ذخیره تغییرات</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(btn) {
        let id = btn.getAttribute('data-id');
        let title = btn.getAttribute('data-title');
        let config = JSON.parse(btn.getAttribute('data-config') || '{}');
        
        document.getElementById('modalCargoTitle').innerText = title;
        document.getElementById('editForm').action = `/admin/cargo-document-rules/${id}`;
        
        // تنظیم مقادیر فیلدها از دیتابیس (یا حالت پیش‌فرض مخفی در صورت نبود مقدار)
        let fields = ['receipt_code', 'receipt_amount', 'receipt_file', 'trip_code', 'cmr_date', 'cmr_file', 'tir_carnet_number', 'tir_carnet_date', 'tir_file', 'declaration_file'];
        
        fields.forEach(field => {
            let selectElem = document.getElementById('conf_' + field);
            if(selectElem) {
                selectElem.value = config[field] || 'hidden';
            }
        });
        
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
</script>
@endsection