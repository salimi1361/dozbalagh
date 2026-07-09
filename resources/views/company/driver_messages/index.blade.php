@extends('layouts.app')

@section('header_title')
    پیام رانندگان / <span class="text-blue-600 font-black">مرکز ارتباط شرکت</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-5">
    <div class="bg-slate-950 text-white rounded-2xl p-6 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-lg font-black">مرکز پیام شرکت و رانندگان</h1>
            <p class="text-slate-400 text-xs mt-1">ارسال پیام انتخابی یا گروهی، مشاهده پاسخ راننده، و پیگیری مکالمات در یک صفحه مستقل.</p>
        </div>
        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-white/10 rounded-xl px-4 py-3">
                <div id="stat_drivers" class="text-lg font-black">{{ number_format($drivers->count()) }}</div>
                <div class="text-[10px] text-slate-300">راننده فعال</div>
            </div>
            <div class="bg-white/10 rounded-xl px-4 py-3">
                <div id="stat_unread" class="text-lg font-black">{{ number_format($unreadByDriver->sum()) }}</div>
                <div class="text-[10px] text-slate-300">پاسخ خوانده‌نشده</div>
            </div>
            <div class="bg-white/10 rounded-xl px-4 py-3">
                <div id="stat_threads" class="text-lg font-black">{{ number_format($threads->count()) }}</div>
                <div class="text-[10px] text-slate-300">گفتگوی فعال</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
        <aside class="xl:col-span-4 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="text-sm font-black text-slate-800">سوابق و مخاطبین</h2>
                    <button type="button" id="btn_select_all_drivers" class="text-[11px] font-black text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg">انتخاب همه</button>
                </div>
                <div class="grid grid-cols-3 gap-1 mb-3">
                    <button type="button" id="mode_chats" data-mode="chats" class="list-mode-btn bg-slate-100 text-slate-600 rounded-lg py-2 text-[10px] font-black">سوابق گفتگو</button>
                    <button type="button" id="mode_unread" data-mode="unread" class="list-mode-btn bg-slate-100 text-slate-600 rounded-lg py-2 text-[10px] font-black">خوانده‌نشده</button>
                    <button type="button" id="mode_all" data-mode="all" class="list-mode-btn bg-blue-600 text-white rounded-lg py-2 text-[10px] font-black">همه</button>
                </div>
                <input id="driver_search" type="text" placeholder="جستجو نام، موبایل یا کد ملی..."
                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs focus:outline-none focus:border-blue-500">
            </div>

            <div id="driver_list" class="max-h-[620px] overflow-y-auto divide-y divide-slate-100">
                @forelse($driverPayload as $driver)
                    <div class="driver-item bg-white p-4 hover:bg-slate-50 transition">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" class="driver-check mt-1 w-4 h-4" data-driver-id="{{ $driver['id'] }}">
                            <button type="button" class="driver-open flex-1 text-right" data-driver-id="{{ $driver['id'] }}">
                                <div class="flex items-center justify-between gap-2">
                                    <b class="text-sm text-slate-800">{{ $driver['name'] }}</b>
                                    @if($driver['unread_count'] > 0)
                                        <span class="bg-rose-100 text-rose-700 text-[10px] font-black px-2 py-1 rounded-full">{{ $driver['unread_count'] }} پاسخ</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500 mt-1">{{ $driver['mobile'] ?: 'بدون موبایل' }} · {{ $driver['national_code'] }}</div>
                                <div class="text-[10px] text-slate-400 mt-1">{{ $driver['last_message_at'] ? 'آخرین گفتگو: '.$driver['last_message_at'] : 'بدون سابقه گفتگو' }}</div>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs font-bold text-slate-400">راننده‌ای برای شرکت ثبت نشده است.</div>
                @endforelse
            </div>
        </aside>

        <section class="xl:col-span-4 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h2 class="text-sm font-black text-slate-800">ارسال پیام</h2>
                <p id="selected_summary" class="text-[11px] text-slate-500 mt-1">هیچ راننده‌ای انتخاب نشده است.</p>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" id="scope_selected" data-scope="selected"
                            class="scope-btn bg-blue-600 text-white rounded-xl px-4 py-3 text-xs font-black">انتخابی</button>
                    <button type="button" id="scope_all" data-scope="all"
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

                <button type="button" id="send_btn"
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
            <div id="thread_body" class="h-[470px] overflow-y-auto p-5 bg-slate-50/60">
                <div class="h-full flex items-center justify-center text-center text-slate-400 text-xs font-bold">
                    هنوز گفتگویی انتخاب نشده است.
                </div>
            </div>
            <div class="border-t border-slate-100 bg-white p-4">
                <label class="block text-xs font-black text-slate-600 mb-2">پاسخ سریع در همین گفتگو</label>
                <textarea id="quick_reply_body" rows="3" maxlength="1000" disabled
                          placeholder="ابتدا یک راننده را از لیست انتخاب کنید..."
                          class="w-full border border-slate-200 rounded-xl px-3 py-3 text-sm leading-7 resize-none bg-slate-50 focus:outline-none focus:border-blue-500"></textarea>
                <button type="button" id="quick_reply_btn" disabled
                        class="mt-3 w-full bg-slate-300 text-white rounded-xl px-5 py-3 text-sm font-black transition">
                    ارسال پاسخ به همین راننده
                </button>
            </div>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script>
    var drivers = @json($driverPayload);
    var selectedDriverIds = [];
    var currentScope = 'selected';
    var activeThreadDriverId = null;
    var currentListMode = 'all';
    var threadRefreshInFlight = false;

    function toSafeText(value) {
        return String(value == null ? '' : value).replace(/[&<>'"]/g, function(ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[ch];
        });
    }

    function toFaNumber(value) {
        try { return Number(value || 0).toLocaleString('fa-IR'); }
        catch (e) { return String(value || 0); }
    }

    function driverExists(driverId) {
        for (var i = 0; i < drivers.length; i++) {
            if (Number(drivers[i].id) === Number(driverId)) return true;
        }
        return false;
    }

    function isSelected(driverId) {
        return selectedDriverIds.indexOf(Number(driverId)) !== -1;
    }

    function renderDrivers() {
        var query = ($('#driver_search').val() || '').toLowerCase();
        var html = '';

        for (var i = 0; i < drivers.length; i++) {
            var driver = drivers[i];
            var unread = Number(driver.unread_count || 0);

            if (currentListMode === 'chats' && !driver.last_message_at) continue;
            if (currentListMode === 'unread' && unread === 0) continue;

            var haystack = String(driver.name || '') + ' ' + String(driver.mobile || '') + ' ' + String(driver.national_code || '');
            if (query && haystack.toLowerCase().indexOf(query) === -1) continue;

            var activeClass = Number(activeThreadDriverId) === Number(driver.id) ? 'bg-blue-50' : 'bg-white';
            var checked = isSelected(driver.id) ? 'checked' : '';
            var lastMessage = driver.last_message_at ? 'آخرین گفتگو: ' + toSafeText(driver.last_message_at) : 'بدون سابقه گفتگو';

            html += '<div class="driver-item ' + activeClass + ' p-4 hover:bg-slate-50 transition">' +
                '<div class="flex items-start gap-3">' +
                    '<input type="checkbox" class="driver-check mt-1 w-4 h-4" data-driver-id="' + driver.id + '" ' + checked + '>' +
                    '<button type="button" class="driver-open flex-1 text-right" data-driver-id="' + driver.id + '">' +
                        '<div class="flex items-center justify-between gap-2">' +
                            '<b class="text-sm text-slate-800">' + toSafeText(driver.name) + '</b>' +
                            (unread > 0 ? '<span class="bg-rose-100 text-rose-700 text-[10px] font-black px-2 py-1 rounded-full">' + toFaNumber(unread) + ' پاسخ</span>' : '') +
                        '</div>' +
                        '<div class="text-[11px] text-slate-500 mt-1">' + toSafeText(driver.mobile || 'بدون موبایل') + ' · ' + toSafeText(driver.national_code || '') + '</div>' +
                        '<div class="text-[10px] text-slate-400 mt-1">' + lastMessage + '</div>' +
                    '</button>' +
                '</div>' +
            '</div>';
        }

        $('#driver_list').html(html || emptyDriverListHtml());
        updateSummary();
    }

    function emptyDriverListHtml() {
        var label = 'راننده‌ای یافت نشد.';
        if (currentListMode === 'chats') label = 'هنوز سابقه گفتگویی ثبت نشده است. از تب «همه» راننده را انتخاب کنید.';
        if (currentListMode === 'unread') label = 'پاسخ خوانده‌نشده‌ای وجود ندارد.';
        return '<div class="p-8 text-center text-xs font-bold text-slate-400 leading-7">' + label + '</div>';
    }

    function setListMode(mode) {
        currentListMode = mode;
        $('.list-mode-btn').removeClass('bg-blue-600 text-white').addClass('bg-slate-100 text-slate-600');
        $('#mode_' + mode).removeClass('bg-slate-100 text-slate-600').addClass('bg-blue-600 text-white');
        renderDrivers();
    }

    function toggleDriver(driverId) {
        driverId = Number(driverId);
        var index = selectedDriverIds.indexOf(driverId);
        if (index === -1) selectedDriverIds.push(driverId);
        else selectedDriverIds.splice(index, 1);
        updateSummary();
    }

    function selectAllDrivers() {
        selectedDriverIds = [];
        for (var i = 0; i < drivers.length; i++) selectedDriverIds.push(Number(drivers[i].id));
        setScope('all');
        renderDrivers();
    }

    function setScope(scope) {
        currentScope = scope;
        $('#scope_selected, #scope_all').removeClass('bg-blue-600 text-white').addClass('bg-slate-100 text-slate-700');
        $('#scope_' + scope).removeClass('bg-slate-100 text-slate-700').addClass('bg-blue-600 text-white');
        updateSummary();
    }

    function updateSummary() {
        var count = currentScope === 'all' ? drivers.length : selectedDriverIds.length;
        var text = currentScope === 'all'
            ? 'ارسال برای همه رانندگان فعال (' + toFaNumber(count) + ' نفر)'
            : (count > 0 ? toFaNumber(count) + ' راننده انتخاب شده است.' : 'هیچ راننده‌ای انتخاب نشده است.');
        $('#selected_summary').text(text);
    }

    function sendMessage() {
        var btn = $('#send_btn');
        var originalText = btn.text();
        var title = $('#message_title').val() || '';
        var body = $('#message_body').val() || '';

        if (currentScope === 'selected' && selectedDriverIds.length === 0) {
            Swal.fire({ icon: 'warning', title: 'راننده انتخاب نشده', text: 'حداقل یک راننده را انتخاب کنید.' });
            return;
        }

        if (!title.trim() || !body.trim()) {
            Swal.fire({ icon: 'warning', title: 'پیام ناقص است', text: 'عنوان و متن پیام را وارد کنید.' });
            return;
        }

        btn.prop('disabled', true).text('در حال ارسال...');

        $.post("{{ route('company.driver_messages.store') }}", {
            _token: "{{ csrf_token() }}",
            scope: currentScope,
            driver_ids: currentScope === 'selected' ? selectedDriverIds : [],
            title: title,
            message: body,
            category: $('#message_category').val(),
            priority: $('#message_priority').val(),
            requires_acknowledgement: $('#message_ack').is(':checked') ? 1 : 0
        }, function(res) {
            btn.prop('disabled', false).text(originalText);
            if (res.success) {
                $('#message_title').val('');
                $('#message_body').val('');
                $('#message_ack').prop('checked', false);
                Swal.fire({ icon: 'success', title: 'ارسال شد', text: res.message, confirmButtonColor: '#2563eb' });
                refreshSummary();
            } else {
                Swal.fire({ icon: 'error', title: 'خطا', text: res.message || 'ارسال انجام نشد.' });
            }
        }).fail(function(xhr) {
            btn.prop('disabled', false).text(originalText);
            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'خطا در ارتباط با سرور';
            Swal.fire({ icon: 'error', title: 'عدم ارسال پیام', text: message });
        });
    }

    function loadThread(driverId, silent) {
        if (threadRefreshInFlight) return;
        threadRefreshInFlight = true;
        activeThreadDriverId = Number(driverId);
        renderDrivers();

        if (!silent) $('#thread_body').html('<div class="p-8 text-center text-xs font-bold text-slate-400">در حال دریافت گفتگو...</div>');

        $.get("{{ url('/web/company/driver-messages') }}/" + driverId, function(res) {
            if (!res.success) return;

            $('#thread_title').text((res.driver.name || 'راننده') + ' · ' + (res.driver.mobile || 'بدون موبایل'));
            $('#quick_reply_body')
                .prop('disabled', false)
                .attr('placeholder', 'پاسخ به ' + (res.driver.name || 'راننده') + '...');
            $('#quick_reply_btn')
                .prop('disabled', false)
                .removeClass('bg-slate-300')
                .addClass('bg-blue-600 hover:bg-blue-700');

            for (var i = 0; i < drivers.length; i++) {
                if (Number(drivers[i].id) === Number(driverId)) drivers[i].unread_count = 0;
            }

            var html = '';
            for (var j = 0; j < res.messages.length; j++) {
                var item = res.messages[j];
                var isCompany = item.sender === 'company';
                html += '<div class="mb-3 flex ' + (isCompany ? 'justify-start' : 'justify-end') + '">' +
                    '<div class="max-w-[86%] rounded-2xl px-4 py-3 shadow-sm ' + (isCompany ? 'bg-white border border-slate-200 text-slate-700' : 'bg-blue-600 text-white') + '">' +
                        (item.title ? '<div class="text-xs font-black mb-1">' + toSafeText(item.title) + '</div>' : '') +
                        '<div class="text-sm leading-7 whitespace-pre-wrap">' + toSafeText(item.message) + '</div>' +
                        '<div class="text-[10px] mt-2 opacity-70">' + toSafeText(item.created_at || '') + '</div>' +
                    '</div>' +
                '</div>';
            }

            $('#thread_body').html(html || '<div class="h-full flex items-center justify-center text-center text-slate-400 text-xs font-bold">هنوز پیامی با این راننده ثبت نشده است.</div>');
            $('#thread_body').scrollTop($('#thread_body')[0].scrollHeight);
        }).always(function() {
            threadRefreshInFlight = false;
        });
    }

    function reloadActiveThread() {
        if (!activeThreadDriverId) return;
        if (threadRefreshInFlight) {
            setTimeout(reloadActiveThread, 350);
            return;
        }
        loadThread(activeThreadDriverId, true);
    }

    function sendQuickReply() {
        if (!activeThreadDriverId) {
            Swal.fire({ icon: 'warning', title: 'گفتگو انتخاب نشده', text: 'ابتدا یک راننده را از لیست انتخاب کنید.' });
            return;
        }

        var body = $('#quick_reply_body').val() || '';
        if (!body.trim()) {
            Swal.fire({ icon: 'warning', title: 'پاسخ خالی است', text: 'متن پاسخ را وارد کنید.' });
            return;
        }

        var btn = $('#quick_reply_btn');
        var originalText = btn.text();
        btn.prop('disabled', true).text('در حال ارسال...');

        $.post("{{ url('/web/company/driver-messages') }}/" + activeThreadDriverId + "/reply", {
            _token: "{{ csrf_token() }}",
            message: body
        }, function(res) {
            btn.prop('disabled', false).text(originalText);

            if (res.success) {
                $('#quick_reply_body').val('');
                reloadActiveThread();
                refreshSummary();
            } else {
                Swal.fire({ icon: 'error', title: 'خطا', text: res.message || 'ارسال پاسخ انجام نشد.' });
            }
        }).fail(function(xhr) {
            btn.prop('disabled', false).text(originalText);
            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'خطا در ارتباط با سرور';
            Swal.fire({ icon: 'error', title: 'عدم ارسال پاسخ', text: message });
        });
    }

    function refreshSummary() {
        $.get("{{ route('company.driver_messages.summary') }}", function(res) {
            if (!res.success) return;
            drivers = res.drivers || [];
            $('#stat_drivers').text(toFaNumber(res.stats.drivers_count));
            $('#stat_unread').text(toFaNumber(res.stats.unread_count));
            $('#stat_threads').text(toFaNumber(res.stats.threads_count));

            var nextSelected = [];
            for (var i = 0; i < selectedDriverIds.length; i++) {
                if (driverExists(selectedDriverIds[i])) nextSelected.push(selectedDriverIds[i]);
            }
            selectedDriverIds = nextSelected;
            renderDrivers();
        });
    }

    window.setListMode = setListMode;
    window.setScope = setScope;
    window.selectAllDrivers = selectAllDrivers;
    window.sendMessage = sendMessage;
    window.sendQuickReply = sendQuickReply;
    window.loadThread = loadThread;
    window.toggleDriver = toggleDriver;

    $(function() {
        $('#driver_list').on('change', '.driver-check', function() {
            toggleDriver($(this).data('driver-id'));
        });
        $('#driver_list').on('click', '.driver-open', function() {
            loadThread($(this).data('driver-id'), false);
        });
        $('.list-mode-btn').on('click', function() {
            setListMode($(this).data('mode'));
        });
        $('.scope-btn').on('click', function() {
            setScope($(this).data('scope'));
        });
        $('#btn_select_all_drivers').on('click', selectAllDrivers);
        $('#send_btn').on('click', sendMessage);
        $('#quick_reply_btn').on('click', sendQuickReply);
        $('#quick_reply_body').on('keydown', function(event) {
            if (event.ctrlKey && event.keyCode === 13) sendQuickReply();
        });
        $('#driver_search').on('input', renderDrivers);

        renderDrivers();
        setInterval(function() {
            refreshSummary();
            if (activeThreadDriverId) loadThread(activeThreadDriverId, true);
        }, 8000);
    });
</script>
@endsection
