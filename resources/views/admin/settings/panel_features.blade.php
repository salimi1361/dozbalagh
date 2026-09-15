@extends('layouts.admin')

@section('title', 'مدیریت دسترسی پنل‌ها')
@section('header_title', 'مدیریت دسترسی پنل انجمن و شرکت')

@section('content')
<div class="mx-auto max-w-6xl space-y-6" dir="rtl">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-black text-slate-800">فعال‌سازی آیتم‌های هر پنل</h2>
        <p class="mt-2 text-sm leading-7 text-slate-500">با خاموش‌کردن هر آیتم، آن گزینه از منوی پنل مربوط حذف و دسترسی مستقیم به صفحات آن نیز مسدود می‌شود. دسترسی ادمین کل همیشه برقرار می‌ماند.</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.panel-features.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach(['admin' => 'پنل ادمین', 'association' => 'پنل انجمن', 'company' => 'پنل شرکت'] as $role => $roleTitle)
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50 px-6 py-5">
                        <h3 class="text-lg font-black text-slate-800">{{ $roleTitle }}</h3>
                    </div>
                    <div class="space-y-4 p-5">
                        @php
                            $groupedDefinitions = collect($definitions[$role])->groupBy(
                                fn (array $definition) => $definition['group'] ?? 'general',
                                true
                            );
                            $groupOrder = ['general', 'shahbaz', 'cmr'];
                            $groupedDefinitions = $groupedDefinitions->sortBy(
                                fn ($items, $group) => array_search($group, $groupOrder, true) === false
                                    ? 99
                                    : array_search($group, $groupOrder, true)
                            );
                        @endphp

                        @foreach($groupedDefinitions as $group => $items)
                            @php
                                $groupLabel = $group === 'general'
                                    ? 'امکانات عمومی پنل'
                                    : ($items->first()['group_label'] ?? $group);
                                $groupEnabledCount = $items->filter(
                                    fn ($definition, $key) => $features->enabledForRole($role, $key)
                                )->count();
                            @endphp
                            <details class="overflow-hidden rounded-2xl border {{ $group === 'general' ? 'border-slate-200' : 'border-sky-200 bg-sky-50/30' }}" @if($group === 'general') open @endif>
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 bg-slate-50 px-4 py-3 font-black text-slate-800">
                                    <span>{{ $groupLabel }}</span>
                                    <span class="rounded-full bg-white px-2.5 py-1 text-[10px] text-slate-500 shadow-sm">{{ $groupEnabledCount }} فعال از {{ $items->count() }}</span>
                                </summary>
                                <div class="divide-y divide-slate-100 bg-white px-4">
                                    @foreach($items as $key => $definition)
                                        <label class="flex cursor-pointer items-center justify-between gap-4 py-3.5">
                                            <span>
                                                <span class="block font-bold text-slate-700">{{ $definition['label'] }}</span>
                                                @if(($definition['default'] ?? true) === false)
                                                    <span class="mt-1 block text-[10px] font-bold text-amber-600">پیش‌فرض غیرفعال</span>
                                                @endif
                                            </span>
                                            <span class="relative inline-flex shrink-0 items-center">
                                                <input type="checkbox" name="features[{{ $role }}][{{ $key }}]" value="1" class="peer sr-only" @checked($features->enabledForRole($role, $key))>
                                                <span class="h-7 w-12 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500"></span>
                                                <span class="absolute right-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:-translate-x-5"></span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <section class="rounded-3xl border border-amber-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-black text-slate-800">تنظیمات مالی انتقال دوزوله استفاده‌نشده</h3>
            <p class="mt-2 text-sm text-slate-500">این بخش مستقل از مجوز انتقال است و در حالت پیش‌فرض هیچ مبلغی جابه‌جا نمی‌کند.</p>
            <div class="mt-5 grid gap-4">
                <label class="flex items-center justify-between rounded-2xl border border-slate-200 p-4">
                    <span class="font-bold text-slate-700">دریافت هزینه انتقال از شرکت مقصد</span>
                    <input type="checkbox" name="transfer_financial[enabled]" value="1" class="h-5 w-5" @checked($transferFinancial['enabled'])>
                </label>
                <label class="hidden rounded-2xl border border-slate-200 p-4">
                    <span class="block text-xs font-bold text-slate-600">مبلغ هزینه انتقال (ریال)</span>
                    <input type="hidden" name="transfer_financial[fee_amount]" value="0">
                </label>
                <label class="hidden items-center justify-between rounded-2xl border border-slate-200 p-4">
                    <span class="font-bold text-slate-700">بازگشت وجه به شرکت مبدأ</span>
                    <input type="hidden" name="transfer_financial[refund_enabled]" value="0">
                </label>
                <label class="hidden rounded-2xl border border-slate-200 p-4">
                    <span class="block text-xs font-bold text-slate-600">مبلغ بازگشتی (ریال)</span>
                    <input type="hidden" name="transfer_financial[refund_amount]" value="0">
                </label>
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm font-bold leading-7 text-sky-900">مبلغ انتقال از تعرفه عضو یا غیرعضو کشور همان دوزوله خوانده می‌شود. وجه فقط از کیف پول شرکت مقصد کسر می‌شود و برای شرکت مبدأ هیچ تراکنشی انجام نمی‌شود.</div>
            </div>
        </section>

        <div class="sticky bottom-4 flex justify-end">
            <button type="submit" class="rounded-2xl bg-sky-600 px-7 py-3 font-black text-white shadow-lg shadow-sky-600/20 transition hover:bg-sky-700">ذخیره تنظیمات دسترسی</button>
        </div>
    </form>
</div>
@endsection
