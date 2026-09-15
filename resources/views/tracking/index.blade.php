@extends($layout)

@section('header_title', 'ردیابی رانندگان')

@section('content')
<div class="mx-auto max-w-7xl space-y-5 pb-10" dir="rtl">
    <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
        <div class="pointer-events-none absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
        <div class="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>
        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z"/>
                        <circle cx="12" cy="10" r="2.25" stroke-width="1.8"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">ردیابی رانندگان</h1>
                    <p class="mt-1 text-xs font-semibold text-blue-100">نقشه ردیابی {{ $scopeLabel }} و نمایش آخرین موقعیت‌های ثبت‌شده</p>
                </div>
            </div>
            <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                <div class="inline-flex rounded-xl border border-white border-opacity-10 bg-white bg-opacity-10 p-1" role="group" aria-label="نوع نمایش ردیابی">
                    <button type="button" class="tracking-mode rounded-lg bg-emerald-600 px-4 py-2 text-xs font-black text-white shadow-sm transition" data-mode="live">در حال سفر</button>
                    <button type="button" class="tracking-mode rounded-lg px-4 py-2 text-xs font-black text-blue-100 transition hover:bg-white hover:bg-opacity-10" data-mode="recent">آخرین بازدیدها</button>
                </div>
                <span id="tracking-refresh-status" class="rounded-full border border-white border-opacity-10 bg-white bg-opacity-10 px-4 py-2 text-center text-xs font-bold text-blue-100">در حال دریافت...</span>
            </div>
        </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div id="tracking-map-root" data-url="{{ $dataUrl }}" data-tiles="{{ $localTileUrl }}" data-tiles-enabled="{{ $tilesEnabled ? '1' : '0' }}" data-outline="{{ $outlineUrl }}" data-recent-days="{{ $recentDays }}">
                <div id="tracking-map" class="h-[68vh] min-h-[520px] w-full"></div>
                @if ($showMapAttribution)
                    <div class="relative z-10 -mt-7 w-fit rounded-tl-md bg-white/90 px-2 py-1 text-[10px] text-slate-600" dir="ltr">
                        © <a class="underline" href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>
                    </div>
                @endif
            </div>
        </section>
        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 id="tracking-list-title" class="mb-3 font-black text-slate-800">رانندگان در حال سفر</h3>
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
    .tracking-card.is-disabled { cursor: default; opacity: .72; }
    .tracking-card span { display: grid; gap: 3px; }
    .tracking-card small { color: #64748b; font-size: 10px; }
    .tracking-card__dot { width: 10px; height: 10px; border-radius: 50%; background: #94a3b8; }
    .tracking-card__dot.is-online { background: #10b981; box-shadow: 0 0 0 4px #d1fae5; }
</style>
@endsection

@section('scripts')
    @vite('resources/js/tracking-map.js')
@endsection
