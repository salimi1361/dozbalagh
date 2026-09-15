@extends('layouts.admin')

@section('title', 'تابلوی روان شرکت‌ها')
@section('header_title', 'مدیریت تابلوی روان پنل شرکت‌ها')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.vendor.min.css') }}">
<style>
    .datepicker-plot-area {
        z-index: 99999 !important;
        font-family: 'Vazirmatn', Tahoma, sans-serif !important;
        border: 1px solid #dbe3ef !important;
        border-radius: 14px !important;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .16) !important;
    }

    .jalali-datetime-picker {
        cursor: pointer;
        direction: ltr;
        text-align: center;
    }
</style>
<div class="mx-auto max-w-5xl space-y-6" dir="rtl">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold leading-7 text-rose-700">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-black text-slate-800">پیام‌های مهم برای شرکت‌ها</h2>
                <p class="mt-2 text-sm leading-7 text-slate-500">چند خبر فعال پشت‌سرهم در نوار بالای پنل شرکت‌ها نمایش داده می‌شود. خبرهای زمان‌دار فقط در بازه تعیین‌شده نمایش داده می‌شوند.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-black text-slate-600">{{ $items->count() }} خبر ثبت‌شده</span>
        </div>

        <form method="POST" action="{{ route('association.company-header-ticker.store') }}" class="mt-6 rounded-2xl border border-sky-200 bg-sky-50/40 p-5">
            @csrf
            <h3 class="text-base font-black text-slate-800">افزودن خبر جدید</h3>
            <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_7rem_10rem_10rem]">
                <label class="block lg:col-span-1">
                    <span class="mb-2 block text-xs font-black text-slate-600">متن خبر</span>
                    <input name="message" maxlength="500" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100" placeholder="مثلاً: مهلت تکمیل اطلاعات تا پایان هفته تمدید شد.">
                </label>
                <label class="block">
                    <span class="mb-2 block text-xs font-black text-slate-600">ترتیب</span>
                    <input name="sort_order" type="number" min="0" max="9999" value="10" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                </label>
                <label class="block">
                    <span class="mb-2 block text-xs font-black text-slate-600">شروع نمایش (شمسی)</span>
                    <input name="starts_at_jalali" type="text" value="{{ old('starts_at_jalali') }}" placeholder="۱۴۰۵/۰۶/۰۴ ۱۴:۳۰" inputmode="numeric" autocomplete="off" class="jalali-datetime-picker w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-xs outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                </label>
                <label class="block">
                    <span class="mb-2 block text-xs font-black text-slate-600">پایان نمایش (شمسی)</span>
                    <input name="ends_at_jalali" type="text" value="{{ old('ends_at_jalali') }}" placeholder="۱۴۰۵/۰۶/۰۵ ۱۴:۳۰" inputmode="numeric" autocomplete="off" class="jalali-datetime-picker w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-xs outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                </label>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                <label class="flex cursor-pointer items-center gap-3 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" checked class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    خبر فعال باشد
                </label>
                <button type="submit" class="rounded-xl bg-sky-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-sky-600/20 transition hover:bg-sky-700">+ افزودن خبر</button>
            </div>
        </form>
    </section>

    <section class="space-y-4">
        @forelse($items as $item)
            <article class="rounded-2xl border {{ $item->is_active ? 'border-slate-200 bg-white' : 'border-slate-200 bg-slate-50' }} p-5 shadow-sm">
                <form method="POST" action="{{ route('association.company-header-ticker.update', $item) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-sm font-black text-slate-600">{{ $item->sort_order }}</span>
                            <div>
                                <h3 class="text-sm font-black text-slate-800">خبر شماره {{ $loop->iteration }}</h3>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $item->is_active ? 'فعال' : 'غیرفعال' }} @if($item->starts_at || $item->ends_at) — زمان‌بندی‌شده @endif</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2 text-xs font-black text-white transition hover:bg-slate-700">ذخیره تغییرات</button>
                            <button type="submit" form="delete-ticker-{{ $item->id }}" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-black text-rose-600 transition hover:bg-rose-100">حذف خبر</button>
                        </div>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-[1fr_7rem_10rem_10rem]">
                        <label class="block">
                            <span class="mb-2 block text-xs font-black text-slate-600">متن خبر</span>
                            <input name="message" value="{{ $item->message }}" maxlength="500" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-xs font-black text-slate-600">ترتیب</span>
                            <input name="sort_order" type="number" min="0" max="9999" value="{{ $item->sort_order }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-xs font-black text-slate-600">شروع نمایش (شمسی)</span>
                            <input name="starts_at_jalali" type="text" value="{{ old('starts_at_jalali', $item->starts_at ? verta($item->starts_at)->format('Y/m/d H:i') : '') }}" placeholder="۱۴۰۵/۰۶/۰۴ ۱۴:۳۰" inputmode="numeric" autocomplete="off" class="jalali-datetime-picker w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-xs outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-xs font-black text-slate-600">پایان نمایش (شمسی)</span>
                            <input name="ends_at_jalali" type="text" value="{{ old('ends_at_jalali', $item->ends_at ? verta($item->ends_at)->format('Y/m/d H:i') : '') }}" placeholder="۱۴۰۵/۰۶/۰۵ ۱۴:۳۰" inputmode="numeric" autocomplete="off" class="jalali-datetime-picker w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-xs outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                        </label>
                    </div>
                    <label class="flex cursor-pointer items-center gap-3 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="is_active" value="1" @checked($item->is_active) class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        نمایش این خبر در پنل شرکت‌ها
                    </label>
                </form>
                <form id="delete-ticker-{{ $item->id }}" method="POST" action="{{ route('association.company-header-ticker.destroy', $item) }}" onsubmit="return confirm('این خبر حذف شود؟');">
                    @csrf
                    @method('DELETE')
                </form>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm font-bold text-slate-400">هنوز خبری ثبت نشده است.</div>
        @endforelse
    </section>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/persian-date.vendor.min.js') }}"></script>
<script src="{{ asset('assets/js/persian-datepicker.vendor.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pickerJQuery = window.jQuery;
    if (!pickerJQuery || typeof pickerJQuery.fn.pDatepicker !== 'function') return;

    const options = {
        format: 'YYYY/MM/DD HH:mm',
        initialValueType: 'persian',
        autoClose: true,
        responsive: true,
        onlySelectOnDate: false,
        calendarType: 'persian',
        calendar: {
            persian: { locale: 'fa', showHint: false, leapYearMode: 'algorithmic' },
            gregorian: { showHint: false }
        },
        navigator: { enabled: true, scroll: { enabled: true } },
        toolbox: { enabled: true, calendarSwitch: { enabled: false }, todayButton: { enabled: true }, submitButton: { enabled: true } },
        timePicker: { enabled: true, second: { enabled: false }, meridian: { enabled: false } }
    };

    pickerJQuery('.jalali-datetime-picker').each(function () {
        const input = pickerJQuery(this);
        input.pDatepicker(Object.assign({}, options, {
            initialValue: input.val().trim() !== ''
        }));
    });
});
</script>
@endsection
