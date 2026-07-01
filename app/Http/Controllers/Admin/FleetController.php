<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FleetController extends Controller
{
    public function index()
    {
        // 🟢 تغییر current_company_id به company_id
        $fleets = DB::table('fleets')
            ->leftJoin('companies', 'fleets.company_id', '=', 'companies.id')
            ->select('fleets.*', 'companies.name_fa as company_name')
            ->addSelect([
                'active_dozbalaghs' => DB::table('permit_requests') 
                                        ->whereColumn('permit_requests.fleet_id', 'fleets.id')
                                        ->where('permit_requests.status', 'صادر شده') 
                                        ->selectRaw('count(*)')
            ])
            ->orderBy('fleets.id', 'desc')
            ->get(); 

        $companies = DB::table('companies')->where('status', 'approved')->select('id', 'name_fa')->get();

        return view('admin.fleets.index', compact('fleets', 'companies'));
    }

    public function updateCompany(Request $request, $id)
    {
        $companyId = $request->company_id;
        
        // 🟢 تغییر current_company_id به company_id
        DB::table('fleets')->where('id', $id)->update([
            'company_id' => empty($companyId) ? null : $companyId,
            'updated_at' => now()
        ]);

        return back()->with('success', 'وضعیت و شرکت ناوگان با موفقیت بروزرسانی شد.');
    }

    // حذف ناوگان
    public function destroy($id)
    {
        $activeCount = DB::table('permit_requests')
                         ->where('fleet_id', $id)
                         ->where('status', 'صادر شده')
                         ->count();

        if ($activeCount > 0) {
            return back()->with('error', "خطا: این ناوگان دارای پروانه فعال است. ابتدا باید پروانه‌های آن لغو شود یا از شرکت فعلی «آزاد» گردد.");
        }

        DB::table('fleets')->where('id', $id)->delete();
        return back()->with('success', 'ناوگان با موفقیت از دیتابیس سامانه حذف شد.');
    }
}