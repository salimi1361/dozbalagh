<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyApproval
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->company && $user->company->status !== 'approved') {
            return redirect()->route('company.profile.edit')
                ->with('warning', 'برای ورود به سامانه، ابتدا باید فرم اطلاعات و مدارک خود را تکمیل کنید.');
        }

        return $next($request);
    }
}
