<?php

namespace App\CMR\Http\Controllers\Admin;

use App\CMR\Models\CmrGoodsTemplate;
use App\CMR\Models\CmrLocation;
use App\CMR\Models\CmrParty;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CmrMasterDataController extends Controller
{
    public function index(Request $request)
    {
        $companies = Company::orderBy('name_fa')->get();
        $company = $request->filled('company_id') ? $companies->firstWhere('id', $request->integer('company_id')) : $companies->first();
        return view('CMR.admin.master-data', [
            'companies' => $companies, 'company' => $company,
            'parties' => $company ? CmrParty::where('company_id', $company->id)->orderBy('party_type')->orderBy('legal_name')->get() : collect(),
            'locations' => $company ? CmrLocation::where('company_id', $company->id)->orderBy('location_type')->orderBy('name')->get() : collect(),
            'goodsTemplates' => $company ? CmrGoodsTemplate::where('company_id', $company->id)->orderBy('name')->get() : collect(),
        ]);
    }

    public function storeParty(Request $request, Company $company)
    {
        $latin = 'regex:/^[\p{Latin}\p{N}\p{P}\p{Z}\r\n]+$/u';
        $data = $request->validate(['party_type'=>['required','in:consignor,consignee,carrier,successive_carrier'],'legal_name'=>['required','string','max:255',$latin],'identifier'=>['nullable','string','max:255',$latin],'address'=>['nullable','string','max:2000',$latin],'country_code'=>['nullable','string','size:2']]);
        CmrParty::updateOrCreate(['company_id'=>$company->id,'party_type'=>$data['party_type'],'legal_name'=>$data['legal_name']],$data+['company_id'=>$company->id,'is_active'=>true]);
        return back()->with('success','طرف تجاری در اطلاعات پایه ذخیره شد.');
    }

    public function storeLocation(Request $request, Company $company)
    {
        $latin = 'regex:/^[\p{Latin}\p{N}\p{P}\p{Z}\r\n]+$/u';
        $data = $request->validate(['location_type'=>['required','in:taking_over,delivery'],'name'=>['required','string','max:255',$latin],'country_code'=>['nullable','string','size:2']]);
        CmrLocation::updateOrCreate(['company_id'=>$company->id,'location_type'=>$data['location_type'],'name'=>$data['name']],$data+['company_id'=>$company->id,'is_active'=>true]);
        return back()->with('success','مکان در اطلاعات پایه ذخیره شد.');
    }

    public function storeGoods(Request $request, Company $company)
    {
        $latin = 'regex:/^[\p{Latin}\p{N}\p{P}\p{Z}\r\n]+$/u';
        $data = $request->validate(['name'=>['required','string','max:255',$latin],'description'=>['required','string','max:255',$latin],'package_type'=>['nullable','string','max:100',$latin],'commodity_code'=>['nullable','string','max:100',$latin],'un_number'=>['nullable','string','max:20',$latin],'adr_class'=>['nullable','string','max:50',$latin]]);
        CmrGoodsTemplate::updateOrCreate(['company_id'=>$company->id,'name'=>$data['name']],$data+['company_id'=>$company->id,'is_active'=>true]);
        return back()->with('success','الگوی کالا در اطلاعات پایه ذخیره شد.');
    }
}
