@extends(auth()->user()->hasRole('company') ? 'layouts.app' : 'layouts.admin')

@section('header_title', 'اطلاع‌رسانی به رانندگان')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" dir="rtl">
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 font-bold text-rose-700">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('driver-announcements.store') }}" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div><h1 class="text-xl font-black text-slate-900">ایجاد اطلاعیه رانندگان</h1><p class="mt-2 text-sm font-semibold text-slate-500">اطلاعیه اجباری تا زمان تأیید راننده مانع ورود به داشبورد اپ خواهد بود.</p></div>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-2 block text-xs font-black text-slate-600">عنوان</label><input name="title" value="{{ old('title') }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3"></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">نوع نمایش</label><select name="display_mode" class="w-full rounded-xl border border-slate-300 px-4 py-3"><option value="normal">عادی</option><option value="important">مهم هنگام بازشدن</option><option value="mandatory">اجباری و مسدودکننده</option><option value="emergency">اضطراری</option></select></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">اولویت</label><select name="priority" class="w-full rounded-xl border border-slate-300 px-4 py-3"><option value="normal">عادی</option><option value="important">مهم</option><option value="urgent">فوری</option></select></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">مخاطبان</label><select name="audience_type" id="announcement_audience" class="w-full rounded-xl border border-slate-300 px-4 py-3"><option value="all">همه رانندگان مجاز</option>@if($role !== 'company')<option value="company">رانندگان یک شرکت</option>@endif<option value="selected">رانندگان انتخابی</option></select></div>
            @if($role !== 'company')<div id="announcement_company"><label class="mb-2 block text-xs font-black text-slate-600">شرکت</label><select name="company_id" class="w-full rounded-xl border border-slate-300 px-4 py-3"><option value="">انتخاب شرکت</option>@foreach($companies as $company)<option value="{{ $company->id }}">{{ $company->name_fa }}</option>@endforeach</select></div>@endif
            <div id="announcement_drivers"><label class="mb-2 block text-xs font-black text-slate-600">رانندگان انتخابی</label><select name="driver_ids[]" multiple size="5" class="w-full rounded-xl border border-slate-300 px-4 py-3">@foreach($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->first_name_fa }} {{ $driver->last_name_fa }} — {{ $driver->mobile }}</option>@endforeach</select></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">شروع نمایش</label><input type="datetime-local" name="starts_at" class="w-full rounded-xl border border-slate-300 px-4 py-3" dir="ltr"></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">پایان اعتبار</label><input type="datetime-local" name="ends_at" class="w-full rounded-xl border border-slate-300 px-4 py-3" dir="ltr"></div>
            <div class="md:col-span-2"><label class="mb-2 block text-xs font-black text-slate-600">متن اطلاعیه</label><textarea name="message" rows="6" required class="w-full rounded-xl border border-slate-300 px-4 py-3 leading-8">{{ old('message') }}</textarea></div>
            <div><label class="mb-2 block text-xs font-black text-slate-600">متن دکمه تأیید</label><input name="acknowledgement_text" value="مطالعه کردم" class="w-full rounded-xl border border-slate-300 px-4 py-3"></div>
            <div class="flex flex-wrap items-center gap-4"><label class="flex items-center gap-2 text-sm font-black"><input type="checkbox" name="show_once" value="1" checked> نمایش فقط یک‌بار</label><label class="flex items-center gap-2 text-sm font-black"><input type="checkbox" name="requires_acknowledgement" value="1"> نیازمند تأیید مطالعه</label></div>
        </div>
        <button class="rounded-xl bg-emerald-600 px-7 py-3 font-black text-white hover:bg-emerald-700">انتشار اطلاعیه</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-right">اطلاعیه</th><th class="px-4 py-3 text-right">نوع</th><th class="px-4 py-3 text-center">مخاطب</th><th class="px-4 py-3 text-center">مشاهده</th><th class="px-4 py-3 text-center">تأیید</th><th class="px-4 py-3 text-center">وضعیت</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($announcements as $item)<tr><td class="px-4 py-4"><b class="text-slate-900">{{ $item->title }}</b><div class="mt-1 max-w-xl text-xs leading-6 text-slate-500">{{ \Illuminate\Support\Str::limit($item->message, 140) }}</div></td><td class="px-4 py-4">{{ ['normal'=>'عادی','important'=>'مهم','mandatory'=>'اجباری','emergency'=>'اضطراری'][$item->display_mode] ?? $item->display_mode }}</td><td class="px-4 py-4 text-center font-black">{{ number_format($item->receipts_count) }}</td><td class="px-4 py-4 text-center font-black text-sky-600">{{ number_format($item->seen_count) }}</td><td class="px-4 py-4 text-center font-black text-emerald-600">{{ number_format($item->acknowledged_count) }}</td><td class="px-4 py-4 text-center"><form method="POST" action="{{ route('driver-announcements.toggle', $item) }}">@csrf @method('PUT')<button class="rounded-lg px-3 py-2 text-xs font-black {{ $item->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $item->is_active ? 'فعال' : 'متوقف' }}</button></form></td></tr>
            @empty<tr><td colspan="6" class="p-10 text-center font-bold text-slate-400">هنوز اطلاعیه‌ای منتشر نشده است.</td></tr>@endforelse
        </tbody></table></div><div class="border-t border-slate-100 p-4">{{ $announcements->links() }}</div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const audience = document.getElementById('announcement_audience');
    const company = document.getElementById('announcement_company');
    const drivers = document.getElementById('announcement_drivers');
    function syncAudience() { if (company) company.style.display = audience.value === 'company' ? '' : 'none'; drivers.style.display = audience.value === 'selected' ? '' : 'none'; }
    audience.addEventListener('change', syncAudience); syncAudience();
});
</script>
@endsection
