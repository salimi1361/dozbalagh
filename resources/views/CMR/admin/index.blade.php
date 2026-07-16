@extends('layouts.admin')
@section('header_title', 'مدیریت e-CMR')
@section('content')
<div class="space-y-5" dir="rtl">
    <div class="flex items-center justify-between"><div><h2 class="text-2xl font-black">اسناد e-CMR</h2><p class="text-sm text-slate-500">ماژول مستقل بارنامه الکترونیکی</p></div><div class="flex gap-2"><a href="{{ route('admin.cmr.settings') }}" class="rounded-xl border px-4 py-2 font-bold">تعرفه</a><a href="{{ route('admin.cmr.create') }}" class="rounded-xl bg-sky-600 px-4 py-2 font-bold text-white">پیش‌نویس جدید</a></div></div>
    <div class="overflow-hidden rounded-2xl border bg-white"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="p-3 text-right">شماره</th><th>شرکت</th><th>وضعیت</th><th>نسخه</th><th>تاریخ</th><th></th></tr></thead><tbody>@forelse($documents as $document)<tr class="border-t"><td class="p-3 font-mono">{{ $document->number ?: 'پیش‌نویس #'.$document->id }}</td><td>{{ $document->company->name_fa ?: $document->company->name }}</td><td>{{ $document->status }}</td><td>{{ $document->version }}</td><td>{{ $document->created_at }}</td><td><a class="text-sky-600 font-bold" href="{{ route('admin.cmr.show', $document) }}">مشاهده</a></td></tr>@empty<tr><td colspan="6" class="p-10 text-center text-slate-400">سندی ثبت نشده است.</td></tr>@endforelse</tbody></table></div>
    {{ $documents->links() }}
</div>
@endsection
