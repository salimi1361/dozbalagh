<style>form[action$="/serial-pools"]:not([data-primary-serial-range]){display:none}</style>
<form data-primary-serial-range method="POST" action="{{ route('admin.cmr.company-settings.serial-pools.store', $company) }}" class="space-y-4 rounded-2xl border-2 border-indigo-200 bg-white p-5 lg:col-span-2">
    @csrf
    <div>
        <h3 class="font-black">ثبت بازه رسمی شماره‌های تحویلی شرکت</h3>
        <p class="mt-1 text-sm text-slate-600">شماره‌ها از ابتدا تا انتهای بازه به‌ترتیب و فقط یک‌بار مصرف می‌شوند.</p>
    </div>
    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
        <label class="text-sm">نام یا مرجع تحویل
            <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border p-3" placeholder="مثلاً حواله سری ۱۴۰۵">
        </label>
        <label class="text-sm">پیشوند
            <input dir="ltr" name="prefix" value="{{ old('prefix') }}" class="mt-1 w-full rounded-xl border p-3" placeholder="CMR-IR-">
        </label>
        <label class="text-sm">پسوند اختیاری
            <input dir="ltr" name="suffix" value="{{ old('suffix') }}" class="mt-1 w-full rounded-xl border p-3" placeholder="/2026">
        </label>
        <label class="text-sm">از شماره
            <input dir="ltr" type="number" min="1" name="range_start" value="{{ old('range_start') }}" required class="mt-1 w-full rounded-xl border p-3" placeholder="1">
        </label>
        <label class="text-sm">تا شماره
            <input dir="ltr" type="number" min="1" name="range_end" value="{{ old('range_end') }}" required class="mt-1 w-full rounded-xl border p-3" placeholder="1000">
        </label>
        <label class="text-sm">تعداد رقم بخش عددی
            <input dir="ltr" type="number" min="1" max="20" name="number_padding" value="{{ old('number_padding', 6) }}" required class="mt-1 w-full rounded-xl border p-3" placeholder="6">
        </label>
    </div>
    <p class="rounded-xl bg-indigo-50 p-3 text-sm text-indigo-800" dir="rtl">
        نمونه با پیشوند CMR-IR-، شروع 1 و تعداد رقم 6: <span dir="ltr" class="font-bold">CMR-IR-000001</span>
    </p>
    <p class="text-sm font-bold text-slate-700">شماره آماده مصرف: {{ number_format($availableSerialCount + $availablePoolCount) }}</p>
    <button class="rounded-xl bg-indigo-600 px-5 py-3 font-black text-white">ثبت بازه رسمی</button>
</form>
