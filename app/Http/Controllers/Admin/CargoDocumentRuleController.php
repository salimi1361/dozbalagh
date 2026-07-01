<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CargoDocumentRule;
use Illuminate\Http\Request;

class CargoDocumentRuleController extends Controller
{
    /**
     * نمایش لیست قوانین مدارک
     */
    public function index()
    {
        $rules = CargoDocumentRule::all();
        return view('admin.cargo_types.index', compact('rules'));
    }

    /**
     * ذخیره‌سازی وضعیت جدید فیلدها (JSON)
     */
    public function update(Request $request, $id)
    {
        $rule = CargoDocumentRule::findOrFail($id);

        // دریافت آرایه‌ی فیلدها از فرم ادمین و ذخیره مستقیم در دیتابیس
        // (چون در مدل cast => array گذاشتیم، لاراول خودش اینجا آرایه را به JSON تبدیل می‌کند)
        $rule->update([
            'fields_config' => $request->input('fields_config', [])
        ]);

        return redirect()->back()->with('success', "قوانین فیلدها برای عملیات «{$rule->cargo_type}» با موفقیت بروزرسانی شد.");
    }
}