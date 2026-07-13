@extends('layouts.admin')

@section('header_title', 'مدیریت کاربران انجمن')

@section('content')
<div class="mx-auto max-w-6xl space-y-6" dir="rtl">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 font-bold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">
            <ul class="list-disc space-y-1 pr-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-black text-slate-800">ساخت کاربر جدید انجمن</h2>
        <form method="POST" action="{{ route('admin.association-users.store') }}" class="mt-6 grid gap-4 md:grid-cols-2">
            @csrf
            <label class="space-y-2"><span class="text-sm font-bold text-slate-600">نام کاربری</span><input name="username" value="{{ old('username') }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="space-y-2"><span class="text-sm font-bold text-slate-600">شماره موبایل</span><input name="mobile" value="{{ old('mobile') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="space-y-2"><span class="text-sm font-bold text-slate-600">رمز عبور</span><input type="password" name="password" required minlength="8" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="space-y-2"><span class="text-sm font-bold text-slate-600">تکرار رمز عبور</span><input type="password" name="password_confirmation" required minlength="8" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <div class="md:col-span-2 flex justify-end"><button class="rounded-xl bg-sky-600 px-6 py-3 font-black text-white hover:bg-sky-700">ایجاد کاربر انجمن</button></div>
        </form>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-black text-slate-800">کاربران فعلی انجمن</h2>
        <div class="mt-5 space-y-4">
            @forelse($users as $user)
                <form method="POST" action="{{ route('admin.association-users.update', $user) }}" class="grid items-end gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2 xl:grid-cols-6">
                    @csrf
                    @method('PUT')
                    <label class="space-y-1 xl:col-span-2"><span class="text-xs font-bold text-slate-500">نام کاربری</span><input name="username" value="{{ $user->username }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></label>
                    <label class="space-y-1"><span class="text-xs font-bold text-slate-500">موبایل</span><input name="mobile" value="{{ $user->mobile }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></label>
                    <label class="space-y-1"><span class="text-xs font-bold text-slate-500">وضعیت</span><select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"><option value="active" @selected($user->status === 'active')>فعال</option><option value="inactive" @selected($user->status === 'inactive')>غیرفعال</option><option value="suspended" @selected($user->status === 'suspended')>تعلیق</option></select></label>
                    <label class="space-y-1"><span class="text-xs font-bold text-slate-500">رمز جدید (اختیاری)</span><input type="password" name="password" minlength="8" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></label>
                    <div class="space-y-2"><input type="password" name="password_confirmation" minlength="8" placeholder="تکرار رمز جدید" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"><button class="w-full rounded-xl bg-slate-800 px-4 py-2.5 font-bold text-white hover:bg-slate-900">ذخیره</button></div>
                </form>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center font-bold text-slate-500">هنوز کاربر انجمن ساخته نشده است.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
