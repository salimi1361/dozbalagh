@extends('layouts.app')

@section('header_title', 'پیام‌ها و پشتیبانی انجمن')

@section('content')
@php
    $categoryLabels = [
        'general' => 'عمومی',
        'financial' => 'مالی',
        'documents' => 'مدارک',
        'membership' => 'عضویت',
        'legal' => 'رسمی',
        'urgent' => 'فوری',
        'technical' => 'فنی',
        'other' => 'سایر',
    ];
    $priorityLabels = ['normal' => 'عادی', 'important' => 'مهم', 'urgent' => 'فوری'];
    $statusLabels = ['open' => 'باز', 'in_progress' => 'در حال بررسی', 'answered' => 'پاسخ داده شده', 'closed' => 'بسته شده'];
@endphp

<div class="space-y-5" dir="rtl">
    @if(session('warning'))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">{{ session('warning') }}</div>
    @endif

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        <a href="#messages" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700">پیام‌های انجمن</a>
        <a href="#tickets" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700">تیکت و گفتگو</a>
    </div>

    <section id="tickets" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-lg font-black text-slate-900">ثبت تیکت جدید</h2>
        <form id="newTicketForm" action="{{ route('company.association_crm.tickets.store') }}" method="POST" class="grid gap-4 lg:grid-cols-4">
            @csrf
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-black text-slate-600">عنوان</label>
                <input name="title" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-black text-slate-600">دسته‌بندی</label>
                <select name="category" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    @foreach(['general', 'financial', 'documents', 'membership', 'technical', 'other'] as $category)
                        <option value="{{ $category }}">{{ $categoryLabels[$category] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-black text-slate-600">اولویت</label>
                <select name="priority" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    @foreach($priorityLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-4">
                <label class="mb-1 block text-xs font-black text-slate-600">شرح درخواست</label>
                <textarea name="description" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required></textarea>
            </div>
            <div class="lg:col-span-4">
                <button class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-black text-white hover:bg-emerald-700">ثبت تیکت</button>
            </div>
        </form>
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
        <div id="messages" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-slate-900">پیام‌های انجمن</h2>
            <div id="ticketsList" class="space-y-3">
                @forelse($messages as $message)
                    @php
                        $receipt = $message->receipts->first();
                        $acknowledged = filled($receipt?->acknowledged_at);
                    @endphp
                    <div class="rounded-lg border {{ $acknowledged ? 'border-emerald-200 bg-emerald-50/30' : 'border-amber-200 bg-amber-50/30' }} p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="font-black text-slate-900">{{ $message->title }}</h3>
                                <p class="mt-1 text-xs font-bold text-slate-500">
                                    {{ $categoryLabels[$message->category] ?? $message->category }}
                                    · {{ $priorityLabels[$message->priority] ?? $message->priority }}
                                    · {{ verta($message->published_at ?? $message->created_at)->format('Y/m/d H:i') }}
                                </p>
                            </div>
                            <span class="rounded-lg px-3 py-1 text-xs font-black {{ $acknowledged ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $acknowledged ? 'تایید شده' : 'نیازمند تایید' }}
                            </span>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $message->body }}</p>

                        <div class="mt-4 grid gap-3 lg:grid-cols-2">
                            @unless($acknowledged)
                                <form action="{{ route('company.association_crm.messages.acknowledge', $message) }}" method="POST" class="rounded-lg border border-white bg-white p-3">
                                    @csrf
                                    <textarea name="acknowledgement_note" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="یادداشت اختیاری برای انجمن"></textarea>
                                    <button class="mt-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-black text-white">خواندم و تایید می‌کنم</button>
                                </form>
                            @endunless
                            <form action="{{ route('company.association_crm.tickets.store') }}" method="POST" class="rounded-lg border border-white bg-white p-3">
                                @csrf
                                <input type="hidden" name="message_id" value="{{ $message->id }}">
                                <input type="hidden" name="title" value="پیگیری پیام: {{ $message->title }}">
                                <input type="hidden" name="category" value="general">
                                <input type="hidden" name="priority" value="{{ $message->priority === 'urgent' ? 'urgent' : 'important' }}">
                                <textarea name="description" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="اگر سوال یا مشکلی درباره این پیام دارید، اینجا بنویسید"></textarea>
                                <button class="mt-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-black text-white">ثبت تیکت مرتبط</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm font-bold text-slate-500">در حال حاضر پیامی برای شرکت شما وجود ندارد.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $messages->links() }}</div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-slate-900">تیکت‌های من</h2>
            <div class="space-y-3">
                @forelse($tickets as $ticket)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-black text-slate-900">{{ $ticket->title }}</h3>
                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $categoryLabels[$ticket->category] ?? $ticket->category }} · {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</p>
                            </div>
                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-black text-slate-700">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
                        </div>
                        <div id="ticket-messages-{{ $ticket->id }}" class="mt-3 max-h-64 space-y-2 overflow-auto rounded-lg bg-slate-50 p-3">
                            @forelse($ticket->messages as $chatMessage)
                                <div class="rounded-lg {{ $chatMessage->sender === 'company' ? 'bg-emerald-50 text-emerald-900' : 'bg-white text-slate-700' }} p-2 text-xs font-bold leading-6">
                                    <div class="mb-1 flex items-center justify-between gap-2 text-[10px] text-slate-500">
                                        <span>{{ $chatMessage->sender === 'company' ? 'شرکت' : 'انجمن' }}</span>
                                        <span>{{ verta($chatMessage->created_at)->format('Y/m/d H:i') }}</span>
                                    </div>
                                    {{ $chatMessage->body }}
                                </div>
                            @empty
                                <p class="text-xs font-bold text-slate-500">{{ $ticket->description }}</p>
                            @endforelse
                        </div>
                        <form class="ticket-reply-form mt-3 flex gap-2" action="{{ route('company.association_crm.tickets.reply', $ticket) }}" method="POST" data-target="ticket-messages-{{ $ticket->id }}">
                            @csrf
                            <input name="body" class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="پاسخ خود را بنویسید..." required>
                            <button class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-black text-white">ارسال</button>
                        </form>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm font-bold text-slate-500">هنوز تیکتی ثبت نکرده‌اید.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $tickets->links() }}</div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function chatBubble(message) {
        const isCompany = message.sender === 'company';
        return `
            <div class="rounded-lg ${isCompany ? 'bg-emerald-50 text-emerald-900' : 'bg-white text-slate-700'} p-2 text-xs font-bold leading-6">
                <div class="mb-1 flex items-center justify-between gap-2 text-[10px] text-slate-500">
                    <span>${isCompany ? 'شرکت' : 'انجمن'}</span>
                    <span>${escapeHtml(message.created_at)}</span>
                </div>
                ${escapeHtml(message.body)}
            </div>
        `;
    }

    function ticketCard(ticket) {
        const messages = ticket.messages.map(chatBubble).join('');
        return `
            <div class="rounded-lg border border-slate-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-black text-slate-900">${escapeHtml(ticket.title)}</h3>
                        <p class="mt-1 text-xs font-bold text-slate-500">تازه ثبت شده · ${escapeHtml(ticket.created_at)}</p>
                    </div>
                    <span class="rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-black text-slate-700">${escapeHtml(ticket.status)}</span>
                </div>
                <div id="ticket-messages-${ticket.id}" class="mt-3 max-h-64 space-y-2 overflow-auto rounded-lg bg-slate-50 p-3">${messages}</div>
                <form class="ticket-reply-form mt-3 flex gap-2" action="/web/company/association-crm/tickets/${ticket.id}/reply" method="POST" data-target="ticket-messages-${ticket.id}">
                    <input name="body" class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="پاسخ خود را بنویسید..." required>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-black text-white">ارسال</button>
                </form>
            </div>
        `;
    }

    function bindReplyForm(form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const input = form.querySelector('[name="body"]');
            const button = form.querySelector('button');
            button.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ body: input.value }),
                });

                if (!response.ok) {
                    throw new Error('request failed');
                }

                const data = await response.json();
                const target = document.getElementById(form.dataset.target);
                target.insertAdjacentHTML('beforeend', chatBubble(data.message));
                target.scrollTop = target.scrollHeight;
                input.value = '';
            } catch (error) {
                alert('ارسال پیام انجام نشد. لطفا دوباره تلاش کنید.');
            } finally {
                button.disabled = false;
            }
        });
    }

    document.querySelectorAll('.ticket-reply-form').forEach(bindReplyForm);

    document.getElementById('newTicketForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const button = form.querySelector('button');
        button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            });

            if (!response.ok) {
                throw new Error('request failed');
            }

            const data = await response.json();
            document.getElementById('ticketsList').insertAdjacentHTML('afterbegin', ticketCard(data.ticket));
            bindReplyForm(document.querySelector(`#ticket-messages-${data.ticket.id}`).nextElementSibling);
            form.reset();
        } catch (error) {
            alert('ثبت تیکت انجام نشد. لطفا دوباره تلاش کنید.');
        } finally {
            button.disabled = false;
        }
    });
</script>
@endsection
