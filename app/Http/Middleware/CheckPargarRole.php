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
        
        // اگر کاربر شرکت است و هنوز تایید نشده
        if ($user && $user->company && $user->company->status !== 'approved') {
            
            // روت‌هایی که شرکتِ تایید نشده مجاز است ببیند (داشبورد، ویرایش پروفایل، خروج)
            $allowedRoutes = [
                'dashboard', 
                'company.profile.edit', 
                'company.profile.update', 
                'logout'
            ];

            // اگر خواست جای دیگری برود، او را به صفحه تکمیل مدارک برگردان
            if (!in_array($request->route()->getName(), $allowedRoutes)) {
                return redirect()->route('company.profile.edit')
                    ->with('warning', 'برای استفاده از امکانات سامانه، ابتدا باید فرم اطلاعات و مدارک خود را تکمیل کنید.');
            }
        }

        return $next($request);
    }
}