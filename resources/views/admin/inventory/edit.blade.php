@extends('layouts.admin')

@section('header_title')
    انبار کل / ویرایش پارت / <span class="text-indigo-600 font-black">#{{ $batch->id }}</span>
@endsection

@section('content')
@php($permitTypeLabels = config('permit_types.labels', []))
<div class="max-w-2xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="bg-slate-900 p-6 text-white flex justify-between items-center">
        <h2 class="font-black text-lg flex items-center gap-2">✏️ ویرایش اطلاعات پارت #{{ $batch->id }}</h2>
        <a href="{{ route('admin.inventory.index') }}" class="text-slate-400 hover:text-white text-sm font-bold transition">بازگشت ↩</a>
    </div>

    <form action="{{ route('admin.inventory.update', $batch->id) }}" method="POST" class="p-8">
        @csrf
        @method('PUT')
        
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-slate-500">🔒</span>
                <p class="text-xs font-bold text-slate-500">اطلاعات پایه (غیرقابل ویرایش به دلیل ثبت سریال‌ها در سیستم)</p>
            </div>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="bg-white p-3 rounded-lg border border-slate-100 shadow-sm">
                    <div class="text-[10px] text-slate-400 font-bold mb-1">از سریال</div>
                    <div class="font-black text-indigo-600 font-mono">{{ $batch->serial_start }}</div>
                </div>
                <div class="bg-white p-3 rounded-lg border border-slate-100 shadow-sm">
                    <div class="text-[10px] text-slate-400 font-bold mb-1">تا سریال</div>
                    <div class="font-black text-indigo-600 font-mono">{{ $batch->serial_end }}</div>
                </div>
                <div class="bg-white p-3 rounded-lg border border-slate-100 shadow-sm">
                    <div class="text-[10px] text-slate-400 font-bold mb-1">تعداد کل</div>
                    <div class="font-black text-slate-700">{{ $batch->total_quantity }} عدد</div>
                </div>
            </div>
        </div>

        <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 mb-8 flex items-start gap-3">
            <span class="text-xl">⚠️</span>
            <div>
                <h4 class="text-sm font-black text-rose-800 mb-1">نکته مهم در صورت ثبت اشتباه:</h4>
                <p class="text-xs text-rose-600 leading-relaxed font-medium">
                    اگر در زمان تولید، «شماره شروع» یا «شماره پایان» را اشتباه وارد کرده‌اید، امکان اصلاح آن‌ها وجود ندارد. در این حالت باید از بخش لیست پارت‌ها، کل این پارت را حذف کرده و یک پارت جدید با شماره‌های صحیح تولید کنید.
                </p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block mb-2 font-bold text-sm text-slate-700">کشور مقصد <span class="text-rose-500">*</span></label>
                <select name="country_id" id="inventory_edit_country" required class="w-full p-3.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white text-slate-800 font-bold">
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}" data-permits='@json($c->allowed_permit_types ?? [])' {{ $batch->country_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block mb-2 font-bold text-sm text-slate-700">نوع مجوز <span class="text-rose-500">*</span></label>
                <select name="permit_type" id="inventory_edit_permit_type" data-current-permit="{{ $batch->permit_type }}" required class="w-full p-3.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white text-slate-800 font-bold">
                    <option value="">انتخاب نوع مجوز...</option>
                </select>
                <p class="mt-2 text-xs font-semibold text-slate-500">برای پارت‌های قدیمی که نوع مجوزشان مشخص نیست، اینجا نوع صحیح را تعیین کنید.</p>
            </div>
            <div>
                <label for="inventory_edit_received_date" class="block mb-2 font-bold text-sm text-slate-700">تاریخ تحویل/ثبت (میلادی) <span class="text-rose-500">*</span></label>
                <input type="date" name="received_date" id="inventory_edit_received_date" value="{{ old('received_date', $batch->received_date?->format('Y-m-d')) }}" required lang="en" dir="ltr" class="w-full p-2.5 border border-slate-300 rounded-xl font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white text-slate-800">
            </div>
            <div>
                <label for="inventory_edit_expiry_date" class="block mb-2 font-bold text-sm text-slate-700">تاریخ پایان/انقضا (اختیاری)</label>
                <input type="date" name="expiry_date" id="inventory_edit_expiry_date" value="{{ old('expiry_date', $batch->expiry_date?->format('Y-m-d')) }}" lang="en" dir="ltr" class="w-full p-2.5 border border-slate-300 rounded-xl font-mono focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition bg-white text-slate-800">
            </div>
        </div>
        
        <div class="mt-8 border-t border-slate-100 pt-6">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3.5 rounded-xl font-bold w-full transition shadow-lg shadow-indigo-200 text-lg">
                💾 ذخیره تغییرات
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    const inventoryPermitLabels = @json($permitTypeLabels);
    const editCountry = document.getElementById('inventory_edit_country');
    const editPermit = document.getElementById('inventory_edit_permit_type');

    function refreshInventoryEditPermitTypes() {
        if (!editCountry || !editPermit) return;
        const selectedCountry = editCountry.options[editCountry.selectedIndex];
        let permitTypes = [];
        try {
            permitTypes = selectedCountry && selectedCountry.dataset.permits ? JSON.parse(selectedCountry.dataset.permits) : [];
        } catch (error) {
            permitTypes = [];
        }

        const currentPermit = editPermit.dataset.currentPermit || editPermit.value;
        editPermit.innerHTML = '<option value="">انتخاب نوع مجوز...</option>';
        permitTypes.forEach((type) => {
            const option = document.createElement('option');
            option.value = type;
            option.textContent = inventoryPermitLabels[type] || type;
            option.selected = type === currentPermit;
            editPermit.appendChild(option);
        });
        editPermit.disabled = permitTypes.length === 0;
        if (permitTypes.length === 0 && editCountry.value) {
            editPermit.innerHTML = '<option value="">برای این کشور نوع مجوز ثبت نشده است</option>';
        }
    }

    editCountry?.addEventListener('change', () => {
        editPermit.dataset.currentPermit = '';
        refreshInventoryEditPermitTypes();
    });
    refreshInventoryEditPermitTypes();
</script>
@endsection
