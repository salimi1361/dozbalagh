<?php

namespace Tests\Unit;

use App\Services\CargoDocumentRuleService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CargoDocumentRuleServiceTest extends TestCase
{
    public function test_normalize_config_returns_only_known_fields_with_safe_states(): void
    {
        $service = new CargoDocumentRuleService();

        $config = $service->normalizeConfig([
            'receipt_code' => 'required',
            'tir_file' => 'not-a-state',
            'unknown_field' => 'required',
        ]);

        $this->assertSame('required', $config['receipt_code']);
        $this->assertSame('hidden', $config['tir_file']);
        $this->assertArrayNotHasKey('unknown_field', $config);
        $this->assertCount(10, $config);
    }

    public function test_invalid_admin_config_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        (new CargoDocumentRuleService())->normalizeSubmittedConfig([
            'receipt_code' => 'required',
            'unknown_field' => 'optional',
        ]);
    }

    public function test_submission_rules_translate_field_state_to_server_validation(): void
    {
        $service = new CargoDocumentRuleService();
        $config = array_fill_keys($service->fieldKeys(), 'hidden');
        $config['receipt_code'] = 'required';
        $config['receipt_file'] = 'required';
        $config['tir_file'] = 'optional';

        $rules = $service->submissionRulesForConfig($config);

        $this->assertContains('required', $rules['receipt_code']);
        $this->assertContains('image', $rules['receipt_file']);
        $this->assertContains('prohibited', $rules['tir_carnet_number']);
        $this->assertContains('nullable', $rules['tir_file']);
    }
}
