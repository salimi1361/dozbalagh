@extends('layouts.admin')

@section('title', 'انواع بارگیر')
@section('header_title', $isAssociation ? 'مدیریت انواع بارگیر انجمن' : 'تنظیمات انواع بارگیر ناوگان')
@section('hero_subtitle', $isAssociation ? 'مشاهده انواع فعال و افزودن نوع بارگیر سفارشی برای انجمن' : 'مدیریت فهرست سازمان راهداری و گزینه‌های مجاز ناوگان بین‌المللی')

@section('content')
<div class="mx-auto max-w-7xl space-y-5" dir="rtl">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 font-bold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 font-bold text-rose-700">{{ session('error') }}</div>
    @endif

    <section class="rounded-3xl border border-sky-200 bg-sky-50/70 p-5 shadow-sm">
        <h1 class="text-lg font-black text-slate-900">فهرست استاندارد نوع بارگیر</h1>
        <p class="mt-2 text-sm leading-7 text-slate-700">
            عنوان سازمان راهداری ممکن است مربوط به خود وسیله باری باشد، نه الزاماً یدک ترانزیتی. گزینه‌ای که در «مجاز برای ناوگان بین‌المللی» فعال می‌شود در فرم شرکت به‌عنوان نوع بارگیر انتخابی و الزامی نمایش داده خواهد شد.
        </p>
        @if($isAssociation)
            <p class="mt-2 text-xs font-bold leading-6 text-sky-900">انواع استانداردی که انجمن فعال کرده در اینجا قابل مشاهده‌اند. برای مورد جدید، نوع سفارشی انجمن را اضافه کنید.</p>
        @else
            <p class="mt-2 text-xs font-bold leading-6 text-sky-900">رکوردهای واردشده از فایل سازمان قابل اصلاح و غیرفعال‌سازی هستند؛ رکوردهای جدید بدون کد سازمانی به‌عنوان «سفارشی» ذخیره می‌شوند.</p>
        @endif
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-black text-slate-900">{{ $isAssociation ? 'افزودن نوع بارگیر سفارشی' : 'افزودن نوع بارگیر جدید' }}</h2>
                <p class="mt-1 text-xs font-semibold text-slate-500">نام گزینه و محدوده وزن را در صورت نیاز وارد کنید.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ number_format($types->count()) }} مورد قابل نمایش</span>
        </div>
        <form method="POST" action="{{ route('admin.fleet-loading-types.store') }}" class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-6">
            @csrf
            <label class="lg:col-span-2"><span class="mb-1.5 block text-xs font-black text-slate-700">نام نوع بارگیر <b class="text-rose-500">*</b></span><input name="name_fa" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="مثلاً یخچالدار تریلی"></label>
            @if(!$isAssociation)
                <label><span class="mb-1.5 block text-xs font-black text-slate-700">کد سازمان</span><input name="rmto_code" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" dir="ltr" placeholder="اختیاری"></label>
                <label><span class="mb-1.5 block text-xs font-black text-slate-700">نوع کاربرد</span><select name="vehicle_scope" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><option value="trailer">یدک/تریلی</option><option value="truck">کامیون/خودرو</option><option value="special">خاص/سنگین</option><option value="other">سایر</option></select></label>
            @else
                <input type="hidden" name="vehicle_scope" value="trailer">
            @endif
            <label><span class="mb-1.5 block text-xs font-black text-slate-700">حداقل وزن (کیلو)</span><input type="number" min="0" name="min_weight_kg" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" dir="ltr"></label>
            <label><span class="mb-1.5 block text-xs font-black text-slate-700">حداکثر وزن (کیلو)</span><input type="number" min="0" name="max_weight_kg" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" dir="ltr"></label>
            @if(!$isAssociation)
                <div class="flex flex-wrap items-end gap-3 lg:col-span-2">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-700"><input type="checkbox" name="is_international" value="1" checked class="h-4 w-4 rounded border-slate-300 text-sky-600"> مجاز برای بین‌المللی</label>
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-700"><input type="checkbox" name="association_enabled" value="1" checked class="h-4 w-4 rounded border-slate-300 text-violet-600"> نمایش برای انجمن</label>
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-700"><input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-emerald-600"> فعال</label>
                </div>
            @endif
            <div class="flex items-end justify-end lg:col-span-2"><button class="w-full rounded-xl bg-sky-700 px-5 py-2.5 text-sm font-black text-white transition hover:bg-sky-800 sm:w-auto">+ افزودن گزینه</button></div>
        </form>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="font-black text-slate-900">فهرست نوع بارگیرها</h2><p class="mt-1 text-xs font-semibold text-slate-500">برای پیدا کردن سریع، بر اساس نام یا کد جستجو کنید.</p></div>
            <input id="loading_type_search" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs sm:w-72" placeholder="جستجوی نام یا کد...">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-right text-xs" id="loading_types_table">
                <thead class="bg-slate-100 font-black text-slate-700"><tr><th class="p-3">کد سازمان</th><th class="p-3">عنوان</th><th class="p-3">کاربرد</th><th class="p-3">وزن</th><th class="p-3 text-center">بین‌المللی</th><th class="p-3 text-center">انجمن</th><th class="p-3 text-center">وضعیت</th><th class="p-3 text-center">عملیات</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($types as $type)
                    @php $editable = !$isAssociation || ($type->is_custom && (int) $type->created_by_user_id === (int) auth()->id()); @endphp
                    <tr class="loading-type-row hover:bg-slate-50" data-search="{{ $type->rmto_code }} {{ $type->name_fa }}">
                        <td class="p-3 font-mono font-bold text-slate-600" dir="ltr">{{ $type->rmto_code ?: 'سفارشی' }}</td>
                        <td class="p-3 font-bold text-slate-800">{{ $type->name_fa }} @if($type->is_custom)<span class="mr-1 rounded-full bg-violet-100 px-2 py-0.5 text-[10px] text-violet-700">سفارشی</span>@endif</td>
                        <td class="p-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 font-bold text-slate-600">{{ ['truck'=>'کامیون/خودرو','trailer'=>'یدک/تریلی','special'=>'خاص/سنگین','other'=>'سایر'][$type->vehicle_scope] ?? $type->vehicle_scope }}</span></td>
                        <td class="p-3 font-mono text-slate-600" dir="ltr">{{ $type->min_weight_kg !== null || $type->max_weight_kg !== null ? number_format($type->min_weight_kg ?? 0).' - '.number_format($type->max_weight_kg ?? 0) : '—' }}</td>
                        <td class="p-3 text-center">@if($type->is_international)<span class="rounded-full bg-emerald-100 px-2 py-1 font-black text-emerald-700">مجاز</span>@else<span class="rounded-full bg-slate-100 px-2 py-1 font-bold text-slate-500">خیر</span>@endif</td>
                        <td class="p-3 text-center">@if($type->association_enabled)<span class="rounded-full bg-violet-100 px-2 py-1 font-black text-violet-700">فعال</span>@else<span class="rounded-full bg-slate-100 px-2 py-1 font-bold text-slate-500">خیر</span>@endif</td>
                        <td class="p-3 text-center">@if($type->is_active)<span class="rounded-full bg-sky-100 px-2 py-1 font-black text-sky-700">فعال</span>@else<span class="rounded-full bg-rose-100 px-2 py-1 font-black text-rose-700">غیرفعال</span>@endif</td>
                        <td class="p-3 text-center">
                            @if($editable)
                                <details class="inline-block text-right"><summary class="cursor-pointer rounded-lg bg-sky-100 px-3 py-1.5 font-black text-sky-700">ویرایش</summary>
                                    <div class="absolute z-20 mt-2 w-[22rem] rounded-2xl border border-slate-200 bg-white p-4 text-right shadow-2xl">
                                        <form method="POST" action="{{ route('admin.fleet-loading-types.update', $type) }}" class="space-y-3">@csrf @method('PUT')
                                            <label class="block"><span class="mb-1 block text-[11px] font-black text-slate-600">عنوان</span><input name="name_fa" required value="{{ $type->name_fa }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs"></label>
                                            @if(!$isAssociation)
                                                <label class="block"><span class="mb-1 block text-[11px] font-black text-slate-600">کد سازمان</span><input name="rmto_code" value="{{ $type->rmto_code }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs" dir="ltr"></label>
                                                <label class="block"><span class="mb-1 block text-[11px] font-black text-slate-600">کاربرد</span><select name="vehicle_scope" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs"><option value="trailer" @selected($type->vehicle_scope === 'trailer')>یدک/تریلی</option><option value="truck" @selected($type->vehicle_scope === 'truck')>کامیون/خودرو</option><option value="special" @selected($type->vehicle_scope === 'special')>خاص/سنگین</option><option value="other" @selected($type->vehicle_scope === 'other')>سایر</option></select></label>
                                            @else
                                                <input type="hidden" name="vehicle_scope" value="trailer">
                                            @endif
                                            <div class="grid grid-cols-2 gap-2"><label><span class="mb-1 block text-[11px] font-black text-slate-600">حداقل وزن</span><input type="number" min="0" name="min_weight_kg" value="{{ $type->min_weight_kg }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs" dir="ltr"></label><label><span class="mb-1 block text-[11px] font-black text-slate-600">حداکثر وزن</span><input type="number" min="0" name="max_weight_kg" value="{{ $type->max_weight_kg }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs" dir="ltr"></label></div>
                                            @if(!$isAssociation)
                                                <div class="space-y-2 text-xs font-bold"><label class="flex items-center gap-2"><input type="checkbox" name="is_international" value="1" @checked($type->is_international)> مجاز برای بین‌المللی</label><label class="flex items-center gap-2"><input type="checkbox" name="association_enabled" value="1" @checked($type->association_enabled)> نمایش برای انجمن</label><label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($type->is_active)> فعال</label></div>
                                            @endif
                                            <button class="w-full rounded-lg bg-sky-700 px-3 py-2 text-xs font-black text-white">ذخیره</button>
                                        </form>
                                    </div>
                                </details>
                                <form method="POST" action="{{ route('admin.fleet-loading-types.destroy', $type) }}" class="mt-2 inline-block" onsubmit="return confirm('این گزینه حذف شود؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-50 px-3 py-1.5 font-black text-rose-700 transition hover:bg-rose-100">حذف</button>
                                </form>
                            @else
                                <span class="text-[10px] font-bold text-slate-400">فقط مشاهده</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-8 text-center font-bold text-slate-400">موردی برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
    document.getElementById('loading_type_search')?.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        document.querySelectorAll('.loading-type-row').forEach(row => {
            row.classList.toggle('hidden', query !== '' && !row.dataset.search.toLowerCase().includes(query));
        });
    });
</script>
@endsection
