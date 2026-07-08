@extends('layouts.admin')

@section('header_title', 'صدور و ثبت دستی سریال دوزوله')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-2xl p-6 shadow-xl border border-slate-800">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-emerald-500/10 text-emerald-500 rounded-xl border border-emerald-500/20 text-2xl">
                ✍️
            </div>
            <div>
                <h2 class="text-lg font-black tracking-wide">ثبت دستی سریال و تسویه نهایی پروانه‌ها</h2>
                <p class="text-slate-400 text-xs mt-1">پرونده‌های تایید شده که اپراتور انجمن باید شماره سریال دوزوله کشور را هنگام صدور و چاپ وارد کند.</p>
            </div>
        </div>
    </div>

    <div class="dbz-issue-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="dbz-toolbar p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/50">
            <h3 class="font-bold text-sm text-slate-700 flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                مجوزهای آماده صدور (<span id="request-count">{{ $requests->total() }}</span>)
            </h3>
            
            <div class="relative w-full sm:w-72">
                <input type="text" id="tableSearch" onkeyup="filterTable()" placeholder="جستجو در راننده، ناوگان، کد رهگیری..." class="w-full pl-3 pr-9 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-slate-700">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 text-sm">
                    🔍
                </div>
            </div>
        </div>

        <div class="dbz-table-wrap overflow-x-auto">
            <table id="permitsTable" class="dbz-issue-table w-full text-right border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-100 text-slate-600 text-xs font-bold whitespace-nowrap">
                        <th class="p-4">کد سیستم</th>
                        <th class="p-4">کد رهگیری سامانه</th>
                        <th class="p-4">مشخصات راننده متقاضی</th>
                        <th class="p-4 text-center">ناوگان / پلاک</th>
                        <th class="p-4">کشور مقصد</th>
                        <th class="p-4 text-center">ثبت دستی سریال و چاپ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 text-sm text-slate-700">
                    @forelse($requests as $index => $req)
                        <tr id="req-row-{{ $req->id }}" class="permit-row dbz-row transition-all duration-200 {{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} hover:bg-indigo-50/40">
                            
                            <td class="p-4 font-bold text-slate-400">#{{ $req->id }}</td>
                            
                            <td class="p-4 font-mono font-bold text-slate-900">
                                @if(isset($req->d_code))
                                    <span onclick="copyToClipboard('{{ $req->d_code }}', this)" class="cursor-pointer bg-white hover:bg-indigo-50 text-slate-700 hover:text-indigo-600 px-2.5 py-1.5 rounded-lg border border-slate-200 hover:border-indigo-200 transition-all inline-flex items-center gap-1.5 group shadow-sm" title="کلیک جهت کپی کدرهگیری">
                                        <span class="search-target">{{ $req->d_code }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-500 transition-colors"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5" /></svg>
                                    </span>
                                @else
                                    <span class="text-slate-400">---</span>
                                @endif
                            </td>

                            <td class="p-4">
                                <div class="dbz-driver-card flex flex-col">
                                    <span class="font-bold text-slate-900 search-target">
                                        {{ $req->driver ? trim(($req->driver->first_name_fa ?? '') . ' ' . ($req->driver->last_name_fa ?? '')) : 'نامشخص' }}
                                    </span>
                                    <span class="text-slate-400 text-[11px] mt-0.5 search-target">کد ملی: {{ $req->driver->national_code ?? $req->driver_id ?? '---' }}</span>
                                </div>
                            </td>

                            <td class="p-4 flex flex-col items-center justify-center gap-1.5 whitespace-nowrap">
                                <span class="text-xs font-bold text-slate-600 search-target">
                                    💳 کارت هوشمند: <strong class="font-mono text-slate-900">{{ $req->fleet->smart_card_number ?? $req->fleet_id ?? '---' }}</strong>
                                </span>
                                
                                @php
                                    $rawPlate = optional($req->fleet)->transit_plate ?? '';
                                    $plateParts = !empty($rawPlate) ? explode('-', $rawPlate) : [];
                                @endphp
                                @if(count($plateParts) == 4)
                                    <div dir="ltr" class="dbz-plate search-target" title="پلاک ناوگان">
                                        <div class="dbz-plate-blue">
                                            <div class="dbz-flag"><span></span><span></span><span></span></div>
                                            <div class="dbz-iran-text"><span>I.R.</span><span>IRAN</span></div>
                                        </div>
                                        <div class="dbz-plate-main">
                                            <span>{{ $plateParts[0] }}</span>
                                            <span class="dbz-plate-letter">{{ $plateParts[1] }}</span>
                                            <span>{{ $plateParts[2] }}</span>
                                        </div>
                                        <div class="dbz-plate-city">
                                            <span>ایران</span>
                                            <strong>{{ $plateParts[3] }}</strong>
                                        </div>
                                    </div>
                                @else
                                    <span class="bg-slate-50 text-slate-500 px-2 py-0.5 rounded border border-slate-100 text-[11px] font-bold w-fit search-target">{{ $rawPlate ?: 'بدون پلاک' }}</span>
                                @endif
                            </td>

                            <td class="p-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-100 search-target">
                                    {{ $req->country_name ?? 'نامشخص' }}
                                </span>
                            </td>

                            <td class="p-4 text-center">
                                {{-- 🟢 متغیرهای روز اعتبار و تاریخ پایان هم به صورت هوشمند پاس داده شدند --}}
                                <button type="button" onclick="manualAssignAndPrint(@js($req->id), @js($req->d_code), @js($req->next_serial_in_warehouse), @js($req->validity_days), @js($req->expire_date_jalali), @js($req->is_renewal ?? false))" class="dbz-action-btn px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 mx-auto cursor-pointer">
                                    {{ ($req->is_renewal ?? false) ? '🔄 صدور تمدید و چاپ' : '✍️ ثبت سریال و چاپ پروانه' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400 bg-white">
                                <div class="text-4xl mb-3">🗂️</div>
                                <p class="font-bold text-sm">هیچ پرونده‌ای در این مرحله وجود ندارد.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between font-bold text-xs">
                <div class="text-slate-500">
                    نمایش {{ $requests->firstItem() }} تا {{ $requests->lastItem() }} از کل {{ $requests->total() }} پروانه معلق
                </div>
                <div class="flex items-center gap-1">
                    @if($requests->onFirstPage())
                        <span class="px-3 py-2 bg-slate-100 text-slate-400 rounded-xl cursor-not-allowed">قبلی</span>
                    @else
                        <a href="{{ $requests->previousPageUrl() }}" class="px-3 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl transition">قبلی</a>
                    @endif

                    @if($requests->hasMorePages())
                        <a href="{{ $requests->nextPageUrl() }}" class="px-3 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl transition">بعدی</a>
                    @else
                        <span class="px-3 py-2 bg-slate-100 text-slate-400 rounded-xl cursor-not-allowed">بعدی</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<div id="copy-toast" class="fixed top-5 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-2xl transition-all duration-300 opacity-0 pointer-events-none z-[200] flex items-center gap-2">
    <span>📋</span> کد رهگیری با موفقیت کپی شد.
</div>

<style>

/* Dozbalagh issue page polish - content only */
.dbz-issue-card{
    box-shadow: 0 18px 55px rgba(15,23,42,.07), 0 2px 8px rgba(15,23,42,.04) !important;
    border-color: #dbe3ef !important;
}
.dbz-toolbar{
    background: linear-gradient(180deg,#ffffff 0%,#f8fafc 100%) !important;
}
.dbz-table-wrap{
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.dbz-table-wrap::-webkit-scrollbar{height:8px;}
.dbz-table-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px;}
.dbz-issue-table thead tr{
    background: linear-gradient(180deg,#f8fafc 0%,#eef2f7 100%) !important;
}
.dbz-issue-table th{
    padding-top: 18px !important;
    padding-bottom: 18px !important;
    color:#334155 !important;
    letter-spacing:-.01em;
}
.dbz-row{
    position: relative;
}
.dbz-row:hover{
    background: linear-gradient(90deg, rgba(238,242,255,.65), rgba(236,253,245,.35)) !important;
    transform: translateY(-1px);
    box-shadow: inset 4px 0 0 #10b981;
}
.dbz-driver-card{
    min-width: 190px;
    padding: 8px 10px;
    border-radius: 14px;
    background: linear-gradient(180deg,#fff,#f8fafc);
    border: 1px solid #eef2f7;
}
.dbz-action-btn{
    background: linear-gradient(135deg,#059669,#10b981) !important;
    border: 1px solid rgba(255,255,255,.18) !important;
    min-width: 178px;
    justify-content: center;
    box-shadow: 0 12px 24px rgba(5,150,105,.22) !important;
}
.dbz-action-btn:hover{
    transform: translateY(-2px);
    box-shadow: 0 16px 30px rgba(5,150,105,.30) !important;
}
.dbz-plate{
    width: 178px;
    height: 48px;
    display: inline-flex;
    align-items: stretch;
    overflow: hidden;
    border-radius: 10px;
    border: 2px solid #111827;
    background: #f6b900;
    color: #020617;
    box-shadow: 0 9px 18px rgba(15,23,42,.16), inset 0 1px 0 rgba(255,255,255,.55);
    font-family: Tahoma, Arial, sans-serif;
    direction: ltr;
    transform: translateZ(0);
}
.dbz-plate-blue{
    width: 26px;
    flex: 0 0 26px;
    background: linear-gradient(180deg,#0647b8,#002b7f);
    border-right: 2px solid #111827;
    color:#fff;
    padding: 4px 3px;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    align-items:center;
}
.dbz-flag{
    width: 16px;
    height: 8px;
    border-radius: 2px;
    overflow:hidden;
    box-shadow: 0 0 0 1px rgba(255,255,255,.25);
}
.dbz-flag span{display:block;height:33.33%;}
.dbz-flag span:nth-child(1){background:#239f40;}
.dbz-flag span:nth-child(2){background:#fff;}
.dbz-flag span:nth-child(3){background:#da0000;}
.dbz-iran-text{
    font-size: 6px;
    font-weight: 900;
    line-height: .95;
    text-align:center;
    letter-spacing:-.03em;
}
.dbz-iran-text span{display:block;}
.dbz-plate-main{
    flex:1;
    min-width:0;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    padding: 0 8px;
    font-size: 22px;
    font-weight: 1000;
    font-family: Tahoma, Arial, sans-serif;
    text-shadow: 0 1px 0 rgba(255,255,255,.35);
}
.dbz-plate-letter{
    font-family: Tahoma, Arial, sans-serif;
    font-size: 20px;
    font-weight: 1000;
    margin-top:-2px;
}
.dbz-plate-city{
    width: 42px;
    flex:0 0 42px;
    border-left: 2px solid #111827;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-align:center;
    line-height:1;
    background: linear-gradient(180deg,#ffc928,#f3b300);
}
.dbz-plate-city span{
    font-size: 8px;
    font-weight: 900;
    padding-bottom: 4px;
    width:100%;
    border-bottom: 1.5px solid #111827;
}
.dbz-plate-city strong{
    font-size: 17px;
    font-weight: 1000;
    padding-top: 4px;
    font-family: Tahoma, Arial, sans-serif;
}
@media (max-width: 768px){
    .dbz-plate{width:154px;height:43px;}
    .dbz-plate-main{font-size:18px;gap:5px;}
    .dbz-plate-letter{font-size:17px;}
    .dbz-plate-city{width:37px;flex-basis:37px;}
    .dbz-action-btn{min-width:150px;}
}

.serial-swal-popup {
    width: min(720px, calc(100vw - 28px)) !important;
    border-radius: 24px !important;
    padding: 30px 38px 26px !important;
    font-family: inherit !important;
    box-shadow: 0 28px 80px rgba(15, 23, 42, .28) !important;
}
.serial-swal-title {
    color: #0f2350 !important;
    font-size: 28px !important;
    font-weight: 900 !important;
    margin-top: 4px !important;
}
.serial-modal-box {
    text-align: right;
    color: #334155;
}
.serial-doc-icon {
    width: 86px;
    height: 86px;
    border-radius: 999px;
    margin: 0 auto 14px;
    background: linear-gradient(135deg, #eef5ff, #ffffff);
    border: 1px solid #b8ccff;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    font-size: 34px;
}
.serial-doc-icon small {
    position: absolute;
    bottom: 13px;
    right: 18px;
    background: #2563eb;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border: 2px solid white;
}
.serial-desc {
    text-align: center;
    color: #64748b;
    font-size: 14px;
    font-weight: 700;
    line-height: 2;
    margin: 0 0 18px;
}
.serial-desc b {
    color: #2563eb;
    font-family: monospace;
    font-size: 16px;
}
.serial-suggest {
    height: 54px;
    border-radius: 14px;
    background: #f3f7ff;
    border: 1px solid #b8ccff;
    color: #0f2350;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 900;
    margin-bottom: 20px;
}
.serial-suggest-icon {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #2563eb;
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.serial-suggest b { color: #2563eb; }
.serial-field { margin-top: 16px; }
.serial-field label {
    display: block;
    color: #1e293b;
    font-size: 14px;
    font-weight: 900;
    margin-bottom: 8px;
}
.serial-field label em {
    color: #ef4444;
    font-style: normal;
}
.serial-input-wrap {
    height: 58px;
    border: 1px solid #cbd5e1;
    border-radius: 13px;
    overflow: hidden;
    display: flex;
    align-items: center;
    background: #fff;
    transition: .2s ease;
}
.serial-input-wrap:focus-within {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
}
.serial-input-wrap input {
    flex: 1;
    height: 100%;
    border: 0;
    outline: 0;
    text-align: center;
    font-size: 20px;
    font-weight: 900;
    color: #0f172a;
    font-family: monospace;
    padding: 0 14px;
}
.serial-input-wrap input::placeholder { color: #94a3b8; }
.serial-input-wrap span {
    width: 58px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eef5ff;
    color: #2563eb;
    font-size: 22px;
    border-right: 1px solid #e2e8f0;
}
.validity-days-wrap span {
    background: #ecfdf5;
    color: #059669;
}
.serial-field small {
    display: block;
    margin-top: 8px;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
}
.serial-validity-preview {
    margin-top: 22px;
    border: 1px solid #86efac;
    border-radius: 15px;
    background: linear-gradient(135deg, #ecfdf5, #f8fffb);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.serial-validity-preview span,
.serial-validity-preview small {
    display: block;
    color: #475569;
    font-size: 13px;
    font-weight: 800;
}
.serial-validity-preview b {
    display: block;
    color: #059669;
    font-size: 28px;
    font-weight: 1000;
    margin-top: 4px;
    direction: ltr;
}
.serial-validity-days { text-align: left; }
.serial-validity-days strong {
    display: block;
    color: #047857;
    font-size: 15px;
    font-weight: 900;
    margin-bottom: 3px;
}
.serial-final-note {
    margin-top: 18px;
    border-radius: 13px;
    background: #eff6ff;
    color: #2563eb;
    padding: 13px 16px;
    text-align: center;
    font-size: 13px;
    font-weight: 900;
}
.serial-swal-actions { gap: 12px !important; margin-top: 22px !important; }
.serial-confirm-btn {
    background: #059669 !important;
    border-radius: 11px !important;
    padding: 13px 32px !important;
    font-weight: 900 !important;
    box-shadow: 0 12px 22px rgba(5, 150, 105, .24) !important;
}
.serial-cancel-btn {
    background: #f8fafc !important;
    color: #334155 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 11px !important;
    padding: 13px 30px !important;
    font-weight: 900 !important;
}
@media (max-width: 640px) {
    .serial-swal-popup { padding: 22px 18px !important; }
    .serial-swal-title { font-size: 22px !important; }
    .serial-validity-preview { flex-direction: column; align-items: stretch; text-align: center; }
    .serial-validity-days { text-align: center; }
}


/* Compact manual serial modal - admin05 - no horizontal scroll */
.serial-swal-popup {
    width: min(560px, calc(100vw - 20px)) !important;
    max-width: calc(100vw - 20px) !important;
    padding: 16px 20px 18px !important;
    border-radius: 18px !important;
    overflow-x: hidden !important;
}
.serial-swal-popup .swal2-html-container {
    margin: 10px 0 0 !important;
    padding: 0 !important;
    overflow-x: hidden !important;
}
.serial-swal-popup *,
.serial-swal-popup *::before,
.serial-swal-popup *::after { box-sizing: border-box !important; }
.serial-swal-title { font-size: 21px !important; margin: 0 !important; line-height: 1.4 !important; }
.serial-modal-box { width: 100% !important; max-width: 100% !important; overflow-x: hidden !important; }
.serial-doc-icon { width: 50px !important; height: 50px !important; font-size: 22px !important; margin-bottom: 6px !important; }
.serial-doc-icon small { width: 15px !important; height: 15px !important; font-size: 9px !important; bottom: 7px !important; right: 9px !important; }
.serial-desc { font-size: 12.5px !important; line-height: 1.7 !important; margin-bottom: 8px !important; }
.serial-desc b { font-size: 13px !important; word-break: break-word !important; }
.serial-suggest { height: 38px !important; margin-bottom: 10px !important; border-radius: 10px !important; font-size: 12px !important; padding: 0 10px !important; max-width: 100% !important; overflow: hidden !important; }
.serial-suggest-icon { width: 20px !important; height: 20px !important; flex: 0 0 20px !important; }
.serial-fields-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 10px; align-items: start; width: 100%; max-width: 100%; }
.serial-field { margin-top: 0 !important; min-width: 0 !important; }
.serial-field label { font-size: 12.5px !important; margin-bottom: 5px !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.serial-input-wrap { height: 43px !important; border-radius: 10px !important; width: 100% !important; max-width: 100% !important; min-width: 0 !important; }
.serial-input-wrap input { font-size: 16px !important; min-width: 0 !important; width: 100% !important; padding: 0 8px !important; }
.serial-input-wrap span { width: 40px !important; min-width: 40px !important; flex: 0 0 40px !important; font-size: 16px !important; }
.serial-help-line { display:block; margin-top: 7px; color:#64748b; font-size: 10.5px; font-weight: 800; text-align:center; line-height:1.7; }
.serial-validity-preview { margin-top: 10px !important; padding: 10px 12px !important; border-radius: 11px !important; gap: 8px !important; max-width: 100% !important; }
.serial-validity-preview b { font-size: 19px !important; margin-top: 1px !important; word-break: break-word !important; }
.serial-validity-preview span, .serial-validity-preview small { font-size: 11.5px !important; }
.serial-validity-preview small { margin-top: 1px; }
.serial-validity-days { min-width: 105px !important; }
.serial-validity-days strong { font-size: 12px !important; white-space: nowrap !important; }
.serial-final-note { margin-top: 8px !important; padding: 8px 10px !important; font-size: 11.5px !important; line-height: 1.6 !important; }
.serial-swal-actions { margin-top: 12px !important; gap: 8px !important; flex-wrap: wrap !important; }
.serial-confirm-btn, .serial-cancel-btn { padding: 9px 16px !important; border-radius: 9px !important; font-size: 12.5px !important; margin: 0 !important; }
@media (max-width: 520px) {
    .serial-swal-popup { width: calc(100vw - 12px) !important; max-width: calc(100vw - 12px) !important; padding: 14px 10px 16px !important; }
    .serial-swal-title { font-size: 19px !important; }
    .serial-fields-grid { grid-template-columns: 1fr; gap: 8px; }
    .serial-validity-preview { flex-direction: column; align-items: stretch; text-align: center; }
    .serial-validity-days { text-align: center; min-width: 0 !important; }
    .serial-confirm-btn, .serial-cancel-btn { width: 100%; }
}

</style>

@endsection

@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
function copyToClipboard(text, element) {
    navigator.clipboard.writeText(text).then(function() {
        const toast = document.getElementById('copy-toast');
        const originalBg = element.className;
        
        element.className = element.className.replace('bg-slate-5', 'bg-emerald-50').replace('border-slate-20', 'border-emerald-300');
        toast.classList.remove('opacity-0');
        toast.classList.add('opacity-100', 'top-8');
        
        setTimeout(function() {
            toast.classList.remove('opacity-100', 'top-8');
            toast.classList.add('opacity-0', 'top-5');
            element.className = originalBg;
        }, 2000);
    });
}

function filterTable() {
    const input = document.getElementById('tableSearch');
    const filter = input.value.toLowerCase();
    const rows = document.getElementsByClassName('permit-row');

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const targets = row.getElementsByClassName('search-target');
        let found = false;

        const systemId = row.cells[0].innerText.toLowerCase();
        const dCode = row.cells[1].innerText.toLowerCase();
        if (systemId.includes(filter) || dCode.includes(filter)) {
            found = true;
        }

        for (let j = 0; j < targets.length; j++) {
            if (targets[j].innerText.toLowerCase().includes(filter)) {
                found = true;
                break;
            }
        }

        if (found) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    }
}

// 🟢 ثبت دستی سریال دوزوله کشور (حذف اینپوت روز و استفاده از اطلاعات محاسبه شده بک‌اند)
function manualAssignAndPrint(id, dCode, nextSerial, validityDays, expireDateFa, isRenewal = false) {
    const suggestedSerial = (nextSerial && nextSerial !== 'بدون موجودی') ? nextSerial : '';
    const modalTitle = isRenewal ? 'صدور تمدید دوزوله' : 'ثبت سریال دوزوله کشور';
    const serialLabel = isRenewal ? 'شماره دوزوله قبلی' : 'شماره سریال کشور';
    const serialHelp = isRenewal ? 'در تمدید، شماره جدید از انبار مصرف نمی‌شود و همان شماره قبلی تمدید می‌گردد.' : 'سریال باید در انبار خام همان کشور موجود باشد.';
    const confirmText = isRenewal ? 'صدور تمدید و چاپ پروانه' : 'ثبت سریال و چاپ پروانه';
    const readOnlyAttr = isRenewal ? 'readonly' : '';
    const inputValue = isRenewal ? suggestedSerial : suggestedSerial;

    Swal.fire({
        title: modalTitle,
        html: `
            <div class="serial-modal-box" dir="rtl">
                <div class="serial-doc-icon">
                    <span>📄</span>
                    <small>✓</small>
                </div>

                <p class="serial-desc">
                    ${isRenewal ? 'این پرونده تمدیدی است. همان شماره دوزوله قبلی برای پرونده' : 'شماره سریال درج‌شده روی برگه فیزیکی کشور را برای پرونده'}
                    <b>${dCode || '---'}</b>
                    وارد کنید.
                </p>

                <div class="serial-suggest">
                    <span class="serial-suggest-icon">ℹ️</span>
                    <span>${isRenewal ? 'شماره قبلی:' : 'پیشنهاد انبار:'}</span>
                    <b>${nextSerial || 'بدون موجودی'}</b>
                </div>

                <div class="serial-fields-grid" style="grid-template-columns: 1fr;">
                    <div class="serial-field">
                        <label for="manual-serial-input">${serialLabel} <em>*</em></label>
                        <div class="serial-input-wrap">
                            <input id="manual-serial-input" type="text" inputmode="numeric" dir="ltr" value="${inputValue}" ${readOnlyAttr} placeholder="123456">
                            <span>▦</span>
                        </div>
                    </div>
                </div>

                <small class="serial-help-line">${serialHelp}</small>

                <div class="serial-validity-preview" style="flex-direction: row; justify-content: space-between; align-items: center; background: #ecfdf5; border-color: #34d399;">
                    <div class="serial-validity-days" style="text-align: right;">
                        <span style="color: #059669; font-weight: bold; font-size: 11px;">اعتبار پروانه</span>
                        <strong style="color: #047857; font-size: 16px; margin-top: 4px;">( ${validityDays} روز )</strong>
                    </div>
                    <div style="width: 1px; height: 35px; background: #6ee7b7; opacity: 0.6;"></div>
                    <div style="text-align: left;">
                        <span style="color: #059669; font-weight: bold; font-size: 11px;">تاریخ پایان سیستمی</span>
                        <b style="color: #064e3b; font-size: 20px; font-family: monospace; letter-spacing: 1px;">${expireDateFa}</b>
                    </div>
                </div>

                <div class="serial-final-note">🛡️ پس از ثبت، ویرایش تاریخ و سریال امکان‌پذیر نیست.</div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'انصراف',
        reverseButtons: true,
        focusConfirm: false,
        customClass: {
            popup: 'serial-swal-popup',
            title: 'serial-swal-title',
            confirmButton: 'serial-confirm-btn',
            cancelButton: 'serial-cancel-btn',
            actions: 'serial-swal-actions'
        },
        didOpen: () => {
            const serialInput = document.getElementById('manual-serial-input');
            if (serialInput) serialInput.focus();
        },
        preConfirm: () => {
            const serial = document.getElementById('manual-serial-input').value.trim();

            if (!serial) {
                Swal.showValidationMessage(isRenewal ? 'شماره دوزوله قبلی در پرونده تمدید ثبت نشده است.' : 'شماره سریال کشور را وارد کنید.');
                return false;
            }

            if (!isRenewal && !/^\d+$/.test(serial)) {
                Swal.showValidationMessage('شماره سریال فقط باید عدد باشد.');
                return false;
            }

            return {
                serial_number: serial,
                // ارسال متغیر پنهان به بک‌اند جهت سازگاری ۱۰۰٪ با متد قبلی
                validity_days: validityDays
            };
        }
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'در حال ثبت...',
            text: 'لطفاً چند لحظه صبر کنید.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        fetch('{{ url("/web/association/request/serial") }}/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(result.value)
        })
        .then(async response => {
            const data = await response.json().catch(() => null);
            if (!response.ok || !data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'خطا در ثبت اطلاعات.');
            }
            return data;
        })
        .then(data => {
            Swal.fire({
                title: 'پروانه صادر شد',
                text: 'سریال ' + data.serial + ' ثبت شد. تاریخ پایان اعتبار: ' + data.valid_until,
                icon: 'success',
                timer: 1600,
                showConfirmButton: false
            });

            const row = document.getElementById('req-row-' + id);
            if (row) row.remove();

            // آپدیت تعداد در هدر
            const countEl = document.getElementById('request-count');
            if(countEl) {
                let count = parseInt(countEl.innerText);
                if(count > 0) countEl.innerText = count - 1;
            }

            setTimeout(() => {
                const printWindow = window.open('/web/association/request/print/' + id, '_blank');
                if (printWindow) printWindow.focus();
            }, 300);
        })
        .catch(error => {
            Swal.fire({
                title: 'خطا در ثبت سریال',
                text: error.message || 'خطا در ارتباط با سرور.',
                icon: 'error',
                confirmButtonText: 'تایید'
            });
        });
    });
}

</script>
@endsection