@extends('layouts.app')

@section('header_title', 'تنظیمات امنیتی و حساب')

@section('content')
<div id="change-password" class="mx-auto max-w-3xl space-y-5 text-right" dir="rtl">
    <section class="company-page-header rounded-2xl border border-slate-200 p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-black text-slate-800">تنظیمات امنیتی و حساب</h1>
                <p class="mt-1 text-sm text-slate-500">رمز عبور حساب شرکت را از این بخش تغییر دهید.</p>
            </div>
            <span class="rounded-full bg-blue-50 px-4 py-2 text-xs font-black text-blue-700">امنیت حساب</span>
        </div>
        @if(session('success'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <ul class="list-disc space-y-1 pr-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex items-center gap-3 border-b border-slate-100 pb-4">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xl">🔑</span>
            <div>
                <h2 class="font-black text-slate-800">تغییر رمز عبور</h2>
                <p class="mt-1 text-xs font-bold text-slate-400">برای حفظ امنیت، رمز فعلی را وارد کنید.</p>
            </div>
        </div>
        <form action="{{ route('company.password.update') }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <label class="block text-sm font-bold text-slate-700">رمز عبور فعلی
                <input type="password" name="current_password" required autocomplete="current-password" placeholder="رمز فعلی را وارد کنید" dir="ltr" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-3 text-left focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-50">
            </label>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block text-sm font-bold text-slate-700">رمز عبور جدید
                    <input type="password" name="password" required minlength="6" autocomplete="new-password" placeholder="حداقل ۶ کاراکتر" dir="ltr" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-3 text-left focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-50">
                </label>
                <label class="block text-sm font-bold text-slate-700">تکرار رمز عبور جدید
                    <input type="password" name="password_confirmation" required minlength="6" autocomplete="new-password" placeholder="رمز جدید را تکرار کنید" dir="ltr" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-3 text-left focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-50">
                </label>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('company.profile.edit') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-600 hover:bg-slate-50">بازگشت به اطلاعات شرکت</a>
                <button class="rounded-xl bg-slate-800 px-6 py-3 text-sm font-black text-white shadow-sm transition hover:bg-slate-700">ثبت رمز جدید</button>
            </div>
        </form>
    </section>
</div>
@endsection
