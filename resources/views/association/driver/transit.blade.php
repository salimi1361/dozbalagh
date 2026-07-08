@extends('layouts.admin')

@section('header_title', 'ظ…ط¯غŒط±غŒطھ طھط±ط¯ط¯ ط¯ظˆط²ظˆظ„ظ‡')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-2xl p-6 shadow-xl border border-slate-800">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-500/10 text-amber-500 rounded-xl border border-amber-500/20 text-2xl">
                ًںڑڑ
            </div>
            <div>
                <h2 class="text-lg font-black tracking-wide">ع©ط§ط±طھط§ط¨ظ„ ظ†ط§ظˆع¯ط§ظ† ط¯ط± ط­ط§ظ„ طھط±ط¯ط¯</h2>
                <p class="text-slate-400 text-xs mt-1">ظ„غŒط³طھ ظ†ط§ظˆع¯ط§ظ†غŒ ع©ظ‡ ط¯ط± ظ…ط³غŒط± طھط±ط§ظ†ط²غŒطھ ظ‡ط³طھظ†ط¯. طھط­ظˆغŒظ„ ظ„ط§ط´ظ‡ ط§ط² ط§غŒظ† ط¨ط®ط´ ط­ط°ظپ ط´ط¯ظ‡ ظˆ ط¯ط± ظ¾ظ†ظ„ ط´ط±ع©طھ ط§ظ†ط¬ط§ظ… ظ…غŒâ€Œط´ظˆط¯.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-sm text-slate-700 flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                ظ¾ط±ظˆط§ظ†ظ‡â€Œظ‡ط§غŒ ط¯ط± ط­ط§ظ„ طھط±ط¯ط¯ ظپط¹ط§ظ„ (<span id="request-count">{{ $requests->total() }}</span>)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-100 text-slate-600 text-xs font-bold whitespace-nowrap">
                        <th class="p-4">ط³ط±غŒط§ظ„ ظ¾ط±ظˆط§ظ†ظ‡</th>
                        <th class="p-4">ع©ط¯ ظ¾ط±ظˆظ†ط¯ظ‡</th>
                        <th class="p-4">ط±ط§ظ†ظ†ط¯ظ‡ ظ…طھظ‚ط§ط¶غŒ</th>
                        <th class="p-4 text-center">ظ†ط§ظˆع¯ط§ظ† / ظ¾ظ„ط§ع©</th>
                        <th class="p-4">ع©ط´ظˆط± ظ…ظ‚طµط¯</th>
                        <th class="p-4 text-center">ط§ظ‚ط¯ط§ظ…ط§طھ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 text-sm text-slate-700">
                    @forelse($requests as $index => $req)
                        <tr id="req-row-{{ $req->id }}" class="transition-all duration-200 {{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} hover:bg-indigo-50/30">
                            
                            <td class="p-4 font-mono font-black text-indigo-600 text-sm">ًںژ« {{ $req->serial_number }}</td>
                            <td class="p-4 font-mono text-xs font-bold text-slate-500">{{ $req->d_code }}</td>
                            
                            <td class="p-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-900">
                                        {{ $req->driver ? trim(($req->driver->first_name_fa ?? '') . ' ' . ($req->driver->last_name_fa ?? '')) : 'ظ†ط§ظ…ط´ط®طµ' }}
                                    </span>
                                    <span class="text-slate-400 text-[11px]">ع©ط¯ ظ…ظ„غŒ: {{ $req->driver->national_code ?? '---' }}</span>
                                </div>
                            </td>

                            <td class="p-4 text-center font-mono font-black text-xs text-slate-800">
                                {{ optional($req->fleet)->transit_plate ?? '---' }}
                            </td>

                            <td class="p-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    {{ $req->country_name }}
                                </span>
                            </td>

                            <td class="p-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button onclick="window.open('/web/association/request/print/{{ $req->id }}', '_blank')" class="px-2 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-all" title="ط¨ط§ط² ع©ط±ط¯ظ† ظ…ط¬ط¯ط¯ طµظپط­ظ‡ ع†ط§ظ¾ ط±ط³ظ…غŒ">
                                        ًں–¨ï¸ڈ ع†ط§ظ¾
                                    </button>

                                    @if(!empty($req->company_return_image))
                                        <button onclick='receiveLash(@json($req->id), @json($req->serial_number), @json($req->courier_name ?? ""), @json($req->courier_mobile ?? ""))' class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-black transition-all">
                                            تحویل لاشه
                                        </button>
                                    @else
                                        <span class="px-2.5 py-1.5 bg-slate-100 text-slate-400 rounded-lg text-xs font-black border border-slate-200">
                                            در انتظار لاشه شرکت
                                        </span>
                                    @endif

                                    <button onclick="processTransit({{ $req->id }}, 'lost', '{{ $req->serial_number }}')" class="px-2.5 py-1.5 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg text-xs font-black transition-all">
                                        âڑ ï¸ڈ ظ…ظپظ‚ظˆط¯غŒ
                                    </button>

                                    <button onclick="openExtensionModal({{ $req->id }}, '{{ $req->d_code }}')" class="px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-lg text-xs font-black transition-all shadow-sm">
                                        ًں”„ طھظ…ط¯غŒط¯ ظ…ط³غŒط±
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400 bg-white">
                                <div class="text-4xl mb-3">ًںڑڑ</div>
                                <p class="font-bold text-sm">ط¯ط± ط­ط§ظ„ ط­ط§ط¶ط± ظ‡غŒع† ظ†ط§ظˆع¯ط§ظ†غŒ ط¯ط± ط­ط§ظ„ طھط±ط¯ط¯ ط¨ط§ ظ¾ط±ظˆط§ظ†ظ‡â€Œظ‡ط§غŒ ظپط¹ط§ظ„ ظ†غŒط³طھ.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
function processTransit(id, status, serial) {
    if (status === 'lost') {
        // ط±ظˆط§ظ„ ظ…ظپظ‚ظˆط¯غŒ ط³ط§ط¯ظ‡ ط¨ط¯ظˆظ† ظ†غŒط§ط² ط¨ظ‡ ط¹ع©ط³
        Swal.fire({
            title: 'ط«ط¨طھ ط§ط¹ظ„ط§ظ… ظ…ظپظ‚ظˆط¯غŒ ظ¾ط±ظˆط§ظ†ظ‡',
            text: `ط¯ط± طµظˆط±طھ ط«ط¨طھ ظ…ظپظ‚ظˆط¯غŒ ط³ط±غŒط§ظ„ ${serial}طŒ ط±ط§ظ†ظ†ط¯ظ‡ ط¢ط²ط§ط¯ ط´ط¯ظ‡ ط§ظ…ط§ ط³ظˆط§ط¨ظ‚ ط¬ط±غŒظ…ظ‡ ظ…ظپظ‚ظˆط¯غŒ ط¯ط± ظ¾ط±ظˆظ†ط¯ظ‡ ط´ط±ع©طھ ط¯ط±ط¬ ط®ظˆط§ظ‡ط¯ ط´ط¯. طھط§غŒغŒط¯ ظ…غŒâ€Œع©ظ†غŒط¯طں`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'طھط§غŒغŒط¯ ظˆ ط«ط¨طھ ظ…ظپظ‚ظˆط¯غŒ',
            cancelButtonText: 'ط§ظ†طµط±ط§ظپ'
        }).then((result) => {
            if (result.isConfirmed) {
                executeSettleRequest(id, status, null);
            }
        });
        return;
    }

    return;
}

function receiveLash(id, serial, courierName, courierMobile) {
    Swal.fire({
        title: 'تحویل لاشه دوزوله',
        html: `
            <div class="text-right space-y-3" dir="rtl">
                <p class="text-xs text-slate-500 font-bold">سریال ${serial}</p>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-600 font-bold">
                    <div>پیک: ${courierName || 'ثبت نشده'}</div>
                    <div>موبایل: <span dir="ltr">${courierMobile || '---'}</span></div>
                </div>
                <input id="courier_code" class="w-full text-center font-mono text-lg border border-slate-300 rounded-xl p-3 tracking-widest" maxlength="6" placeholder="کد ۶ رقمی پیک">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'تایید تحویل لاشه',
        cancelButtonText: 'انصراف',
        confirmButtonColor: '#059669',
        preConfirm: () => {
            const code = document.getElementById('courier_code').value.trim();
            if (!code) {
                Swal.showValidationMessage('کد تحویل پیک را وارد کنید.');
                return false;
            }
            return code;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            executeSettleRequest(id, 'collected', null, result.value);
        }
    });
}

// ط§ط±ط³ط§ظ„ ظ†ظ‡ط§غŒغŒ ط¯ط±ط®ظˆط§ط³طھ طھط¹غŒغŒظ† طھع©ظ„غŒظپ طھط±ط¯ط¯
function executeSettleRequest(id, status, fileBlob, courierCode = null) {
    const formData = new FormData();
    formData.append('status', status);
    if (courierCode) {
        formData.append('courier_code', courierCode);
    }
    
    if (fileBlob) {
        formData.append('image', fileBlob);
    }

    // ط¨ط§ط² ع©ط±ط¯ظ† ظ„ظˆط¯غŒظ†ع¯ ط¯ط± ط­غŒظ† ط¢ظ¾ظ„ظˆط¯
    Swal.fire({ title: 'ط¯ط± ط­ط§ظ„ ط«ط¨طھ ظˆط¶ط¹غŒطھ طھط±ط¯ط¯...', didOpen: () => { Swal.showLoading(); } });

    fetch(`/web/association/request/process/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ title: 'ظ…ظˆظپظ‚غŒطھâ€Œط¢ظ…غŒط²', text: data.message, icon: 'success', timer: 2000, showConfirmButton: false });
            document.getElementById(`req-row-${id}`).remove();
        } else {
            Swal.fire({ title: 'ط®ط·ط§', text: data.message, icon: 'error', confirmButtonText: 'طھط§غŒغŒط¯' });
        }
    })
    .catch(err => {
        Swal.fire({ title: 'ط®ط·ط§', text: 'ط®ط·ط§ ط¯ط± ط§ط±طھط¨ط§ط· ط¨ط§ ط³ط±ظˆط± ظ…ط±ع©ط²غŒ.', icon: 'error', confirmButtonText: 'طھط§غŒغŒط¯' });
    });
}
</script>
@endsection
