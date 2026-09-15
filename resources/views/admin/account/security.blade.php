@extends('layouts.admin')

@section('header_title', 'امنیت حساب مدیر')

@section('content')
<div class="mx-auto max-w-2xl" dir="rtl">
    <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
        <h1 class="text-xl font-black text-slate-900">تغییر رمز ورود پنل مدیریت</h1>
        <p class="mt-2 text-sm font-semibold leading-7 text-slate-500">برای حفظ امنیت، رمز فعلی را وارد کنید. رمز جدید باید حداقل ۸ کاراکتر باشد.</p>

        @if(session('success'))
            <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-700">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.account.security.update') }}" class="mt-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-2 block text-sm font-black text-slate-700" for="current_password">رمز عبور فعلی</label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-300 px-4 py-3" dir="ltr">
                @error('current_password')<div class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-black text-slate-700" for="password">رمز عبور جدید</label>
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 px-4 py-3" dir="ltr">
                @error('password')<div class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-black text-slate-700" for="password_confirmation">تکرار رمز عبور جدید</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 px-4 py-3" dir="ltr">
            </div>

            <button type="submit" class="w-full rounded-xl bg-sky-600 px-5 py-3 font-black text-white hover:bg-sky-700">ذخیره رمز عبور جدید</button>
        </form>
    </div>
</div>
@endsection
