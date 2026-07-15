@extends('layouts.admin')

@section('header_title', 'وضعیت اپلیکیشن‌ها')

@section('content')
<div class="space-y-6" dir="rtl">
    <div>
        <h1 class="text-2xl font-black text-slate-900">آمار نصب و دستگاه‌ها</h1>
        <p class="mt-2 text-sm font-semibold text-slate-500">نصب واقعی اپلیکیشن موبایل و نصب وب‌اپلیکیشن به‌صورت جداگانه نمایش داده می‌شوند.</p>
    </div>

    <div class="inline-flex rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm">
        <a href="{{ route('admin.reports.pwa-installations.index', ['section' => 'app']) }}" class="rounded-xl px-5 py-2.5 text-sm font-black transition {{ $section === 'app' ? 'bg-cyan-600 text-white shadow' : 'text-slate-600 hover:bg-slate-100' }}">📱 اپلیکیشن موبایل</a>
        <a href="{{ route('admin.reports.pwa-installations.index', ['section' => 'web']) }}" class="rounded-xl px-5 py-2.5 text-sm font-black transition {{ $section === 'web' ? 'bg-cyan-600 text-white shadow' : 'text-slate-600 hover:bg-slate-100' }}">🌐 وب‌اپلیکیشن</a>
    </div>

    @if($section === 'app')
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-semibold leading-7 text-blue-800">
            اطلاعات این بخش مستقیماً از اپ نصب‌شده و پس از ورود راننده ثبت می‌شود. شناسه دستگاه، سازنده، مدل واقعی، نسخه سیستم‌عامل و نسخه برنامه قابل مشاهده است. به‌دلیل محدودیت‌های امنیتی Android و iOS، آدرس MAC قابل دریافت نیست و از شناسه امن و پایدار مخصوص برنامه استفاده می‌شود.
        </div>
    @else
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold leading-7 text-amber-800">
            مرورگر حذف وب‌اپ را اعلام نمی‌کند؛ بنابراین دستگاه‌های بدون فعالیت بیش از ۳۰ روز غیرفعال محسوب می‌شوند و شناسه آن‌ها ممکن است با پاک‌شدن اطلاعات مرورگر تغییر کند.
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => $section === 'app' ? 'دستگاه‌های دارای اپ' : 'دستگاه‌های نصب‌شده', 'value' => $stats['devices']],
            ['label' => $section === 'app' ? 'رانندگان یکتا' : 'کاربران یکتای نصب‌کننده', 'value' => $stats['users']],
            ['label' => 'نصب‌های این ماه', 'value' => $stats['month']],
            ['label' => 'فعال در ۳۰ روز اخیر', 'value' => $stats['active']],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm font-bold text-slate-500">{{ $card['label'] }}</div><div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($card['value']) }}</div></div>
        @endforeach
    </div>

    @if($section === 'app')
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-3">
            <input type="hidden" name="section" value="app">
            <select name="platform" class="rounded-xl border border-slate-300 px-3 py-2.5"><option value="">همه سیستم‌عامل‌ها</option><option value="android" @selected(request('platform') === 'android')>Android</option><option value="ios" @selected(request('platform') === 'ios')>iPhone / iOS</option></select>
            <select name="status" class="rounded-xl border border-slate-300 px-3 py-2.5"><option value="">همه وضعیت‌ها</option><option value="active" @selected(request('status') === 'active')>فعال ۳۰ روز اخیر</option><option value="inactive" @selected(request('status') === 'inactive')>غیرفعال</option><option value="revoked" @selected(request('status') === 'revoked')>لغوشده / ریست‌شده</option></select>
            <button class="rounded-xl bg-slate-800 px-5 py-2.5 font-bold text-white">اعمال فیلتر</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-right">راننده</th><th class="px-4 py-3 text-right">سیستم‌عامل</th><th class="px-4 py-3 text-right">گوشی واقعی</th><th class="px-4 py-3 text-right">نسخه اپ</th><th class="px-4 py-3 text-right">شناسه دستگاه</th><th class="px-4 py-3 text-right">تاریخ نصب</th><th class="px-4 py-3 text-right">آخرین اتصال</th><th class="px-4 py-3 text-center">عملیات</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($installations as $item)
                            @php
                                $driver = $item->driver;
                                $driverName = trim(($driver?->first_name_fa ?? '').' '.($driver?->last_name_fa ?? ''));
                                $active = ! $item->revoked_at && $item->last_seen_at?->gte(now()->subDays(30));
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-4 font-bold text-slate-800">{{ $driverName ?: 'راننده حذف‌شده' }}<div class="mt-1 text-xs font-normal text-slate-400">{{ $driver?->mobile }} · {{ $driver?->company?->name_fa }}</div></td>
                                <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-black {{ $item->platform === 'ios' ? 'bg-slate-900 text-white' : 'bg-emerald-100 text-emerald-700' }}">{{ $item->platform === 'ios' ? 'iOS' : 'Android' }}</span><div class="mt-2 text-xs text-slate-500">{{ $item->os_version ?: 'نامشخص' }} @if($item->sdk_version) (API {{ $item->sdk_version }}) @endif</div></td>
                                <td class="px-4 py-4 font-bold">{{ trim(($item->manufacturer ?? '').' '.($item->model ?? '')) ?: 'نامشخص' }}<div class="mt-1 text-xs font-normal text-slate-400">{{ $item->device_name }}</div></td>
                                <td class="px-4 py-4" dir="ltr">{{ $item->app_version ?: '—' }} @if($item->app_build)<span class="text-xs text-slate-400">({{ $item->app_build }})</span>@endif</td>
                                <td class="max-w-56 break-all px-4 py-4 font-mono text-xs text-slate-500">{{ $item->device_uuid }}</td>
                                <td class="px-4 py-4 whitespace-nowrap" dir="ltr">{{ $item->installed_at ? verta($item->installed_at)->format('Y/m/d H:i') : '—' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap" dir="ltr"><span class="font-bold {{ $item->revoked_at ? 'text-rose-600' : ($active ? 'text-emerald-600' : 'text-amber-600') }}">{{ $item->revoked_at ? 'لغوشده' : ($active ? 'فعال' : 'غیرفعال') }}</span><div class="mt-1 text-xs text-slate-500">{{ $item->last_seen_at ? verta($item->last_seen_at)->format('Y/m/d H:i') : '—' }}</div></td>
                                <td class="px-4 py-4 text-center">@if(! $item->revoked_at && $driver)<form method="POST" action="{{ route('driver-device-reset.destroy', $driver) }}" onsubmit="return confirm('دستگاه قبلی از مدار خارج و نشست‌های راننده بسته شود؟')">@csrf @method('DELETE')<button class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-black text-white">ریست دستگاه</button></form>@else<span class="text-xs text-slate-400">—</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-12 text-center font-bold text-slate-400">هنوز اپلیکیشن موبایل از هیچ دستگاهی اطلاعات ارسال نکرده است.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $installations->links() }}</div>
        </div>
    @else
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-4">
            <input type="hidden" name="section" value="web">
            <select name="role" class="rounded-xl border border-slate-300 px-3 py-2.5"><option value="">همه نقش‌ها</option>@foreach(['admin'=>'ادمین','association'=>'انجمن','company'=>'شرکت','driver'=>'راننده'] as $key=>$label)<option value="{{ $key }}" @selected(request('role') === $key)>{{ $label }}</option>@endforeach</select>
            <select name="device_type" class="rounded-xl border border-slate-300 px-3 py-2.5"><option value="">همه دستگاه‌ها</option>@foreach(['mobile'=>'موبایل','tablet'=>'تبلت','desktop'=>'رومیزی','unknown'=>'نامشخص'] as $key=>$label)<option value="{{ $key }}" @selected(request('device_type') === $key)>{{ $label }}</option>@endforeach</select>
            <select name="status" class="rounded-xl border border-slate-300 px-3 py-2.5"><option value="">همه وضعیت‌ها</option><option value="installed" @selected(request('status') === 'installed')>نصب‌شده</option><option value="browser" @selected(request('status') === 'browser')>فقط مرورگر</option><option value="active" @selected(request('status') === 'active')>فعال ۳۰ روز اخیر</option><option value="inactive" @selected(request('status') === 'inactive')>غیرفعال</option></select>
            <button class="rounded-xl bg-slate-800 px-5 py-2.5 font-bold text-white">اعمال فیلتر</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto"><table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-right">کاربر</th><th class="px-4 py-3 text-right">نقش</th><th class="px-4 py-3 text-right">وضعیت</th><th class="px-4 py-3 text-right">دستگاه</th><th class="px-4 py-3 text-right">مرورگر / سیستم‌عامل</th><th class="px-4 py-3 text-right">تاریخ نصب</th><th class="px-4 py-3 text-right">آخرین فعالیت</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($installations as $item)
                        @php
                            $actor = $item->actor_type === 'driver' ? $drivers->get($item->actor_id) : $users->get($item->actor_id);
                            $actorName = $item->actor_type === 'driver' ? trim(($actor?->first_name_fa ?? '').' '.($actor?->last_name_fa ?? '')) : ($actor?->company?->name_fa ?? $actor?->username ?? 'کاربر حذف‌شده');
                            $active = $item->last_seen_at?->gte(now()->subDays(30));
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4 font-bold text-slate-800">{{ $actorName ?: 'راننده ناشناس' }}<div class="mt-1 text-xs font-normal text-slate-400">{{ $item->device_uuid }}</div></td>
                            <td class="px-4 py-4">{{ ['admin'=>'ادمین','association'=>'انجمن','company'=>'شرکت','driver'=>'راننده'][$item->role] ?? $item->role }}</td>
                            <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $item->is_installed ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $item->is_installed ? 'نصب‌شده' : 'فقط مرورگر' }}</span><div class="mt-2 text-xs {{ $active ? 'text-emerald-600' : 'text-amber-600' }}">{{ $active ? 'فعال' : 'غیرفعال' }}</div></td>
                            <td class="px-4 py-4">{{ ['mobile'=>'موبایل','tablet'=>'تبلت','desktop'=>'رومیزی','unknown'=>'نامشخص'][$item->device_type] ?? 'نامشخص' }}@if($item->is_standalone)<div class="text-xs text-sky-600">اجرای مستقل</div>@endif</td>
                            <td class="px-4 py-4">{{ $item->browser ?: 'نامشخص' }}<div class="text-xs text-slate-400">{{ $item->platform ?: 'نامشخص' }}</div></td>
                            <td class="px-4 py-4" dir="ltr">{{ $item->installed_at ? verta($item->installed_at)->format('Y/m/d H:i') : '—' }}</td>
                            <td class="px-4 py-4" dir="ltr">{{ $item->last_seen_at ? verta($item->last_seen_at)->format('Y/m/d H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center font-bold text-slate-400">هنوز اطلاعاتی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <div class="border-t border-slate-100 p-4">{{ $installations->links() }}</div>
        </div>
    @endif
</div>
@endsection
