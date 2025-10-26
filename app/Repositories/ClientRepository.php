<?php

namespace App\Repositories;

use App\Models\Client;
use App\Models\CustomClientField;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientRepository
{
    /**
     * Find client by user_id
     */
    public function findById(int $userId): ?Client
    {
        return Client::with('customFields', 'referrer', 'referrals')->find($userId);
    }

    /**
     * Find client by Telegram ID
     */
    public function findByTelegramId(int $telegramId): ?Client
    {
        return Client::with('customFields', 'referrer', 'referrals')->byTelegramId($telegramId)->first();
    }

    /**
     * Find client by phone number
     */
    public function findByPhoneNumber(string $phoneNumber): ?Client
    {
        return Client::with('customFields', 'referrer', 'referrals')->byPhoneNumber($phoneNumber)->first();
    }

    /**
     * Find client by referral code
     */
    public function findByReferralCode(string $referralCode): ?Client
    {
        return Client::with('customFields', 'referrer', 'referrals')->byReferralCode($referralCode)->first();
    }

    /**
     * Create new client
     */
    public function create(array $data): Client
    {
        // Генерируем реферальный код если не предоставлен
        if (!isset($data['referral_code'])) {
            $data['referral_code'] = Client::generateReferralCode();
        }

        $client = Client::create($data);

        // Сохраняем кастомные поля если они есть
        if (isset($data['custom_fields'])) {
            $this->saveCustomFields($client, $data['custom_fields']);
        }

        return $client->load('customFields', 'referrer', 'referrals');
    }

    /**
     * Update client
     */
    public function update(int $userId, array $data): bool
    {
        $client = Client::find($userId);

        if (!$client) {
            return false;
        }

        $result = $client->update($data);

        // Обновляем кастомные поля если они есть
        if (isset($data['custom_fields'])) {
            $this->saveCustomFields($client, $data['custom_fields']);
        }

        return $result;
    }

    /**
     * Delete client
     */
    public function delete(int $userId): bool
    {
        $client = $this->findById($userId);

        if (!$client) {
            return false;
        }

        return $client->delete();
    }

    /**
     * Get all clients with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Client::with('customFields', 'referrer')
            ->orderBy('registration_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get clients by referrer
     */
    public function getReferrals(int $referrerId): Collection
    {
        return Client::where('referred_by', $referrerId)
            ->with('customFields', 'referrer')
            ->orderBy('registration_date', 'desc')
            ->get();
    }

    /**
     * Check if Telegram ID exists
     */
    public function telegramIdExists(int $telegramId): bool
    {
        return Client::where('telegram_id', $telegramId)->exists();
    }

    /**
     * Check if phone number exists
     */
    public function phoneNumberExists(string $phoneNumber): bool
    {
        return Client::where('phone_number', $phoneNumber)->exists();
    }

    /**
     * Get client statistics
     */
    public function getStatistics(int $userId): array
    {
        $client = $this->findById($userId);

        if (!$client) {
            return [];
        }

        return [
            'referrals_count' => $client->referrals()->count(),
            'registration_date' => $client->registration_date,
            'has_referrer' => !is_null($client->referred_by),
            'custom_fields_count' => $client->customFields()->count(),
        ];
    }

    /**
     * Save custom fields for client
     */
    private function saveCustomFields(Client $client, array $customFields): void
    {
        foreach ($customFields as $field) {
            if (!empty($field['name']) && isset($field['value'])) {
                $client->setCustomField(
                    $field['name'],
                    $field['value'],
                    $field['type'] ?? 'text'
                );
            }
        }
    }

    /**
     * Get custom fields for client
     */
    public function getCustomFields(int $userId): array
    {
        $client = $this->findById($userId);
        return $client ? $client->getCustomFieldsArray() : [];
    }

    /**
     * Update specific custom field
     */
    public function updateCustomField(int $userId, string $fieldName, string $fieldValue, string $fieldType = 'text'): bool
    {
        $client = Client::find($userId);

        if (!$client) {
            return false;
        }

        $client->setCustomField($fieldName, $fieldValue, $fieldType);
        return true;
    }

    /**
     * Delete specific custom field
     */
    public function deleteCustomField(int $userId, string $fieldName): bool
    {
        $client = Client::find($userId);

        if (!$client) {
            return false;
        }

        return $client->customFields()->where('field_name', $fieldName)->delete() > 0;
    }

    /**
     * Get clients with specific custom field value
     */
    public function findByCustomField(string $fieldName, string $fieldValue): Collection
    {
        return Client::whereHas('customFields', function ($query) use ($fieldName, $fieldValue) {
            $query->where('field_name', $fieldName)
                ->where('field_value', $fieldValue);
        })->with('customFields', 'referrer')->get();
    }

    /**
     * Get clients with custom field containing value (LIKE search)
     */
    public function searchByCustomField(string $fieldName, string $searchValue): Collection
    {
        return Client::whereHas('customFields', function ($query) use ($fieldName, $searchValue) {
            $query->where('field_name', $fieldName)
                ->where('field_value', 'LIKE', "%{$searchValue}%");
        })->with('customFields', 'referrer')->get();
    }

    /**
     * Bulk update custom fields for multiple clients
     */
    public function bulkUpdateCustomFields(array $userIds, string $fieldName, string $fieldValue, string $fieldType = 'text'): int
    {
        $updated = 0;

        foreach ($userIds as $userId) {
            if ($this->updateCustomField($userId, $fieldName, $fieldValue, $fieldType)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Get all unique custom field names used in the system
     */
    public function getUsedCustomFieldNames(): array
    {
        return CustomClientField::distinct()
            ->pluck('field_name')
            ->toArray();
    }

    /**
     * Get statistics for custom field usage
     */
    public function getCustomFieldsStatistics(): array
    {
        return [
            'total_custom_fields' => CustomClientField::count(),
            'unique_field_names' => CustomClientField::distinct('field_name')->count('field_name'),
            'most_used_fields' => CustomClientField::select('field_name')
                ->selectRaw('COUNT(*) as usage_count')
                ->groupBy('field_name')
                ->orderByDesc('usage_count')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }
}
