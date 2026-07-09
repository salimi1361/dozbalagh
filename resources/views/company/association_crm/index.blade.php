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
        <a href="{{ route('company.association_crm.index') }}" class="rounded-lg px-4 py-2 text-sm font-black {{ ($activeTab ?? 'messages') === 'messages' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200' }}">پیام‌های انجمن</a>
        <a href="{{ route('company.association_crm.tickets.index') }}" class="rounded-lg px-4 py-2 text-sm font-black {{ ($activeTab ?? 'messages') === 'tickets' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200' }}">تیکت انجمن</a>
    </div>

    <section id="tickets" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-lg font-black text-slate-900">ثبت تیکت جدید</h2>
        <form action="{{ route('company.association_crm.tickets.store') }}" method="POST" class="grid gap-4 lg:grid-cols-4">
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
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-slate-900">پیام‌های انجمن</h2>
            <div class="space-y-3">
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
                                    · {{ optional($message->published_at ?? $message->created_at)->format('Y/m/d H:i') }}
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
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $ticket->description }}</p>
                        @if($ticket->admin_response)
                            <div class="mt-3 rounded-lg bg-sky-50 p-3 text-sm font-bold leading-7 text-sky-800">
                                پاسخ انجمن: {{ $ticket->admin_response }}
                            </div>
                        @endif
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
