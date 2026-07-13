@extends('layouts.admin')

@section('header_title', 'وضعیت نصب وب‌اپلیکیشن')

@section('content')
<div class="space-y-6" dir="rtl">
    <div>
        <h1 class="text-2xl font-black text-slate-900">آمار نصب وب‌اپلیکیشن</h1>
        <p class="mt-2 text-sm font-semibold text-slate-500">هر ردیف یک نصب یا استفاده از مرورگر روی یک دستگاه است. حذف برنامه توسط مرورگر اعلام نمی‌شود؛ دستگاه‌های بدون فعالیت بیش از ۳۰ روز «غیرفعال» محسوب می‌شوند.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'دستگاه‌های نصب‌شده', 'value' => $stats['devices'], 'color' => 'sky'],
            ['label' => 'کاربران یکتای نصب‌کننده', 'value' => $stats['users'], 'color' => 'emerald'],
            ['label' => 'نصب‌های این ماه', 'value' => $stats['month'], 'color' => 'violet'],
            ['label' => 'نصب فعال در ۳۰ روز', 'value' => $stats['active'], 'color' => 'amber'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm font-bold text-slate-500">{{ $card['label'] }}</div><div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($card['value']) }}</div></div>
        @endforeach
    </div>

    <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-4">
        <select name="role" class="rounded-xl border border-slate-300 px-3 py-2.5"><option value="">همه نقش‌ها</option>@foreach(['admin'=>'ادمین','association'=>'انجمن','company'=>'شرکت','driver'=>'راننده'] as $key=>$label)<option value="{{ $key }}" @selected(request('role')===$key)>{{ $label }}</option>@endforeach</select>
        <select name="device_type" class="rounded-xl border border-slate-300 px-3 py-2.5"><option value="">همه دستگاه‌ها</option>@foreach(['mobile'=>'موبایل','tablet'=>'تبلت','desktop'=>'رومیزی','unknown'=>'نامشخص'] as $key=>$label)<option value="{{ $key }}" @selected(request('device_type')===$key)>{{ $label }}</option>@endforeach</select>
        <select name="status" class="rounded-xl border border-slate-300 px-3 py-2.5"><option value="">همه وضعیت‌ها</option><option value="installed" @selected(request('status')==='installed')>نصب‌شده</option><option value="browser" @selected(request('status')==='browser')>فقط مرورگر</option><option value="active" @selected(request('status')==='active')>فعال ۳۰ روز اخیر</option><option value="inactive" @selected(request('status')==='inactive')>غیرفعال</option></select>
        <button class="rounded-xl bg-slate-800 px-5 py-2.5 font-bold text-white">اعمال فیلتر</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-right">کاربر</th><th class="px-4 py-3 text-right">نقش</th><th class="px-4 py-3 text-right">وضعیت</th><th class="px-4 py-3 text-right">دستگاه</th><th class="px-4 py-3 text-right">مرورگر / سیستم‌عامل</th><th class="px-4 py-3 text-right">تاریخ نصب</th><th class="px-4 py-3 text-right">آخرین فعالیت</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($installations as $item)
                        @php
                            $actor = $item->actor_type === 'driver' ? $drivers->get($item->actor_id) : $users->get($item->actor_id);
                            $actorName = $item->actor_type === 'driver'
                                ? trim(($actor?->first_name_fa ?? '').' '.($actor?->last_name_fa ?? ''))
                                : ($actor?->company?->name_fa ?? $actor?->username ?? 'کاربر حذف‌شده');
                            $active = $item->last_seen_at?->gte(now()->subDays(30));
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4 font-bold text-slate-800">{{ $actorName ?: 'راننده ناشناس' }}<div class="mt-1 text-xs font-normal text-slate-400">{{ $item->device_uuid }}</div></td>
                            <td class="px-4 py-4">{{ ['admin'=>'ادمین','association'=>'انجمن','company'=>'شرکت','driver'=>'راننده'][$item->role] ?? $item->role }}</td>
                            <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $item->is_installed ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $item->is_installed ? 'نصب‌شده' : 'فقط مرورگر' }}</span><div class="mt-2 text-xs {{ $active ? 'text-emerald-600' : 'text-amber-600' }}">{{ $active ? 'فعال' : 'غیرفعال' }}</div></td>
                            <td class="px-4 py-4">{{ ['mobile'=>'موبایل','tablet'=>'تبلت','desktop'=>'رومیزی','unknown'=>'نامشخص'][$item->device_type] ?? 'نامشخص' }}@if($item->is_standalone)<div class="text-xs text-sky-600">اجرای standalone</div>@endif</td>
                            <td class="px-4 py-4">{{ $item->browser ?: 'نامشخص' }}<div class="text-xs text-slate-400">{{ $item->platform ?: 'نامشخص' }}</div></td>
                            <td class="px-4 py-4">{{ $item->installed_at?->format('Y-m-d H:i') ?: '—' }}</td>
                            <td class="px-4 py-4">{{ $item->last_seen_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center font-bold text-slate-400">هنوز اطلاعاتی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $installations->links() }}</div>
    </div>
</div>
@endsection
