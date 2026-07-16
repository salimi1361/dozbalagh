@extends('layouts.admin')

@section('header_title', 'مدیریت نسخه اپلیکیشن')

@section('content')
<div class="mx-auto max-w-6xl space-y-6" dir="rtl">
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-700">{{ session('success') }}</div>@endif
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-black text-slate-900">سیاست انتشار و به‌روزرسانی اپلیکیشن</h1>
        <p class="mt-2 text-sm font-semibold leading-7 text-slate-500">مقایسه نسخه بر اساس شماره Build انجام می‌شود. حداقل Build مجاز، نسخه‌های قدیمی‌تر را مسدود می‌کند.</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach(['android' => 'Android', 'ios' => 'iPhone / iOS'] as $platform => $platformLabel)
            @php $version = $versions->get($platform); @endphp
            <form method="POST" action="{{ route('admin.mobile-app.versions.update', $platform) }}" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
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
                <div><label class="mb-2 block text-xs font-black text-slate-600">لینک دریافت</label><input type="url" name="download_url" value="{{ old('download_url', $version?->download_url) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5" dir="ltr"></div>
                <div><label class="mb-2 block text-xs font-black text-slate-600">SHA-256 فایل (اختیاری)</label><input name="file_checksum" value="{{ old('file_checksum', $version?->file_checksum) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-xs" dir="ltr"></div>
                <div><label class="mb-2 block text-xs font-black text-slate-600">پیام به‌روزرسانی</label><textarea name="message" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">{{ old('message', $version?->message) }}</textarea></div>
                <div><label class="mb-2 block text-xs font-black text-slate-600">تغییرات نسخه</label><textarea name="release_notes" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">{{ old('release_notes', $version?->release_notes) }}</textarea></div>
                <div><label class="mb-2 block text-xs font-black text-slate-600">زمان انتشار</label><input type="datetime-local" name="published_at" value="{{ old('published_at', $version?->published_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" dir="ltr"></div>
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
