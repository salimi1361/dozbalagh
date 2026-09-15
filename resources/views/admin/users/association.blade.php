@extends('layouts.admin')

@section('header_title', 'مدیریت کاربران انجمن')

@section('content')
@php
    $featureGroups = collect($featureDefinitions)->groupBy(
        fn (array $definition) => $definition['group'] ?? 'general',
        true
    );
    $groupLabels = ['general' => 'امکانات عمومی', 'shahbaz' => 'شحباز', 'cmr' => 'CMR'];
@endphp
<div class="mx-auto max-w-7xl space-y-6" dir="rtl">
    <section class="overflow-hidden rounded-3xl bg-gradient-to-l from-slate-900 to-sky-900 p-6 text-white shadow-lg">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-black text-sky-200">کنترل دسترسی مستقل</p>
                <h1 class="mt-2 text-2xl font-black">مدیریت کارکنان انجمن</h1>
        <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-200">هر کارمند فقط بخش‌هایی را می‌بیند که همین‌جا برای او انتخاب می‌کنید. دسترسی شحباز نیازی به فعال‌کردن داشبورد انجمن یا داشبورد مدیر ندارد.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="min-w-28 rounded-2xl bg-white/10 px-5 py-3 backdrop-blur"><strong class="block text-2xl">{{ $users->count() }}</strong><span class="text-xs text-slate-200">کاربر ثبت‌شده</span></div>
                <div class="min-w-28 rounded-2xl bg-white/10 px-5 py-3 backdrop-blur"><strong class="block text-2xl">{{ $users->where('status', 'active')->count() }}</strong><span class="text-xs text-slate-200">کاربر فعال</span></div>
            </div>
        </div>
    </section>
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 font-bold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">
            <ul class="list-disc space-y-1 pr-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @include('admin.users.partials.association-user-create')
    @include('admin.users.partials.association-user-list')
    @if(false)
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-black text-slate-800">ساخت کاربر جدید انجمن</h2>
        <p class="mt-2 text-sm leading-7 text-slate-500">حداقل یک بخش را انتخاب کنید. برای کارمند شحباز فقط گروه «شحباز» را باز کنید و همان دسترسی را علامت بزنید.</p>
        <form method="POST" action="{{ route('admin.association-users.store') }}" class="mt-6 grid gap-4 md:grid-cols-2">
            @csrf
            <label class="space-y-2"><span class="text-sm font-bold text-slate-600">نام کاربری</span><input name="username" value="{{ old('username') }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="space-y-2"><span class="text-sm font-bold text-slate-600">شماره موبایل</span><input name="mobile" value="{{ old('mobile') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="space-y-2"><span class="text-sm font-bold text-slate-600">رمز عبور</span><input type="password" name="password" required minlength="8" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="space-y-2"><span class="text-sm font-bold text-slate-600">تکرار رمز عبور</span><input type="password" name="password_confirmation" required minlength="8" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>

            <div class="space-y-3 md:col-span-2">
                <h3 class="font-black text-slate-700">دسترسی منوها و عملیات</h3>
                <div class="grid gap-3 lg:grid-cols-3">
                    @foreach($featureGroups as $group => $items)
                        <details class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50" @if($group === 'shahbaz') open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 font-black text-slate-700">
                                <span>{{ $groupLabels[$group] ?? ($items->first()['group_label'] ?? $group) }}</span>
                                <span class="text-xs text-slate-400">{{ $items->count() }} آیتم</span>
                            </summary>
                            <div class="divide-y divide-slate-100 border-t border-slate-200 bg-white px-4">
                                @foreach($items as $key => $definition)
                                    <label class="flex cursor-pointer items-start gap-3 py-3 text-sm font-bold text-slate-600">
                                        <input type="checkbox" name="features[]" value="{{ $key }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600" @checked(in_array($key, old('features', []), true))>
                                        <span>{{ $definition['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>

            <div class="md:col-span-2 flex justify-end"><button class="rounded-xl bg-sky-600 px-6 py-3 font-black text-white hover:bg-sky-700">ایجاد کاربر انجمن</button></div>
        </form>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-black text-slate-800">کاربران فعلی انجمن</h2>
        <div class="mt-5 space-y-5">
            @forelse($users as $user)
                @php
                    $selectedFeatures = $user->association_feature_keys ?? array_keys($featureDefinitions);
                @endphp
                <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <summary class="flex cursor-pointer list-none flex-col gap-3 bg-slate-50 px-5 py-4 transition hover:bg-sky-50 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 font-black text-sky-700">{{ mb_substr($user->username, 0, 1) }}</span>
                            <span class="min-w-0">
                                <strong class="block truncate text-sm text-slate-800">{{ $user->username }}</strong>
                                <small class="mt-1 block text-xs text-slate-500">{{ $user->mobile ?: 'بدون شماره موبایل' }}</small>
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-[10px] font-black {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $user->status === 'active' ? 'فعال' : 'غیرفعال' }}</span>
                            <span class="rounded-full bg-violet-100 px-3 py-1 text-[10px] font-black text-violet-700">{{ count($selectedFeatures) }} دسترسی</span>
                            <span class="rounded-xl bg-slate-800 px-4 py-2 text-xs font-black text-white group-open:hidden">نمایش و ویرایش دسترسی‌ها</span>
                            <span class="hidden rounded-xl bg-sky-600 px-4 py-2 text-xs font-black text-white group-open:inline-block">بستن دسترسی‌ها</span>
                        </div>
                    </summary>
                    <div class="border-t border-slate-200 p-4">
                    <form method="POST" action="{{ route('admin.association-users.update', $user) }}" class="grid items-end gap-3 md:grid-cols-2 xl:grid-cols-6">
                        @csrf
                        @method('PUT')
                        <label class="space-y-1 xl:col-span-2"><span class="text-xs font-bold text-slate-500">نام کاربری</span><input name="username" value="{{ $user->username }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></label>
                        <label class="space-y-1"><span class="text-xs font-bold text-slate-500">موبایل</span><input name="mobile" value="{{ $user->mobile }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></label>
                        <label class="space-y-1"><span class="text-xs font-bold text-slate-500">وضعیت</span><select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"><option value="active" @selected($user->status === 'active')>فعال</option><option value="inactive" @selected($user->status === 'inactive')>غیرفعال</option><option value="suspended" @selected($user->status === 'suspended')>تعلیق</option></select></label>
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                            <input type="checkbox" name="change_password" value="1" class="association-password-toggle h-4 w-4 rounded border-slate-300 text-sky-600">
                            <span class="text-xs font-bold text-slate-600">تغییر رمز عبور</span>
                        </label>
                        <div class="association-password-fields hidden space-y-3 md:col-span-2 xl:col-span-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="space-y-1"><span class="text-xs font-bold text-slate-500">رمز جدید</span><input type="password" name="password" minlength="8" autocomplete="new-password" disabled class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></label>
                                <label class="space-y-1"><span class="text-xs font-bold text-slate-500">تکرار رمز جدید</span><input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" disabled class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></label>
                            </div>
                        </div>

                        <div class="space-y-3 md:col-span-2 xl:col-span-6">
                            <h3 class="font-black text-slate-700">دسترسی‌های اختصاصی این کاربر</h3>
                            <div class="grid gap-3 lg:grid-cols-3">
                                @foreach($featureGroups as $group => $items)
                                    <details class="overflow-hidden rounded-2xl border border-slate-200 bg-white" @if($group === 'shahbaz' && $items->keys()->intersect($selectedFeatures)->isNotEmpty()) open @endif>
                                        <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 font-black text-slate-700">
                                            <span>{{ $groupLabels[$group] ?? ($items->first()['group_label'] ?? $group) }}</span>
                                            <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] text-slate-500">{{ $items->keys()->intersect($selectedFeatures)->count() }} فعال</span>
                                        </summary>
                                        <div class="divide-y divide-slate-100 border-t border-slate-200 px-4">
                                            @foreach($items as $key => $definition)
                                                <label class="flex cursor-pointer items-start gap-3 py-3 text-sm font-bold text-slate-600">
                                                    <input type="checkbox" name="features[]" value="{{ $key }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600" @checked(in_array($key, $selectedFeatures, true))>
                                                    <span>{{ $definition['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-end md:col-span-2 xl:col-span-6">
                            <button class="rounded-xl bg-slate-800 px-6 py-2.5 font-bold text-white hover:bg-slate-900">ذخیره اطلاعات و دسترسی‌ها</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.association-users.destroy', $user) }}" class="mt-3 border-t border-slate-200 pt-3" onsubmit="return confirm('این کاربر انجمن حذف و امکان ورود او فوراً قطع می‌شود. ادامه می‌دهید؟')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-black text-rose-600 hover:bg-rose-50">حذف کاربر</button>
                    </form>
                    </div>
                </details>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center font-bold text-slate-500">هنوز کاربر انجمن ساخته نشده است.</div>
            @endforelse
        </div>
    </section>
    @endif
</div>
<script>
document.querySelectorAll('.association-password-toggle').forEach(toggle => {
    toggle.addEventListener('change', () => {
        const form = toggle.closest('form');
        const fields = form.querySelector('.association-password-fields');
        fields.classList.toggle('hidden', !toggle.checked);
        fields.querySelectorAll('input').forEach(input => {
            input.disabled = !toggle.checked;
            if (!toggle.checked) input.value = '';
        });
    });
});
</script>
@endsection
