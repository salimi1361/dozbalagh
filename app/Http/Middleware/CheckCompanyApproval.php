<?php

namespace App\Http\Middleware;

use App\Models\AssociationCompanyMessage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyApproval
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // اگر کاربر از نوع شرکت است و تایید نشده، مستقیماً پرتش کن تو فرم مدارک
        if ($user && $user->company && $user->company->status !== 'approved') {
            return redirect()->route('company.profile.edit')
                ->with('warning', 'برای ورود به سامانه، ابتدا باید فرم اطلاعات و مدارک خود را تکمیل کنید.');
        }

        if (
            $user
            && $user->company
            && ! $request->routeIs('company.association_crm.*')
            && $this->hasPendingMandatoryAssociationMessages((int) $user->company->id)
        ) {
            return redirect()->route('company.association_crm.index')
                ->with('warning', 'ابتدا پیام‌های مهم انجمن را مطالعه و تایید کنید.');
        }

        return $next($request);
    }

    private function hasPendingMandatoryAssociationMessages(int $companyId): bool
    {
        return AssociationCompanyMessage::query()
            ->visibleForCompany($companyId)
            ->where('is_mandatory', true)
            ->whereDoesntHave('receipts', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)->whereNotNull('acknowledged_at');
            })
            ->exists();
    }
}
