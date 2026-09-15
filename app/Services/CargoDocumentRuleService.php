<?php

namespace App\Services;

use App\Models\CargoDocumentRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CargoDocumentRuleService
{
    public function states(): array
    {
        return config('cargo_document_rules.states', ['required', 'optional', 'hidden']);
    }

    public function groups(): array
    {
        return config('cargo_document_rules.groups', []);
    }

    public function fieldDefinitions(): array
    {
        $definitions = [];

        foreach ($this->groups() as $groupKey => $group) {
            foreach ($group['fields'] ?? [] as $fieldKey => $field) {
                $definitions[$fieldKey] = array_merge($field, [
                    'key' => $fieldKey,
                    'group_key' => $groupKey,
                    'group_title' => $group['title'] ?? $groupKey,
                ]);
            }
        }

        return $definitions;
    }

    public function fieldKeys(): array
    {
        return array_keys($this->fieldDefinitions());
    }

    public function normalizeConfig(?array $config): array
    {
        $config = is_array($config) ? $config : [];
        $states = $this->states();
        $normalized = [];

        foreach ($this->fieldKeys() as $fieldKey) {
            $state = $config[$fieldKey] ?? 'hidden';
            $normalized[$fieldKey] = in_array($state, $states, true) ? $state : 'hidden';
        }

        return $normalized;
    }

    public function normalizeSubmittedConfig(array $submitted, ?array $current = null): array
    {
        $fieldKeys = $this->fieldKeys();
        $states = $this->states();
        $errors = [];

        $unknownFields = array_values(array_diff(array_keys($submitted), $fieldKeys));
        if ($unknownFields) {
            $errors['fields_config'] = 'فیلدهای ناشناخته در تنظیمات قوانین ارسال شده است.';
        }

        foreach ($submitted as $fieldKey => $state) {
            if (!in_array($state, $states, true)) {
                $errors["fields_config.{$fieldKey}"] = 'وضعیت انتخاب‌شده برای این فیلد معتبر نیست.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $normalized = $this->normalizeConfig($current);

        foreach ($submitted as $fieldKey => $state) {
            if (in_array($fieldKey, $fieldKeys, true)) {
                $normalized[$fieldKey] = $state;
            }
        }

        return $normalized;
    }

    public function frontendRules(): array
    {
        return CargoDocumentRule::query()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (CargoDocumentRule $rule) => [
                $rule->cargo_type => [
                    'cargo_type' => $rule->cargo_type,
                    'fields_config' => $this->normalizeConfig($rule->fields_config),
                ],
            ])
            ->all();
    }

    public function configForCargoType(?string $cargoType): array
    {
        if (blank($cargoType)) {
            return [];
        }

        $rule = CargoDocumentRule::query()
            ->where('cargo_type', $cargoType)
            ->first();

        return $rule ? $this->normalizeConfig($rule->fields_config) : [];
    }

    public function submissionRules(
        ?string $cargoType,
        string $context = 'store',
        ?object $existingItem = null,
    ): array {
        return $this->submissionRulesForConfig(
            $this->configForCargoType($cargoType),
            $context,
            $existingItem,
        );
    }

    public function submissionRulesForConfig(
        array $config,
        string $context = 'store',
        ?object $existingItem = null,
    ): array {
        $definitions = $this->fieldDefinitions();
        $rules = [];

        foreach ($this->normalizeConfig($config) as $fieldKey => $state) {
            $definition = $definitions[$fieldKey];

            if ($state === 'hidden') {
                $rules[$fieldKey] = ['prohibited'];
                continue;
            }

            $valueRules = $definition['type'] === 'file'
                ? ['image', 'mimes:jpg,png', 'extensions:jpg,png', 'max:500']
                : ($definition['rules'] ?? ['string', 'max:255']);

            if ($state === 'required') {
                if ($definition['type'] === 'file' && $context === 'update') {
                    $rules[$fieldKey] = [
                        'nullable',
                        Rule::requiredIf(fn () => blank($existingItem?->{$fieldKey})),
                        ...$valueRules,
                    ];
                } else {
                    $rules[$fieldKey] = ['required', ...$valueRules];
                }
            } else {
                $rules[$fieldKey] = ['nullable', ...$valueRules];
            }
        }

        return $rules;
    }

    public function stateCounts(?array $config): array
    {
        $counts = array_fill_keys($this->states(), 0);

        foreach ($this->normalizeConfig($config) as $state) {
            if (array_key_exists($state, $counts)) {
                $counts[$state]++;
            }
        }

        return $counts;
    }
}
