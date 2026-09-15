<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CargoDocumentRule;
use App\Services\CargoDocumentRuleService;
use Illuminate\Http\Request;

class CargoDocumentRuleController extends Controller
{
    /**
     * نمایش لیست قوانین مدارک
     */
    public function index(CargoDocumentRuleService $ruleService)
    {
        $rules = CargoDocumentRule::query()->orderBy('id')->get();
        $normalizedConfigs = $rules->mapWithKeys(fn (CargoDocumentRule $rule) => [
            $rule->id => $ruleService->normalizeConfig($rule->fields_config),
        ]);

        return view('admin.cargo_types.index', [
            'rules' => $rules,
            'fieldGroups' => $ruleService->groups(),
            'normalizedConfigs' => $normalizedConfigs,
        ]);
    }

    /**
     * ذخیره‌سازی وضعیت جدید فیلدها (JSON)
     */
    public function update(Request $request, CargoDocumentRuleService $ruleService, $id)
    {
        $rule = CargoDocumentRule::findOrFail($id);
        $validated = $request->validate([
            'fields_config' => ['required', 'array'],
        ]);

        $rule->update([
            'fields_config' => $ruleService->normalizeSubmittedConfig(
                $validated['fields_config'],
                $rule->fields_config,
            ),
        ]);

        return redirect()->back()->with('success', "قوانین فیلدها برای عملیات «{$rule->cargo_type}» با موفقیت بروزرسانی شد.");
    }
}
