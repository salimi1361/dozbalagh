@extends('layouts.admin')

@section('title', 'مدیریت دسترسی پنل‌ها')
@section('header_title', 'مدیریت دسترسی پنل انجمن و شرکت')

@section('content')
<div class="mx-auto max-w-6xl space-y-6" dir="rtl">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-black text-slate-800">فعال‌سازی آیتم‌های هر پنل</h2>
        <p class="mt-2 text-sm leading-7 text-slate-500">با خاموش‌کردن هر آیتم، آن گزینه از منوی پنل مربوط حذف و دسترسی مستقیم به صفحات آن نیز مسدود می‌شود. دسترسی ادمین کل همیشه برقرار می‌ماند.</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.panel-features.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach(['association' => 'پنل انجمن', 'company' => 'پنل شرکت'] as $role => $roleTitle)
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50 px-6 py-5">
                        <h3 class="text-lg font-black text-slate-800">{{ $roleTitle }}</h3>
                    </div>
                    <div class="divide-y divide-slate-100 px-6">
                        @foreach($definitions[$role] as $key => $definition)
                            <label class="flex cursor-pointer items-center justify-between gap-4 py-4">
                                <span class="font-bold text-slate-700">{{ $definition['label'] }}</span>
                                <span class="relative inline-flex items-center">
                                    <input type="checkbox" name="features[{{ $role }}][{{ $key }}]" value="1" class="peer sr-only" @checked($features->enabledForRole($role, $key))>
                                    <span class="h-7 w-12 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500"></span>
                                    <span class="absolute right-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:-translate-x-5"></span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="sticky bottom-4 flex justify-end">
            <button type="submit" class="rounded-2xl bg-sky-600 px-7 py-3 font-black text-white shadow-lg shadow-sky-600/20 transition hover:bg-sky-700">ذخیره تنظیمات دسترسی</button>
        </div>
    </form>
</div>
@endsection
