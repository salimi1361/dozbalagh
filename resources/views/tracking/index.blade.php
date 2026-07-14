@extends($layout)

@section('header_title', 'ردیابی رانندگان')

@section('content')
<div class="space-y-4" dir="rtl">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-black text-slate-900">نقشه ردیابی {{ $scopeLabel }}</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">نقاط آفلاین پس از اتصال راننده به اینترنت، به‌ترتیب زمان روی مسیر نمایش داده می‌شوند.</p>
            </div>
            <span id="tracking-refresh-status" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-bold text-slate-600">در حال دریافت...</span>
        </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div id="tracking-map-root" data-url="{{ $dataUrl }}" data-tiles="{{ $localTileUrl }}">
                <div id="tracking-map" class="h-[68vh] min-h-[520px] w-full"></div>
            </div>
        </section>
        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 font-black text-slate-800">آخرین وضعیت رانندگان</h3>
            <div id="tracking-list" class="max-h-[65vh] space-y-2 overflow-auto"></div>
        </aside>
    </div>
</div>

<style>
    .tracking-marker { width: 26px; height: 26px; border: 4px solid white; border-radius: 999px; background: #64748b; box-shadow: 0 4px 15px #0f172a55; }
    .tracking-marker.is-online { background: #10b981; }
    .tracking-marker span { display: block; width: 8px; height: 8px; margin: 5px; border-radius: 999px; background: white; }
    .tracking-popup { display: grid; min-width: 190px; gap: 5px; font-family: Vazirmatn, Tahoma, sans-serif; }
    .tracking-popup strong { font-size: 14px; color: #0f172a; }
    .tracking-popup span { font-size: 11px; color: #475569; }
    .tracking-card { display: grid; width: 100%; grid-template-columns: 12px 1fr 1fr; gap: 10px; align-items: center; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; text-align: right; transition: .2s; }
    .tracking-card:hover { border-color: #38bdf8; background: #f0f9ff; }
    .tracking-card span { display: grid; gap: 3px; }
    .tracking-card small { color: #64748b; font-size: 10px; }
    .tracking-card__dot { width: 10px; height: 10px; border-radius: 50%; background: #94a3b8; }
    .tracking-card__dot.is-online { background: #10b981; box-shadow: 0 0 0 4px #d1fae5; }
</style>
@endsection

@section('scripts')
    @vite('resources/js/tracking-map.js')
@endsection
