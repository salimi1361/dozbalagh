@extends('layouts.admin')

@section('header_title', 'امنیت حساب انجمن')

@section('content')
<div class="mx-auto max-w-2xl" dir="rtl">
    <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
        <h1 class="text-xl font-black text-slate-900">تغییر رمز ورود پنل انجمن</h1>
        <p class="mt-2 text-sm font-semibold leading-7 text-slate-500">رمز جدید حداقل باید ۸ کاراکتر داشته باشد.</p>

        @if(session('success'))
            <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-700">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('association.account.security.update') }}" class="mt-6 space-y-5">
            @csrf
            @method('PUT')
            <div><label class="mb-2 block text-sm font-black text-slate-700">رمز عبور فعلی</label><input type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-300 px-4 py-3" dir="ltr">@error('current_password')<div class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</div>@enderror</div>
            <div><label class="mb-2 block text-sm font-black text-slate-700">رمز عبور جدید</label><input type="password" name="password" required autocomplete="new-password" class="w-full rounded-xl border border-slate-300 px-4 py-3" dir="ltr">@error('password')<div class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</div>@enderror</div>
            <div><label class="mb-2 block text-sm font-black text-slate-700">تکرار رمز عبور جدید</label><input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-xl border border-slate-300 px-4 py-3" dir="ltr"></div>
            <button class="w-full rounded-xl bg-sky-600 px-5 py-3 font-black text-white hover:bg-sky-700">ذخیره رمز عبور جدید</button>
        </form>
    </div>
</div>
@endsection
