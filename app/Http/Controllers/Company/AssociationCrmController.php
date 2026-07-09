<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\AssociationCompanyMessage;
use App\Models\AssociationMessageReceipt;
use App\Models\AssociationSupportTicket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssociationCrmController extends Controller
{
    public function index()
    {
        return $this->show();
    }

    public function tickets()
    {
        return $this->show('tickets');
    }

    private function show(string $activeTab = 'messages')
    {
        $companyId = $this->companyId();

        $messages = AssociationCompanyMessage::query()
            ->visibleForCompany($companyId)
            ->with(['receipts' => fn ($query) => $query->where('company_id', $companyId)])
            ->latest()
            ->paginate(10, ['*'], 'messages_page');

        foreach ($messages as $message) {
            $this->receiptFor($message, $companyId);
        }

        $tickets = AssociationSupportTicket::query()
            ->where('company_id', $companyId)
            ->with('message:id,title')
            ->latest()
            ->paginate(10, ['*'], 'tickets_page');

        return view('company.association_crm.index', compact('messages', 'tickets', 'activeTab'));
    }

    public function acknowledge(Request $request, AssociationCompanyMessage $message)
    {
        $companyId = $this->companyId();
        abort_unless($this->canSeeMessage($message, $companyId), 404);

        $validated = $request->validate([
            'acknowledgement_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->receiptFor($message, $companyId)->update([
            'user_id' => auth()->id(),
            'seen_at' => now(),
            'acknowledged_at' => now(),
            'acknowledgement_note' => $validated['acknowledgement_note'] ?? null,
        ]);

        return back()->with('success', 'تایید خواندن پیام ثبت شد.');
    }

    public function storeTicket(Request $request)
    {
        $companyId = $this->companyId();

        $validated = $request->validate([
            'message_id' => ['nullable', 'exists:association_company_messages,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:3000'],
            'category' => ['required', Rule::in(['general', 'financial', 'documents', 'membership', 'technical', 'other'])],
            'priority' => ['required', Rule::in(['normal', 'important', 'urgent'])],
        ]);

        if (! empty($validated['message_id'])) {
            $message = AssociationCompanyMessage::findOrFail($validated['message_id']);
            abort_unless($this->canSeeMessage($message, $companyId), 404);
            $this->receiptFor($message, $companyId);
        }

        AssociationSupportTicket::create([
            ...$validated,
            'company_id' => $companyId,
            'created_by' => auth()->id(),
            'status' => 'open',
        ]);

        return back()->with('success', 'تیکت شما برای انجمن ثبت شد.');
    }

    private function companyId(): int
    {
        $companyId = auth()->user()->company->id ?? auth()->user()->company_id ?? null;

        abort_unless($companyId, 403);

        return (int) $companyId;
    }

    private function canSeeMessage(AssociationCompanyMessage $message, int $companyId): bool
    {
        return AssociationCompanyMessage::query()
            ->visibleForCompany($companyId)
            ->whereKey($message->id)
            ->exists();
    }

    private function receiptFor(AssociationCompanyMessage $message, int $companyId): AssociationMessageReceipt
    {
        return AssociationMessageReceipt::firstOrCreate(
            ['message_id' => $message->id, 'company_id' => $companyId],
            ['user_id' => auth()->id(), 'seen_at' => now()]
        );
    }
}
