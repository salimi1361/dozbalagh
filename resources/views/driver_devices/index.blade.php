@extends(auth()->user()->hasRole('company') ? 'layouts.app' : 'layouts.admin')

@section('header_title', 'مدیریت دستگاه اپلیکیشن رانندگان')

@section('content')
<div class="mx-auto max-w-7xl space-y-5" dir="rtl">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 font-bold text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-black text-slate-900">دستگاه‌های ثبت‌شده رانندگان</h1>
        <p class="mt-2 text-sm font-semibold leading-7 text-slate-500">با ریست دستگاه، گوشی قبلی از مدار خارج و نشست‌های راننده بسته می‌شوند. سپس راننده می‌تواند با گوشی جدید وارد شود.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-right">راننده</th><th class="px-4 py-3 text-right">شرکت</th><th class="px-4 py-3 text-right">گوشی</th><th class="px-4 py-3 text-right">نسخه اپ</th><th class="px-4 py-3 text-right">آخرین اتصال</th><th class="px-4 py-3 text-center">وضعیت و عملیات</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($installations as $item)
                        @php
                            $driver = $item->driver;
                            $driverName = trim(($driver?->first_name_fa ?? '').' '.($driver?->last_name_fa ?? ''));
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4 font-black text-slate-800">{{ $driverName ?: 'راننده حذف‌شده' }}<div class="mt-1 text-xs font-normal text-slate-400" dir="ltr">{{ $driver?->mobile }}</div></td>
                            <td class="px-4 py-4">{{ $driver?->company?->name_fa ?: 'بدون شرکت' }}</td>
                            <td class="px-4 py-4 font-bold">{{ trim(($item->manufacturer ?? '').' '.($item->model ?? '')) ?: 'نامشخص' }}<div class="mt-1 text-xs font-normal text-slate-400">{{ strtoupper($item->platform) }} {{ $item->os_version }}</div></td>
                            <td class="px-4 py-4" dir="ltr">{{ $item->app_version ?: '—' }} @if($item->app_build)({{ $item->app_build }})@endif</td>
                            <td class="px-4 py-4 whitespace-nowrap" dir="ltr">{{ $item->last_seen_at ? verta($item->last_seen_at)->format('Y/m/d H:i') : '—' }}</td>
                            <td class="px-4 py-4 text-center">
                                @if($item->revoked_at)
                                    <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-black text-rose-700">لغوشده</span>
                                    <div class="mt-2 text-xs text-slate-400" dir="ltr">{{ verta($item->revoked_at)->format('Y/m/d H:i') }}</div>
                                @elseif($driver)
                                    <form method="POST" action="{{ route('driver-device-reset.destroy', $driver) }}" onsubmit="return confirm('گوشی قبلی از مدار خارج و تمام نشست‌های راننده بسته شود؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-black text-white hover:bg-rose-700">ریست دستگاه</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center font-bold text-slate-400">هنوز دستگاهی در محدوده دسترسی شما ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $installations->links() }}</div>
    </div>
</div>
@endsection
