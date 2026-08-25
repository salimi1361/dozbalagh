@extends('layouts.admin')

@section('header_title', 'مدیریت وضعیت مجوزهای کشورها')

@section('header_actions')
    <a href="{{ route('admin.countries.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 shadow-sm hover:bg-slate-50">بازگشت به کشورها</a>
@endsection

@section('content')
<div class="mx-auto max-w-7xl space-y-5" dir="rtl">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 font-bold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">
            <ul class="list-disc space-y-1 pr-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-l from-slate-900 via-blue-900 to-sky-700 p-6 text-white shadow-lg">
        <div class="relative z-10">
            <h1 class="text-2xl font-black">فعال یا غیرفعال‌سازی مجوزها برای هر کشور</h1>
            <p class="mt-2 max-w-3xl text-sm leading-7 text-blue-100">مجوز غیرفعال در درخواست‌های جدید، ثبت موجودی و تخصیص نمایش داده نمی‌شود. سوابق و دوزوله‌های قبلاً صادرشده حذف یا تغییر نمی‌کنند. می‌توانید همه مجوزهای یک کشور را نیز موقتاً خاموش کنید.</p>
        </div>
        <div class="pointer-events-none absolute -bottom-20 -left-12 h-48 w-48 rounded-full bg-cyan-300/20 blur-2xl"></div>
    </section>

    <div class="grid gap-5 xl:grid-cols-2">
        @forelse($countries as $country)
            @php($activeTypes = $country->allowed_permit_types ?? [])
            <form method="POST" action="{{ route('admin.countries.permit-statuses.update', $country) }}" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                @csrf
                @method('PUT')
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 bg-slate-50 px-5 py-4">
                    <div>
                        <h2 class="font-black text-slate-800">{{ $country->name }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ count($activeTypes) }} مجوز فعال از {{ count($permitTypes) }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-[11px] font-black {{ $country->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">کشور {{ $country->is_active ? 'فعال' : 'غیرفعال' }}</span>
                </div>

                <div class="divide-y divide-slate-100 px-5">
                    @foreach($permitTypes as $key => $label)
                        @php($isActive = in_array($key, $activeTypes, true))
                        <label class="flex cursor-pointer items-center justify-between gap-4 py-3.5">
                            <span>
                                <span class="block font-bold text-slate-700">{{ $label }}</span>
                                <span class="mt-1 block text-[10px] font-bold {{ $isActive ? 'text-emerald-600' : 'text-slate-400' }}">{{ $isActive ? 'قابل درخواست' : 'غیرفعال و غیرقابل درخواست' }}</span>
                            </span>
                            <span class="relative inline-flex shrink-0 items-center">
                                <input type="checkbox" name="permit_types[]" value="{{ $key }}" class="peer sr-only" @checked($isActive)>
                                <span class="h-7 w-12 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500"></span>
                                <span class="absolute right-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:-translate-x-5"></span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-end border-t border-slate-100 bg-slate-50 px-5 py-4">
                    <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-black text-white shadow-sm hover:bg-blue-700">ذخیره وضعیت مجوزهای {{ $country->name }}</button>
                </div>
            </form>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center font-bold text-slate-500 xl:col-span-2">هنوز کشوری ثبت نشده است.</div>
        @endforelse
    </div>
</div>
@endsection
