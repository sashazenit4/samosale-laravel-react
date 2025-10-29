<?php
// app/Services/CustomFieldValidationService.php

namespace App\Services;

use App\Models\CustomFieldTemplate;
use Illuminate\Support\Facades\Validator;

class CustomFieldValidationService
{
    /**
     * Validate custom fields against templates
     */
    public function validateFields(array $customFields): array
    {
        $errors = [];
        $validatedFields = [];

        // Получаем все активные шаблоны
        $templates = CustomFieldTemplate::active()->get()->keyBy('name');

        foreach ($customFields as $index => $field) {
            $fieldName = $field['name'] ?? null;

            if (!$fieldName) {
                $errors["custom_fields.{$index}.name"] = 'Field name is required';
                continue;
            }

            // Проверяем существует ли шаблон для этого поля
            if (!$templates->has($fieldName)) {
                $errors["custom_fields.{$index}.name"] = "Field '{$fieldName}' is not allowed";
                continue;
            }

            $template = $templates->get($fieldName);
            $fieldValue = $field['value'] ?? null;

            // Проверяем обязательность поля
            if ($template->is_required && ($fieldValue === null || $fieldValue === '')) {
                $errors["custom_fields.{$index}.value"] = "Field '{$template->label}' is required";
                continue;
            }

            // Проверяем тип значения
            if (!$template->isValidValue($fieldValue)) {
                $errors["custom_fields.{$index}.value"] = "Invalid value for field '{$template->label}'";
                continue;
            }

            // Сохраняем валидированное поле
            $validatedFields[] = [
                'name' => $fieldName,
                'value' => $fieldValue,
                'type' => $template->type,
                'template' => $template,
            ];
        }

        return [
            'errors' => $errors,
            'validated_fields' => $validatedFields,
        ];
    }

    /**
     * Get validation rules for custom fields
     */
    public function getValidationRules(): array
    {
        $rules = [];
        $templates = CustomFieldTemplate::active()->get();

        foreach ($templates as $template) {
            $rules["custom_fields.{$template->name}"] = $template->getValidationRule();
        }

        return $rules;
    }

    /**
     * Transform custom fields array to named array for validation
     */
    public function transformFieldsForValidation(array $customFields): array
    {
        $transformed = [];

        foreach ($customFields as $field) {
            if (isset($field['name'], $field['value'])) {
                $transformed[$field['name']] = $field['value'];
            }
        }

        return $transformed;
    }

    /**
     * Transform validated fields back to original format
     */
    public function transformFieldsToOriginalFormat(array $validatedFields): array
    {
        $result = [];

        foreach ($validatedFields as $name => $value) {
            $result[] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        return $result;
    }
}
