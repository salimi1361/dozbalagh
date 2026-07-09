<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssociationCompanyMessage;
use App\Models\AssociationSupportTicket;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssociationCrmController extends Controller
{
    public function index()
    {
        $companies = Company::query()
            ->orderBy('name_fa')
            ->orderBy('name')
            ->get(['id', 'name', 'name_fa', 'company_code']);

        $messages = AssociationCompanyMessage::query()
            ->with([
                'company:id,name,name_fa,company_code',
                'receipts.company:id,name,name_fa,company_code',
                'receipts.user:id,username,mobile',
            ])
            ->withCount([
                'receipts',
                'receipts as acknowledged_count' => fn ($query) => $query->whereNotNull('acknowledged_at'),
                'tickets',
            ])
            ->latest()
            ->paginate(12, ['*'], 'messages_page');

        $messageReceiptDetails = $messages->getCollection()
            ->mapWithKeys(function (AssociationCompanyMessage $message) use ($companies) {
                $targetCompanies = $message->audience === 'all'
                    ? $companies
                    : $companies->where('id', $message->company_id)->values();

                $receiptsByCompany = $message->receipts->keyBy('company_id');

                $rows = $targetCompanies->map(function (Company $company) use ($receiptsByCompany) {
                    $receipt = $receiptsByCompany->get($company->id);

                    return [
                        'company_name' => $company->name_fa ?? $company->name,
                        'company_code' => $company->company_code,
                        'seen_at' => $receipt?->seen_at ? verta($receipt->seen_at)->format('Y/m/d H:i') : null,
                        'acknowledged_at' => $receipt?->acknowledged_at ? verta($receipt->acknowledged_at)->format('Y/m/d H:i') : null,
                        'user' => $receipt?->user?->username,
                        'note' => $receipt?->acknowledgement_note,
                    ];
                })->values();

                return [$message->id => [
                    'total' => $targetCompanies->count(),
                    'seen' => $rows->whereNotNull('seen_at')->count(),
                    'acknowledged' => $rows->whereNotNull('acknowledged_at')->count(),
                    'rows' => $rows,
                ]];
            });

        $tickets = AssociationSupportTicket::query()
            ->with(['company:id,name,name_fa,company_code', 'message:id,title', 'messages' => fn ($query) => $query->orderBy('created_at')])
            ->latest()
            ->paginate(10, ['*'], 'tickets_page');

        $stats = [
            'active_messages' => AssociationCompanyMessage::where('is_active', true)->count(),
            'mandatory_messages' => AssociationCompanyMessage::where('is_mandatory', true)->count(),
            'open_tickets' => AssociationSupportTicket::whereIn('status', ['open', 'in_progress'])->count(),
            'acknowledged_receipts' => \App\Models\AssociationMessageReceipt::whereNotNull('acknowledged_at')->count(),
        ];

        return view('admin.association_crm.index', compact('companies', 'messages', 'tickets', 'stats', 'messageReceiptDetails'));
    }

    public function storeMessage(Request $request)
    {
        $validated = $request->validate([
            'audience' => ['required', Rule::in(['all', 'selected'])],
            'company_id' => ['nullable', 'required_if:audience,selected', 'exists:companies,id'],
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:3000'],
            'category' => ['required', Rule::in(['general', 'financial', 'documents', 'membership', 'legal', 'urgent'])],
            'priority' => ['required', Rule::in(['normal', 'important', 'urgent'])],
            'is_mandatory' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        AssociationCompanyMessage::create([
            ...$validated,
            'company_id' => $validated['audience'] === 'all' ? null : $validated['company_id'],
            'created_by' => auth()->id(),
            'is_mandatory' => $request->boolean('is_mandatory', true),
            'is_active' => $request->boolean('is_active', true),
            'published_at' => now(),
        ]);

        return back()->with('success', 'پیام انجمن با موفقیت ثبت و منتشر شد.');
    }

    public function toggleMessage(AssociationCompanyMessage $message)
    {
        $message->update(['is_active' => ! $message->is_active]);

        return back()->with('success', $message->is_active ? 'پیام فعال شد.' : 'پیام غیرفعال شد.');
    }

    public function destroyMessage(AssociationCompanyMessage $message)
    {
        $message->delete();

        return back()->with('success', 'پیام از CRM انجمن حذف شد.');
    }

    public function updateTicket(Request $request, AssociationSupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'answered', 'closed'])],
            'admin_response' => ['nullable', 'string', 'max:3000'],
        ]);

        $ticket->update([
            ...$validated,
            'assigned_to' => auth()->id(),
            'closed_at' => $validated['status'] === 'closed' ? now() : null,
        ]);

        if (filled($validated['admin_response'])) {
            $ticket->messages()->create([
                'user_id' => auth()->id(),
                'sender' => 'association',
                'body' => $validated['admin_response'],
            ]);
        }

        return back()->with('success', 'وضعیت تیکت بروزرسانی شد.');
    }
}
