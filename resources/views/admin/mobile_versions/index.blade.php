@extends('layouts.admin')

@section('header_title', 'مدیریت نسخه اپلیکیشن')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.vendor.min.css') }}">
<style>
    .datepicker-plot-area { font-family: 'Vazirmatn', Tahoma, sans-serif !important; z-index: 99999 !important; }
    .jalali-picker { cursor: pointer; }
</style>
<div class="mx-auto max-w-6xl space-y-6" dir="rtl">
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">
            <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-black text-slate-900">سیاست انتشار و به‌روزرسانی اپلیکیشن</h1>
        <p class="mt-2 text-sm font-semibold leading-7 text-slate-500">مقایسه نسخه بر اساس شماره Build انجام می‌شود. حداقل Build مجاز، نسخه‌های قدیمی‌تر را مسدود می‌کند.</p>
    </div>

    <div class="flex flex-col gap-4 rounded-3xl border border-rose-200 bg-gradient-to-l from-rose-50 to-amber-50 p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div><h2 class="text-lg font-black text-rose-900">پیام فوری برای رانندگان</h2><p class="mt-2 text-sm font-semibold leading-7 text-rose-700">بدون انتشار نسخه جدید، برای همه رانندگان، رانندگان یک شرکت یا افراد انتخابی Push بفرستید و متن کامل را هنگام بازشدن اپ نمایش دهید.</p></div>
        <a href="{{ route('driver-announcements.index') }}" class="whitespace-nowrap rounded-xl bg-rose-600 px-5 py-3 text-center font-black text-white hover:bg-rose-700">ایجاد پیام فوری</a>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach(['android' => 'Android', 'ios' => 'iPhone / iOS'] as $platform => $platformLabel)
            @php $version = $versions->get($platform); @endphp
            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.mobile-app.versions.update', $platform) }}" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf @method('PUT')
                <h2 class="text-lg font-black text-slate-900">{{ $platformLabel }}</h2>
                <div class="flex items-center justify-between rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 text-xs font-black text-violet-800">
                    <span>دستگاه‌های آماده دریافت اعلان بروزرسانی</span>
                    <span class="rounded-full bg-violet-600 px-3 py-1 text-white">{{ number_format((int) ($pushRecipients[$platform] ?? 0)) }}</span>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div><label class="mb-2 block text-xs font-black text-slate-600">نام نسخه</label><input name="version_name" value="{{ old('version_name', $version?->version_name) }}" required placeholder="1.2.0" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" dir="ltr"></div>
                    <div><label class="mb-2 block text-xs font-black text-slate-600">آخرین Build</label><input type="number" name="latest_build" value="{{ old('latest_build', $version?->latest_build) }}" min="1" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5" dir="ltr"></div>
                    <div><label class="mb-2 block text-xs font-black text-slate-600">حداقل Build مجاز</label><input type="number" name="minimum_build" value="{{ old('minimum_build', $version?->minimum_build) }}" min="1" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5" dir="ltr"></div>
                </div>
                <div class="space-y-3 rounded-2xl border border-sky-200 bg-sky-50 p-4">
                    <div>
                        <label class="mb-2 block text-xs font-black text-slate-700">آپلود مستقیم فایل {{ $platform === 'android' ? 'APK' : 'IPA' }}</label>
                        <input type="file" name="release_file" accept="{{ $platform === 'android' ? '.apk,application/vnd.android.package-archive' : '.ipa' }}" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" dir="ltr">
                        <p class="mt-2 text-xs font-semibold leading-6 text-slate-500">در صورت انتخاب فایل، لینک دریافت و SHA-256 به‌صورت خودکار ساخته می‌شود. حداکثر حجم ۲۵۰ مگابایت است.</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs font-black text-slate-400"><span class="h-px flex-1 bg-slate-200"></span><span>یا</span><span class="h-px flex-1 bg-slate-200"></span></div>
                    <div><label class="mb-2 block text-xs font-black text-slate-600">لینک دریافت از منبع دیگر</label><input type="url" name="download_url" value="{{ old('download_url', $version?->download_url) }}" placeholder="https://..." class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5" dir="ltr"></div>
                    @if($version?->download_url)<a href="{{ $version->download_url }}" target="_blank" rel="noopener" class="inline-flex text-xs font-black text-sky-700 hover:text-sky-900">مشاهده فایل فعلی ↗</a>@endif
                </div>
                <div><label class="mb-2 block text-xs font-black text-slate-600">SHA-256 فایل (اختیاری)</label><input name="file_checksum" value="{{ old('file_checksum', $version?->file_checksum) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-xs" dir="ltr"></div>
                <div><label class="mb-2 block text-xs font-black text-slate-600">پیام به‌روزرسانی</label><textarea name="message" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">{{ old('message', $version?->message) }}</textarea></div>
                <div><label class="mb-2 block text-xs font-black text-slate-600">تغییرات نسخه</label><textarea name="release_notes" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">{{ old('release_notes', $version?->release_notes) }}</textarea></div>
                <div>
                    <label class="mb-2 block text-xs font-black text-slate-600">زمان انتشار (هجری شمسی)</label>
                    <span class="relative block">
                        <input type="text" readonly name="published_at_jalali" value="{{ old('published_at_jalali', $version?->published_at ? verta($version->published_at)->format('Y/m/d H:i') : '') }}" placeholder="انتخاب تاریخ و ساعت انتشار" autocomplete="off" class="jalali-picker w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-11 pr-3 text-center" dir="ltr">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg" aria-hidden="true">🗓️</span>
                    </span>
                    <p class="mt-2 text-xs font-semibold text-slate-400">تاریخ و ساعت انتشار را از تقویم شمسی انتخاب کنید.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="flex items-center gap-2 rounded-xl bg-slate-50 p-3 text-xs font-black"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $version?->is_active ?? true))> سیاست فعال</label>
                    <label class="flex items-center gap-2 rounded-xl bg-amber-50 p-3 text-xs font-black text-amber-800"><input type="checkbox" name="force_update" value="1" @checked(old('force_update', $version?->force_update))> آپدیت اجباری</label>
                    <label class="flex items-center gap-2 rounded-xl bg-rose-50 p-3 text-xs font-black text-rose-800"><input type="checkbox" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $version?->maintenance_mode))> حالت تعمیرات</label>
                </div>
                <label class="flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm font-black text-sky-900">
                    <input type="checkbox" name="send_push_notification" value="1" class="mt-1">
                    <span><b class="block">ارسال اعلان بروزرسانی پس از ذخیره</b><small class="mt-1 block font-semibold leading-6 text-sky-700">برای دستگاه‌های فعال این پلتفرم که مجوز اعلان و FCM Token دارند ارسال می‌شود.</small></span>
                </label>
                <button class="w-full rounded-xl bg-sky-600 px-5 py-3 font-black text-white hover:bg-sky-700">ذخیره تنظیمات {{ $platformLabel }}</button>
            </form>
        @endforeach
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/persian-date.vendor.min.js') }}"></script>
<script src="{{ asset('assets/js/persian-datepicker.vendor.min.js') }}"></script>
<script>
const mobileVersionDatepickerJQuery = window.jQuery;

document.addEventListener('DOMContentLoaded', function () {
    if (!mobileVersionDatepickerJQuery || typeof mobileVersionDatepickerJQuery.fn.pDatepicker !== 'function') return;

    const pickerJQuery = mobileVersionDatepickerJQuery;

    const placePicker = model => requestAnimationFrame(() => {
        const box = model.view.$container;
        const inputRect = model.inputElement.getBoundingClientRect();
        const plot = box.find('.datepicker-plot-area')[0];
        if (!plot) return;

        const plotRect = plot.getBoundingClientRect();
        const above = window.innerHeight - inputRect.bottom < plotRect.height && inputRect.top > plotRect.height;
        const currentTop = parseFloat(box.css('top')) || 0;
        const delta = above
            ? inputRect.top - 4 - plotRect.bottom
            : inputRect.bottom + 4 - plotRect.top;

        box.css('top', (currentTop + delta) + 'px');
    });

    const pickerOptions = {
            format: 'YYYY/MM/DD HH:mm',
            initialValue: true,
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
            timePicker: { enabled: true, second: { enabled: false }, meridian: { enabled: false } },
            onShow: placePicker
    };

    pickerJQuery('.jalali-picker').each(function () {
        pickerJQuery(this).pDatepicker(pickerOptions);
    });

    const closeOnOutsideMove = event => {
        if (event.target instanceof Element && event.target.closest('.datepicker-container, .datepicker-plot-area')) return;
        pickerJQuery('.jalali-picker').each(function () {
            const picker = pickerJQuery(this).data('datepicker');
            if (picker) picker.hide();
        });
    };

    window.addEventListener('wheel', closeOnOutsideMove, { passive: true, capture: true });
    window.addEventListener('touchmove', closeOnOutsideMove, { passive: true, capture: true });
});
</script>
@endsection
