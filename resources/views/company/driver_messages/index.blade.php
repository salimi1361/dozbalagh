@extends('layouts.app')

@section('header_title')
    پیام رانندگان / <span class="text-blue-600 font-black">مرکز ارتباط شرکت</span>
@endsection

@section('content')
@php
    $driverPayload = $drivers->map(function ($driver) use ($threads, $unreadByDriver) {
        $name = trim(($driver->first_name_fa ?? '') . ' ' . ($driver->last_name_fa ?? ''));

        return [
            'id' => $driver->id,
            'name' => $name ?: 'راننده بدون نام',
            'national_code' => $driver->national_code,
            'mobile' => $driver->mobile,
            'last_message_at' => $threads[$driver->id] ?? null,
            'unread_count' => (int) ($unreadByDriver[$driver->id] ?? 0),
        ];
    })->values();
@endphp

<div class="max-w-7xl mx-auto space-y-5">
    <div class="bg-slate-950 text-white rounded-2xl p-6 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-lg font-black">مرکز پیام شرکت و رانندگان</h1>
            <p class="text-slate-400 text-xs mt-1">ارسال پیام انتخابی یا گروهی، مشاهده پاسخ راننده، و پیگیری مکالمات در یک صفحه مستقل.</p>
        </div>
        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-white/10 rounded-xl px-4 py-3">
                <div class="text-lg font-black">{{ number_format($drivers->count()) }}</div>
                <div class="text-[10px] text-slate-300">راننده فعال</div>
            </div>
            <div class="bg-white/10 rounded-xl px-4 py-3">
                <div class="text-lg font-black">{{ number_format($unreadByDriver->sum()) }}</div>
                <div class="text-[10px] text-slate-300">پاسخ خوانده‌نشده</div>
            </div>
            <div class="bg-white/10 rounded-xl px-4 py-3">
                <div class="text-lg font-black">{{ number_format($threads->count()) }}</div>
                <div class="text-[10px] text-slate-300">گفتگوی فعال</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
        <aside class="xl:col-span-4 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="text-sm font-black text-slate-800">رانندگان</h2>
                    <button type="button" onclick="selectAllDrivers()" class="text-[11px] font-black text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg">انتخاب همه</button>
                </div>
                <input id="driver_search" type="text" placeholder="جستجو نام، موبایل یا کد ملی..."
                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs focus:outline-none focus:border-blue-500">
            </div>

            <div id="driver_list" class="max-h-[620px] overflow-y-auto divide-y divide-slate-100"></div>
        </aside>

        <section class="xl:col-span-4 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h2 class="text-sm font-black text-slate-800">ارسال پیام</h2>
                <p id="selected_summary" class="text-[11px] text-slate-500 mt-1">هیچ راننده‌ای انتخاب نشده است.</p>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" onclick="setScope('selected')" id="scope_selected"
                            class="scope-btn bg-blue-600 text-white rounded-xl px-4 py-3 text-xs font-black">انتخابی</button>
                    <button type="button" onclick="setScope('all')" id="scope_all"
                            class="scope-btn bg-slate-100 text-slate-700 rounded-xl px-4 py-3 text-xs font-black">همه رانندگان</button>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-600 mb-2">عنوان</label>
                    <input id="message_title" type="text" maxlength="120" placeholder="مثلا: تغییر برنامه سفر"
                           class="w-full border border-slate-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-blue-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black text-slate-600 mb-2">نوع پیام</label>
                        <select id="message_category" class="w-full border border-slate-200 rounded-xl px-3 py-3 text-sm bg-white focus:outline-none focus:border-blue-500">
                            <option value="general">اطلاعیه عادی</option>
                            <option value="warning">هشدار مهم</option>
                            <option value="trip_change">تغییر برنامه سفر</option>
                            <option value="action_required">نیازمند اقدام</option>
                            <option value="document">مدارک</option>
                            <option value="settlement">مالی و تسویه</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-600 mb-2">اولویت</label>
                        <select id="message_priority" class="w-full border border-slate-200 rounded-xl px-3 py-3 text-sm bg-white focus:outline-none focus:border-blue-500">
                            <option value="normal">عادی</option>
                            <option value="important">مهم</option>
                            <option value="urgent">فوری</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-600 mb-2">متن پیام</label>
                    <textarea id="message_body" rows="7" maxlength="1000" placeholder="متن پیام برای راننده..."
                              class="w-full border border-slate-200 rounded-xl px-3 py-3 text-sm leading-7 resize-none focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <label class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-xl p-3 text-xs font-bold text-blue-900">
                    <input id="message_ack" type="checkbox" class="w-4 h-4">
                    نیازمند تایید مشاهده توسط راننده
                </label>

                <button type="button" onclick="sendMessage()" id="send_btn"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-5 py-3 text-sm font-black transition">
                    ارسال پیام
                </button>
            </div>
        </section>

        <section class="xl:col-span-4 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50">
                <h2 class="text-sm font-black text-slate-800">گفتگو با راننده</h2>
                <p id="thread_title" class="text-[11px] text-slate-500 mt-1">از لیست رانندگان یک نفر را انتخاب کنید.</p>
            </div>
            <div id="thread_body" class="h-[620px] overflow-y-auto p-5 bg-slate-50/60">
                <div class="h-full flex items-center justify-center text-center text-slate-400 text-xs font-bold">
                    هنوز گفتگویی انتخاب نشده است.
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
    const drivers = @json($driverPayload);
    let selectedDriverIds = [];
    let currentScope = 'selected';
    let activeThreadDriverId = null;

    function escapeText(value) {
        return String(value ?? '').replace(/[&<>'"]/g, ch => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
        }[ch]));
    }

    function renderDrivers() {
        const query = $('#driver_search').val().trim().toLowerCase();
        const filtered = drivers.filter(driver => {
            const haystack = `${driver.name} ${driver.mobile || ''} ${driver.national_code || ''}`.toLowerCase();
            return haystack.includes(query);
        });

        $('#driver_list').html(filtered.map(driver => {
            const checked = selectedDriverIds.includes(driver.id);
            const active = activeThreadDriverId === driver.id;
            const unread = Number(driver.unread_count || 0);

            return `
                <div class="driver-item ${active ? 'bg-blue-50' : 'bg-white'} p-4 hover:bg-slate-50 transition">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" class="mt-1 w-4 h-4" ${checked ? 'checked' : ''} onchange="toggleDriver(${driver.id})">
                        <button type="button" onclick="loadThread(${driver.id})" class="flex-1 text-right">
                            <div class="flex items-center justify-between gap-2">
                                <b class="text-sm text-slate-800">${escapeText(driver.name)}</b>
                                ${unread > 0 ? `<span class="bg-rose-100 text-rose-700 text-[10px] font-black px-2 py-1 rounded-full">${unread.toLocaleString('fa-IR')} پاسخ</span>` : ''}
                            </div>
                            <div class="text-[11px] text-slate-500 mt-1">${escapeText(driver.mobile || 'بدون موبایل')} · ${escapeText(driver.national_code || '')}</div>
                            <div class="text-[10px] text-slate-400 mt-1">${driver.last_message_at ? `آخرین گفتگو: ${escapeText(driver.last_message_at)}` : 'بدون سابقه گفتگو'}</div>
                        </button>
                    </div>
                </div>`;
        }).join('') || '<div class="p-8 text-center text-xs font-bold text-slate-400">راننده‌ای یافت نشد.</div>');

        updateSummary();
    }

    function toggleDriver(driverId) {
        selectedDriverIds = selectedDriverIds.includes(driverId)
            ? selectedDriverIds.filter(id => id !== driverId)
            : [...selectedDriverIds, driverId];
        renderDrivers();
    }

    function selectAllDrivers() {
        selectedDriverIds = drivers.map(driver => driver.id);
        setScope('all');
        renderDrivers();
    }

    function setScope(scope) {
        currentScope = scope;
        $('#scope_selected')
            .toggleClass('bg-blue-600 text-white', scope === 'selected')
            .toggleClass('bg-slate-100 text-slate-700', scope !== 'selected');
        $('#scope_all')
            .toggleClass('bg-blue-600 text-white', scope === 'all')
            .toggleClass('bg-slate-100 text-slate-700', scope !== 'all');
        updateSummary();
    }

    function updateSummary() {
        const count = currentScope === 'all' ? drivers.length : selectedDriverIds.length;
        $('#selected_summary').text(
            currentScope === 'all'
                ? `ارسال برای همه رانندگان فعال (${count.toLocaleString('fa-IR')} نفر)`
                : count > 0 ? `${count.toLocaleString('fa-IR')} راننده انتخاب شده است.` : 'هیچ راننده‌ای انتخاب نشده است.'
        );
    }

    function sendMessage() {
        const btn = $('#send_btn');
        const originalText = btn.text();
        const data = {
            _token: "{{ csrf_token() }}",
            scope: currentScope,
            driver_ids: currentScope === 'selected' ? selectedDriverIds : [],
            title: $('#message_title').val(),
            message: $('#message_body').val(),
            category: $('#message_category').val(),
            priority: $('#message_priority').val(),
            requires_acknowledgement: $('#message_ack').is(':checked') ? 1 : 0,
        };

        if (currentScope === 'selected' && selectedDriverIds.length === 0) {
            Swal.fire({ icon: 'warning', title: 'راننده انتخاب نشده', text: 'حداقل یک راننده را انتخاب کنید.' });
            return;
        }

        if (!data.title.trim() || !data.message.trim()) {
            Swal.fire({ icon: 'warning', title: 'پیام ناقص است', text: 'عنوان و متن پیام را وارد کنید.' });
            return;
        }

        btn.prop('disabled', true).text('در حال ارسال...');

        $.post("{{ route('company.driver_messages.store') }}", data, function(res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'ارسال شد', text: res.message, confirmButtonColor: '#2563eb' })
                    .then(() => location.reload());
            } else {
                btn.prop('disabled', false).text(originalText);
                Swal.fire({ icon: 'error', title: 'خطا', text: res.message || 'ارسال انجام نشد.' });
            }
        }).fail(function(xhr) {
            btn.prop('disabled', false).text(originalText);
            const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'خطا در ارتباط با سرور';
            Swal.fire({ icon: 'error', title: 'عدم ارسال پیام', text: message });
        });
    }

    function loadThread(driverId) {
        activeThreadDriverId = driverId;
        renderDrivers();
        $('#thread_body').html('<div class="p-8 text-center text-xs font-bold text-slate-400">در حال دریافت گفتگو...</div>');

        $.get(`{{ url('/web/company/driver-messages') }}/${driverId}`, function(res) {
            if (!res.success) return;

            $('#thread_title').text(`${res.driver.name} · ${res.driver.mobile || 'بدون موبایل'}`);

            const html = res.messages.length
                ? res.messages.map(item => `
                    <div class="mb-3 flex ${item.sender === 'company' ? 'justify-start' : 'justify-end'}">
                        <div class="max-w-[86%] rounded-2xl px-4 py-3 shadow-sm ${item.sender === 'company' ? 'bg-white border border-slate-200 text-slate-700' : 'bg-blue-600 text-white'}">
                            ${item.title ? `<div class="text-xs font-black mb-1">${escapeText(item.title)}</div>` : ''}
                            <div class="text-sm leading-7 whitespace-pre-wrap">${escapeText(item.message)}</div>
                            <div class="text-[10px] mt-2 opacity-70">${escapeText(item.created_at || '')}</div>
                        </div>
                    </div>
                `).join('')
                : '<div class="h-full flex items-center justify-center text-center text-slate-400 text-xs font-bold">هنوز پیامی با این راننده ثبت نشده است.</div>';

            $('#thread_body').html(html);
            $('#thread_body').scrollTop($('#thread_body')[0].scrollHeight);
        });
    }

    $('#driver_search').on('input', renderDrivers);
    $(document).ready(renderDrivers);
</script>
@endsection
