@extends('layouts.admin')

@section('header_title', 'مدیریت تردد دوزوله')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-2xl p-6 shadow-xl border border-slate-800">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-500/10 text-amber-500 rounded-xl border border-amber-500/20 text-2xl">تردد</div>
            <div>
                <h2 class="text-lg font-black tracking-wide">کارتابل ناوگان در حال تردد</h2>
                <p class="text-slate-400 text-xs mt-1">لیست ناوگانی که در مسیر ترانزیت هستند. تحویل لاشه از این بخش حذف شده و در پنل شرکت انجام می‌شود.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-sm text-slate-700 flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                پروانه‌های در حال تردد فعال (<span id="request-count">{{ $requests->total() }}</span>)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-100 text-slate-600 text-xs font-bold whitespace-nowrap">
                        <th class="p-4">سریال پروانه</th>
                        <th class="p-4">کد پرونده</th>
                        <th class="p-4">راننده متقاضی</th>
                        <th class="p-4 text-center">ناوگان / پلاک</th>
                        <th class="p-4">کشور مقصد</th>
                        <th class="p-4 text-center">اقدامات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 text-sm text-slate-700">
                    @forelse($requests as $index => $req)
                        <tr id="req-row-{{ $req->id }}" class="transition-all duration-200 {{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} hover:bg-indigo-50/30">
                            
                            <td class="p-4 font-mono font-black text-indigo-600 text-sm">شماره {{ $req->serial_number }}</td>
                            <td class="p-4 font-mono text-xs font-bold text-slate-500">{{ $req->d_code }}</td>
                            
                            <td class="p-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-900">
                                        {{ $req->driver ? trim(($req->driver->first_name_fa ?? '') . ' ' . ($req->driver->last_name_fa ?? '')) : 'نامشخص' }}
                                    </span>
                                    <span class="text-slate-400 text-[11px]">کد ملی: {{ $req->driver->national_code ?? '---' }}</span>
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
                                    <button onclick="window.open('/web/association/request/print/{{ $req->id }}', '_blank')" class="px-2 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-all" title="باز کردن صفحه چاپ رسمی">
                                        چاپ
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

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400 bg-white">
                                <div class="text-4xl mb-3"></div>
                                <p class="font-bold text-sm">در حال حاضر هیچ ناوگانی در حال تردد با پروانه‌های فعال نیست.</p>
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
            executeSettleRequest(id, 'archived', null, result.value);
        }
    });
}

// ارسال نهایی درخواست تعیین تکلیف تردد
function executeSettleRequest(id, status, fileBlob, courierCode = null) {
    const formData = new FormData();
    formData.append('status', status);
    if (courierCode) {
        formData.append('courier_code', courierCode);
    }
    
    if (fileBlob) {
        formData.append('image', fileBlob);
    }

    // باز کردن لودینگ در حین آپلود
    Swal.fire({ title: 'در حال ثبت وضعیت تردد...', didOpen: () => { Swal.showLoading(); } });

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
            Swal.fire({ title: 'موفقیت‌آمیز', text: data.message, icon: 'success', timer: 2000, showConfirmButton: false });
            document.getElementById(`req-row-${id}`).remove();
        } else {
            Swal.fire({ title: 'خطا', text: data.message, icon: 'error', confirmButtonText: 'تایید' });
        }
    })
    .catch(err => {
        Swal.fire({ title: 'خطا', text: 'خطا در ارتباط با سرور مرکزی.', icon: 'error', confirmButtonText: 'تایید' });
    });
}
</script>
@endsection
