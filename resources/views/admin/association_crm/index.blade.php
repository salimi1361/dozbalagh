@extends('layouts.admin')

@section('header_title', 'CRM انجمن')

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
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>
    @endif

    <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-black text-slate-500">پیام فعال</p>
            <b class="mt-2 block font-mono text-3xl font-black text-slate-900">{{ number_format($stats['active_messages']) }}</b>
        </div>
        <div class="rounded-lg border border-amber-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-black text-slate-500">پیام اجباری</p>
            <b class="mt-2 block font-mono text-3xl font-black text-amber-600">{{ number_format($stats['mandatory_messages']) }}</b>
        </div>
        <div class="rounded-lg border border-rose-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-black text-slate-500">تیکت باز</p>
            <b class="mt-2 block font-mono text-3xl font-black text-rose-600">{{ number_format($stats['open_tickets']) }}</b>
        </div>
        <div class="rounded-lg border border-emerald-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-black text-slate-500">رسید خواندن</p>
            <b class="mt-2 block font-mono text-3xl font-black text-emerald-600">{{ number_format($stats['acknowledged_receipts']) }}</b>
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-lg font-black text-slate-900">ثبت پیام برای شرکت‌ها</h2>
        <form action="{{ route('admin.association_crm.messages.store') }}" method="POST" class="grid gap-4 lg:grid-cols-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-black text-slate-600">مخاطب</label>
                <select name="audience" id="audience" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" onchange="toggleCompanySelect()">
                    <option value="selected">شرکت مشخص</option>
                    <option value="all">همه شرکت‌ها</option>
                </select>
            </div>
            <div id="companySelectWrap">
                <label class="mb-1 block text-xs font-black text-slate-600">شرکت</label>
                <select name="company_id" id="company_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
                    <option value="">انتخاب کنید</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name_fa ?? $company->name }} - {{ $company->company_code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-black text-slate-600">دسته‌بندی</label>
                <select name="category" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    @foreach(['general', 'financial', 'documents', 'membership', 'legal', 'urgent'] as $category)
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
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-black text-slate-600">عنوان</label>
                <input name="title" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" maxlength="180" required>
            </div>
            <div class="flex items-end gap-4">
                <label class="inline-flex items-center gap-2 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="is_mandatory" value="1" checked class="rounded border-slate-300">
                    نیازمند تایید خواندن
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                    فعال
                </label>
            </div>
            <div class="lg:col-span-4">
                <label class="mb-1 block text-xs font-black text-slate-600">متن پیام</label>
                <textarea name="body" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required></textarea>
            </div>
            <div class="lg:col-span-4">
                <button class="rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-black text-white hover:bg-sky-700">انتشار پیام</button>
            </div>
        </form>
    </section>

    <section class="grid gap-5 xl:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-slate-900">پیام‌های اخیر</h2>
            <div class="max-h-[680px] space-y-3 overflow-y-auto pr-1">
                @forelse($messages as $message)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="font-black text-slate-900">{{ $message->title }}</h3>
                                <p class="mt-1 text-xs font-bold text-slate-500">
                                    {{ $message->audience === 'all' ? 'همه شرکت‌ها' : ($message->company->name_fa ?? $message->company->name ?? 'شرکت') }}
                                    · {{ $categoryLabels[$message->category] ?? $message->category }}
                                    · {{ $priorityLabels[$message->priority] ?? $message->priority }}
                                    · {{ verta($message->published_at ?? $message->created_at)->format('Y/m/d H:i') }}
                                </p>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <form action="{{ route('admin.association_crm.messages.toggle', $message) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button class="rounded-lg px-3 py-1.5 text-xs font-black {{ $message->is_active ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                                        {{ $message->is_active ? 'غیرفعال' : 'فعال' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.association_crm.messages.destroy', $message) }}" method="POST" onsubmit="return confirm('این پیام و رسیدهای آن حذف شود؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-700">حذف</button>
                                </form>
                            </div>
                        </div>
                        <p class="mt-3 line-clamp-2 text-sm leading-7 text-slate-600">{{ $message->body }}</p>
                        <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-black text-slate-600">
                            <span class="rounded-lg bg-slate-100 px-2 py-1">دیده شده: {{ number_format($message->receipts_count) }}</span>
                            <span class="rounded-lg bg-emerald-50 px-2 py-1 text-emerald-700">تایید شده: {{ number_format($message->acknowledged_count) }}</span>
                            <span class="rounded-lg bg-amber-50 px-2 py-1 text-amber-700">تیکت مرتبط: {{ number_format($message->tickets_count) }}</span>
                            <button type="button" onclick="openReceiptModal('receipts-{{ $message->id }}')" class="rounded-lg bg-sky-50 px-2 py-1 text-sky-700">مشاهده شرکت‌ها</button>
                        </div>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm font-bold text-slate-500">هنوز پیامی ثبت نشده است.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $messages->links() }}</div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-slate-900">تیکت‌های شرکت‌ها</h2>
            <div class="max-h-[680px] space-y-3 overflow-y-auto pr-1">
                @forelse($tickets as $ticket)
                    <form action="{{ route('admin.association_crm.tickets.update', $ticket) }}" method="POST" class="rounded-lg border border-slate-200 p-4">
                        @csrf
                        @method('PUT')
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="font-black text-slate-900">{{ $ticket->title }}</h3>
                                <p class="mt-1 text-xs font-bold text-slate-500">
                                    {{ $ticket->company->name_fa ?? $ticket->company->name }}
                                    · {{ $categoryLabels[$ticket->category] ?? $ticket->category }}
                                    · {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}
                                </p>
                            </div>
                            <select name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold">
                                @foreach($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($ticket->message)
                            <p class="mt-2 text-xs font-bold text-sky-700">مرتبط با پیام: {{ $ticket->message->title }}</p>
                        @endif
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $ticket->description }}</p>
                        @if($ticket->messages->isNotEmpty())
                            <div class="mt-3 max-h-48 space-y-2 overflow-auto rounded-lg bg-slate-50 p-3">
                                @foreach($ticket->messages as $chatMessage)
                                    <div class="rounded-lg {{ $chatMessage->sender === 'association' ? 'bg-sky-50 text-sky-900' : 'bg-white text-slate-700' }} p-2 text-xs font-bold leading-6">
                                        <div class="mb-1 flex items-center justify-between gap-2 text-[10px] text-slate-500">
                                            <span>{{ $chatMessage->sender === 'association' ? 'انجمن' : 'شرکت' }}</span>
                                            <span>{{ verta($chatMessage->created_at)->format('Y/m/d H:i') }}</span>
                                        </div>
                                        {{ $chatMessage->body }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <textarea name="admin_response" rows="2" class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="پاسخ جدید یا یادداشت پیگیری"></textarea>
                        <button class="mt-3 rounded-lg bg-slate-900 px-4 py-2 text-xs font-black text-white">ثبت پیگیری</button>
                    </form>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm font-bold text-slate-500">هنوز تیکتی ثبت نشده است.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $tickets->links() }}</div>
        </div>
    </section>
    @foreach($messages as $message)
        @php $detail = $messageReceiptDetails[$message->id] ?? ['total' => 0, 'seen' => 0, 'acknowledged' => 0, 'rows' => collect()]; @endphp
        <div id="receipts-{{ $message->id }}" class="fixed inset-0 z-[80] hidden bg-slate-900/60 p-4 backdrop-blur-sm">
            <div class="mx-auto mt-8 max-h-[85vh] max-w-5xl overflow-hidden rounded-lg bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 p-4">
                    <div>
                        <h3 class="font-black text-slate-900">وضعیت مشاهده پیام: {{ $message->title }}</h3>
                        <p class="mt-1 text-xs font-bold text-slate-500">
                            کل هدف: {{ number_format($detail['total']) }}
                            · دیده شده: {{ number_format($detail['seen']) }}
                            · تایید شده: {{ number_format($detail['acknowledged']) }}
                        </p>
                    </div>
                    <button type="button" onclick="closeReceiptModal('receipts-{{ $message->id }}')" class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-black text-slate-700">بستن</button>
                </div>
                <div class="max-h-[68vh] overflow-auto p-4">
                    <table class="min-w-full text-right text-sm">
                        <thead class="bg-slate-50 text-xs font-black text-slate-500">
                            <tr>
                                <th class="px-3 py-2">شرکت</th>
                                <th class="px-3 py-2">کد</th>
                                <th class="px-3 py-2">مشاهده</th>
                                <th class="px-3 py-2">تایید</th>
                                <th class="px-3 py-2">کاربر</th>
                                <th class="px-3 py-2">یادداشت</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($detail['rows'] as $row)
                                <tr>
                                    <td class="px-3 py-2 font-bold text-slate-800">{{ $row['company_name'] }}</td>
                                    <td class="px-3 py-2 font-mono text-slate-500">{{ $row['company_code'] ?? '---' }}</td>
                                    <td class="px-3 py-2">
                                        @if($row['seen_at'])
                                            <span class="rounded-lg bg-sky-50 px-2 py-1 text-xs font-black text-sky-700">{{ $row['seen_at'] }}</span>
                                        @else
                                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-black text-slate-500">ندیده</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @if($row['acknowledged_at'])
                                            <span class="rounded-lg bg-emerald-50 px-2 py-1 text-xs font-black text-emerald-700">{{ $row['acknowledged_at'] }}</span>
                                        @else
                                            <span class="rounded-lg bg-amber-50 px-2 py-1 text-xs font-black text-amber-700">تایید نشده</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ $row['user'] ?? '---' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $row['note'] ?? '---' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>

<script>
    function toggleCompanySelect() {
        const audience = document.getElementById('audience');
        const wrap = document.getElementById('companySelectWrap');
        const company = document.getElementById('company_id');
        const selected = audience.value === 'selected';

        wrap.classList.toggle('hidden', !selected);
        company.required = selected;
        if (!selected) {
            company.value = '';
        }
    }

    function openReceiptModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeReceiptModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    toggleCompanySelect();
</script>
@endsection
