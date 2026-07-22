@extends('layouts.admin')
@section('content')
<div dir="rtl" class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div><h1 class="text-2xl font-black text-slate-800">تنظیمات چاپ دوزوله</h1><p class="mt-2 text-sm text-slate-500">قالب مستقل برای هر کشور و نوع مجوز</p></div>
        <a href="{{ route('association.print-layouts.create') }}" class="rounded-xl bg-sky-600 px-5 py-3 text-sm font-bold text-white">قالب جدید</a>
    </div>
    @if(session('success'))<div class="mb-4 rounded-xl bg-emerald-50 p-4 text-emerald-700">{{ session('success') }}</div>@endif
    <div class="overflow-hidden rounded-2xl border bg-white"><table class="w-full text-right text-sm"><thead class="bg-slate-100"><tr><th class="p-4">عنوان</th><th>کشور</th><th>نوع مجوز</th><th>اندازه</th><th>نسخه</th><th></th></tr></thead><tbody>
    @forelse($layouts as $layout)<tr class="border-t"><td class="p-4 font-bold">{{ $layout->name }}</td><td>{{ $layout->country->name }}</td><td>{{ $permitTypeLabels[$layout->permit_type] ?? $layout->permit_type }}</td><td dir="ltr">{{ $layout->paper_width_mm }} × {{ $layout->paper_height_mm }} mm</td><td>{{ $layout->version }} {{ $layout->is_active ? '— فعال' : '— غیرفعال' }}</td><td><div class="flex items-center justify-center gap-3"><a class="font-bold text-sky-600" href="{{ route('association.print-layouts.edit', $layout) }}">ویرایش و جانمایی</a><form method="POST" action="{{ route('association.print-layouts.destroy', $layout) }}" onsubmit="return confirm('این قالب چاپ و تمام جانمایی‌های آن حذف شود؟ این عملیات قابل بازگشت نیست.');">@csrf @method('DELETE')<button type="submit" class="rounded-lg bg-red-50 px-3 py-1.5 font-bold text-red-600 hover:bg-red-100">حذف</button></form></div></td></tr>
    @empty<tr><td colspan="6" class="p-12 text-center text-slate-400">هنوز قالبی ساخته نشده است.</td></tr>@endforelse
    </tbody></table></div>
</div>
@endsection
