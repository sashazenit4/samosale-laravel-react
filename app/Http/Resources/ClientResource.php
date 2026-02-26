<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray($request)
    {
        // Получаем значения кастомных полей
        $lastName = $this->getCustomFieldValue('last_name');
        $firstName = $this->getCustomFieldValue('first_name');
        $middleName = $this->getCustomFieldValue('middle_name');

        // Формируем полное имя
        $fullName = $this->buildFullName($lastName, $firstName, $middleName);

        return [
            'user_id' => $this->user_id,
            'telegram_id' => $this->telegram_id,
            'username' => $this->username,
            'phone_number' => $this->phone_number,
            'name' => $this->name,
            'full_name' => $fullName,
            'balance' => (float) $this->balance,
            'referral_code' => $this->referral_code,
            // Дополнительно можно вернуть отдельные компоненты имени
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $middleName,
        ];
    }

    /**
     * Получить значение кастомного поля по имени
     */
    private function getCustomFieldValue(string $fieldName): ?string
    {
        $field = $this->customFields
            ->where('field_name', $fieldName)
            ->first();

        return $field ? $field->field_value : null;
    }

    /**
     * Сформировать полное имя из компонентов
     */
    private function buildFullName(?string $lastName, ?string $firstName, ?string $middleName): string
    {
        $this->name = $this->name ?? '';
        $parts = array_filter([$lastName, $firstName, $middleName]);
        return implode(' ', $parts) ?: $this->name;
    }
}
