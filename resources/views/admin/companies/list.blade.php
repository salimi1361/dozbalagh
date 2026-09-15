@extends('layouts.admin')

@section('header_title', 'مدیریت شرکت‌ها')

@section('content')
@php
    $statusLabels = [
        'pending' => ['تکمیل‌نشده', 'bg-slate-100 text-slate-700 border-slate-200'],
        'profile_incomplete' => ['تکمیل‌نشده', 'bg-slate-100 text-slate-700 border-slate-200'],
        'pending_review' => ['منتظر بررسی', 'bg-amber-50 text-amber-700 border-amber-200'],
        'correction_required' => ['نیازمند اصلاح', 'bg-rose-50 text-rose-700 border-rose-200'],
        'shahbaz_required' => ['در حال تکمیل شحباز', 'bg-sky-50 text-sky-700 border-sky-200'],
        'shahbaz_review' => ['منتظر تأیید شحباز', 'bg-violet-50 text-violet-700 border-violet-200'],
        'shahbaz_correction' => ['اصلاح پرونده شحباز', 'bg-rose-50 text-rose-700 border-rose-200'],
        'approved' => ['تأییدشده', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    ];
@endphp

<div class="space-y-5" dir="rtl">
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
        <div class="absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
        <div class="absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>
        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v16M9 7h3m-3 4h3m-3 4h3m4-6h2a1 1 0 0 1 1 1v11"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">مدیریت شرکت‌ها</h1>
                    <p class="mt-1 text-xs font-semibold text-blue-100">مدیریت اعضای انجمن، شرکت‌های غیرعضو و روند تأیید اطلاعات</p>
                </div>
            </div>
            <button type="button" onclick="openCompanyModal()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-black text-blue-800 shadow-md transition hover:bg-blue-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                افزودن شرکت جدید
            </button>
        </div>

        <div class="relative mt-4 grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-5">
            <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">کل شرکت‌ها</span><strong class="text-lg font-black">{{ number_format((int) $companyStats->total) }}</strong></div>
            <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">اعضای انجمن</span><strong class="text-lg font-black text-cyan-200">{{ number_format((int) $companyStats->members) }}</strong></div>
            <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">شرکت‌های غیرعضو</span><strong class="text-lg font-black text-violet-200">{{ number_format((int) $companyStats->non_members) }}</strong></div>
            <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">تأییدشده</span><strong class="text-lg font-black text-emerald-300">{{ number_format((int) $companyStats->approved) }}</strong></div>
            <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2"><span class="text-[10px] font-bold text-blue-100">نیازمند پیگیری</span><strong class="text-lg font-black text-amber-300">{{ number_format((int) $companyStats->follow_up) }}</strong></div>
        </div>
    </section>

    <form method="GET" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <label class="min-w-0 flex-1 text-xs font-black text-slate-600">جستجو در شرکت‌ها
                <span class="relative mt-2 block">
                    <svg class="absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z"/></svg>
                    <input name="q" value="{{ request('q') }}" class="w-full rounded-xl border border-slate-300 py-3 pl-4 pr-11 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100" placeholder="نام شرکت یا شناسه ملی را وارد کنید">
                </span>
            </label>
            <label class="text-xs font-black text-slate-600 lg:w-48">نوع عضویت
                <select name="membership" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    <option value="">همه شرکت‌ها</option>
                    <option value="1" @selected(request('membership') === '1')>اعضای انجمن</option>
                    <option value="0" @selected(request('membership') === '0')>شرکت‌های غیرعضو</option>
                </select>
            </label>
            <label class="text-xs font-black text-slate-600 lg:w-52">وضعیت پرونده
                <select name="status" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    <option value="">همه وضعیت‌ها</option>
                    @foreach($statusLabels as $value => $meta)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $meta[0] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex gap-2">
                <button class="flex-1 rounded-xl bg-slate-800 px-5 py-3 text-sm font-black text-white transition hover:bg-slate-700">اعمال فیلتر</button>
                @if(request()->hasAny(['q','status','membership']))
                    <a href="{{ route('admin.companies.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-600 hover:bg-slate-100">پاک‌کردن</a>
                @endif
            </div>
        </div>
        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
            <span>{{ number_format($companies->total()) }} نتیجه مطابق فیلتر فعلی</span>
            <span>نمایش {{ $companies->firstItem() ?? 0 }} تا {{ $companies->lastItem() ?? 0 }}</span>
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div><h2 class="font-black text-slate-800">فهرست شرکت‌ها</h2><p class="mt-1 text-xs text-slate-500">برای مشاهده جزئیات یا اصلاح اطلاعات از گزینه‌های هر ردیف استفاده کنید.</p></div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-right text-sm">
                <thead class="bg-slate-50 text-xs font-black text-slate-600">
                    <tr>
                        <th class="w-14 px-4 py-3.5 text-center">ردیف</th>
                        <th class="min-w-72 px-4 py-3.5">مشخصات شرکت</th>
                        <th class="min-w-40 px-4 py-3.5">نام کاربری</th>
                        <th class="min-w-52 px-4 py-3.5 text-center">عضویت و وضعیت</th>
                        <th class="min-w-64 px-4 py-3.5 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($companies as $company)
                        @php($status = $statusLabels[$company->status] ?? [$company->status, 'bg-slate-100 text-slate-600 border-slate-200'])
                        <tr class="transition hover:bg-blue-50/40">
                            <td class="px-4 py-4 text-center text-xs font-bold text-slate-400">{{ $companies->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M6 21V4.5h10V21M9 8h4m-4 4h4m-4 4h4m3-7h2v12"/></svg>
                                    </div>
                                    <div class="min-w-0"><div class="font-black leading-6 text-slate-800">{{ $company->name_fa ?? $company->name }}</div><div dir="ltr" class="mt-0.5 text-right font-mono text-[11px] text-slate-400">{{ $company->company_code }}</div></div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4"><div dir="ltr" class="text-right font-mono text-sm font-bold text-slate-700">{{ $company->national_id }}</div><div class="mt-1 text-[10px] text-slate-400">شناسه ملی شرکت</div></td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex flex-wrap justify-center gap-1.5">
                                    <span class="rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $company->is_association_member ? 'border-blue-100 bg-blue-50 text-blue-700' : 'border-violet-200 bg-violet-50 text-violet-700' }}">{{ $company->is_association_member ? 'عضو انجمن' : 'غیرعضو' }}</span>
                                    <span class="rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $status[1] }}">{{ $status[0] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center justify-center gap-1.5 text-xs font-bold">
                                    @if(in_array($company->status, ['pending_review', 'correction_required']))
                                        <a href="{{ route('admin.companies.review', $company->id) }}" class="rounded-lg bg-amber-50 px-3 py-2 text-amber-700 hover:bg-amber-100">{{ $company->status === 'pending_review' ? 'بررسی' : 'مشاهده نقص' }}</a>
                                    @endif
                                    <a href="{{ route('admin.companies.edit', $company->id) }}" class="rounded-lg bg-indigo-50 px-3 py-2 text-indigo-700 hover:bg-indigo-100">ویرایش</a>
                                    <form action="{{ route('admin.companies.reset-password', $company->id) }}" method="POST">@csrf @method('PUT')<button class="rounded-lg bg-slate-100 px-3 py-2 text-slate-600 hover:bg-slate-200">بازنشانی رمز</button></form>
                                    <button type="button" onclick="confirmDelete('delete-form-{{ $company->id }}', @js($company->name_fa ?? $company->name))" class="rounded-lg bg-rose-50 px-3 py-2 text-rose-600 hover:bg-rose-100">حذف</button>
                                    <form id="delete-form-{{ $company->id }}" action="{{ route('admin.companies.destroy', $company->id) }}" method="POST" class="hidden">@csrf @method('DELETE')</form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-16 text-center"><div class="font-bold text-slate-500">شرکتی با این مشخصات پیدا نشد.</div><a href="{{ route('admin.companies.index') }}" class="mt-2 inline-block text-sm font-bold text-blue-600">نمایش همه شرکت‌ها</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($companies->hasPages())
        <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">{{ $companies->links() }}</div>
    @endif
</div>

@include('admin.companies.create_modal')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
function confirmDelete(formId, companyName) {
    Swal.fire({title:'حذف شرکت',text:`آیا از حذف «${companyName}» مطمئن هستید؟`,icon:'warning',showCancelButton:true,confirmButtonColor:'#dc2626',confirmButtonText:'حذف',cancelButtonText:'انصراف'}).then((result)=>{if(result.isConfirmed)document.getElementById(formId).submit();});
}
</script>
@endsection
