@extends('layouts.admin')
@section('header_title','تنظیمات مراحل پرونده شحباز')
@section('content')
<div dir="rtl" class="mx-auto max-w-6xl space-y-6">
@if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-800">{{ session('success') }}</div>@endif
<div class="rounded-3xl border bg-white p-6 shadow-sm"><h1 class="text-xl font-black">نمایش گام‌به‌گام پرونده شرکت</h1><p class="mt-2 text-sm leading-7 text-slate-600">بخش‌های قابل نمایش و بخش‌های اجباری را تعیین کنید. شرکت تا تکمیل یک گام اجباری، به گام بعدی دسترسی نخواهد داشت.</p></div>
<form method="POST" action="{{ route('admin.shahbaz.settings.update') }}" class="space-y-6">@csrf @method('PUT')
<section class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-sm"><h2 class="font-black">سطح کنترل شهباز برای درخواست دوزوله</h2><p class="mt-2 text-sm leading-7 text-slate-600">این تنظیم فقط هنگام ثبت یا تمدید دوزوله بررسی می‌شود و مانع ورود سایر شرکت‌ها به سامانه نخواهد شد.</p><div class="mt-4 grid gap-3 md:grid-cols-3">
@foreach([
 'disabled'=>['بدون محدودیت','هیچ شرطی از پرونده شهباز برای دوزوله کنترل نشود.'],
 'profile'=>['تکمیل مشخصات شرکت','فقط مشخصات الزامی شرکت کامل باشد. پیشنهاد فعلی'],
 'full_shahbaz'=>['تأیید کامل شهباز','پرونده تأییدشده و پروانه فعالیت معتبر الزامی باشد.'],
] as $value=>$option)
<label class="cursor-pointer rounded-2xl border p-4 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50"><span class="flex items-start gap-3"><input type="radio" name="dozbalagh_gate_mode" value="{{ $value }}" @checked($dozbalaghGateMode===$value) class="mt-1 accent-emerald-600"><span><b class="block">{{ $option[0] }}</b><small class="mt-1 block leading-6 text-slate-500">{{ $option[1] }}</small></span></span></label>
@endforeach
</div></section>
<section class="overflow-hidden rounded-3xl border bg-white shadow-sm"><table class="w-full text-right text-sm"><thead class="bg-slate-100"><tr><th class="p-4">ترتیب</th><th>بخش پرونده</th><th class="text-center">نمایش به شرکت</th><th class="text-center">اجباری</th></tr></thead><tbody>
@foreach($rows as $row)<tr class="border-t"><td class="p-4 text-slate-500">{{ $loop->iteration }}</td><td class="font-bold">{{ $row['label'] }} @if($row['company_read_only'])<span class="text-xs text-slate-400">(فقط مشاهده)</span>@endif</td><td class="text-center"><input type="checkbox" name="sections[{{ $row['key'] }}][visible]" value="1" @checked($row['visible']) class="h-5 w-5 accent-emerald-600"></td><td class="text-center"><input type="checkbox" name="sections[{{ $row['key'] }}][required]" value="1" @checked($row['required']) class="h-5 w-5 accent-amber-600"></td></tr>@endforeach
</tbody></table></section>
<section class="rounded-3xl border bg-white p-6 shadow-sm"><h2 class="font-black">یادآوری اعتبار</h2><div class="mt-4 grid gap-4 md:grid-cols-3"><label class="font-bold md:col-span-3">روزهای یادآوری<input name="reminder_days[]" value="{{ implode(',', $reminders['days']) }}" disabled class="mt-2 w-full rounded-xl border bg-slate-100 p-3"><span class="mt-1 block text-xs text-slate-500">آستانه‌های فعال: {{ implode('، ', $reminders['days']) }} روز. در این فاز ثابت هستند.</span></label><label class="flex items-center gap-3"><input type="hidden" name="panel_enabled" value="0"><input type="checkbox" name="panel_enabled" value="1" @checked($reminders['panel_enabled'])> هشدار داخل پنل</label><label class="flex items-center gap-3"><input type="hidden" name="sms_enabled" value="0"><input type="checkbox" name="sms_enabled" value="1" @checked($reminders['sms_enabled'])> یادآوری پیامکی</label>@foreach($reminders['days'] as $day)<input type="hidden" name="reminder_days[]" value="{{ $day }}">@endforeach</div></section>
<button class="rounded-2xl bg-emerald-600 px-8 py-3 font-black text-white">ذخیره تنظیمات</button></form></div>
@endsection
