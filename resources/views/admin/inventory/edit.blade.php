@extends('layouts.admin')

@section('header_title')
    انبار کل / ویرایش پارت / <span class="text-indigo-600 font-black">#{{ $batch->id }}</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="bg-slate-900 p-6 text-white flex justify-between items-center">
        <h2 class="font-black text-lg flex items-center gap-2">✏️ ویرایش اطلاعات پارت #{{ $batch->id }}</h2>
        <a href="{{ route('admin.inventory.index') }}" class="text-slate-400 hover:text-white text-sm font-bold transition">بازگشت ↩</a>
    </div>

    <form action="{{ route('admin.inventory.update', $batch->id) }}" method="POST" class="p-8">
        @csrf
        
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

        <div class="space-y-6">
            <div>
                <label class="block mb-2 font-bold text-sm text-slate-700">کشور مقصد <span class="text-rose-500">*</span></label>
                <select name="country_id" required class="w-full p-3.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white text-slate-800 font-bold">
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}" {{ $batch->country_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
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