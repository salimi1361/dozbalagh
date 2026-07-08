@extends('layouts.app')

@section('header_title', 'ط¯ط§ط´ط¨ظˆط±ط¯ ط§طµظ„غŒ ط³غŒط³طھظ…')

@section('content')
<div class="space-y-6" dir="rtl">
    
    {{-- ظ‡ط¯ط± ط®ظˆط´â€Œط¢ظ…ط¯ظ‡ط§غŒ ط³غŒط³طھظ… ظˆ ظˆط¶ط¹غŒطھ طھط§غŒغŒط¯ ط´ط±ع©طھ --}}
    <div class="bg-white rounded-2xl border-2 border-slate-100 shadow-sm p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-slate-100 rounded-2xl text-2xl shadow-inner">ًںڈ¢</div>
                <div>
                    <h2 class="text-xl font-black text-slate-800 tracking-tight">ط®ظˆط´ ط¢ظ…ط¯غŒط¯طŒ {{ $company->ceo_name ?? 'ظ…ط¯غŒط± ظ…ط­طھط±ظ… ط´ط±ع©طھ' }} ط¹ط²غŒط²</h2>
                    <p class="text-slate-600 text-sm font-bold mt-2">ظ†ط§ظ… ط´ط±ع©طھ: <span class="text-slate-900 font-black">{{ $company->name_fa ?? $company->name }}</span> | ع©ط¯ ط§ط®طھطµط§طµغŒ: <span class="font-mono text-slate-900 font-black">{{ $company->company_code ?? '---' }}</span></p>
                    <p class="text-slate-500 text-xs font-bold mt-1.5">ًں“چ ط¢ط¯ط±ط³ ط¯ظپطھط± ظ…ط±ع©ط²غŒ: {{ $company->address_fa ?? 'ط«ط¨طھ ظ†ط´ط¯ظ‡' }}</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-3 w-full sm:w-auto">
                <div>
                    @if($company->status == 'approved')
                        <div class="bg-emerald-50 text-emerald-800 px-5 py-2.5 rounded-xl text-sm font-black border-2 border-emerald-200/80 shadow-sm flex items-center gap-2">
                            <span class="text-lg">âœ…</span> ط­ط³ط§ط¨ ع©ط§ط±ط¨ط±غŒ ظپط¹ط§ظ„ ظˆ طھط§غŒغŒط¯ ط´ط¯ظ‡
                        </div>
                    @else
                        <div class="bg-amber-50 text-amber-800 px-5 py-2.5 rounded-xl text-sm font-black border-2 border-amber-200/80 shadow-sm flex items-center gap-2">
                            <span class="text-lg">âڈ³</span> ط¯ط± ط§ظ†طھط¸ط§ط± طھط§غŒغŒط¯ ظ…ط¯ط§ط±ع© ظ…ط¯غŒط±غŒطھ
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ط±ط¯غŒظپ ط§ظˆظ„: ع©ط§ط±طھâ€Œظ‡ط§غŒ ط¢ظ…ط§ط±غŒ ط´ط§ط®طµâ€Œظ‡ط§غŒ ط§طµظ„غŒ --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        
        {{-- ع©ط§ط±طھ ظ…ط§ظ„غŒ / ع©غŒظپ ظ¾ظˆظ„ --}}
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 p-6 rounded-2xl text-white shadow-md flex flex-col justify-between relative overflow-hidden">
            <div>
                <div class="flex justify-between items-center mb-3">
                    <span class="text-xs font-black text-slate-400 tracking-wider">ظ…ظˆط¬ظˆط¯غŒ ع©غŒظپ ظ¾ظˆظ„</span>
                    <span class="bg-emerald-500/20 text-emerald-400 px-2.5 py-0.5 rounded-md text-[10px] font-black">طھظˆظ…ط§ظ†</span>
                </div>
                <div class="text-2xl font-black font-mono tracking-wide text-emerald-400">
                    {{ number_format($walletBalance) }}
                </div>
            </div>
            <div class="mt-6 pt-3 border-t border-white/10 flex justify-between items-center text-xs font-bold">
                <span class="text-slate-400">ط¨ظ„ظˆع©ظ‡: <strong class="text-amber-400 font-mono font-black">{{ number_format($blockedBalance) }}</strong></span>
                <a href="{{ route('company.wallet.index') }}" class="text-sky-400 hover:text-sky-300 font-black transition">
                    ط´ط§ط±عک ط­ط³ط§ط¨ â†گ
                </a>
            </div>
        </div>

        {{-- ع©ط§ط±طھ ط±ط§ظ†ظ†ط¯ع¯ط§ظ† --}}
        <div class="bg-white p-6 rounded-2xl border-2 border-slate-100 shadow-sm flex items-center justify-between hover:border-sky-500/30 transition-all">
            <div class="space-y-2">
                <span class="text-xs font-black text-slate-500 block">ط±ط§ظ†ظ†ط¯ع¯ط§ظ† ظپط¹ط§ظ„ ط¯ط± ع©ط§ط±طھط§ط¨ظ„</span>
                <span class="text-3xl font-black text-slate-800 block font-mono tracking-tight">{{ $driversCount }} <span class="text-xs font-bold text-slate-400 mr-1">ظ†ظپط±</span></span>
                <span class="text-[10px] text-sky-600 font-black bg-sky-50 px-2 py-0.5 rounded border border-sky-100 block w-max">ط³ط±ظ…ط§غŒظ‡ ط§ظ†ط³ط§ظ†غŒ ظپط¹ط§ظ„</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                ًں‘¤
            </div>
        </div>

        {{-- ع©ط§ط±طھ ظ†ط§ظˆع¯ط§ظ† ظ…ظ„ع©غŒ --}}
        <div class="bg-white p-6 rounded-2xl border-2 border-slate-100 shadow-sm flex items-center justify-between hover:border-amber-500/30 transition-all">
            <div class="space-y-2">
                <span class="text-xs font-black text-slate-500 block">ظ†ط§ظˆع¯ط§ظ† ظ…ظ„ع©غŒ ظپط¹ط§ظ„</span>
                <span class="text-3xl font-black text-slate-800 block font-mono tracking-tight">{{ $fleetsCount }} <span class="text-xs font-bold text-slate-400 mr-1">ط¯ط³طھع¯ط§ظ‡</span></span>
                <span class="text-[10px] text-amber-600 font-black bg-amber-50 px-2 py-0.5 rounded border border-amber-100 block w-max">ع©ط¯ظ‡ط§غŒ ظ‡ظˆط´ظ…ظ†ط¯ ط«ط¨طھâ€Œط´ط¯ظ‡</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                ًںڑ›
            </div>
        </div>

        {{-- ظ†ط±ط® ظ…ظˆظپظ‚غŒطھ ط¹ظ…ظ„ع©ط±ط¯ ط´ط±ع©طھ --}}
        <div class="bg-white p-6 rounded-2xl border-2 border-slate-100 shadow-sm flex flex-col justify-between">
            <div class="flex justify-between items-center">
                <span class="text-xs font-black text-slate-500">ظ†ط±ط® ظ…ظˆط§ظپظ‚طھ ظ¾ط±ظˆط§ظ†ظ‡â€Œظ‡ط§</span>
                <span class="text-base font-black text-emerald-600 font-mono">{{ $successRate }}%</span>
            </div>
            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden my-3 shadow-inner">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all" style="width: {{ $successRate }}%"></div>
            </div>
            <span class="text-[10px] text-slate-400 font-bold">ظ†ط³ط¨طھ طµط§ط¯ط± ط´ط¯ظ‡ ط¨ظ‡ ع©ظ„ ط¯ط±ط®ظˆط§ط³طھâ€Œظ‡ط§غŒ ط¬ط§ط±غŒ</span>
        </div>

    </div>

    {{-- ط±ط¯غŒظپ ط¯ظˆظ…: ظˆط¶ط¹غŒطھâ€Œظ‡ط§غŒ طھظپع©غŒع© ط´ط¯ظ‡ ط¯ظˆط²ظˆظ„ظ‡ ط¨ط§ ط­ط°ظپ ع¯ط²غŒظ†ظ‡ ظ†غŒط§ط² ط¨ظ‡ ط§طµظ„ط§ط­ --}}
    <div class="space-y-3">
        <h3 class="text-xs font-black text-slate-700 tracking-wide flex items-center gap-1.5">
            <span class="w-2 h-2 bg-slate-800 rounded-full"></span> ظ…ط§ظ†غŒطھظˆط±غŒظ†ع¯ ط¢ظ†ظ„ط§غŒظ† ظˆط¶ط¹غŒطھ ظ¾ط±ظˆط§ظ†ظ‡â€Œظ‡ط§
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-4">
            @php
                $stats = [
                    ['ط¯ط± ط§ظ†طھط¸ط§ط± ط¨ط±ط±ط³غŒ', $permitStats->pending_count ?? 0, 'text-amber-700', 'bg-amber-50/60 border-amber-200/80'],
                    ['طµط§ط¯ط± ط´ط¯ظ‡ / ظ…ط¹طھط¨ط±', $permitStats->issued_count ?? 0, 'text-sky-700', 'bg-sky-50/60 border-sky-200/80'],
                    ['ط±ط¯ ط¯ط±ط®ظˆط§ط³طھ', $permitStats->rejected_count ?? 0, 'text-rose-700', 'bg-rose-50/60 border-rose-200/80'],
                    ['نیاز به اصلاح', $permitStats->returned_count ?? 0, 'text-orange-700', 'bg-orange-50/60 border-orange-200/80'],
                    ['ًںڑڑ ظ„ط§ط´ظ‡ طھط­ظˆغŒظ„ ط´ط¯ظ‡', $permitStats->collected_count ?? 0, 'text-indigo-700', 'bg-indigo-50/60 border-indigo-200/80'],
                    ['ًں”„ ط¯ط±ط®ظˆط§ط³طھ طھظ…ط¯غŒط¯', $permitStats->renewal_count ?? 0, 'text-emerald-700', 'bg-emerald-50/60 border-emerald-200/80'],
                    ['âڑ ï¸ڈ ظ…ظپظ‚ظˆط¯غŒ', $permitStats->lost_count ?? 0, 'text-slate-700', 'bg-slate-50 border-slate-300'],
                ];
            @endphp

            @foreach($stats as $item)
            <div class="bg-white rounded-xl shadow-sm p-4 text-center hover:shadow transition-all {{ $item[3] }} border-2">
                <span class="text-[11px] font-black block mb-2">{{ $item[0] }}</span>
                <span class="text-2xl font-black {{ $item[2] }} font-mono">{{ $item[1] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ط±ط¯غŒظپ ط³ظˆظ…: ط¬ط¯ظˆظ„ طھظپطµغŒظ„غŒ ظپط±ط¢غŒظ†ط¯ظ‡ط§غŒ ط§ط®غŒط± ع©ط§ظ…ظ„ط§ظ‹ ط¹ط±غŒط¶ (Full Width) --}}
    <div class="bg-white rounded-2xl border-2 border-slate-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b-2 border-slate-100 flex justify-between items-center bg-slate-50/60">
            <h3 class="font-black text-slate-800 text-sm flex items-center gap-2">ًں“‌ ط¢ط®ط±غŒظ† ط¯ط±ط®ظˆط§ط³طھâ€Œظ‡ط§غŒ ط¯ظˆط²ظˆظ„ظ‡ طµط§ط¯ط± ط´ط¯ظ‡ ظˆ ظ…ط¹ظ„ظ‚</h3>
            <a href="{{ route('dozbalagh.index') }}" class="text-xs text-sky-600 font-black hover:underline bg-white border border-slate-200 px-3 py-1.5 rounded-xl shadow-sm transition">
                ظ…ط´ط§ظ‡ط¯ظ‡ ع©ط§ط±طھط§ط¨ظ„ ط¬ط§ظ…ط¹ ظ¾ط±ظˆط§ظ†ظ‡â€Œظ‡ط§ â†گ
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/60 text-slate-600 font-black border-b border-slate-200 text-[11px]">
                        <th class="p-4">ع©ط¯ ظ¾ط±ظˆظ†ط¯ظ‡</th>
                        <th class="p-4">ط±ط§ظ†ظ†ط¯ظ‡ ظ…طھظ‚ط§ط¶غŒ</th>
                        <th class="p-4">ظ†ط§ظˆع¯ط§ظ† ظ…ظ„ظƒغŒ (ظ¾ظ„ط§ع© طھط±ط§ظ†ط²غŒطھ)</th>
                        <th class="p-4">ظ‡ط²غŒظ†ظ‡ ع©ظ„ ظ¾ط±ظˆط§ظ†ظ‡</th>
                        <th class="p-4">طھط§ط±غŒط® ط«ط¨طھ ظˆ ظپط±ط³طھط´</th>
                        <th class="p-4 text-center">ظ†ظˆط¹ / ظˆط¶ط¹غŒطھ ع†ط±ط®ظ‡ ط¹ظ…ط±</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-bold text-slate-700 text-sm">
                    @forelse($recentRequests->take(10) as $req)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-4 font-black font-mono text-slate-900 text-xs tracking-wider">{{ $req->d_code }}</td>
                            <td class="p-4 text-slate-800">{{ trim($req->first_name_fa . ' ' . $req->last_name_fa) ?: 'ظ†ط§ظ…ط´ط®طµ' }}</td>
                            <td class="p-4">
                                @php
                                    $plateRaw = $req->transit_plate ?? $req->plate_number ?? null;
                                    $plateParts = $plateRaw ? preg_split('/[-\s]+/', trim($plateRaw)) : [];

                                    $plateLeft   = $plateParts[0] ?? '';
                                    $plateLetter = $plateParts[1] ?? '';
                                    $plateMid    = $plateParts[2] ?? '';
                                    $plateIran   = $plateParts[3] ?? '';

                                    if ($plateRaw && count($plateParts) < 4) {
                                        $plateLeft = '';
                                        $plateLetter = '';
                                        $plateMid = $plateRaw;
                                        $plateIran = '';
                                    }
                                @endphp

                                @if($plateRaw)
                                    <div class="inline-flex items-stretch rounded border border-slate-900 bg-white overflow-hidden shadow-sm w-[110px] h-[26px] align-middle" dir="ltr">
                                        <div class="w-[14px] bg-blue-800 text-white flex flex-col items-center justify-between py-[1px] shrink-0">
                                            <span class="text-[4px] font-black leading-none mt-[1px]">I.R.</span>
                                            <span class="w-[8px] h-[4px] rounded-[1px] overflow-hidden border border-white/20">
                                                <span class="block h-[1px] bg-green-500"></span>
                                                <span class="block h-[1px] bg-white"></span>
                                                <span class="block h-[1px] bg-red-500"></span>
                                            </span>
                                            <span class="text-[4px] font-black leading-none mb-[1px]">IRAN</span>
                                        </div>

                                        <div class="flex flex-1 items-center bg-gradient-to-b from-white to-slate-100 text-slate-950">
                                            <div class="w-[24px] h-full flex items-center justify-center border-r border-slate-300">
                                                <span class="font-black text-[11px] leading-none font-mono">{{ $plateLeft ?: '---' }}</span>
                                            </div>

                                            <div class="w-[18px] h-full flex items-center justify-center border-r border-slate-300">
                                                <span class="font-black text-[11px] leading-none">{{ $plateLetter ?: '-' }}</span>
                                            </div>

                                            <div class="w-[32px] h-full flex items-center justify-center border-r border-slate-300">
                                                <span class="font-black text-[11px] leading-none font-mono">{{ $plateMid ?: '---' }}</span>
                                            </div>

                                            <div class="w-[22px] h-full flex flex-col items-center justify-center bg-white">
                                                <span class="text-[5px] font-black text-slate-500 leading-none mb-[2px]">ط§غŒط±ط§ظ†</span>
                                                <span class="font-black text-[10px] leading-none font-mono">{{ $plateIran ?: '--' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-slate-100 text-slate-400 border border-slate-200 font-black text-[10px]">ط¨ط¯ظˆظ† ظ¾ظ„ط§ع©</span>
                                @endif
                            </td>
                            <td class="p-4 font-mono font-black text-slate-900 text-xs">{{ number_format($req->total_amount) }} <span class="text-[10px] text-slate-400 font-bold mr-0.5">طھظˆظ…ط§ظ†</span></td>
                            <td class="p-4 text-slate-500 font-mono font-medium text-xs">{{ $req->jalali_date }}</td>
                            
                            <td class="p-4 text-center">
                                @php
                                    $requestType = $req->request_type ?? 'new';
                                    $typeText = $requestType === 'renewal' ? 'ًں”„ طھظ…ط¯غŒط¯' : 'ًں†• ط¬ط¯غŒط¯';
                                    $typeClass = $requestType === 'renewal' ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-slate-50 text-slate-700 border-slate-200';
                                    $statusMap = [
                                        'pending' => ['âڈ³ ط¯ط± ط§ظ†طھط¸ط§ط± ط¨ط±ط±ط³غŒ', 'bg-amber-50 text-amber-800 border-amber-200'],
                                        'approved' => ['âœ… ط¢ظ…ط§ط¯ظ‡ طµط¯ظˆط±', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                                        'issued' => ['ًں“„ طµط§ط¯ط± ط´ط¯ظ‡ / ظ…ط¹طھط¨ط±', 'bg-sky-50 text-sky-800 border-sky-200'],
                                        'collected' => ['ًںڑڑ ظ„ط§ط´ظ‡ طھط­ظˆغŒظ„ ط´ط¯', 'bg-slate-100 text-slate-700 border-slate-200'],
                                        'archived' => ['ًں—‚ï¸ڈ ط¨ط§غŒع¯ط§ظ†غŒ ط´ط¯', 'bg-slate-100 text-slate-700 border-slate-200'],
                                        'rejected' => ['â‌Œ ط±ط¯ ط¯ط±ط®ظˆط§ط³طھ', 'bg-rose-50 text-rose-800 border-rose-200'],
                                        'returned' => ['نیاز به اصلاح', 'bg-orange-50 text-orange-800 border-orange-200'],
                                        'lost' => ['âڑ ï¸ڈ ظ…ظپظ‚ظˆط¯غŒ', 'bg-zinc-100 text-zinc-700 border-zinc-200'],
                                    ];
                                    [$statusText, $statusClass] = $statusMap[$req->status] ?? [$req->status, 'bg-slate-100 text-slate-800 border-slate-200'];
                                    $associationNote = $req->reject_reason ?: null;
                                @endphp
                                <div class="flex flex-col items-center gap-1.5">
                                    <span class="px-3 py-1 rounded-lg border font-black text-[10px] {{ $typeClass }}">{{ $typeText }}</span>
                                    <span class="px-3 py-1 rounded-lg border font-black text-[10px] {{ $statusClass }}">{{ $statusText }}</span>
                                    @if(in_array($req->status, ['rejected', 'returned']) && $associationNote)
                                        <span class="max-w-[180px] text-[10px] leading-5 text-rose-700 bg-rose-50 border border-rose-100 rounded-lg px-2 py-1">
                                            {{ $associationNote }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-slate-400 font-black">ظ‡غŒع† ط¯ط±ط®ظˆط§ط³طھغŒ طھط§ ط§غŒظ† ظ„ط­ط¸ظ‡ ط¨ط±ط§غŒ ط§غŒظ† ط´ط±ع©طھ ط¯ط± ط¯غŒطھط§ط¨غŒط³ غŒط§ظپطھ ظ†ط´ط¯.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
