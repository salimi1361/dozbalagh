@extends('layouts.admin')

@section('header_title')
    مدیریت مدارک / <span class="text-indigo-400 font-black">قوانین دوزوله</span>
@endsection

@section('content')
@php
    $ruleTotal = $rules->count();
    $ruleStats = [];

    foreach ($rules as $rule) {
        $config = $normalizedConfigs->get($rule->id, []);
        $counts = collect($config)->countBy();
        $ruleStats[$rule->id] = [
            'required' => (int) ($counts['required'] ?? 0),
            'optional' => (int) ($counts['optional'] ?? 0),
            'hidden' => (int) ($counts['hidden'] ?? 0),
        ];
    }

    $configuredRules = collect($ruleStats)->filter(fn ($stats) => ($stats['required'] + $stats['optional']) > 0)->count();
    $unconfiguredRules = max(0, $ruleTotal - $configuredRules);
    $configuredFields = collect($ruleStats)->sum(fn ($stats) => $stats['required'] + $stats['optional']);
@endphp

<div class="mx-auto max-w-7xl space-y-5 pb-10" dir="rtl">
    <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
        <div class="pointer-events-none absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
        <div class="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>
        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3.75h7.5L19 8.25v12H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3.75v4.5h4.5M8.5 12h7M8.5 15.5h7"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">مدیریت قوانین دوزوله</h1>
                    <p class="mt-1 text-xs font-semibold text-blue-100">تنظیم مدارک و فیلدهای موردنیاز برای هر نوع بار و عملیات</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:min-w-[39rem]">
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                    <span class="text-[10px] font-bold text-blue-100">کل قوانین</span>
                    <strong class="text-lg font-black">{{ number_format($ruleTotal) }}</strong>
                </div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                    <span class="text-[10px] font-bold text-blue-100">تنظیم‌شده</span>
                    <strong class="text-lg font-black text-emerald-300">{{ number_format($configuredRules) }}</strong>
                </div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                    <span class="text-[10px] font-bold text-blue-100">بدون تنظیم</span>
                    <strong class="text-lg font-black text-amber-200">{{ number_format($unconfiguredRules) }}</strong>
                </div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2">
                    <span class="text-[10px] font-bold text-blue-100">فیلدهای فعال</span>
                    <strong class="text-lg font-black text-cyan-200">{{ number_format($configuredFields) }}</strong>
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-1 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-800">فهرست قوانین دوزوله</h2>
                <p class="mt-1 text-xs font-medium text-slate-500">قانون هر نوع بار مستقیماً روی فرم ثبت درخواست و اعتبارسنجی سرور اعمال می‌شود.</p>
            </div>
            <span class="inline-flex w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ number_format($ruleTotal) }} قانون ثبت‌شده</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm whitespace-nowrap">
                <thead class="border-b border-slate-200 bg-slate-50 font-bold text-slate-600">
                    <tr>
                        <th class="p-4">شناسه</th>
                        <th class="p-4">نوع بار / عملیات</th>
                        <th class="p-4">خلاصه قانون</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                        @php($config = $normalizedConfigs->get($rule->id, []))
                        @php($stats = $ruleStats[$rule->id] ?? ['required' => 0, 'optional' => 0, 'hidden' => 0])
                        <tr class="border-b border-slate-100 transition duration-200 hover:bg-slate-50/80">
                            <td class="p-4 font-mono text-slate-400">{{ $rule->id }}</td>
                            <td class="p-4 text-base font-black text-slate-800">{{ $rule->cargo_type }}</td>
                            <td class="p-4">
                                <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                                    <span class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-rose-700">{{ $stats['required'] }} اجباری</span>
                                    <span class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-amber-700">{{ $stats['optional'] }} اختیاری</span>
                                    <span class="rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-slate-500">{{ $stats['hidden'] }} مخفی</span>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <button
                                    type="button"
                                    data-title="{{ $rule->cargo_type }}"
                                    data-action="{{ route('admin.cargo_rules.update', $rule->id) }}"
                                    data-config="{{ json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                    onclick="openEditModal(this)"
                                    class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-600 shadow-sm transition hover:bg-indigo-600 hover:text-white"
                                >
                                    ✏️ تنظیمات فیلدها
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-10 text-center text-sm font-bold text-slate-500">هیچ قانون دوزوله‌ای ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="editModal" class="fixed inset-0 z-[999] hidden items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="modalCargoTitle">
    <div class="my-auto w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-slate-50 p-5">
            <h3 class="text-lg font-black text-slate-800">پیکربندی فیلدها: <span id="modalCargoTitle" class="text-indigo-600"></span></h3>
            <button type="button" onclick="closeEditModal()" class="text-2xl font-black leading-none text-slate-400 hover:text-rose-500" aria-label="بستن">&times;</button>
        </div>

        <form id="editForm" method="POST" action="">
            @csrf
            @method('PUT')

            <div class="p-6">
                <div class="mb-6 flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-800">
                    <span class="text-base">💡</span>
                    <p><strong>اجباری:</strong> شرکت باید مقدار/مدرک را ثبت کند | <strong>اختیاری:</strong> ثبت آن آزاد است | <strong>مخفی:</strong> از فرم حذف و در سرور نیز پذیرفته نمی‌شود.</p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @foreach($fieldGroups as $group)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                            <h4 class="mb-4 border-b pb-2 text-sm font-black text-slate-700">{{ $group['icon'] ?? '📄' }} {{ $group['title'] }}</h4>
                            <div class="space-y-3">
                                @foreach($group['fields'] ?? [] as $key => $field)
                                    <div class="flex items-center justify-between gap-3">
                                        <label for="conf_{{ $key }}" class="text-xs font-bold text-slate-600">{{ $field['label'] }}</label>
                                        <select name="fields_config[{{ $key }}]" id="conf_{{ $key }}" data-rule-field="{{ $key }}" class="w-40 rounded-lg border border-slate-300 bg-white p-1.5 text-xs">
                                            <option value="required">🔴 اجباری</option>
                                            <option value="optional">🟡 اختیاری</option>
                                            <option value="hidden">⚪ مخفی</option>
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 p-5">
                <button type="button" onclick="closeEditModal()" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-100">انصراف</button>
                <button type="submit" class="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700">💾 ذخیره تغییرات</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(button) {
        let config = {};

        try {
            config = JSON.parse(button.getAttribute('data-config') || '{}');
        } catch (error) {
            config = {};
        }

        document.getElementById('modalCargoTitle').innerText = button.getAttribute('data-title') || '';
        document.getElementById('editForm').action = button.getAttribute('data-action') || '';

        document.querySelectorAll('#editForm [data-rule-field]').forEach(function (select) {
            select.value = config[select.dataset.ruleField] || 'hidden';
        });

        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeEditModal();
    });
</script>
@endsection
