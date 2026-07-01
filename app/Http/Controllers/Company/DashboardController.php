<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // گرفتن اطلاعات کاربری که لاگین کرده
        $user = auth()->user();
        $company = $user->company;

        // اگر احیاناً ادمین وارد این روت شد، جلویش را بگیریم
        if (!$company) {
            return redirect()->route('admin.companies.index');
        }

// تغییر از company.dashboard به dashboard
return view('dashboard', compact('company', 'user'));
    }
}