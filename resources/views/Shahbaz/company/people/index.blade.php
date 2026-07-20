@extends('layouts.app')
@php($titles=['personnel'=>'پرسنل','board'=>'هیئت‌مدیره','shareholders'=>'سهامداران'])
@section('header_title',$titles[$type])
@section('content')
<div dir="rtl" class="mx-auto max-w-6xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-3xl border bg-white p-6"><div><h1 class="text-xl font-black">{{ $titles[$type] }}</h1><p class="mt-2 text-sm text-slate-500">{{ $editable?'پرونده باز است؛ امکان افزودن، ویرایش و بایگانی وجود دارد.':'پرونده بسته یا در حال بررسی است؛ اطلاعات فقط قابل مشاهده است.' }}</p></div><div class="flex gap-2"><a href="{{ route('company.shahbaz.dossier.show') }}" class="rounded-xl border px-5 py-3 font-bold">بازگشت به پرونده</a>@if($editable)<a href="{{ route('company.shahbaz.people.create',$type) }}" class="rounded-xl bg-emerald-600 px-5 py-3 font-black text-white">+ ایجاد</a>@endif</div></div>
    @if(session('success'))<div class="rounded-xl bg-emerald-50 p-4 font-bold text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 font-bold text-rose-800">{{ $errors->first() }}</div>@endif
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($items as $item)
            <article class="rounded-2xl border bg-white p-5 shadow-sm"><div class="flex justify-between gap-3"><b>{{ $item->person->first_name }} {{ $item->person->last_name }}</b><span class="rounded-full px-2 py-1 text-xs {{ $item->status==='archived'?'bg-violet-100 text-violet-800':'bg-emerald-100 text-emerald-800' }}">{{ $item->status==='archived'?'بایگانی':'فعال' }}</span></div>
                <div class="mt-4 grid gap-2 text-sm">
                    @php($identity=['تابعیت'=>$item->person->nationality,'کد ملی'=>$item->person->national_code,'شماره گذرنامه'=>$item->person->passport_number,'نام پدر'=>$item->person->father_name,'شماره شناسنامه'=>$item->person->birth_certificate_number,'تاریخ تولد'=>$item->person->birth_date?verta($item->person->birth_date)->format('Y/m/d'):null,'محل تولد'=>$item->person->birth_place,'تاریخ صدور شناسنامه'=>$item->person->issued_on?verta($item->person->issued_on)->format('Y/m/d'):null,'شهر صدور'=>$item->person->issue_city,'جنسیت'=>$item->person->gender,'شرایط ایثارگری'=>$item->person->is_veteran?'دارد':'ندارد'])
                    @foreach($identity as $label=>$value)<div><span class="text-slate-500">{{ $label }}:</span> <b>{{ filled($value)?$value:'ثبت نشده' }}</b></div>@endforeach
                    @if($type==='personnel')<div><span class="text-slate-500">عنوان شغلی:</span> <b>{{ $item->job_title ?: 'ثبت نشده' }}</b></div>@endif
                    @if($type==='board')<div><span class="text-slate-500">سمت در هیئت‌مدیره:</span> <b>{{ $item->board_position ?: 'ثبت نشده' }}</b></div>@endif
                    @if($type==='shareholders')<div><span class="text-slate-500">نوع سهامدار:</span> <b>{{ $item->shareholder_type ?: 'ثبت نشده' }}</b></div><div><span class="text-slate-500">نوع سهام:</span> <b>{{ $item->share_type ?: 'ثبت نشده' }}</b></div><div><span class="text-slate-500">مبلغ سهام:</span> <b>{{ $item->share_amount!==null?number_format((float)$item->share_amount).' ریال':'ثبت نشده' }}</b></div><div><span class="text-slate-500">درصد سهام:</span> <b>{{ $item->share_percentage!==null?rtrim(rtrim(number_format((float)$item->share_percentage,4,'.',''),'0'),'.').'٪':'ثبت نشده' }}</b></div>@endif
                    <div><span class="text-slate-500">شروع همکاری:</span> <b>{{ $item->started_on?verta($item->started_on)->format('Y/m/d'):'ثبت نشده' }}</b></div><div><span class="text-slate-500">پایان همکاری:</span> <b>{{ $item->ended_on?verta($item->ended_on)->format('Y/m/d'):'ثبت نشده' }}</b></div>
                    @if($item->note)<div class="rounded-lg bg-slate-50 p-2"><span class="text-slate-500">توضیحات:</span> {{ $item->note }}</div>@endif
                    @if($item->status==='archived' && $item->note)<div class="rounded-lg bg-violet-50 p-2 text-violet-800">دلیل بایگانی: {{ $item->note }}</div>@endif
                </div>
                @if($editable&&$item->status!=='archived')<div class="mt-5 space-y-3"><a class="inline-block font-bold text-blue-700" href="{{ route('company.shahbaz.people.edit',[$type,$item]) }}">ویرایش</a><form method="POST" action="{{ route('company.shahbaz.people.archive',[$type,$item]) }}" class="flex gap-2">@csrf @method('DELETE')<input name="archive_reason" required maxlength="1000" placeholder="دلیل بایگانی" class="min-w-0 flex-1 rounded-lg border p-2 text-sm"><button class="font-bold text-rose-700">بایگانی</button></form></div>@endif
            </article>
        @empty<div class="rounded-2xl border border-dashed bg-white p-10 text-center text-slate-500 md:col-span-3">رکوردی ثبت نشده است.</div>@endforelse
    </div>
</div>
@endsection
